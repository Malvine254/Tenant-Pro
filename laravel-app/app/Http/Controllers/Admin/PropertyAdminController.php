<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

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
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'address_line' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'initial_units_count' => 'nullable|integer|min:1|max:100',
            'first_unit_number' => 'nullable|string|max:50',
            'initial_floor' => 'nullable|integer',
            'initial_rent_amount' => 'nullable|numeric|min:0',
            'water_monthly_fee' => 'required|numeric|min:0',
            'garbage_monthly_fee' => 'required|numeric|min:0',
            'electricity_billing_mode' => 'required|in:PREPAID,POSTPAID',
        ]);
        $this->assertLandlordId($data['landlord_id']);

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
        $newImageUrl = null;
        if ($request->hasFile('cover_image')) {
            $newImageUrl = $this->storePropertyImage($request->file('cover_image'));
            $propertyFields['cover_image_url'] = $newImageUrl;
        }
        $propertyFields['billing_settings'] = [
            'water_monthly_fee' => (float) $data['water_monthly_fee'],
            'garbage_monthly_fee' => (float) $data['garbage_monthly_fee'],
            'electricity_billing_mode' => $data['electricity_billing_mode'],
        ];

        try {
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
        } catch (Throwable $exception) {
            if ($newImageUrl) {
                $this->deletePropertyImage($newImageUrl);
            }
            throw $exception;
        }

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
        $property->load([
            'units' => fn ($query) => $query
                ->orderByRaw('floor IS NULL')
                ->orderBy('floor')
                ->orderBy('unit_number'),
            'units.tenant',
        ]);
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
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'remove_cover_image' => 'nullable|boolean',
            'address_line' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'water_monthly_fee' => 'required|numeric|min:0',
            'garbage_monthly_fee' => 'required|numeric|min:0',
            'electricity_billing_mode' => 'required|in:PREPAID,POSTPAID',
            'units' => 'nullable|array',
            'units.*.id' => 'required|uuid|distinct',
            'units.*.unit_number' => 'required|string|max:50',
            'units.*.floor' => 'nullable|integer',
            'units.*.rent_amount' => 'required|numeric|min:0',
            'units.*.status' => 'required|in:AVAILABLE,OCCUPIED,UNDER_MAINTENANCE',
            'units.*.water_monthly_fee' => 'nullable|numeric|min:0',
            'units.*.garbage_monthly_fee' => 'nullable|numeric|min:0',
        ]);
        $this->assertLandlordId($data['landlord_id']);
        $propertyFields = collect($data)->except([
            'cover_image', 'remove_cover_image', 'water_monthly_fee', 'garbage_monthly_fee',
            'electricity_billing_mode', 'units',
        ])->all();
        $propertyFields['billing_settings'] = array_merge($property->billing_settings ?? [], [
            'water_monthly_fee' => (float) $data['water_monthly_fee'],
            'garbage_monthly_fee' => (float) $data['garbage_monthly_fee'],
            'electricity_billing_mode' => $data['electricity_billing_mode'],
        ]);

        $property->loadMissing('units');
        $unitsById = $property->units->keyBy('id');
        $submittedUnits = collect($data['units'] ?? []);

        foreach ($submittedUnits as $index => $unitData) {
            if (!$unitsById->has($unitData['id'])) {
                throw ValidationException::withMessages([
                    "units.{$index}.id" => 'This unit does not belong to the selected property.',
                ]);
            }
        }

        // Validate the final set of unit numbers so two units cannot be given the same identity.
        $finalUnitNumbers = $unitsById->mapWithKeys(
            fn (Unit $unit) => [$unit->id => trim($unit->unit_number)]
        );
        foreach ($submittedUnits as $unitData) {
            $finalUnitNumbers[$unitData['id']] = trim($unitData['unit_number']);
        }
        $duplicateNumbers = $finalUnitNumbers
            ->groupBy(fn (string $number) => mb_strtolower($number))
            ->filter(fn ($numbers) => $numbers->count() > 1)
            ->flatten()
            ->unique()
            ->values();
        if ($duplicateNumbers->isNotEmpty()) {
            throw ValidationException::withMessages([
                'units' => 'Unit numbers must be unique within this property. Duplicates: '.$duplicateNumbers->join(', '),
            ]);
        }

        $oldImageUrl = $property->cover_image_url;
        $newImageUrl = null;
        if ($request->hasFile('cover_image')) {
            $newImageUrl = $this->storePropertyImage($request->file('cover_image'));
            $propertyFields['cover_image_url'] = $newImageUrl;
        } elseif ($request->boolean('remove_cover_image')) {
            $propertyFields['cover_image_url'] = null;
        }

        try {
            DB::transaction(function () use ($property, $propertyFields, $submittedUnits, $unitsById) {
                $property->update($propertyFields);

                foreach ($submittedUnits as $unitData) {
                    $unit = $unitsById->get($unitData['id']);
                    $unit->update([
                        'unit_number' => trim($unitData['unit_number']),
                        'floor' => $unitData['floor'] ?? null,
                        'rent_amount' => $unitData['rent_amount'],
                        'status' => $unitData['status'],
                        'billing_overrides' => $this->unitBillingOverrides($unitData),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            if ($newImageUrl) {
                $this->deletePropertyImage($newImageUrl);
            }
            throw $exception;
        }

        if ($oldImageUrl && array_key_exists('cover_image_url', $propertyFields) && $oldImageUrl !== $newImageUrl) {
            $this->deletePropertyImage($oldImageUrl);
        }

        return redirect()->route('admin.properties.show', $property)->with('success', 'Property and unit details updated.');
    }

    public function destroy(Property $property)
    {
        $this->authorizeLandlordProperty($property);

        if ($property->units()->exists()) {
            return back()->with('error', 'Properties with units cannot be deleted. Preserve the rental and financial history.');
        }

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

    private function unitBillingOverrides(array $data): ?array
    {
        $overrides = collect([
            'water_monthly_fee' => $data['water_monthly_fee'] ?? null,
            'garbage_monthly_fee' => $data['garbage_monthly_fee'] ?? null,
        ])->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (float) $value)
            ->all();

        return $overrides ?: null;
    }

    private function storePropertyImage($file): string
    {
        $path = Storage::disk('public')->put('properties', $file);
        if (!$path) {
            throw new RuntimeException('The property image could not be stored.');
        }

        return asset('storage/'.$path);
    }

    private function deletePropertyImage(string $imageUrl): void
    {
        $storagePrefix = asset('storage/');
        if (str_starts_with($imageUrl, $storagePrefix)) {
            Storage::disk('public')->delete(substr($imageUrl, strlen($storagePrefix)));
        }
    }

    private function assertLandlordId(string $landlordId): void
    {
        if (!User::whereKey($landlordId)->whereHas('role', fn ($query) => $query->where('name', 'LANDLORD'))->exists()) {
            throw ValidationException::withMessages([
                'landlord_id' => 'Select a valid landlord account.',
            ]);
        }
    }
}
