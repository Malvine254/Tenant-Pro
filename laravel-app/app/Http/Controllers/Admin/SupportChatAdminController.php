<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Http\Request;

class SupportChatAdminController extends Controller
{
    public function index(Request $request)
    {
        abort_if($this->isCaretaker($request->user()), 403);

        $conversationsQuery = $this->scopedConversations($request->user())
            ->with([
                'tenant.role',
                'tenant.tenant.unit.property',
                'messages' => fn($messages) => $messages->latest()->limit(1),
            ])
            ->when($request->status === 'open', fn($query) => $query->where('is_open', true))
            ->when($request->status === 'closed', fn($query) => $query->where('is_open', false))
            ->when($request->search, fn($query) => $query->where(function ($search) use ($request) {
                $search->where('subject', 'like', "%{$request->search}%")
                    ->orWhere('topic', 'like', "%{$request->search}%")
                    ->orWhereHas('tenant', fn($tenant) => $tenant
                        ->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%")
                        ->orWhere('phone_number', 'like', "%{$request->search}%"));
            }))
            ->latest();

        $conversations = $conversationsQuery->paginate(15)->withQueryString();

        $selectedConversation = null;
        if ($request->conversation_id) {
            $selectedConversation = $this->scopedConversations($request->user())
                ->with([
                    'tenant.tenant.unit.property',
                    'messages.sender.role',
                ])
                ->findOrFail($request->conversation_id);
        } elseif ($conversations->first()) {
            $selectedConversation = $this->scopedConversations($request->user())
                ->with([
                    'tenant.tenant.unit.property',
                    'messages.sender.role',
                ])
                ->find($conversations->first()->id);
        }

        if ($selectedConversation) {
            $selectedConversation->messages()
                ->where('is_from_tenant', true)
                ->where('status', 'SENT')
                ->update(['status' => 'READ']);
        }

        return view('admin.support.index', compact('conversations', 'selectedConversation'));
    }

    public function reply(Request $request, SupportConversation $supportConversation)
    {
        abort_if($this->isCaretaker($request->user()), 403);
        $this->authorizeConversation($request->user(), $supportConversation);

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        SupportMessage::create([
            'conversation_id' => $supportConversation->id,
            'sender_id' => $request->user()->id,
            'topic' => $supportConversation->topic ?: 'General',
            'body' => $data['body'],
            'is_from_tenant' => false,
            'status' => 'SENT',
        ]);

        $supportConversation->update(['is_open' => true]);

        return redirect()
            ->route('admin.support.index', ['conversation_id' => $supportConversation->id])
            ->with('success', 'Reply sent.');
    }

    public function toggle(Request $request, SupportConversation $supportConversation)
    {
        abort_if($this->isCaretaker($request->user()), 403);
        $this->authorizeConversation($request->user(), $supportConversation);

        $data = $request->validate([
            'is_open' => 'required|boolean',
        ]);

        $supportConversation->update(['is_open' => (bool) $data['is_open']]);

        return redirect()
            ->route('admin.support.index', ['conversation_id' => $supportConversation->id])
            ->with('success', (bool) $data['is_open'] ? 'Chat reopened.' : 'Chat closed.');
    }

    private function scopedConversations(?User $user)
    {
        return SupportConversation::query()
            ->when(
                $user?->role?->name === 'LANDLORD',
                fn($query) => $query->whereHas(
                    'tenant.tenant.unit.property',
                    fn($property) => $property->where('landlord_id', $user->id)
                )
            );
    }

    private function authorizeConversation(?User $user, SupportConversation $conversation): void
    {
        if ($user?->role?->name !== 'LANDLORD') {
            return;
        }

        $conversation->loadMissing('tenant.tenant.unit.property');

        abort_if(
            $conversation->tenant?->tenant?->unit?->property?->landlord_id !== $user->id,
            403
        );
    }

    private function isCaretaker(?User $user): bool
    {
        return $user?->role?->name === 'CARETAKER';
    }
}
