<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PropertyUnitAdminController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $properties = Property::with([
            'units' => fn($query) => $query
                ->orderByRaw('floor IS NULL')
                ->orderBy('floor')
                ->orderBy('unit_number'),
            'units.tenant.user',
        ])
            ->when(
                $user?->role?->name === 'LANDLORD',
                fn($query) => $query->where('landlord_id', $user->id)
            )
            ->orderBy('name')
            ->get();

        return view('admin.units.index', compact('properties'));
    }

    public function create(Property $property)
    {
        $this->authorizeLandlordProperty($property);

        return view('admin.properties.units.create', compact('property'));
    }

    public function store(Request $request, Property $property)
    {
        $this->authorizeLandlordProperty($property);

        $data = $request->validate([
            'unit_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units')->where('property_id', $property->id),
            ],
            'units_count' => 'required|integer|min:1|max:100',
            'floor' => 'nullable|integer',
            'rent_amount' => 'required|numeric|min:0',
            'water_monthly_fee' => 'nullable|numeric|min:0',
            'garbage_monthly_fee' => 'nullable|numeric|min:0',
            'status' => 'required|in:AVAILABLE,OCCUPIED,UNDER_MAINTENANCE',
        ]);

        $unitNumbers = $this->buildUnitNumbers($data['unit_number'], (int) $data['units_count']);
        $duplicates = $property->units()
            ->whereIn('unit_number', $unitNumbers)
            ->pluck('unit_number');

        if ($duplicates->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors([
                    'unit_number' => 'These unit numbers already exist: '.$duplicates->join(', '),
                ]);
        }

        DB::transaction(function () use ($property, $data, $unitNumbers) {
            foreach ($unitNumbers as $unitNumber) {
                $property->units()->create([
                    'unit_number' => $unitNumber,
                    'floor' => $data['floor'] ?? null,
                    'rent_amount' => $data['rent_amount'],
                    'status' => $data['status'],
                    'billing_overrides' => $this->billingOverrides($data),
                ]);
            }
        });

        return redirect()
            ->route('admin.properties.show', $property)
            ->with('success', count($unitNumbers).' '.str('unit')->plural(count($unitNumbers)).' added.');
    }

    public function edit(Property $property, Unit $unit)
    {
        $this->authorizeLandlordProperty($property);
        abort_if($unit->property_id !== $property->id, 404);

        return view('admin.properties.units.edit', compact('property', 'unit'));
    }

    public function update(Request $request, Property $property, Unit $unit)
    {
        $this->authorizeLandlordProperty($property);
        abort_if($unit->property_id !== $property->id, 404);

        $data = $request->validate([
            'unit_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units')
                    ->where('property_id', $property->id)
                    ->ignore($unit->id),
            ],
            'floor' => 'nullable|integer',
            'rent_amount' => 'required|numeric|min:0',
            'water_monthly_fee' => 'nullable|numeric|min:0',
            'garbage_monthly_fee' => 'nullable|numeric|min:0',
            'status' => 'required|in:AVAILABLE,OCCUPIED,UNDER_MAINTENANCE',
        ]);

        $unitFields = collect($data)->except(['water_monthly_fee', 'garbage_monthly_fee'])->all();
        $unitFields['billing_overrides'] = $this->billingOverrides($data);
        $unit->update($unitFields);

        return redirect()
            ->route('admin.properties.show', $property)
            ->with('success', 'Unit updated.');
    }

    public function destroy(Property $property, Unit $unit)
    {
        $this->authorizeLandlordProperty($property);
        abort_if($unit->property_id !== $property->id, 404);

        if ($unit->tenant()->exists() || $unit->invoices()->exists() || $unit->maintenanceRequests()->exists()) {
            return back()->with('error', 'Units with tenancy, invoice or maintenance history cannot be deleted.');
        }

        $unit->delete();

        return redirect()
            ->route('admin.properties.show', $property)
            ->with('success', 'Unit deleted.');
    }

    private function authorizeLandlordProperty(Property $property): void
    {
        $user = request()->user();
        abort_if($user?->role?->name === 'LANDLORD' && $property->landlord_id !== $user->id, 403);
    }

    private function billingOverrides(array $data): ?array
    {
        $overrides = collect([
            'water_monthly_fee' => $data['water_monthly_fee'] ?? null,
            'garbage_monthly_fee' => $data['garbage_monthly_fee'] ?? null,
        ])->filter(fn ($value) => $value !== null && $value !== '')->map(fn ($value) => (float) $value)->all();

        return $overrides ?: null;
    }

    private function buildUnitNumbers(string $firstUnitNumber, int $count): array
    {
        $firstUnitNumber = trim($firstUnitNumber);

        if ($count === 1) {
            return [$firstUnitNumber];
        }

        if (!preg_match('/^(.*?)(\d+)$/', $firstUnitNumber, $matches)) {
            throw ValidationException::withMessages([
                'unit_number' => 'For multiple units, the first unit number must end in a number, such as 101 or A01.',
            ]);
        }

        $prefix = $matches[1];
        $startingNumber = (int) $matches[2];
        $numberWidth = strlen($matches[2]);

        return array_map(
            fn(int $offset) => $prefix.str_pad(
                (string) ($startingNumber + $offset),
                $numberWidth,
                '0',
                STR_PAD_LEFT
            ),
            range(0, $count - 1)
        );
    }
}
