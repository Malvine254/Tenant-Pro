<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use App\Services\LandlordSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LandlordAdminController extends Controller
{
    public function __construct(
        private readonly LandlordSubscriptionService $subscriptionService,
    ) {
    }

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
            // Keep the admin list accurate even if the external scheduler has
            // been temporarily unavailable.
            $this->subscriptionService->evaluate($landlord);

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
            'monthly_service_fee' => 'required|numeric|min:0|max:1000000',
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

        $this->subscriptionService->initializeTrial($user, (float) $data['monthly_service_fee']);

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
            'monthly_service_fee' => 'required|numeric|min:0|max:1000000',
        ]);

        $updates = [
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'monthly_service_fee' => $data['monthly_service_fee'],
        ];

        if (!empty($data['password'])) {
            $updates['password'] = Hash::make($data['password']);
        }

        DB::transaction(function () use ($landlord, $updates) {
            $landlord->update($updates);

            if (!$landlord->is_active) {
                $this->revokeSuspendedLandlordSessions($landlord);
            }
        });

        return redirect()->route('admin.landlords.index')->with('success', 'Landlord account updated.');
    }

    public function updateStatus(Request $request, User $landlord)
    {
        abort_if($this->isLandlord($request->user()), 403);
        abort_unless($this->isLandlord($landlord), 404);

        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        DB::transaction(function () use ($landlord, $data) {
            $landlord->update(['is_active' => (bool) $data['is_active']]);

            if (!$landlord->is_active) {
                $this->revokeSuspendedLandlordSessions($landlord);
            }
        });

        return back()->with('success', 'Landlord status updated.');
    }

    public function recordPayment(Request $request, User $landlord)
    {
        abort_if($this->isLandlord($request->user()), 403);
        abort_unless($this->isLandlord($landlord), 404);

        $data = $request->validate([
            'months' => 'required|integer|min:1|max:24',
        ]);

        $this->subscriptionService->recordPayment($landlord, (int) $data['months']);

        return back()->with('success', 'Subscription payment recorded for '.$data['months'].' month(s).');
    }

    private function isLandlord(?User $user): bool
    {
        return $user?->role?->name === 'LANDLORD';
    }

    private function revokeSuspendedLandlordSessions(User $landlord): void
    {
        $landlord->tokens()->delete();
    }
}
