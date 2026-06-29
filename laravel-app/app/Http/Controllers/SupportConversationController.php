<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use Illuminate\Http\Request;

class SupportConversationController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportConversation::with(['tenant', 'messages'])
            ->when($request->tenant_user_id, fn($q) => $q->where('tenant_user_id', $request->tenant_user_id))
            ->when($request->is_open !== null, fn($q) => $q->where('is_open', $request->boolean('is_open')));
        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_user_id' => 'required|uuid|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'topic' => 'required|string|max:255',
        ]);
        $data['is_open'] = true;
        return response()->json(SupportConversation::create($data)->load('tenant'), 201);
    }

    public function show(SupportConversation $supportConversation)
    {
        return response()->json($supportConversation->load(['tenant', 'messages.sender']));
    }

    public function update(Request $request, SupportConversation $supportConversation)
    {
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
        $supportConversation->delete();
        return response()->json(null, 204);
    }
}
