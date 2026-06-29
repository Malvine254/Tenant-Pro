<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use Illuminate\Http\Request;

class SupportMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportMessage::with('sender')
            ->when($request->conversation_id, fn($q) => $q->where('conversation_id', $request->conversation_id));
        return response()->json($query->oldest()->paginate(50));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'conversation_id' => 'required|uuid|exists:support_conversations,id',
            'sender_id' => 'required|uuid|exists:users,id',
            'topic' => 'required|string|max:255',
            'body' => 'required|string',
            'attachment_name' => 'nullable|string|max:255',
            'attachment_uri' => 'nullable|url',
            'is_from_tenant' => 'required|boolean',
        ]);
        $data['status'] = 'SENT';
        return response()->json(SupportMessage::create($data)->load('sender'), 201);
    }

    public function show(SupportMessage $supportMessage)
    {
        return response()->json($supportMessage->load(['sender', 'conversation']));
    }

    public function update(Request $request, SupportMessage $supportMessage)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:SENT,READ',
            'body' => 'sometimes|string',
        ]);
        $supportMessage->update($data);
        return response()->json($supportMessage);
    }

    public function destroy(SupportMessage $supportMessage)
    {
        $supportMessage->delete();
        return response()->json(null, 204);
    }
}
