<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\TenantAppNotificationService;
use App\Services\TenantBillingService;
use App\Services\TenantEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitationAdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isLandlord = $user?->role?->name === 'LANDLORD';
        $tenantSettingValues = $isLandlord && is_array($user->app_settings['tenantSettings'] ?? null)
            ? $user->app_settings['tenantSettings']
            : [];
        $tenantInviteExpiryDefault = now()->addDays((int) ($tenantSettingValues['default_invite_expiry_days'] ?? 7))->toDateString();

        $invitations = Invitation::with(['property', 'unit', 'sentBy'])
            ->when(
                $isLandlord,
                fn($query) => $query->where('sent_by_id', $user->id)
            )
            ->when($request->type, fn($query) => $query->where('invite_type', $request->type))
            ->when($request->status, fn($query) => $query->where('status', $request->status))
            ->when($request->search, fn($query) => $query->where(function ($search) use ($request) {
                $search->where('invitee_name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone_number', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $properties = Property::query()
            ->when($isLandlord, fn($query) => $query->where('landlord_id', $user->id))
            ->whereHas('units', fn($units) => $units->whereDoesntHave(
                'tenant',
                fn($tenant) => $tenant->where('is_active', true)
            ))
            ->with(['units' => fn($units) => $units
                ->whereDoesntHave('tenant', fn($tenant) => $tenant->where('is_active', true))
                ->orderBy('unit_number')])
            ->orderBy('name')
            ->get();

        $tenantUsers = User::query()
            ->whereHas('role', fn($role) => $role->where('name', 'TENANT'))
            ->whereNotNull('email')
            ->when(
                $isLandlord,
                fn($query) => $query->where(function ($tenantQuery) use ($user) {
                    $tenantQuery
                        ->whereDoesntHave('tenancies', fn($tenancies) => $tenancies->where('is_active', true))
                        ->orWhereHas('tenancies.unit.property', fn($property) => $property->where('landlord_id', $user->id));
                })
            )
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone_number']);

        $editingInvitation = null;
        if ($request->filled('edit')) {
            $editingInvitation = Invitation::query()
                ->with(['property', 'unit', 'sentBy'])
                ->findOrFail($request->string('edit'));

            $this->authorizeInvitation($request, $editingInvitation);
            abort_if(!in_array($editingInvitation->status, ['PENDING', 'EXPIRED'], true), 422, 'Only pending or expired invitations can be edited.');
        }

        return view('admin.invitations.index', compact('invitations', 'properties', 'tenantUsers', 'isLandlord', 'tenantInviteExpiryDefault', 'editingInvitation'));
    }

    public function storeTenant(Request $request)
    {
        $user = $request->user();
        abort_if($user?->role?->name === 'CARETAKER', 403);

        $data = $request->validate([
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'tenant_user_id' => 'nullable|uuid|exists:users,id',
            'invitee_name' => 'nullable|string|max:160',
            'email' => 'nullable|email|max:255|required_without:tenant_user_id',
            'phone_number' => 'nullable|string|max:30',
            'move_in_date' => 'nullable|date',
            'rent_amount' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'expires_at' => 'required|date|after:today',
            'message' => 'nullable|string|max:1000',
        ]);

        $property = Property::findOrFail($data['property_id']);
        $unit = Unit::with('tenant')->findOrFail($data['unit_id']);
        $landlordSettings = is_array($property->landlord?->app_settings['tenantSettings'] ?? null)
            ? $property->landlord->app_settings['tenantSettings']
            : [];
        $autoAssignOnSelect = (bool) ($landlordSettings['auto_assign_unit_on_accept'] ?? true);
        $allowMultiUnitAssignment = (bool) ($landlordSettings['allow_multi_unit_assignment'] ?? true);

        $selectedTenant = null;
        if (!empty($data['tenant_user_id'])) {
            $selectedTenant = User::query()
                ->with('role')
                ->findOrFail($data['tenant_user_id']);

            abort_if($selectedTenant->role?->name !== 'TENANT', 422, 'Selected user must be a tenant account.');
            abort_if(blank($selectedTenant->email), 422, 'Selected tenant does not have an email address.');

            $data['email'] = $selectedTenant->email;
            $data['phone_number'] = $data['phone_number'] ?: $selectedTenant->phone_number;
            $data['invitee_name'] = $data['invitee_name'] ?: $selectedTenant->name;

            if (!$allowMultiUnitAssignment) {
                $hasActiveTenancy = Tenant::query()
                    ->where('user_id', $selectedTenant->id)
                    ->where('is_active', true)
                    ->exists();

                abort_if($hasActiveTenancy, 422, 'This landlord does not allow multi-unit assignment for a tenant account.');
            }
        }

        $normalizedEmail = strtolower(trim((string) ($data['email'] ?? '')));
        abort_if($normalizedEmail === '', 422, 'Tenant email is required when no existing tenant account is selected.');

        [$loginUser, $temporaryPassword, $firstTimeSetup] = $this->prepareTenantLogin(
            $normalizedEmail,
            (string) ($data['invitee_name'] ?? '')
        );
        if (!$selectedTenant && !$firstTimeSetup && $loginUser->role?->name === 'TENANT') {
            $selectedTenant = $loginUser;
        }

        abort_if($unit->property_id !== $property->id, 422, 'The selected unit does not belong to this property.');
        abort_if($user?->role?->name === 'LANDLORD' && $property->landlord_id !== $user->id, 403);
        abort_if($unit->tenant()->where('is_active', true)->exists(), 422, 'This unit is already occupied.');

        $autoAssignedTenant = null;

        $invitation = DB::transaction(function () use ($request, $data, $unit, $normalizedEmail, $firstTimeSetup, $selectedTenant, &$autoAssignedTenant) {
            return Invitation::create([
                'invite_type' => 'TENANT',
                'code' => $this->uniqueCode(),
                'invitee_name' => $data['invitee_name'] ?? null,
                'email' => $normalizedEmail,
                'phone_number' => $data['phone_number'] ?? null,
                'message' => $data['message'] ?? null,
                'property_id' => $data['property_id'],
                'unit_id' => $data['unit_id'],
                'sent_by_id' => $request->user()->id,
                'status' => 'PENDING',
                'expires_at' => $data['expires_at'],
                'last_sent_at' => now(),
                'sent_via' => 'EMAIL',
                'metadata' => [
                    'move_in_date' => $data['move_in_date'] ?? null,
                    'rent_amount' => $data['rent_amount'] ?? $unit->rent_amount,
                    'deposit_amount' => $data['deposit_amount'] ?? null,
                    'first_time_setup' => $firstTimeSetup,
                    'auto_assigned' => false,
                ],
            ]);
        });

        if ($selectedTenant && $autoAssignOnSelect) {
            $autoAssignedTenant = DB::transaction(function () use ($selectedTenant, $unit, $data, $invitation) {
                $tenant = Tenant::query()->updateOrCreate(
                    [
                        'user_id' => $selectedTenant->id,
                        'unit_id' => $unit->id,
                    ],
                    [
                        'move_in_date' => $data['move_in_date'] ?? now()->toDateString(),
                        'move_out_date' => null,
                        'is_active' => true,
                    ]
                );

                $unit->update(['status' => 'OCCUPIED']);

                $metadata = $invitation->metadata ?? [];
                $metadata['auto_assigned'] = true;
                $metadata['auto_assigned_at'] = now()->toIso8601String();
                $metadata['auto_assigned_user_id'] = $selectedTenant->id;

                $invitation->update([
                    'metadata' => $metadata,
                ]);

                return $tenant->fresh(['user', 'unit.property']);
            });

            $invoice = app(TenantBillingService::class)->createInitialRentInvoice($autoAssignedTenant);
            app(TenantAppNotificationService::class)->tenantAssigned($autoAssignedTenant);
            if ($invoice->wasRecentlyCreated) {
                app(TenantAppNotificationService::class)->invoiceCreated($invoice);
                app(TenantEmailService::class)->invoiceCreated($invoice);
            }
        }

        $emailSent = app(TenantEmailService::class)->tenantInvitation($invitation, [
            'loginEmail' => $loginUser->email,
            'temporaryPassword' => $temporaryPassword,
            'firstTimeSetup' => $firstTimeSetup,
        ]);

        return back()->with(
            'success',
            $selectedTenant
                ? 'Tenant linked to the unit immediately. Invitation code was also sent for fallback confirmation.'
                : ($emailSent
                    ? 'Tenant invitation sent by email.'
                    : 'Tenant invitation saved, but email could not be sent. Check mail logs.')
        );
    }

    private function prepareTenantLogin(string $email, string $inviteeName): array
    {
        $tenantRole = Role::query()->firstOrCreate(
            ['name' => 'TENANT'],
            ['description' => 'Tenant user']
        );

        /** @var User|null $existingUser */
        $existingUser = User::query()
            ->with('role')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existingUser) {
            $existingRole = $existingUser->role?->name;
            if ($existingRole && !in_array($existingRole, ['TENANT', 'ADMIN', 'SUPER_ADMIN'], true)) {
                abort(422, 'The invite email already belongs to a non-tenant account. Use another email.');
            }

            $updates = [];
            if (!$existingUser->role_id) {
                $updates['role_id'] = $tenantRole->id;
            }
            if (!$existingUser->is_active) {
                $updates['is_active'] = true;
            }
            if (!$existingUser->email_verified_at) {
                $updates['email_verified_at'] = now();
            }
            if (blank($existingUser->name) && trim($inviteeName) !== '') {
                $updates['name'] = trim($inviteeName);
            }

            if (!empty($updates)) {
                $existingUser->update($updates);
            }

            return [$existingUser->fresh(), null, false];
        }

        $temporaryPassword = $this->generateTemporaryPassword();
        $cleanName = trim($inviteeName);
        $firstName = $cleanName !== '' ? strtok($cleanName, ' ') : null;
        $lastName = $cleanName !== '' ? trim((string) substr($cleanName, strlen((string) $firstName))) : null;

        $newUser = User::query()->create([
            'name' => $cleanName !== '' ? $cleanName : 'Tenant User',
            'first_name' => $firstName ?: null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'email' => $email,
            'password' => $temporaryPassword,
            'role_id' => $tenantRole->id,
            'is_active' => true,
            'email_verified_at' => now(),
            'requires_password_change' => true,
        ]);

        return [$newUser, $temporaryPassword, true];
    }

    private function generateTemporaryPassword(): string
    {
        return 'Tp!'.strtoupper(Str::random(2)).Str::random(6).random_int(10, 99);
    }

    public function storeLandlord(Request $request)
    {
        abort_if($request->user()?->role?->name === 'LANDLORD', 403);

        $data = $request->validate([
            'invitee_name' => 'required|string|max:160',
            'email' => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:30',
            'business_name' => 'nullable|string|max:160',
            'message' => 'nullable|string|max:1000',
            'expires_at' => 'required|date|after:today',
        ]);

        $invitation = Invitation::create([
            'invite_type' => 'LANDLORD',
            'code' => $this->uniqueCode(),
            'invitee_name' => $data['invitee_name'],
            'email' => strtolower($data['email']),
            'phone_number' => $data['phone_number'] ?? null,
            'business_name' => $data['business_name'] ?? null,
            'message' => $data['message'] ?? null,
            'sent_by_id' => $request->user()->id,
            'status' => 'PENDING',
            'expires_at' => $data['expires_at'],
            'last_sent_at' => now(),
            'sent_via' => 'EMAIL',
        ]);

        $emailSent = app(TenantEmailService::class)->landlordInvitation($invitation);

        return back()->with(
            'success',
            $emailSent
                ? 'Landlord invitation sent by email.'
                : 'Landlord invitation saved, but email could not be sent. Check mail logs.'
        );
    }

    public function update(Request $request, Invitation $invitation)
    {
        $this->authorizeInvitation($request, $invitation);
        abort_if(!in_array($invitation->status, ['PENDING', 'EXPIRED'], true), 422, 'Only pending or expired invitations can be edited.');

        $data = $request->validate([
            'invitee_name' => 'nullable|string|max:160',
            'email' => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:30',
            'business_name' => 'nullable|string|max:160',
            'message' => 'nullable|string|max:1000',
            'expires_at' => 'required|date|after:today',
        ]);

        $email = strtolower(trim($data['email']));
        $emailChanged = strtolower((string) $invitation->email) !== $email;
        $temporaryPassword = null;

        if ($invitation->invite_type === 'TENANT' && $emailChanged) {
            $temporaryUser = User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower((string) $invitation->email)])
                ->where('requires_password_change', true)
                ->first();

            if ($temporaryUser) {
                $emailTaken = User::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->whereKeyNot($temporaryUser->id)
                    ->exists();

                abort_if($emailTaken, 422, 'The corrected email already belongs to another account.');

                $temporaryPassword = $this->generateTemporaryPassword();
                $temporaryUser->update([
                    'email' => $email,
                    'password' => $temporaryPassword,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'requires_password_change' => true,
                ]);
            }
        }

        $invitation->update([
            'invitee_name' => $data['invitee_name'] ?: null,
            'email' => $email,
            'phone_number' => $data['phone_number'] ?: null,
            'business_name' => $invitation->invite_type === 'LANDLORD' ? ($data['business_name'] ?: null) : $invitation->business_name,
            'message' => $data['message'] ?: null,
            'status' => 'PENDING',
            'expires_at' => $data['expires_at'],
            'last_sent_at' => now(),
        ]);

        $emailSent = $invitation->invite_type === 'LANDLORD'
            ? app(TenantEmailService::class)->landlordInvitation($invitation)
            : app(TenantEmailService::class)->tenantInvitation($invitation, [
                'loginEmail' => $email,
                'temporaryPassword' => $temporaryPassword,
                'firstTimeSetup' => $temporaryPassword !== null,
            ]);

        return redirect()
            ->route('admin.invitations.index')
            ->with(
                'success',
                $emailSent
                    ? 'Invitation updated and sent to the corrected recipient details.'
                    : 'Invitation details were updated, but the email could not be sent. Check mail logs.'
            );
    }

    public function resend(Request $request, Invitation $invitation)
    {
        $this->authorizeInvitation($request, $invitation);
        abort_if(!in_array($invitation->status, ['PENDING', 'EXPIRED'], true), 422, 'Only pending or expired invitations can be resent.');

        $invitation->update([
            'status' => 'PENDING',
            'expires_at' => now()->addDays(7),
            'last_sent_at' => now(),
        ]);

        $emailSent = $invitation->invite_type === 'LANDLORD'
            ? app(TenantEmailService::class)->landlordInvitation($invitation)
            : app(TenantEmailService::class)->tenantInvitation($invitation);

        return back()->with(
            'success',
            $emailSent
                ? 'Invitation resent by email.'
                : 'Invitation expiry refreshed, but email could not be sent. Check mail logs.'
        );
    }

    public function cancel(Request $request, Invitation $invitation)
    {
        $this->authorizeInvitation($request, $invitation);
        abort_if($invitation->status === 'ACCEPTED', 422, 'Accepted invitations cannot be cancelled.');

        $invitation->update(['status' => 'CANCELLED']);

        return back()->with('success', 'Invitation cancelled.');
    }

    private function authorizeInvitation(Request $request, Invitation $invitation): void
    {
        abort_if(
            $request->user()?->role?->name === 'LANDLORD' && $invitation->sent_by_id !== $request->user()->id,
            403
        );
    }

    private function uniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Invitation::where('code', $code)->exists());

        return $code;
    }
}
