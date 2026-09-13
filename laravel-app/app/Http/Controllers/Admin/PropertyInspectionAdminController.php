<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyInspection;
use App\Models\Unit;
use App\Models\User;
use App\Services\PdfService;
use Illuminate\Http\Request;

class PropertyInspectionAdminController extends Controller
{
    public function __construct(private readonly PdfService $pdfService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isLandlord = $user?->role?->name === 'LANDLORD';
        $landlordId = $user->landlordAccountId();

        $inspections = PropertyInspection::with(['property', 'unit', 'tenant'])
            ->when($isLandlord, fn ($q) => $q->whereHas('property', fn ($p) => $p->where('landlord_id', $landlordId)))
            ->latest('inspection_date')
            ->paginate(15);

        return view('admin.inspections.index', compact('inspections'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $isLandlord = $user?->role?->name === 'LANDLORD';
        $landlordId = $user->landlordAccountId();

        $properties = Property::with(['units.tenant.user'])
            ->when($isLandlord, fn ($q) => $q->where('landlord_id', $landlordId))
            ->orderBy('name')
            ->get();

        return view('admin.inspections.create', compact('properties'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $isLandlord = $user?->role?->name === 'LANDLORD';

        $data = $request->validate([
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'tenant_id' => 'nullable|uuid|exists:users,id',
            'type' => 'required|in:MOVE_IN,MOVE_OUT,PERIODIC',
            'inspection_date' => 'required|date',
            'inspector_name' => 'required|string|max:150',
            'meter_reading_water' => 'nullable|numeric|min:0',
            'meter_reading_electricity' => 'nullable|numeric|min:0',
            'general_notes' => 'nullable|string|max:2000',
            'items' => 'nullable|array',
            'items.*.category' => 'required|string|max:100',
            'items.*.item_name' => 'required|string|max:150',
            'items.*.condition' => 'required|in:GOOD,FAIR,DAMAGED',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        if ($isLandlord) {
            Property::where('id', $data['property_id'])->where('landlord_id', $user->landlordAccountId())->firstOrFail();
        }

        $unit = Unit::with('tenant.user')->where('id', $data['unit_id'])->firstOrFail();
        $tenantId = $data['tenant_id'] ?? $unit->tenant?->user_id;

        $checklist = [];
        if (! empty($data['items'])) {
            foreach ($data['items'] as $item) {
                if (! empty($item['item_name'])) {
                    $checklist[] = [
                        'category' => $item['category'] ?? 'General',
                        'item_name' => $item['item_name'],
                        'condition' => $item['condition'] ?? 'GOOD',
                        'notes' => $item['notes'] ?? '',
                    ];
                }
            }
        }

        // Default standard checklist if none provided
        if (empty($checklist)) {
            $checklist = [
                ['category' => 'Living Room', 'item_name' => 'Walls & Paint Condition', 'condition' => 'GOOD', 'notes' => 'Clean, no marks'],
                ['category' => 'Living Room', 'item_name' => 'Windows & Curtain Rods', 'condition' => 'GOOD', 'notes' => 'Intact & functional'],
                ['category' => 'Living Room', 'item_name' => 'Light Fixtures & Switches', 'condition' => 'GOOD', 'notes' => 'Working normally'],
                ['category' => 'Kitchen', 'item_name' => 'Sink & Taps Plumbing', 'condition' => 'GOOD', 'notes' => 'No leaks detected'],
                ['category' => 'Kitchen', 'item_name' => 'Cabinets & Shelving', 'condition' => 'GOOD', 'notes' => 'Clean & intact'],
                ['category' => 'Bathroom', 'item_name' => 'Shower / Instant Heater', 'condition' => 'GOOD', 'notes' => 'Operational'],
                ['category' => 'Bathroom', 'item_name' => 'Toilet System & Flush', 'condition' => 'GOOD', 'notes' => 'Working properly'],
                ['category' => 'Bedrooms', 'item_name' => 'Door Locks & Key Handover', 'condition' => 'GOOD', 'notes' => '2 Original Keys provided'],
            ];
        }

        $inspection = PropertyInspection::create([
            'property_id' => $data['property_id'],
            'unit_id' => $data['unit_id'],
            'tenant_id' => $tenantId,
            'type' => $data['type'],
            'status' => 'COMPLETED',
            'inspection_date' => $data['inspection_date'],
            'inspector_name' => $data['inspector_name'],
            'meter_reading_water' => $data['meter_reading_water'] ?? null,
            'meter_reading_electricity' => $data['meter_reading_electricity'] ?? null,
            'checklist_data' => $checklist,
            'general_notes' => $data['general_notes'] ?? null,
        ]);

        return redirect()->route('admin.inspections.show', $inspection)->with('success', 'Condition inspection recorded successfully.');
    }

    public function show(PropertyInspection $inspection)
    {
        $user = request()->user();
        if ($user?->role?->name === 'LANDLORD') {
            abort_if($inspection->property?->landlord_id !== $user->landlordAccountId(), 403);
        }

        $inspection->load(['property', 'unit', 'tenant']);
        return view('admin.inspections.show', compact('inspection'));
    }

    public function pdf(PropertyInspection $inspection)
    {
        $user = request()->user();
        if ($user?->role?->name === 'LANDLORD') {
            abort_if($inspection->property?->landlord_id !== $user->landlordAccountId(), 403);
        }

        return $this->pdfService->generateInspectionCertificate($inspection);
    }
}
