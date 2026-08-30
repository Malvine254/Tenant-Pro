<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\TenantAppNotificationService;
use App\Services\TenantEmailService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Invoice::with(['tenant', 'unit.property'])
            ->when($this->isTenant($user), fn($q) => $q
                ->where('tenant_id', $user->id)
                ->whereExists(function ($activeTenancy) use ($user) {
                    $activeTenancy->selectRaw('1')
                        ->from('tenants')
                        ->whereColumn('tenants.unit_id', 'invoices.unit_id')
                        ->where('tenants.user_id', $user->id)
                        ->where('tenants.is_active', true);
                }))
            ->when($user?->role?->name === 'LANDLORD', fn($q) => $q->whereHas('unit.property', fn($property) => $property->where('landlord_id', $user->id)))
            ->when($request->tenant_id, fn($q) => $q->where('tenant_id', $request->tenant_id))
            ->when($request->unit_id, fn($q) => $q->where('unit_id', $request->unit_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status));
        // Prioritize actionable invoices, including invoices generated now for
        // a future month, then order them by the date the tenant must pay.
        $query
            ->orderByRaw("CASE WHEN status IN ('PENDING', 'PARTIAL', 'PARTIALLY_PAID', 'UNPAID', 'OVERDUE') THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw("CASE WHEN status IN ('PENDING', 'PARTIAL', 'PARTIALLY_PAID', 'UNPAID', 'OVERDUE') THEN due_date END ASC")
            ->orderByRaw("CASE WHEN status NOT IN ('PENDING', 'PARTIAL', 'PARTIALLY_PAID', 'UNPAID', 'OVERDUE') THEN due_date END DESC")
            ->orderByDesc('created_at');

        return response()->json($query->paginate(min(max($request->integer('per_page', 15), 1), 100)));
    }

    public function store(Request $request)
    {
        $this->requireManager($request->user());
        $data = $request->validate([
            'tenant_id' => 'required|uuid|exists:users,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'billing_type' => 'required|string',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2000',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'amount' => 'required|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
        ]);
        $unit = \App\Models\Unit::findOrFail($data['unit_id']);
        $this->requireUnitManager($request->user(), $unit);
        abort_unless(Tenant::where('user_id', $data['tenant_id'])->where('unit_id', $unit->id)->where('is_active', true)->exists(), 422, 'Tenant is not actively linked to this unit.');
        $data['user_id'] = $data['tenant_id'];
        $data['paid_amount'] = 0;
        $data['total_amount'] = round((float) $data['amount'] + (float) ($data['penalty_amount'] ?? 0), 2);
        $data['status'] = 'PENDING';
        $invoice = Invoice::create($data)->load(['tenant', 'unit.property']);
        app(TenantAppNotificationService::class)->invoiceCreated($invoice);
        app(TenantEmailService::class)->invoiceCreated($invoice);

        return response()->json($invoice, 201);
    }

    public function show(Invoice $invoice)
    {
        $user = request()->user();
        abort_if($this->isTenant($user) && $invoice->tenant_id !== $user->id, 403);
        abort_if($this->isTenant($user) && ! $this->hasActiveTenancy($user->id, $invoice->unit_id), 404);
        if ($user?->role?->name === 'LANDLORD') $this->requireUnitManager($user, $invoice->unit);

        return response()->json($invoice->load(['tenant', 'unit.property', 'payments']));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->requireUnitManager($request->user(), $invoice->unit);
        $data = $request->validate([
            'status' => 'sometimes|in:PENDING,OVERDUE,CANCELLED',
            'penalty_amount' => 'nullable|numeric|min:0',
            'due_date' => 'sometimes|date',
        ]);
        abort_if(
            $invoice->payments()->exists() && array_key_exists('penalty_amount', $data),
            422,
            'Amounts cannot be changed after a payment has been recorded.'
        );
        if (array_key_exists('penalty_amount', $data)) {
            $data['total_amount'] = round((float) $invoice->amount + (float) ($data['penalty_amount'] ?? 0), 2);
        }
        $invoice->update($data);
        return response()->json($invoice->load(['tenant', 'unit']));
    }

    public function destroy(Invoice $invoice)
    {
        $this->requireUnitManager(request()->user(), $invoice->unit);
        abort(405, 'Invoices are financial records and cannot be deleted. Cancel the invoice instead.');
    }

    private function isTenant($user): bool
    {
        return $user?->role?->name === 'TENANT';
    }

    private function hasActiveTenancy(string $userId, string $unitId): bool
    {
        return Tenant::where('user_id', $userId)
            ->where('unit_id', $unitId)
            ->where('is_active', true)
            ->exists();
    }
}
