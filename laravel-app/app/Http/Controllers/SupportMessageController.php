<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportConversation;
use App\Services\TenantEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportMessageController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $this->messagesForUser($request)
                ->map(fn(SupportMessage $message) => $this->messagePayload($message))
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->merge([
            'body' => $request->input('body', $request->input('text')),
            'attachment_name' => $request->input('attachment_name', $request->input('attachmentName')),
            'attachment_uri' => $request->input('attachment_uri', $request->input('attachmentUri')),
        ]);
        if (blank($request->input('body')) && filled($request->input('attachment_uri'))) {
            $request->merge(['body' => 'Attachment shared']);
        }

        $topic = trim((string) $request->input('topic', 'General')) ?: 'General';
        $conversation = $request->conversation_id
            ? SupportConversation::find($request->conversation_id)
            : SupportConversation::firstOrCreate(
                [
                    'tenant_user_id' => $user->id,
                    'topic' => $topic,
                    'is_open' => true,
                ],
                ['subject' => $topic]
            );

        $request->merge([
            'conversation_id' => $conversation?->id,
            'sender_id' => $request->input('sender_id', $user?->id),
            'topic' => $topic,
            'is_from_tenant' => $request->input('is_from_tenant', $this->isTenant($user)),
        ]);

        $data = $request->validate([
            'conversation_id' => 'required|uuid|exists:support_conversations,id',
            'sender_id' => 'required|uuid|exists:users,id',
            'topic' => 'required|string|max:255',
            'body' => 'required|string',
            'attachment_name' => 'nullable|string|max:255',
            'attachment_uri' => 'nullable|string|max:2048',
            'is_from_tenant' => 'required|boolean',
        ]);
        abort_if($this->isTenant($user) && $data['sender_id'] !== $user->id, 403);
        abort_if($this->isTenant($user) && !SupportConversation::where('id', $data['conversation_id'])->where('tenant_user_id', $user->id)->exists(), 403);

        $data['status'] = 'SENT';
        $message = SupportMessage::create($data);

        if ($this->isTenant($user)) {
            app(TenantEmailService::class)->supportMessageReceived($message);
        }

        return response()->json(
            $this->messagesForUser($request)
                ->map(fn(SupportMessage $message) => $this->messagePayload($message)),
            201
        );
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

    public function upload(Request $request)
    {
        $data = $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,webp,pdf,doc,docx',
        ]);
        $path = $data['file']->store('support-attachments', 'public');

        return response()->json([
            'attachmentName' => $data['file']->getClientOriginalName(),
            'attachmentUri' => Storage::disk('public')->url($path),
            'fileName' => basename($path),
        ]);
    }

    private function messagesForUser(Request $request)
    {
        $user = $request->user();

        return SupportMessage::with('sender')
            ->when(
                $this->isTenant($user),
                fn($query) => $query->whereHas(
                    'conversation',
                    fn($conversation) => $conversation->where('tenant_user_id', $user->id)
                )
            )
            ->when($request->conversation_id, fn($query) => $query->where('conversation_id', $request->conversation_id))
            ->oldest()
            ->get();
    }

    private function messagePayload(SupportMessage $message): array
    {
        return [
            'id' => $message->id,
            'topic' => $message->topic,
            'message' => $message->body,
            'isFromTenant' => (bool) $message->is_from_tenant,
            'timestamp' => $message->created_at?->getTimestampMs() ?? 0,
            'status' => $message->status,
            'attachmentUri' => $message->attachment_uri,
            'attachmentName' => $message->attachment_name,
        ];
    }

    private function isTenant($user): bool
    {
        return $user?->role?->name === 'TENANT';
    }
}
