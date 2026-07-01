<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        return view('admin.tenants.index', compact('tenants'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $units = Unit::with('property')
            ->whereDoesntHave('tenant', fn($tenant) => $tenant->where('is_active', true))
            ->when($this->isLandlord($user), fn($q) => $q->whereHas('property', fn($property) => $property->where('landlord_id', $user->id)))
            ->orderBy('unit_number')
            ->get()
            ->sortBy(fn($unit) => ($unit->property?->name ?? '') . ' ' . $unit->unit_number);

        return view('admin.tenants.create', compact('units'));
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
            'unit_id' => 'required|uuid|exists:units,id',
            'move_in_date' => 'required|date',
            'move_out_date' => 'nullable|date|after:move_in_date',
        ]);

        $unit = Unit::with('property')->findOrFail($data['unit_id']);
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

        $tenant = Tenant::create([
            'user_id' => $tenantUser->id,
            'unit_id' => $unit->id,
            'move_in_date' => $data['move_in_date'],
            'move_out_date' => $data['move_out_date'] ?? null,
            'is_active' => true,
        ]);

        $unit->update(['status' => 'OCCUPIED']);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant created and assigned to unit.');
    }

    public function show(Tenant $tenant)
    {
        $user = request()->user();
        abort_if($this->isLandlord($user) && $tenant->unit?->property?->landlord_id !== $user->id, 403);

        $tenant->load(['user', 'unit.property', 'unit.invoices', 'unit.maintenanceRequests']);
        return view('admin.tenants.show', compact('tenant'));
    }

    private function isLandlord(?User $user): bool
    {
        return $user?->role?->name === 'LANDLORD';
    }
}
