<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TenantAdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tenants = Tenant::with(['user', 'unit.property'])
            ->when($this->isLandlord($user), fn($q) => $q->whereHas('unit.property', fn($property) => $property->where('landlord_id', $user->id)))
            ->when($request->search, fn($q) => $q->whereHas('user', function ($u) use ($request) {
                $u->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->latest()->paginate(15);

        $unassignedTenantUsers = $this->unassignedTenantUsers($request)
            ->limit(10)
            ->get();

        return view('admin.tenants.index', compact('tenants', 'unassignedTenantUsers'));
    }

    public function create(Request $request)
    {
        $units = $this->availableUnits($request);
        $properties = $this->assignableProperties($request);

        return view('admin.tenants.create', compact('units', 'properties'));
    }

    public function store(Request $request)
    {
        $admin = $request->user();
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|unique:users,phone_number',
            'password' => 'required|string|min:8|confirmed',
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'move_in_date' => 'required|date',
            'move_out_date' => 'nullable|date|after:move_in_date',
        ]);

        $unit = Unit::with('property')->findOrFail($data['unit_id']);
        abort_if($unit->property_id !== $data['property_id'], 422, 'The selected unit does not belong to this property.');
        abort_if($this->isLandlord($admin) && $unit->property?->landlord_id !== $admin->id, 403);
        abort_if($unit->tenant()->where('is_active', true)->exists(), 422, 'This unit already has an active tenant.');

        $role = Role::firstOrCreate(
            ['name' => 'TENANT'],
            ['description' => 'Tenant user']
        );

        $tenantUser = User::create([
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'password' => Hash::make($data['password']),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $tenant = DB::transaction(function () use ($tenantUser, $unit, $data) {
            $tenant = Tenant::updateOrCreate(
                ['user_id' => $tenantUser->id],
                [
                    'unit_id' => $unit->id,
                    'move_in_date' => $data['move_in_date'],
                    'move_out_date' => $data['move_out_date'] ?? null,
                    'is_active' => true,
                ]
            );
            $unit->update(['status' => 'OCCUPIED']);
            return $tenant;
        });

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant created and assigned to unit.');
    }

    public function assign(Request $request)
    {
        $tenantUsers = $this->unassignedTenantUsers($request)->get();
        $units = $this->availableUnits($request);
        $properties = $this->assignableProperties($request);

        return view('admin.tenants.assign', compact('tenantUsers', 'units', 'properties'));
    }

    public function assignStore(Request $request)
    {
        $admin = $request->user();
        $data = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'move_in_date' => 'required|date',
            'move_out_date' => 'nullable|date|after:move_in_date',
        ]);

        $tenantUser = User::where('id', $data['user_id'])
            ->whereHas('role', fn($role) => $role->where('name', 'TENANT'))
            ->firstOrFail();
        abort_if($tenantUser->tenant()->where('is_active', true)->exists(), 422, 'This tenant already has an active unit assignment.');

        $unit = Unit::with('property')->findOrFail($data['unit_id']);
        abort_if($unit->property_id !== $data['property_id'], 422, 'The selected unit does not belong to this property.');
        abort_if($this->isLandlord($admin) && $unit->property?->landlord_id !== $admin->id, 403);
        abort_if($unit->tenant()->where('is_active', true)->exists(), 422, 'This unit already has an active tenant.');

        $tenant = DB::transaction(function () use ($tenantUser, $unit, $data) {
            $tenant = Tenant::updateOrCreate(
                ['user_id' => $tenantUser->id],
                [
                    'unit_id' => $unit->id,
                    'move_in_date' => $data['move_in_date'],
                    'move_out_date' => $data['move_out_date'] ?? null,
                    'is_active' => true,
                ]
            );

            $unit->update(['status' => 'OCCUPIED']);

            return $tenant;
        });

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Existing tenant account assigned to unit.');
    }

    public function show(Tenant $tenant)
    {
        $user = request()->user();
        abort_if($this->isLandlord($user) && $tenant->unit?->property?->landlord_id !== $user->id, 403);

        $tenant->load(['user', 'unit.property', 'unit.invoices', 'unit.maintenanceRequests']);
        return view('admin.tenants.show', compact('tenant'));
    }

    public function unassign(Request $request, Tenant $tenant)
    {
        $tenant->load('unit.property');
        abort_if(
            $this->isLandlord($request->user()) && $tenant->unit?->property?->landlord_id !== $request->user()->id,
            403
        );

        if (!$tenant->is_active) {
            return back()->with('error', 'This tenant is already unassigned.');
        }

        DB::transaction(function () use ($tenant) {
            $tenant->update([
                'is_active' => false,
                'move_out_date' => now()->toDateString(),
            ]);
            $tenant->unit()->update(['status' => 'AVAILABLE']);
        });

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Tenancy closed on '.now()->format('d M Y').' and the unit was marked available.');
    }

    private function isLandlord(?User $user): bool
    {
        return $user?->role?->name === 'LANDLORD';
    }

    private function availableUnits(Request $request)
    {
        $user = $request->user();

        return Unit::with('property')
            ->whereDoesntHave('tenant', fn($tenant) => $tenant->where('is_active', true))
            ->when($this->isLandlord($user), fn($q) => $q->whereHas('property', fn($property) => $property->where('landlord_id', $user->id)))
            ->orderBy('unit_number')
            ->get()
            ->sortBy(fn($unit) => ($unit->property?->name ?? '') . ' ' . $unit->unit_number);
    }

    private function assignableProperties(Request $request)
    {
        $user = $request->user();

        return Property::query()
            ->when($this->isLandlord($user), fn($query) => $query->where('landlord_id', $user->id))
            ->whereHas('units', fn($units) => $units->whereDoesntHave(
                'tenant',
                fn($tenant) => $tenant->where('is_active', true)
            ))
            ->orderBy('name')
            ->get();
    }

    private function unassignedTenantUsers(Request $request)
    {
        return User::with('role')
            ->whereHas('role', fn($role) => $role->where('name', 'TENANT'))
            ->whereDoesntHave('tenant', fn($tenant) => $tenant->where('is_active', true))
            ->when($request->search, fn($query) => $query->where(function ($userQuery) use ($request) {
                $userQuery->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone_number', 'like', "%{$request->search}%");
            }))
            ->orderBy('name');
    }
}
