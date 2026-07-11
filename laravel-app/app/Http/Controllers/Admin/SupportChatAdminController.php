<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\TenantAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportChatAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_if($request->user()?->role?->name === 'CARETAKER', 403);
        $query = $this->scoped($request->user())
            ->with(['tenant.role', 'tenant.tenant.unit.property', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->when($request->search, fn ($q, $search) => $q->whereHas('tenant', fn ($u) => $u->where('name', 'like', "%{$search}%")))
            ->latest();
        $conversations = $query->paginate(20)->withQueryString();
        $id = $request->conversation_id ?: $conversations->first()?->id;
        $selectedConversation = $id ? $this->scoped($request->user())
            ->with(['tenant.role', 'tenant.tenant.unit.property', 'messages.sender.role'])->findOrFail($id) : null;
        if ($selectedConversation) {
            $selectedConversation->messages()->where('is_from_tenant', true)->whereNull('read_at')
                ->update(['status' => 'READ', 'read_at' => now()]);
        }
        return view('admin.chats.index', compact('conversations', 'selectedConversation'));
    }

    public function reply(Request $request, SupportConversation $supportConversation)
    {
        $this->authorizeConversation($request->user(), $supportConversation);
        $data = $request->validate([
            'body' => 'nullable|required_without:file|string|max:5000',
            'file' => 'nullable|required_without:body|file|max:20480|mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/plain,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,audio/mpeg,audio/mp4,audio/ogg,audio/webm',
        ]);
        $file = $data['file'] ?? null;
        $path = $file?->store('support-attachments', 'public');
        $mime = $file?->getMimeType();
        $message = SupportMessage::create([
            'conversation_id' => $supportConversation->id, 'sender_id' => $request->user()->id,
            'topic' => $supportConversation->topic ?: 'General', 'body' => trim((string) ($data['body'] ?? '')),
            'message_type' => $file ? (str_starts_with($mime, 'image/') ? 'image' : (str_starts_with($mime, 'audio/') ? 'audio' : 'document')) : 'text',
            'attachment_name' => $file?->getClientOriginalName(), 'attachment_uri' => $path ? Storage::disk('public')->url($path) : null,
            'attachment_mime_type' => $mime, 'attachment_size' => $file?->getSize(), 'is_from_tenant' => false, 'status' => 'SENT',
        ]);
        $supportConversation->update(['is_open' => true]);
        $supportConversation->loadMissing('tenant');
        app(TenantAppNotificationService::class)->supportReply($supportConversation->tenant, $supportConversation->topic ?: 'Support', $message->body ?: 'Sent an attachment', $supportConversation->id);
        return response()->json(['ok' => true], 201);
    }

    public function toggle(Request $request, SupportConversation $supportConversation)
    {
        $this->authorizeConversation($request->user(), $supportConversation);
        $data = $request->validate(['is_open' => 'required|boolean']);
        $supportConversation->update(['is_open' => $data['is_open']]);
        return response()->json(['ok' => true, 'is_open' => $supportConversation->is_open]);
    }

    private function scoped(?User $user)
    {
        return SupportConversation::query()->when($user?->role?->name === 'LANDLORD', fn ($q) => $q->whereHas('tenant.tenant.unit.property', fn ($p) => $p->where('landlord_id', $user->id)));
    }

    private function authorizeConversation(?User $user, SupportConversation $conversation): void
    {
        abort_if($user?->role?->name === 'CARETAKER', 403);
        if ($user?->role?->name === 'LANDLORD') {
            $conversation->loadMissing('tenant.tenant.unit.property');
            abort_if($conversation->tenant?->tenant?->unit?->property?->landlord_id !== $user->id, 403);
        }
    }
}
