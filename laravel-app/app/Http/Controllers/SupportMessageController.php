<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\SupportConversation;
use App\Services\TenantEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

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
            'client_message_id' => $request->input('client_message_id', $request->input('clientMessageId')),
            'property_id' => $request->input('property_id', $request->input('propertyId')),
            'conversation_id' => $request->input('conversation_id', $request->input('conversationId')),
        ]);
        if (blank($request->input('body')) && filled($request->input('attachment_uri'))) {
            $request->merge(['body' => 'Attachment shared']);
        }

        $topic = trim((string) $request->input('topic', 'General')) ?: 'General';
        $conversation = $request->conversation_id
            ? SupportConversation::with('property')->find($request->conversation_id)
            : null;

        abort_if($this->isTenant($user) && $conversation && $conversation->tenant_user_id !== $user->id, 403);

        $property = $conversation?->property
            ?? $this->resolveAccessibleProperty($user, $request->input('property_id'));

        $landlordUserId = $conversation?->landlord_user_id ?: $property?->landlord_id;

        if (!$conversation) {
            $conversation = SupportConversation::firstOrCreate(
                [
                    'tenant_user_id' => $user->id,
                    'landlord_user_id' => $landlordUserId,
                    'property_id' => $property?->id,
                ],
                [
                    'topic' => $topic,
                    'subject' => trim(($property?->name ?: 'Property').' support'),
                    'is_open' => true,
                ]
            );
        }

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
            'client_message_id' => 'nullable|uuid',
        ]);
        abort_if($this->isTenant($user) && $data['sender_id'] !== $user->id, 403);
        abort_if($this->isTenant($user) && !SupportConversation::where('id', $data['conversation_id'])->where('tenant_user_id', $user->id)->exists(), 403);

        $data['status'] = 'SENT';
        $data['message_type'] = $request->input('message_type', filled($data['attachment_uri'] ?? null) ? 'document' : 'text');
        $data['attachment_mime_type'] = $request->input('attachment_mime_type');
        $data['attachment_size'] = $request->input('attachment_size');
        $message = filled($data['client_message_id'] ?? null)
            ? SupportMessage::firstOrCreate([
                'sender_id' => $data['sender_id'],
                'client_message_id' => $data['client_message_id'],
            ], $data)
            : SupportMessage::create($data);

        $conversation->update([
            'topic' => $topic,
            'subject' => trim(($property?->name ?: 'Property').' support'),
            'is_open' => true,
        ]);

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
        abort_unless($supportMessage->sender_id === request()->user()?->id, 403);
        if ($supportMessage->attachment_uri) {
            $path = Str::after($supportMessage->attachment_uri, '/storage/');
            if ($path !== $supportMessage->attachment_uri) Storage::disk('public')->delete($path);
        }
        $supportMessage->delete();
        return response()->json(null, 204);
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'file' => 'required|file|max:20480|mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,audio/mpeg,audio/mp4,audio/ogg,audio/webm',
        ]);
        $path = $data['file']->store('support-attachments', 'public');

        return response()->json([
            'attachmentName' => $data['file']->getClientOriginalName(),
            'attachmentUri' => Storage::disk('public')->url($path),
            'fileName' => basename($path),
            'attachmentMimeType' => $data['file']->getMimeType(),
            'attachmentSize' => $data['file']->getSize(),
            'messageType' => str_starts_with($data['file']->getMimeType(), 'image/') ? 'image' : (str_starts_with($data['file']->getMimeType(), 'audio/') ? 'audio' : 'document'),
        ]);
    }

    public function heartbeat(Request $request)
    {
        Cache::put('chat:online:'.$request->user()->id, now()->timestamp, now()->addSeconds(45));
        return response()->json([
            'ok' => true,
            'adminOnline' => Cache::has('chat:admin:online'),
            'adminTyping' => Cache::has('chat:admin:typing'),
        ]);
    }

    public function typing(Request $request)
    {
        $data = $request->validate(['typing' => 'required|boolean']);
        $key = 'chat:typing:'.$request->user()->id;
        $data['typing'] ? Cache::put($key, true, now()->addSeconds(4)) : Cache::forget($key);
        Cache::put('chat:online:'.$request->user()->id, now()->timestamp, now()->addSeconds(45));
        return response()->json(['ok' => true]);
    }

    private function messagesForUser(Request $request)
    {
        $user = $request->user();

        return SupportMessage::with(['sender', 'conversation.property'])
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
        $property = $message->conversation?->property;

        return [
            'id' => $message->id,
            'conversationId' => $message->conversation_id,
            'topic' => $message->topic,
            'message' => $message->body,
            'isFromTenant' => (bool) $message->is_from_tenant,
            'timestamp' => $message->created_at?->getTimestampMs() ?? 0,
            'status' => $message->status,
            'propertyId' => $property?->id,
            'propertyName' => $property?->name,
            'attachmentUri' => $message->attachment_uri,
            'attachmentName' => $message->attachment_name,
            'messageType' => $message->message_type,
            'attachmentMimeType' => $message->attachment_mime_type,
            'attachmentSize' => $message->attachment_size,
            'thumbnailUrl' => $message->thumbnail_url,
            'duration' => $message->duration,
            'uploadStatus' => $message->upload_status,
        ];
    }

    private function isTenant($user): bool
    {
        return $user?->role?->name === 'TENANT';
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
}
