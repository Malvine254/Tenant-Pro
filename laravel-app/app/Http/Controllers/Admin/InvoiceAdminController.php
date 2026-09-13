<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Unit;
use App\Services\PdfService;
use App\Services\TenantAppNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvoiceAdminController extends Controller
{
    public function __construct(
        private readonly PdfService $pdfService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $invoices = Invoice::with(['tenant', 'unit.property'])
            ->when($user?->role?->name === 'LANDLORD', fn ($q) => $q->whereHas('unit.property', fn ($property) => $property->where('landlord_id', $user->landlordAccountId())))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(15);

        return view('admin.invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $isLandlord = $user?->role?->name === 'LANDLORD';
        $landlordId = $user->landlordAccountId();

        $properties = Property::with(['units' => fn ($q) => $q->with('tenant.user')])
            ->when($isLandlord, fn ($q) => $q->where('landlord_id', $landlordId))
            ->orderBy('name')
            ->get();

        return view('admin.invoices.create', compact('properties'));
    }

    public function store(Request $request, TenantAppNotificationService $notificationService)
    {
        $user = $request->user();
        $isLandlord = $user?->role?->name === 'LANDLORD';

        $data = $request->validate([
            'unit_id' => 'required|uuid|exists:units,id',
            'billing_type' => 'required|in:RENT,WATER,GARBAGE,ELECTRICITY,SERVICE_CHARGE,OTHER',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2020|max:2035',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'amount' => 'required|numeric|min:0',
            'water_previous_reading' => 'nullable|numeric|min:0',
            'water_current_reading' => 'nullable|numeric|min:0',
            'water_rate_per_unit' => 'nullable|numeric|min:0',
            'electricity_previous_reading' => 'nullable|numeric|min:0',
            'electricity_current_reading' => 'nullable|numeric|min:0',
            'electricity_rate_per_unit' => 'nullable|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
        ]);

        $unit = Unit::with(['property', 'tenant.user'])->where('id', $data['unit_id'])->firstOrFail();

        if ($isLandlord) {
            abort_if($unit->property?->landlord_id !== $user->landlordAccountId(), 403);
        }

        $tenantUser = $unit->tenant?->user;

        // Sub-meter calculations
        $waterCost = 0.0;
        if (isset($data['water_current_reading'], $data['water_previous_reading']) && $data['water_current_reading'] >= $data['water_previous_reading']) {
            $units = (float) $data['water_current_reading'] - (float) $data['water_previous_reading'];
            $rate = (float) ($data['water_rate_per_unit'] ?? 0);
            $waterCost = $units * $rate;
        }

        $electricityCost = 0.0;
        if (isset($data['electricity_current_reading'], $data['electricity_previous_reading']) && $data['electricity_current_reading'] >= $data['electricity_previous_reading']) {
            $units = (float) $data['electricity_current_reading'] - (float) $data['electricity_previous_reading'];
            $rate = (float) ($data['electricity_rate_per_unit'] ?? 0);
            $electricityCost = $units * $rate;
        }

        $baseRent = (float) $data['amount'];
        $penalty = (float) ($data['penalty_amount'] ?? 0);
        $total = $baseRent + $waterCost + $electricityCost + $penalty;

        $invoice = Invoice::create([
            'unit_id' => $unit->id,
            'tenant_id' => $tenantUser?->id,
            'user_id' => $tenantUser?->id,
            'billing_type' => $data['billing_type'],
            'period_month' => $data['period_month'],
            'period_year' => $data['period_year'],
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'amount' => $baseRent,
            'water_previous_reading' => $data['water_previous_reading'] ?? null,
            'water_current_reading' => $data['water_current_reading'] ?? null,
            'water_rate_per_unit' => $data['water_rate_per_unit'] ?? null,
            'electricity_previous_reading' => $data['electricity_previous_reading'] ?? null,
            'electricity_current_reading' => $data['electricity_current_reading'] ?? null,
            'electricity_rate_per_unit' => $data['electricity_rate_per_unit'] ?? null,
            'penalty_amount' => $penalty,
            'total_amount' => $total,
            'paid_amount' => 0,
            'status' => 'PENDING',
        ]);

        if ($tenantUser) {
            $notificationService->invoiceCreated($invoice);
        }

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Invoice generated successfully with sub-meter charges.');
    }

    public function show(Invoice $invoice)
    {
        $user = request()->user();
        abort_if($user?->role?->name === 'LANDLORD' && $invoice->unit?->property?->landlord_id !== $user->landlordAccountId(), 403);

        $invoice->load(['tenant', 'unit.property', 'payments']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function pdf(Invoice $invoice)
    {
        $user = request()->user();
        abort_if($user?->role?->name === 'LANDLORD' && $invoice->unit?->property?->landlord_id !== $user->landlordAccountId(), 403);

        return $this->pdfService->generateInvoiceStatement($invoice);
    }

    public function sendReminder(Invoice $invoice, TenantAppNotificationService $notificationService)
    {
        $user = request()->user();
        abort_if($user?->role?->name === 'LANDLORD' && $invoice->unit?->property?->landlord_id !== $user->landlordAccountId(), 403);

        if ($invoice->status === 'PAID') {
            return back()->with('error', 'This invoice is already settled in full.');
        }

        $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date) : null;
        $urgency = ($dueDate && $dueDate->isPast() && ! $dueDate->isToday()) ? 'OVERDUE' : 'DUE_SOON';

        $notificationService->rentReminder($invoice, $urgency);
        $invoice->update(['last_reminder_sent_at' => now()]);

        return back()->with('success', 'Rent reminder sent to tenant via push notification and timeline.');
    }
}
