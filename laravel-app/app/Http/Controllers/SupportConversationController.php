<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use Illuminate\Http\Request;

class SupportConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $this->ensureAllowedRole($user);

        $query = SupportConversation::with(['tenant', 'property', 'messages'])
            ->when($this->isTenant($user), fn($q) => $q->where('tenant_user_id', $user->id))
            ->when($this->isLandlord($user), fn($q) => $q->where('landlord_user_id', $user->id))
            ->when($request->tenant_user_id, fn($q) => $q->where('tenant_user_id', $request->tenant_user_id))
            ->when($request->property_id, fn($q) => $q->where('property_id', $request->property_id))
            ->when($request->is_open !== null, fn($q) => $q->where('is_open', $request->boolean('is_open')));
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->ensureAllowedRole($user);

        $property = $this->isTenant($user)
            ? $this->resolveAccessibleProperty($user, $request->input('property_id', $request->input('propertyId')))
            : null;

        abort_if($this->isTenant($user) && !$property, 422, 'A valid property is required to start a support conversation.');

        $request->merge([
            'tenant_user_id' => $request->input('tenant_user_id', $user?->id),
            'landlord_user_id' => $request->input('landlord_user_id', $property?->landlord_id),
            'property_id' => $request->input('property_id', $request->input('propertyId', $property?->id)),
        ]);

        $data = $request->validate([
            'tenant_user_id' => 'required|uuid|exists:users,id',
            'landlord_user_id' => 'required|uuid|exists:users,id',
            'property_id' => 'required|uuid|exists:properties,id',
            'subject' => 'nullable|string|max:255',
            'topic' => 'required|string|max:255',
        ]);
        abort_if($this->isTenant($user) && $data['tenant_user_id'] !== $user->id, 403);
        abort_if($this->isLandlord($user) && $data['landlord_user_id'] !== $user->id, 403);

        $data['is_open'] = true;
        return response()->json(SupportConversation::create($data)->load(['tenant', 'property']), 201);
    }

    public function show(SupportConversation $supportConversation)
    {
        $user = request()->user();
        $this->ensureAllowedRole($user);

        abort_if($this->isTenant($user) && $supportConversation->tenant_user_id !== $user->id, 403);
        abort_if($this->isLandlord($user) && $supportConversation->landlord_user_id !== $user->id, 403);

        return response()->json($supportConversation->load(['tenant', 'property', 'messages.sender']));
    }

    public function update(Request $request, SupportConversation $supportConversation)
    {
        $user = $request->user();
        $this->ensureAllowedRole($user);

        abort_if($this->isTenant($user) && $supportConversation->tenant_user_id !== $user->id, 403);
        abort_if($this->isLandlord($user) && $supportConversation->landlord_user_id !== $user->id, 403);

        $data = $request->validate([
            'subject' => 'nullable|string|max:255',
            'topic' => 'sometimes|string|max:255',
            'is_open' => 'sometimes|boolean',
        ]);
        $supportConversation->update($data);
        return response()->json($supportConversation);
    }

    public function destroy(SupportConversation $supportConversation)
    {
        $user = request()->user();
        $this->ensureAllowedRole($user);

        abort_if($this->isTenant($user) && $supportConversation->tenant_user_id !== $user->id, 403);
        abort_if($this->isLandlord($user) && $supportConversation->landlord_user_id !== $user->id, 403);

        $supportConversation->delete();
        return response()->json(null, 204);
    }

    private function isTenant($user): bool
    {
        return $user?->role?->name === 'TENANT';
    }

    private function isLandlord($user): bool
    {
        return $user?->role?->name === 'LANDLORD';
    }

    private function resolveAccessibleProperty($user, ?string $propertyId)
    {
        if (!$this->isTenant($user)) {
            return null;
        }

        $activeTenancy = $user->tenancies()
            ->where('is_active', true)
            ->with('unit.property')
            ->when($propertyId, fn ($query) => $query->whereHas('unit.property', fn ($propertyQuery) => $propertyQuery->where('id', $propertyId)))
            ->latest('updated_at')
            ->first();

        abort_if($propertyId && !$activeTenancy, 403, 'You can only contact landlords for properties assigned to you.');

        return $activeTenancy?->unit?->property;
    }

    private function ensureAllowedRole($user): void
    {
        abort_if($user?->role?->name === 'CARETAKER', 403, 'Caretakers cannot access support chat endpoints.');
    }
}
