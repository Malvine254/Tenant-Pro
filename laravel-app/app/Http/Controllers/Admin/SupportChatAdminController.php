<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\TenantAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SupportChatAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_if($request->user()?->role?->name === 'CARETAKER', 403);
        $query = $this->scoped($request->user())
            ->with(['tenant.role', 'property', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->when($request->search, fn ($q, $search) => $q->where(function ($inner) use ($search) {
                $inner->whereHas('tenant', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('property', fn ($property) => $property->where('name', 'like', "%{$search}%"));
            }))
            ->latest();
        $conversations = $query->paginate(20)->withQueryString();
        $id = $request->conversation_id ?: $conversations->first()?->id;
        $selectedConversation = $id ? $this->scoped($request->user())
            ->with(['tenant.role', 'property', 'messages.sender.role'])->findOrFail($id) : null;
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
        app(TenantAppNotificationService::class)->supportReply(
            $supportConversation->tenant,
            $request->user()->name ?: 'Property manager',
            $supportConversation->topic ?: 'Support',
            $message->body ?: 'Sent an attachment',
            $supportConversation->id,
            $supportConversation->property_id,
            $message->id
        );
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true], 201);
        }

        // Keep chat usable if JavaScript is unavailable or a stale cached page is served.
        return redirect()
            ->route('admin.chats.index', ['conversation_id' => $supportConversation->id])
            ->with('success', 'Reply sent.');
    }

    public function toggle(Request $request, SupportConversation $supportConversation)
    {
        $this->authorizeConversation($request->user(), $supportConversation);
        $data = $request->validate(['is_open' => 'required|boolean']);
        $supportConversation->update(['is_open' => $data['is_open']]);
        return response()->json(['ok' => true, 'is_open' => $supportConversation->is_open]);
    }

    public function state(Request $request, SupportConversation $supportConversation)
    {
        $this->authorizeConversation($request->user(), $supportConversation);
        Cache::put('chat:admin:online', now()->timestamp, now()->addSeconds(45));
        $tenantId = $supportConversation->tenant_user_id;
        return response()->json([
            'online' => Cache::has('chat:online:'.$tenantId),
            'typing' => Cache::has('chat:typing:'.$tenantId),
        ]);
    }

    public function typing(Request $request, SupportConversation $supportConversation)
    {
        $this->authorizeConversation($request->user(), $supportConversation);
        $data = $request->validate(['typing' => 'required|boolean']);
        $key = 'chat:admin:typing';
        $data['typing'] ? Cache::put($key, true, now()->addSeconds(4)) : Cache::forget($key);
        Cache::put('chat:admin:online', now()->timestamp, now()->addSeconds(45));
        return response()->json(['ok' => true]);
    }

    private function scoped(?User $user)
    {
        return SupportConversation::query()->when(
            $user?->role?->name === 'LANDLORD',
            fn ($q) => $q->where('landlord_user_id', $user->landlordAccountId())
        );
    }

    private function authorizeConversation(?User $user, SupportConversation $conversation): void
    {
        abort_if($user?->role?->name === 'CARETAKER', 403);
        if ($user?->role?->name === 'LANDLORD') {
            abort_if($conversation->landlord_user_id !== $user->landlordAccountId(), 403);
        }
    }
}
