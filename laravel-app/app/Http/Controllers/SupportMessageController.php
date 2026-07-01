<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportConversation;
use Illuminate\Http\Request;

class SupportMessageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = SupportMessage::with('sender')
            ->when($this->isTenant($user), fn($q) => $q->whereHas('conversation', fn($conversation) => $conversation->where('tenant_user_id', $user->id)))
            ->when($request->conversation_id, fn($q) => $q->where('conversation_id', $request->conversation_id));
        return response()->json($query->oldest()->paginate(50));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $conversation = $request->conversation_id
            ? SupportConversation::find($request->conversation_id)
            : null;

        $request->merge([
            'sender_id' => $request->input('sender_id', $user?->id),
            'topic' => $request->input('topic', $conversation?->topic ?? 'Message'),
            'is_from_tenant' => $request->input('is_from_tenant', $this->isTenant($user)),
        ]);

        $data = $request->validate([
            'conversation_id' => 'required|uuid|exists:support_conversations,id',
            'sender_id' => 'required|uuid|exists:users,id',
            'topic' => 'required|string|max:255',
            'body' => 'required|string',
            'attachment_name' => 'nullable|string|max:255',
            'attachment_uri' => 'nullable|url',
            'is_from_tenant' => 'required|boolean',
        ]);
        abort_if($this->isTenant($user) && $data['sender_id'] !== $user->id, 403);
        abort_if($this->isTenant($user) && !SupportConversation::where('id', $data['conversation_id'])->where('tenant_user_id', $user->id)->exists(), 403);

        $data['status'] = 'SENT';
        return response()->json(SupportMessage::create($data)->load('sender'), 201);
    }

    public function show(SupportMessage $supportMessage)
    {
        $user = request()->user();
        abort_if($this->isTenant($user) && $supportMessage->conversation()->where('tenant_user_id', $user->id)->doesntExist(), 403);

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

    private function isTenant($user): bool
    {
        return $user?->role?->name === 'TENANT';
    }
}
