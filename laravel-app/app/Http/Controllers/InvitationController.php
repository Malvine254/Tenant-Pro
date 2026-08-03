<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Tenant;
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
        $data = $request->validate([
            'email' => 'nullable|email|max:255',
            'invitee_name' => 'nullable|string|max:160',
            'phone_number' => 'nullable|string|max:20',
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'sent_via' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);
        $property = \App\Models\Property::findOrFail($data['property_id']);
        $unit = \App\Models\Unit::findOrFail($data['unit_id']);
        abort_if($this->isLandlord($request->user()) && $property->landlord_id !== $request->user()->id, 403);
        abort_if($unit->property_id !== $property->id, 422, 'The selected unit does not belong to this property.');
        abort_if($unit->tenant()->where('is_active', true)->exists(), 422, 'This unit already has an active tenant.');
        abort_if(empty($data['email']) && empty($data['phone_number']), 422, 'Provide an email address or phone number for the invitee.');

        $data['sent_by_id'] = $request->user()->id;
        $data['invite_type'] = 'TENANT';
        $data['code'] = strtoupper(Str::random(8));
        $data['status'] = 'PENDING';
        $data['expires_at'] = now()->addDays(7);
        $data['last_sent_at'] = now();
        $invitation = Invitation::create($data)->load(['property', 'unit', 'sentBy']);
        $emailSent = empty($invitation->email)
            ? false
            : app(TenantEmailService::class)->tenantInvitation($invitation);

        return response()->json([
            ...$invitation->toArray(),
            'email_sent' => $emailSent,
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
            'status' => 'sometimes|in:PENDING,ACCEPTED,EXPIRED,CANCELLED,REVOKED',
            'accepted_at' => 'nullable|date',
        ]);
        if (isset($data['status']) && $data['status'] === 'ACCEPTED' && empty($data['accepted_at'])) {
            $data['accepted_at'] = now();
        }
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

        $existingActiveTenancy = $invitation->unit->tenant()->where('is_active', true)->first();
        if ($existingActiveTenancy && $existingActiveTenancy->user_id === $requestUser->id) {
            $invitation->update(['status' => 'ACCEPTED', 'accepted_at' => now()]);
            return response()->json(['message' => 'Unit already linked to your account. Invitation confirmed.']);
        }

        if ($existingActiveTenancy) {
            return response()->json(['message' => 'This unit already has an active tenant.'], 422);
        }

        $tenant = DB::transaction(function () use ($requestUser, $invitation) {
            $tenant = Tenant::updateOrCreate(
                [
                    'user_id' => $requestUser->id,
                    'unit_id' => $invitation->unit_id,
                ],
                [
                    'move_in_date' => now()->toDateString(),
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

        return response()->json(['message' => 'Invitation accepted and unit linked.']);
    }

    private function isLandlord($user): bool
    {
        return $user?->role?->name === 'LANDLORD';
    }

    private function authorizeInvitation(Invitation $invitation): void
    {
        $user = request()->user();
        abort_if($this->isLandlord($user) && $invitation->sent_by_id !== $user->id, 403);
    }
}
