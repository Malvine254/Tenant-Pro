@extends('admin.layout')
@section('page-title', 'Tenant Chat')

@section('content')
@php
    $selectedTenant = $selectedConversation?->tenant;
    $tenancy = $selectedTenant?->tenant;
    $unit = $tenancy?->unit;
    $property = $unit?->property;
    $latestMessage = fn($conversation) => $conversation->messages->first();
    $initials = function ($name) {
        $parts = collect(preg_split('/\s+/', trim((string) $name)))->filter()->values();
        return $parts->take(2)->map(fn($part) => strtoupper(substr($part, 0, 1)))->join('') ?: 'U';
    };
    $mediaUrl = fn($uri) => str_starts_with((string) $uri, 'http') ? $uri : asset(ltrim((string) $uri, '/'));
@endphp

<div class="support-page" data-live-url="{{ request()->fullUrl() }}">
    <header class="support-toolbar">
        <div class="support-title">
            <span class="support-title-icon">💬</span>
            <div><h2>Tenant conversations</h2><p>Live support inbox</p></div>
            <span class="live-indicator"><i></i> Live</span>
        </div>
        <form method="GET" class="support-filter">
            <input type="hidden" name="conversation_id" value="{{ $selectedConversation?->id }}">
            <label class="support-search"><span>⌕</span><input name="search" value="{{ request('search') }}" placeholder="Search conversations"></label>
            <select name="status" aria-label="Conversation status" onchange="this.form.submit()">
                <option value="">All chats</option>
                <option value="open" @selected(request('status') === 'open')>Open</option>
                <option value="closed" @selected(request('status') === 'closed')>Closed</option>
            </select>
            <button class="support-filter-button" type="submit">Search</button>
        </form>
    </header>

    <div class="support-workspace">
        <aside class="conversation-panel">
            <div class="panel-heading"><strong>Messages</strong><span>{{ $conversations->total() }}</span></div>
            <div class="conversation-list" id="conversationList">
                @forelse($conversations as $conversation)
                    @php
                        $message = $latestMessage($conversation);
                        $tenant = $conversation->tenant;
                        $conversationUnit = $tenant?->tenant?->unit;
                        $conversationProperty = $conversationUnit?->property;
                        $unread = $conversation->messages()->where('is_from_tenant', true)->where('status', 'SENT')->count();
                    @endphp
                    <a class="conversation-item {{ $selectedConversation?->id === $conversation->id ? 'active' : '' }}" href="{{ route('admin.support.index', array_filter(['conversation_id' => $conversation->id, 'status' => request('status'), 'search' => request('search')])) }}">
                        <span class="conversation-avatar">{{ $initials($tenant?->name) }}</span>
                        <span class="conversation-copy">
                            <span class="conversation-top"><strong>{{ $tenant?->name ?? 'Unknown tenant' }}</strong><time>{{ $message?->created_at?->diffForHumans(null, true) ?? '' }}</time></span>
                            <span class="conversation-location">{{ $conversationProperty?->name ?? 'No property' }} · Unit {{ $conversationUnit?->unit_number ?? '-' }}</span>
                            <span class="conversation-preview">{{ $message?->body ?? 'No messages yet' }}</span>
                        </span>
                        @if($unread > 0)<b class="unread-count">{{ $unread }}</b>@endif
                    </a>
                @empty
                    <div class="support-empty"><span>💬</span><strong>No conversations</strong><p>Tenant messages will appear here.</p></div>
                @endforelse
            </div>
            @if($conversations->hasPages())<div class="conversation-pagination">{{ $conversations->onEachSide(0)->links() }}</div>@endif
        </aside>

        <main class="chat-panel" id="chatPanel">
            @if($selectedConversation)
                <header class="chat-header">
                    <div class="chat-person">
                        <span class="conversation-avatar large">{{ $initials($selectedTenant?->name) }}</span>
                        <div><h3>{{ $selectedTenant?->name ?? 'Unknown tenant' }}</h3><p>{{ $property?->name ?? 'No property' }} · Unit {{ $unit?->unit_number ?? '-' }}</p></div>
                    </div>
                    <div class="chat-header-actions">
                        <details class="tenant-details">
                            <summary>Tenant details</summary>
                            <div><strong>{{ $selectedTenant?->email ?? 'No email' }}</strong><span>{{ $selectedTenant?->phone_number ?? 'No phone' }}</span><span>{{ $property?->address_line ?? '' }} {{ $property?->city ?? '' }}</span></div>
                        </details>
                        <form method="POST" action="{{ route('admin.support.toggle', $selectedConversation) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="is_open" value="{{ $selectedConversation->is_open ? 0 : 1 }}">
                            <button class="status-button {{ $selectedConversation->is_open ? 'open' : '' }}" type="submit">{{ $selectedConversation->is_open ? '● Open' : 'Reopen' }}</button>
                        </form>
                    </div>
                </header>

                <div class="message-stream" id="messageStream">
                    <div class="topic-divider"><span>{{ $selectedConversation->subject ?: $selectedConversation->topic }}</span></div>
                    @forelse($selectedConversation->messages->sortBy('created_at') as $message)
                        @php
                            $sender = $message->sender;
                            $senderName = $sender?->name ?? ($message->is_from_tenant ? 'Tenant' : 'Property Manager');
                            $avatarUrl = $sender?->profile_image_url ? $mediaUrl($sender->profile_image_url) : null;
                            $attachmentUrl = $message->attachment_uri ? $mediaUrl($message->attachment_uri) : null;
                            $attachmentExt = strtolower(pathinfo(parse_url((string) $message->attachment_uri, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
                        @endphp
                        <div class="chat-row {{ $message->is_from_tenant ? 'tenant' : 'admin' }}" data-message-id="{{ $message->id }}">
                            @if($message->is_from_tenant)<span class="message-avatar">@if($avatarUrl)<img src="{{ $avatarUrl }}" alt="">@else{{ $initials($senderName) }}@endif</span>@endif
                            <div class="chat-bubble">
                                @if(trim((string) $message->body) !== '' && $message->body !== 'Attachment shared')<div class="chat-body">{{ $message->body }}</div>@endif
                                @if($attachmentUrl)
                                    @if(in_array($attachmentExt, ['jpg','jpeg','png','gif','webp']))<a href="{{ $attachmentUrl }}" target="_blank"><img src="{{ $attachmentUrl }}" alt="{{ $message->attachment_name ?: 'Attachment' }}" class="chat-image"></a>
                                    @else<a href="{{ $attachmentUrl }}" target="_blank" class="chat-file">📎 {{ $message->attachment_name ?: 'Open attachment' }}</a>@endif
                                @endif
                                <div class="chat-meta">{{ $message->created_at?->format('H:i') }} @if(!$message->is_from_tenant) · ✓✓ @endif</div>
                            </div>
                            @if(!$message->is_from_tenant)<span class="message-avatar admin-avatar">@if($avatarUrl)<img src="{{ $avatarUrl }}" alt="">@else{{ $initials($senderName) }}@endif</span>@endif
                        </div>
                    @empty
                        <div class="support-empty"><strong>No messages yet</strong></div>
                    @endforelse
                </div>

                <form class="reply-box" method="POST" action="{{ route('admin.support.reply', $selectedConversation) }}" id="replyForm">
                    @csrf
                    <textarea name="body" rows="1" required maxlength="5000" placeholder="Write a message…" aria-label="Reply to tenant">{{ old('body') }}</textarea>
                    <span class="reply-hint">Enter to send · Shift + Enter for a new line</span>
                    <button type="submit" aria-label="Send reply">➤</button>
                </form>
            @else
                <div class="support-empty full"><span>✉</span><strong>Select a conversation</strong><p>Choose a tenant to view and reply to their messages.</p></div>
            @endif
        </main>
    </div>
</div>

<style>
body.support-chat-active{overflow:hidden}.support-chat-active .content{overflow:hidden;padding:16px}.support-page{height:calc(100dvh - 85px);min-height:0;display:flex;flex-direction:column;gap:12px;color:#172033}.support-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex:0 0 auto}.support-title{display:flex;align-items:center;gap:10px}.support-title-icon{display:grid;place-items:center;width:38px;height:38px;border-radius:12px;background:#e9efff}.support-title h2{font-size:19px;line-height:1.1}.support-title p{font-size:12px;color:#7b879c;margin-top:3px}.live-indicator{font-size:11px;font-weight:800;color:#16845b;background:#e9f9f1;border-radius:99px;padding:5px 8px}.live-indicator i{display:inline-block;width:6px;height:6px;border-radius:50%;background:#20b97b;margin-right:4px;box-shadow:0 0 0 4px rgba(32,185,123,.12)}.support-filter{display:flex;gap:8px}.support-search{height:38px;min-width:250px;display:flex;align-items:center;gap:7px;padding:0 11px;border:1px solid #dce2eb;border-radius:10px;background:#fff;color:#8792a6}.support-search input{border:0;outline:0;width:100%;font-size:13px;background:transparent}.support-filter select,.support-filter-button{height:38px;border:1px solid #dce2eb;border-radius:10px;background:#fff;padding:0 11px;color:#455168}.support-filter-button{font-weight:700;cursor:pointer}.support-workspace{min-height:0;flex:1;display:grid;grid-template-columns:minmax(280px,340px) minmax(0,1fr);overflow:hidden;border:1px solid #dfe5ed;border-radius:16px;background:#fff;box-shadow:0 15px 35px rgba(26,39,68,.08)}.conversation-panel{min-height:0;display:flex;flex-direction:column;border-right:1px solid #e7ebf1;background:#fbfcfe}.panel-heading{height:52px;flex:0 0 52px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;border-bottom:1px solid #e7ebf1}.panel-heading strong{font-size:14px}.panel-heading span{font-size:11px;font-weight:800;padding:3px 7px;border-radius:99px;background:#edf1f7;color:#6d788a}.conversation-list{min-height:0;overflow-y:auto}.conversation-item{position:relative;display:flex;gap:10px;padding:13px 14px;text-decoration:none!important;color:inherit!important;border-bottom:1px solid #edf0f4;transition:.15s}.conversation-item:hover{background:#f4f7fb}.conversation-item.active{background:#eef3ff;box-shadow:inset 3px 0 #4263eb}.conversation-avatar,.message-avatar{display:grid;place-items:center;flex:0 0 40px;width:40px;height:40px;border-radius:50%;background:linear-gradient(145deg,#dfe7ff,#cbd7ff);color:#344cb2;font-size:12px;font-weight:900}.conversation-avatar.large{width:42px;height:42px;flex-basis:42px}.conversation-copy{min-width:0;flex:1}.conversation-top{display:flex;justify-content:space-between;gap:8px}.conversation-top strong{font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.conversation-top time{font-size:10px;color:#9aa3b2;white-space:nowrap}.conversation-location,.conversation-preview{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.conversation-location{font-size:10px;color:#8190a5;margin:3px 0}.conversation-preview{font-size:12px;color:#566277}.unread-count{align-self:center;display:grid;place-items:center;min-width:19px;height:19px;padding:0 5px;border-radius:99px;background:#4263eb;color:#fff;font-size:10px}.conversation-pagination{flex:0 0 auto;padding:6px 10px;border-top:1px solid #e7ebf1;overflow:hidden}.conversation-pagination .pagination{margin:0}.chat-panel{min-width:0;min-height:0;display:flex;flex-direction:column;background:#f7f9fc}.chat-header{height:64px;flex:0 0 64px;display:flex;justify-content:space-between;align-items:center;padding:0 18px;background:#fff;border-bottom:1px solid #e7ebf1}.chat-person{display:flex;align-items:center;gap:10px;min-width:0}.chat-person h3{font-size:14px}.chat-person p{font-size:11px;color:#7c8798;margin-top:3px}.chat-header-actions{display:flex;gap:8px;align-items:center}.status-button,.tenant-details summary{height:34px;display:flex;align-items:center;border:1px solid #dce2eb;border-radius:9px;background:#fff;padding:0 10px;font-size:11px;font-weight:800;color:#5e6878;cursor:pointer}.status-button.open{color:#16845b;background:#edfaf4;border-color:#cbefdf}.tenant-details{position:relative}.tenant-details[open] summary{background:#f5f7fa}.tenant-details>div{position:absolute;z-index:5;right:0;top:40px;width:240px;display:flex;flex-direction:column;gap:5px;padding:12px;border:1px solid #dce2eb;border-radius:10px;background:#fff;box-shadow:0 12px 30px rgba(26,39,68,.16);font-size:11px;color:#6d788a}.tenant-details>div strong{color:#253047}.message-stream{min-height:0;flex:1;overflow-y:auto;padding:18px clamp(14px,3vw,40px);scroll-behavior:smooth}.topic-divider{display:flex;align-items:center;justify-content:center;margin:0 0 18px}.topic-divider span{padding:5px 10px;border-radius:99px;background:#e9edf3;color:#748096;font-size:10px;font-weight:800}.chat-row{display:flex;align-items:flex-end;gap:7px;margin:8px 0}.chat-row.tenant{justify-content:flex-start}.chat-row.admin{justify-content:flex-end}.message-avatar{width:29px;height:29px;flex-basis:29px;font-size:9px;overflow:hidden}.message-avatar img{width:100%;height:100%;object-fit:cover}.admin-avatar{background:#d9e1ff}.chat-bubble{max-width:min(72%,620px);padding:9px 11px;border-radius:14px 14px 14px 4px;background:#fff;border:1px solid #e1e6ed;box-shadow:0 3px 9px rgba(34,48,76,.04)}.chat-row.admin .chat-bubble{border:0;border-radius:14px 14px 4px 14px;background:#4263eb;color:#fff;box-shadow:0 5px 14px rgba(66,99,235,.18)}.chat-body{white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px;line-height:1.45}.chat-meta{text-align:right;margin-top:4px;font-size:9px;opacity:.6}.chat-image{display:block;max-width:360px;width:100%;max-height:260px;object-fit:cover;border-radius:9px;margin-top:7px}.chat-file{display:inline-flex;margin-top:7px;padding:7px 9px;border-radius:8px;background:rgba(255,255,255,.86);font-size:11px;font-weight:800;text-decoration:none}.reply-box{position:relative;flex:0 0 auto;display:grid;grid-template-columns:1fr auto;gap:6px 10px;align-items:center;padding:10px 14px;background:#fff;border-top:1px solid #e7ebf1}.reply-box textarea{resize:none;max-height:100px;min-height:40px;padding:10px 12px;border:1px solid #dce2eb;border-radius:12px;background:#f8fafc;outline:0;font:13px inherit}.reply-box textarea:focus{border-color:#7890ef;box-shadow:0 0 0 3px rgba(66,99,235,.1)}.reply-box button{grid-row:1/3;grid-column:2;width:40px;height:40px;border:0;border-radius:12px;background:#4263eb;color:#fff;font-size:17px;cursor:pointer}.reply-hint{font-size:9px;color:#929cad;padding-left:4px}.support-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:30px;text-align:center;color:#8b96a8}.support-empty span{font-size:28px}.support-empty strong{font-size:13px;color:#5d697c}.support-empty p{font-size:11px}.support-empty.full{height:100%}
@media(max-width:900px){.support-chat-active .content{padding:10px}.support-toolbar{gap:8px}.support-title p,.live-indicator,.support-filter-button{display:none}.support-search{min-width:0}.support-workspace{grid-template-columns:285px minmax(0,1fr)}}
@media(max-width:680px){.support-page{gap:8px}.support-toolbar{align-items:stretch;flex-direction:column}.support-filter{display:grid;grid-template-columns:1fr 100px}.support-workspace{grid-template-columns:1fr;grid-template-rows:minmax(120px,34%) minmax(0,66%)}.conversation-panel{border-right:0;border-bottom:1px solid #e7ebf1}.panel-heading{display:none}.conversation-pagination{display:none}.chat-header{height:54px;flex-basis:54px;padding:0 10px}.conversation-avatar.large{width:34px;height:34px;flex-basis:34px}.tenant-details{display:none}.chat-bubble{max-width:84%}.message-stream{padding:10px}.reply-hint{display:none}.reply-box{grid-template-columns:1fr auto}.reply-box button{grid-row:1}.conversation-item{padding:9px 11px}}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('support-chat-active');
    const page = document.querySelector('.support-page');
    const stream = document.getElementById('messageStream');
    const reply = document.querySelector('#replyForm textarea');
    const scrollToLatest = () => { if (stream) stream.scrollTop = stream.scrollHeight; };
    scrollToLatest();

    if (reply) {
        const resize = () => { reply.style.height = 'auto'; reply.style.height = `${Math.min(reply.scrollHeight, 100)}px`; };
        reply.addEventListener('input', resize);
        reply.addEventListener('keydown', event => {
            if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); if (reply.value.trim()) reply.form.requestSubmit(); }
        });
        resize();
    }

    const replyForm = document.getElementById('replyForm');
    if (replyForm) replyForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!reply.value.trim() || replyForm.dataset.sending === '1') return;
        replyForm.dataset.sending = '1';
        const button = replyForm.querySelector('button');
        button.disabled = true;
        try {
            const response = await fetch(replyForm.action, { method: 'POST', body: new FormData(replyForm), headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Unable to send reply');
            reply.value = '';
            reply.dispatchEvent(new Event('input'));
            await refresh();
        } catch (_) {
            window.alert('Your reply could not be sent. Please check your connection and try again.');
        } finally {
            delete replyForm.dataset.sending;
            button.disabled = false;
            reply.focus();
        }
    });

    let refreshing = false;
    const refresh = async () => {
        if (refreshing || document.hidden || !page) return;
        refreshing = true;
        try {
            const response = await fetch(page.dataset.liveUrl, { headers: { 'X-Support-Live': '1' }, cache: 'no-store' });
            if (!response.ok) return;
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextList = doc.getElementById('conversationList');
            const nextStream = doc.getElementById('messageStream');
            const list = document.getElementById('conversationList');
            const currentStream = document.getElementById('messageStream');
            if (list && nextList && list.innerHTML !== nextList.innerHTML) list.innerHTML = nextList.innerHTML;
            if (currentStream && nextStream && currentStream.innerHTML !== nextStream.innerHTML) {
                const wasNearBottom = currentStream.scrollHeight - currentStream.scrollTop - currentStream.clientHeight < 100;
                currentStream.innerHTML = nextStream.innerHTML;
                if (wasNearBottom) currentStream.scrollTop = currentStream.scrollHeight;
            }
        } catch (_) { /* Keep the current conversation available while temporarily offline. */ }
        finally { refreshing = false; }
    };
    window.setInterval(refresh, 2500);
});
</script>
@endsection
