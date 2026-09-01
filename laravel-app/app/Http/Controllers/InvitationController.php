<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\TenantAppNotificationService;
use App\Services\TenantBillingService;
use App\Services\TenantEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeManager($request->user());
        $query = Invitation::with(['property', 'unit', 'sentBy'])
            ->when(
                $this->isLandlord($request->user()),
                fn($q) => $q->where('sent_by_id', $request->user()->id)
            )
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->property_id, fn($q) => $q->where('property_id', $request->property_id));
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $this->authorizeManager($request->user());
        $data = $request->validate([
            'email' => 'nullable|email|max:255',
            'invitee_name' => 'nullable|string|max:160',
            'phone_number' => 'nullable|string|max:20',
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'sent_via' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);
        $property = Property::findOrFail($data['property_id']);
        $unit = Unit::findOrFail($data['unit_id']);
        abort_if($this->isLandlord($request->user()) && $property->landlord_id !== $request->user()->id, 403);
        abort_if($unit->property_id !== $property->id, 422, 'The selected unit does not belong to this property.');
        abort_if($unit->tenant()->where('is_active', true)->exists(), 422, 'This unit already has an active tenant.');
        abort_if(empty($data['email']) && empty($data['phone_number']), 422, 'Provide an email address or phone number for the invitee.');

        $data['metadata'] = array_merge($data['metadata'] ?? [], [
            'rent_amount' => $unit->rent_amount,
            'deposit_amount' => $unit->rent_amount,
        ]);

        $data['sent_by_id'] = $request->user()->id;
        $data['invite_type'] = 'TENANT';
        $data['code'] = strtoupper(Str::random(8));
        $data['status'] = 'PENDING';
        $data['expires_at'] = now()->addDays(7);
        $data['last_sent_at'] = now();
        $invitation = Invitation::create($data)->load(['property', 'unit', 'sentBy']);
        $matchingUser = $this->findMatchingTenant($invitation);
        $linked = $matchingUser ? $this->linkInvitation($matchingUser, $invitation) : false;
        $emailSent = $linked || empty($invitation->email)
            ? false
            : app(TenantEmailService::class)->tenantInvitation($invitation);

        return response()->json([
            ...$invitation->toArray(),
            'email_sent' => $emailSent,
            'auto_linked' => $linked,
        ], 201);
    }

    public function show(Invitation $invitation)
    {
        $this->authorizeInvitation($invitation);
        return response()->json($invitation->load(['property', 'unit', 'sentBy']));
    }

    public function update(Request $request, Invitation $invitation)
    {
        $this->authorizeInvitation($invitation);
        $data = $request->validate([
            'status' => 'sometimes|in:PENDING,EXPIRED,CANCELLED,REVOKED',
        ]);
        $invitation->update($data);
        return response()->json($invitation->load(['property', 'unit']));
    }

    public function destroy(Invitation $invitation)
    {
        $this->authorizeInvitation($invitation);
        $invitation->delete();
        return response()->json(null, 204);
    }

    public function accept(Request $request)
    {
        $data = $request->validate(['code' => 'required|string']);
        $invitation = Invitation::with('unit')->where('code', strtoupper(trim($data['code'])))->first();
        $requestUser = $request->user();

        if (!$invitation || $invitation->status !== 'PENDING' || $invitation->expires_at < now()) {
            return response()->json(['message' => 'Invitation code is invalid or expired.'], 422);
        }

        abort_unless($requestUser?->role?->name === 'TENANT', 403, 'Only tenant accounts can accept unit invitations.');
        abort_unless($this->identityMatches($requestUser, $invitation), 403, 'This invitation was sent to a different account.');

        $this->linkInvitation($requestUser, $invitation);

        return response()->json(['message' => 'Invitation accepted and unit linked.']);
    }

    public function claim(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->role?->name === 'TENANT', 403, 'Only tenant accounts can claim unit invitations.');

        $invitations = Invitation::query()
            ->with('unit')
            ->where('invite_type', 'TENANT')
            ->where('status', 'PENDING')
            ->where('expires_at', '>', now())
            ->get()
            ->filter(fn (Invitation $invitation) => $this->identityMatches($user, $invitation));

        $linked = 0;
        foreach ($invitations as $invitation) {
            if ($this->linkInvitation($user, $invitation)) $linked++;
        }

        return response()->json([
            'message' => $linked > 0 ? 'Your invited unit was linked automatically.' : 'No pending unit invitations found.',
            'claimed' => $linked,
        ]);
    }

    private function linkInvitation(User $user, Invitation $invitation): bool
    {
        $invitation->loadMissing('unit');

        $existingActiveTenancy = $invitation->unit->tenant()->where('is_active', true)->first();
        if ($existingActiveTenancy && $existingActiveTenancy->user_id === $user->id) {
            $invitation->update(['status' => 'ACCEPTED', 'accepted_at' => now()]);
            return true;
        }

        if ($existingActiveTenancy) {
            abort(422, 'This unit already has an active tenant.');
        }

        $tenant = DB::transaction(function () use ($user, $invitation) {
            $tenant = Tenant::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'unit_id' => $invitation->unit_id,
                ],
                [
                    'move_in_date' => data_get($invitation->metadata, 'move_in_date') ?: now()->toDateString(),
                    'move_out_date' => null,
                    'is_active' => true,
                ]
            );
            $invitation->unit->update(['status' => 'OCCUPIED']);
            $invitation->update(['status' => 'ACCEPTED', 'accepted_at' => now()]);

            return $tenant;
        });

        $tenant = $tenant->fresh(['user', 'unit.property']);
        $invoice = app(TenantBillingService::class)->createInitialRentInvoice($tenant);
        app(TenantAppNotificationService::class)->tenantAssigned($tenant);
        if ($invoice->wasRecentlyCreated) {
            app(TenantAppNotificationService::class)->invoiceCreated($invoice);
            app(TenantEmailService::class)->invoiceCreated($invoice);
        }
        return true;
    }

    private function isLandlord($user): bool
    {
        return $user?->role?->name === 'LANDLORD';
    }

    private function authorizeInvitation(Invitation $invitation): void
    {
        $user = request()->user();
        $this->authorizeManager($user);
        abort_if($this->isLandlord($user) && $invitation->sent_by_id !== $user->id, 403);
    }

    private function authorizeManager($user): void
    {
        abort_unless(in_array($user?->role?->name, ['LANDLORD', 'ADMIN', 'SUPER_ADMIN'], true), 403);
    }

    private function findMatchingTenant(Invitation $invitation): ?User
    {
        $email = strtolower(trim((string) $invitation->email));
        $users = User::query()
            ->with('role')
            ->whereHas('role', fn ($role) => $role->where('name', 'TENANT'))
            ->when($email !== '', fn ($query) => $query->whereRaw('LOWER(email) = ?', [$email]))
            ->get();

        if ($match = $users->first(fn (User $user) => $this->identityMatches($user, $invitation))) return $match;
        if (blank($invitation->phone_number)) return null;

        return User::query()
            ->with('role')
            ->whereNotNull('phone_number')
            ->whereHas('role', fn ($role) => $role->where('name', 'TENANT'))
            ->get()
            ->first(fn (User $user) => $this->identityMatches($user, $invitation));
    }

    private function identityMatches(User $user, Invitation $invitation): bool
    {
        $emailMatches = filled($user->email) && filled($invitation->email)
            && strtolower(trim($user->email)) === strtolower(trim($invitation->email));
        $phoneMatches = filled($user->phone_number) && filled($invitation->phone_number)
            && $this->normalizePhone($user->phone_number) === $this->normalizePhone($invitation->phone_number);
        return $emailMatches || $phoneMatches;
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (str_starts_with($digits, '0')) return '254'.substr($digits, 1);
        if (strlen($digits) === 9 && preg_match('/^[17]/', $digits)) return '254'.$digits;
        return $digits;
    }
}
