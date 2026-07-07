<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LandlordAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_if($this->isLandlord($request->user()), 403);

        $landlords = User::withCount('properties')
            ->whereHas('role', fn ($query) => $query->where('name', 'LANDLORD'))
            ->when($request->status === 'active', fn($query) => $query->where('is_active', true))
            ->when($request->status === 'suspended', fn($query) => $query->where('is_active', false))
            ->when($request->search, fn ($query) => $query->where(function ($userQuery) use ($request) {
                $userQuery->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone_number', 'like', "%{$request->search}%");
            }))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $landlords->getCollection()->transform(function (User $landlord) {
            $invoiceQuery = Invoice::whereHas('unit.property', fn($property) => $property->where('landlord_id', $landlord->id));
            $paymentQuery = Payment::whereHas('invoice.unit.property', fn($property) => $property->where('landlord_id', $landlord->id));

            $totalBilled = (float) (clone $invoiceQuery)->sum('total_amount');
            $totalPaid = (float) (clone $invoiceQuery)->sum('paid_amount');

            $landlord->units_count = \App\Models\Unit::whereHas('property', fn($property) => $property->where('landlord_id', $landlord->id))->count();
            $landlord->tenants_count = \App\Models\Tenant::whereHas('unit.property', fn($property) => $property->where('landlord_id', $landlord->id))->where('is_active', true)->count();
            $landlord->collected_this_month = (float) (clone $paymentQuery)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount');
            $landlord->outstanding_balance = max(0, $totalBilled - $totalPaid);

            return $landlord;
        });

        return view('admin.landlords.index', compact('landlords'));
    }

    public function create()
    {
        abort_if($this->isLandlord(request()->user()), 403);

        return view('admin.landlords.create');
    }

    public function store(Request $request)
    {
        abort_if($this->isLandlord($request->user()), 403);

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|unique:users,phone_number',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'LANDLORD'],
            ['description' => 'Property owner/manager']
        );

        $user = User::create([
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'password' => Hash::make($data['password']),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.properties.create', ['landlord_id' => $user->id])
            ->with('success', 'Landlord created. You can now assign a property to them.');
    }

    public function edit(User $landlord)
    {
        abort_if($this->isLandlord(request()->user()), 403);
        abort_unless($this->isLandlord($landlord), 404);

        return view('admin.landlords.edit', compact('landlord'));
    }

    public function update(Request $request, User $landlord)
    {
        abort_if($this->isLandlord($request->user()), 403);
        abort_unless($this->isLandlord($landlord), 404);

        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $landlord->id,
            'phone_number' => 'nullable|string|unique:users,phone_number,' . $landlord->id,
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'sometimes|boolean',
        ]);

        $updates = [
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if (!empty($data['password'])) {
            $updates['password'] = Hash::make($data['password']);
        }

        $landlord->update($updates);

        return redirect()->route('admin.landlords.index')->with('success', 'Landlord account updated.');
    }

    public function updateStatus(Request $request, User $landlord)
    {
        abort_if($this->isLandlord($request->user()), 403);
        abort_unless($this->isLandlord($landlord), 404);

        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $landlord->update(['is_active' => (bool) $data['is_active']]);

        return back()->with('success', 'Landlord status updated.');
    }

    private function isLandlord(?User $user): bool
    {
        return $user?->role?->name === 'LANDLORD';
    }
}
