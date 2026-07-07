<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Property;
use App\Models\Unit;
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

        return view('admin.invitations.index', compact('invitations', 'properties', 'isLandlord'));
    }

    public function storeTenant(Request $request)
    {
        $user = $request->user();
        abort_if($user?->role?->name === 'CARETAKER', 403);

        $data = $request->validate([
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'invitee_name' => 'nullable|string|max:160',
            'email' => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:30',
            'move_in_date' => 'nullable|date',
            'rent_amount' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'expires_at' => 'required|date|after:today',
            'message' => 'nullable|string|max:1000',
        ]);

        $property = Property::findOrFail($data['property_id']);
        $unit = Unit::with('tenant')->findOrFail($data['unit_id']);

        abort_if($unit->property_id !== $property->id, 422, 'The selected unit does not belong to this property.');
        abort_if($user?->role?->name === 'LANDLORD' && $property->landlord_id !== $user->id, 403);
        abort_if($unit->tenant()->where('is_active', true)->exists(), 422, 'This unit is already occupied.');

        $invitation = DB::transaction(function () use ($request, $data, $unit) {
            return Invitation::create([
                'invite_type' => 'TENANT',
                'code' => $this->uniqueCode(),
                'invitee_name' => $data['invitee_name'] ?? null,
                'email' => strtolower($data['email']),
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
                ],
            ]);
        });

        $emailSent = app(TenantEmailService::class)->tenantInvitation($invitation);

        return back()->with(
            'success',
            $emailSent
                ? 'Tenant invitation sent by email.'
                : 'Tenant invitation saved, but email could not be sent. Check mail logs.'
        );
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
