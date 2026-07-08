@extends('admin.layout')
@section('page-title', 'Tenant Chat')

@section('content')
@php
    $selectedTenant = $selectedConversation?->tenant;
    $tenancy = $selectedTenant?->tenant;
    $unit = $tenancy?->unit;
    $property = $unit?->property;
    $latestMessage = fn($conversation) => $conversation->messages->first();
@endphp

<div class="admin-page-header">
    <div>
        <h2>Tenant Chat Inbox</h2>
        <p>
            View messages sent from the Android app with tenant email, apartment/property, and room/unit details.
        </p>
    </div>
    <form method="GET" class="admin-filter">
        <select name="status">
            <option value="">All chats</option>
            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
        </select>
        <input name="search" value="{{ request('search') }}" placeholder="Search tenant, email, topic...">
        <button class="btn btn-secondary" type="submit">Filter</button>
    </form>
</div>

<div class="support-grid" style="display:grid;grid-template-columns:360px 1fr;gap:16px;align-items:start;">
    <div class="card" style="padding:0;overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1px solid #e2e8f0;">
            <h3 class="section-heading" style="margin-bottom:0;">Conversations</h3>
        </div>
        <div style="max-height:650px;overflow:auto;">
            @forelse($conversations as $conversation)
                @php
                    $message = $latestMessage($conversation);
                    $tenant = $conversation->tenant;
                    $tenantTenancy = $tenant?->tenant;
                    $conversationUnit = $tenantTenancy?->unit;
                    $conversationProperty = $conversationUnit?->property;
                    $unread = $conversation->messages()
                        ->where('is_from_tenant', true)
                        ->where('status', 'SENT')
                        ->count();
                @endphp
                <a href="{{ route('admin.support.index', array_filter(['conversation_id' => $conversation->id, 'status' => request('status'), 'search' => request('search')])) }}"
                   style="display:block;text-decoration:none;color:inherit;padding:14px 16px;border-bottom:1px solid #f1f5f9;background:{{ $selectedConversation?->id === $conversation->id ? '#eff6ff' : '#fff' }};">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
                        <strong style="font-size:14px;">{{ $tenant?->name ?? 'Unknown tenant' }}</strong>
                        <span class="badge {{ $conversation->is_open ? 'badge-green' : 'badge-gray' }}">{{ $conversation->is_open ? 'Open' : 'Closed' }}</span>
                    </div>
                    <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $tenant?->email ?? 'No email' }}</div>
                    <div style="font-size:12px;color:#64748b;margin-top:3px;">
                        {{ $conversationProperty?->name ?? 'No apartment' }} / Unit {{ $conversationUnit?->unit_number ?? '-' }}
                    </div>
                    <div style="font-size:13px;color:#334155;margin-top:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $message?->body ?? 'No messages yet' }}
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                        <span style="font-size:11px;color:#94a3b8;">{{ $message?->created_at?->diffForHumans() ?? $conversation->created_at?->diffForHumans() }}</span>
                        @if($unread > 0)
                            <span class="badge badge-yellow">{{ $unread }} unread</span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="empty-state">No tenant chat messages yet. Messages sent from the Android app will appear here.</div>
            @endforelse
        </div>
        <div style="padding:0 14px 14px;">{{ $conversations->links() }}</div>
    </div>

    <div class="card">
        @if($selectedConversation)
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;border-bottom:1px solid #e2e8f0;padding-bottom:14px;margin-bottom:16px;">
                <div>
                    <h3 style="font-size:18px;font-weight:700;margin-bottom:4px;">{{ $selectedConversation->subject ?: $selectedConversation->topic }}</h3>
                    <div style="font-size:13px;color:#64748b;">Topic: {{ $selectedConversation->topic }}</div>
                </div>
                <form method="POST" action="{{ route('admin.support.toggle', $selectedConversation) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="is_open" value="{{ $selectedConversation->is_open ? 0 : 1 }}">
                    <button class="btn {{ $selectedConversation->is_open ? 'btn-danger' : 'btn-primary' }}" type="submit" onclick="return confirm('{{ $selectedConversation->is_open ? 'Close this chat?' : 'Reopen this chat?' }}')">
                        {{ $selectedConversation->is_open ? 'Close Chat' : 'Reopen Chat' }}
                    </button>
                </form>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;margin-bottom:16px;">
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:700;">Tenant</div>
                    <div style="font-weight:700;margin-top:4px;">{{ $selectedTenant?->name ?? '-' }}</div>
                    <div style="font-size:12px;color:#64748b;">{{ $selectedTenant?->email ?? 'No email' }}</div>
                    <div style="font-size:12px;color:#64748b;">{{ $selectedTenant?->phone_number ?? 'No phone' }}</div>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:700;">Apartment / Property</div>
                    <div style="font-weight:700;margin-top:4px;">{{ $property?->name ?? 'No apartment linked' }}</div>
                    <div style="font-size:12px;color:#64748b;">{{ $property?->address_line ?? '' }} {{ $property?->city ?? '' }}</div>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:700;">Room / Unit</div>
                    <div style="font-weight:700;margin-top:4px;">Unit {{ $unit?->unit_number ?? '-' }}</div>
                    <div style="font-size:12px;color:#64748b;">Floor {{ $unit?->floor ?? '-' }} · {{ $unit?->rent_amount_formatted ?? 'Rent not set' }}</div>
                </div>
            </div>

            <div style="display:grid;gap:12px;max-height:440px;overflow:auto;margin-bottom:16px;padding-right:4px;">
                @forelse($selectedConversation->messages->sortBy('created_at') as $message)
                    <div style="display:flex;justify-content:{{ $message->is_from_tenant ? 'flex-start' : 'flex-end' }};">
                        <div style="max-width:78%;background:{{ $message->is_from_tenant ? '#f1f5f9' : '#dbeafe' }};border:1px solid {{ $message->is_from_tenant ? '#e2e8f0' : '#bfdbfe' }};border-radius:14px;padding:12px;">
                            <div style="font-size:12px;color:#64748b;margin-bottom:6px;">
                                {{ $message->sender?->name ?? 'Unknown sender' }}
                                · {{ $message->created_at?->format('d M Y H:i') }}
                                · {{ $message->status }}
                            </div>
                            <div style="font-size:14px;line-height:1.5;white-space:pre-wrap;">{{ $message->body }}</div>
                            @if($message->attachment_uri)
                                <a href="{{ $message->attachment_uri }}" target="_blank" style="display:inline-block;margin-top:8px;color:#1d4ed8;font-size:13px;">
                                    View attachment{{ $message->attachment_name ? ': '.$message->attachment_name : '' }}
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state">No messages in this conversation yet.</div>
                @endforelse
            </div>

            <form method="POST" action="{{ route('admin.support.reply', $selectedConversation) }}">
                @csrf
                <div class="form-group">
                    <label>Reply to tenant</label>
                    <textarea name="body" rows="4" required placeholder="Type your reply...">{{ old('body') }}</textarea>
                    @error('body')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary" type="submit">Send Reply</button>
            </form>
        @else
            <div class="empty-state">Select a tenant chat to read messages and reply.</div>
        @endif
    </div>
</div>

<style>
@media (max-width: 980px) {
    .support-grid {
        display:block !important;
    }
    .support-grid > .card {
        margin-bottom:14px;
    }
}
</style>
@endsection
