<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropertyAdminController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $properties = Property::with(['landlord', 'units'])
            ->when($this->isLandlord($user), fn($q) => $q->where('landlord_id', $user->id))
            ->latest()
            ->paginate(15);
        return view('admin.properties.index', compact('properties'));
    }

    public function create()
    {
        $user = request()->user();
        $landlords = User::whereHas('role', fn($q) => $q->where('name', 'LANDLORD'))
            ->when($this->isLandlord($user), fn($q) => $q->where('id', $user->id))
            ->orderBy('name')
            ->get();
        return view('admin.properties.create', compact('landlords'));
    }

    public function store(Request $request)
    {
        if ($this->isLandlord($request->user())) {
            $request->merge(['landlord_id' => $request->user()->id]);
        }

        $data = $request->validate([
            'landlord_id' => 'required|uuid|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address_line' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'initial_units_count' => 'nullable|integer|min:1|max:100',
            'first_unit_number' => 'nullable|string|max:50',
            'initial_floor' => 'nullable|integer',
            'initial_rent_amount' => 'nullable|numeric|min:0',
        ]);

        $unitCount = (int) ($data['initial_units_count'] ?? 0);
        $unitNumbers = [];

        if ($unitCount > 0) {
            if (blank($data['first_unit_number'] ?? null)) {
                throw ValidationException::withMessages([
                    'first_unit_number' => 'Enter the first unit number.',
                ]);
            }

            if (!array_key_exists('initial_rent_amount', $data) || $data['initial_rent_amount'] === null) {
                throw ValidationException::withMessages([
                    'initial_rent_amount' => 'Enter the monthly rent for these units.',
                ]);
            }

            $unitNumbers = $this->buildInitialUnitNumbers($data['first_unit_number'], $unitCount);
        }

        $propertyFields = collect($data)->only([
            'landlord_id', 'name', 'description', 'address_line', 'city', 'state', 'country',
        ])->all();

        $property = DB::transaction(function () use ($propertyFields, $data, $unitNumbers) {
            $property = Property::create($propertyFields);

            foreach ($unitNumbers as $unitNumber) {
                $property->units()->create([
                    'unit_number' => $unitNumber,
                    'floor' => $data['initial_floor'] ?? null,
                    'rent_amount' => $data['initial_rent_amount'],
                    'status' => 'AVAILABLE',
                ]);
            }

            return $property;
        });

        $message = $unitCount > 0
            ? "Property created with {$unitCount} ".str('unit')->plural($unitCount).'.'
            : 'Property created.';

        return redirect()
            ->route('admin.properties.show', $property)
            ->with('success', $message);
    }

    public function show(Property $property)
    {
        $this->authorizeLandlordProperty($property);

        $property->load([
            'landlord',
            'units' => fn($query) => $query
                ->orderByRaw('floor IS NULL')
                ->orderBy('floor')
                ->orderBy('unit_number'),
            'units.tenant.user',
        ]);
        return view('admin.properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        $this->authorizeLandlordProperty($property);
        $user = request()->user();
        $landlords = User::whereHas('role', fn($q) => $q->where('name', 'LANDLORD'))
            ->when($this->isLandlord($user), fn($q) => $q->where('id', $user->id))
            ->orderBy('name')
            ->get();
        return view('admin.properties.edit', compact('property', 'landlords'));
    }

    public function update(Request $request, Property $property)
    {
        $this->authorizeLandlordProperty($property);
        if ($this->isLandlord($request->user())) {
            $request->merge(['landlord_id' => $request->user()->id]);
        }

        $data = $request->validate([
            'landlord_id' => 'required|uuid|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address_line' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
        ]);
        $property->update($data);
        return redirect()->route('admin.properties.show', $property)->with('success', 'Property updated.');
    }

    public function destroy(Property $property)
    {
        $this->authorizeLandlordProperty($property);

        $property->delete();
        return redirect()->route('admin.properties.index')->with('success', 'Property deleted.');
    }

    private function isLandlord(?User $user): bool
    {
        return $user?->role?->name === 'LANDLORD';
    }

    private function authorizeLandlordProperty(Property $property): void
    {
        $user = request()->user();
        abort_if($this->isLandlord($user) && $property->landlord_id !== $user->id, 403);
    }

    private function buildInitialUnitNumbers(string $firstUnitNumber, int $count): array
    {
        $firstUnitNumber = trim($firstUnitNumber);

        if ($count === 1) {
            return [$firstUnitNumber];
        }

        if (!preg_match('/^(.*?)(\d+)$/', $firstUnitNumber, $matches)) {
            throw ValidationException::withMessages([
                'first_unit_number' => 'For multiple units, use a number ending such as 101 or A01.',
            ]);
        }

        $prefix = $matches[1];
        $start = (int) $matches[2];
        $width = strlen($matches[2]);

        return array_map(
            fn(int $offset) => $prefix.str_pad(
                (string) ($start + $offset),
                $width,
                '0',
                STR_PAD_LEFT
            ),
            range(0, $count - 1)
        );
    }
}
