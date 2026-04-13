<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index(Request $request)
    {
        $query = Invitation::with(['property', 'unit', 'sentBy'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->property_id, fn($q) => $q->where('property_id', $request->property_id));
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phone_number' => 'required|string|max:20',
            'property_id' => 'required|uuid|exists:properties,id',
            'unit_id' => 'required|uuid|exists:units,id',
            'sent_by_id' => 'required|uuid|exists:users,id',
            'sent_via' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);
        $data['code'] = strtoupper(Str::random(8));
        $data['status'] = 'PENDING';
        $data['expires_at'] = now()->addDays(7);
        return response()->json(Invitation::create($data)->load(['property', 'unit', 'sentBy']), 201);
    }

    public function show(Invitation $invitation)
    {
        return response()->json($invitation->load(['property', 'unit', 'sentBy']));
    }

    public function update(Request $request, Invitation $invitation)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:PENDING,ACCEPTED,EXPIRED,REVOKED',
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
        $invitation->delete();
        return response()->json(null, 204);
    }
}
