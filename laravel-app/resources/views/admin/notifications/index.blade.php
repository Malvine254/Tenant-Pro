@extends('admin.layout')
@section('page-title', 'Notifications')

@section('content')
@php
    $notificationGroups = [
        'all' => $notifications->getCollection(),
        'unread' => $notifications->getCollection()->where('is_read', false),
    ];
@endphp
<style>
    .notification-header { display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:16px; }
    .notification-header h2 { font-size:22px;letter-spacing:-.035em; }
    .notification-header p { margin-top:5px;color:var(--muted);font-size:13px; }
    .notification-list { display:grid;gap:10px; }
    .notification-item { display:grid;grid-template-columns:10px minmax(0,1fr) auto;gap:13px;align-items:start;padding:15px 16px;border:1px solid var(--line);border-radius:14px;background:linear-gradient(180deg,#111827,#0b1220); }
    .notification-item.unread { border-color:rgba(96,165,250,.3);background:linear-gradient(180deg,rgba(30,58,138,.16),#0b1220); }
    .notification-dot { width:9px;height:9px;margin-top:6px;border-radius:50%;background:#475569; }
    .notification-item.unread .notification-dot { background:#60a5fa;box-shadow:0 0 0 4px rgba(96,165,250,.1); }
    .notification-copy strong { display:block;color:#f8fafc;font-size:14px; }
    .notification-copy p { color:#cbd5e1;font-size:13px;line-height:1.55;margin-top:4px; }
    .notification-copy time { display:block;color:#64748b;font-size:11px;margin-top:7px; }
    .notification-empty { padding:34px;text-align:center;color:var(--muted);border:1px dashed var(--line);border-radius:14px; }
    @media (max-width:640px) { .notification-header { flex-direction:column; }.notification-item { grid-template-columns:10px minmax(0,1fr); }.notification-item form { grid-column:2; } }
</style>

<div class="notification-header">
    <div><h2>Notifications</h2><p>Review account, payment, tenant, and operational updates.</p></div>
    @if($notificationGroups['unread']->isNotEmpty())
        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
            @csrf @method('PATCH')
            <button class="btn btn-secondary" type="submit">Mark all as read</button>
        </form>
    @endif
</div>

<div class="ui-tabs" role="tablist" aria-label="Notification filters" data-ui-tabs data-tab-param="notification_tab">
    <button id="notification-tab-all" class="ui-tab active" type="button" role="tab" aria-selected="true" aria-controls="notification-panel-all" data-ui-tab="all" data-tab-panel="notification-panel-all">All <span class="badge badge-gray">{{ $notificationGroups['all']->count() }}</span></button>
    <button id="notification-tab-unread" class="ui-tab" type="button" role="tab" aria-selected="false" aria-controls="notification-panel-unread" data-ui-tab="unread" data-tab-panel="notification-panel-unread">Unread <span class="badge badge-gray">{{ $notificationGroups['unread']->count() }}</span></button>
</div>

@foreach($notificationGroups as $group => $items)
    <section id="notification-panel-{{ $group }}" class="ui-tab-panel {{ $group === 'all' ? 'active' : '' }}" role="tabpanel" aria-labelledby="notification-tab-{{ $group }}">
        <div class="notification-list">
            @forelse($items as $notification)
                <article class="notification-item {{ $notification->is_read ? '' : 'unread' }}">
                    <span class="notification-dot" aria-hidden="true"></span>
                    <div class="notification-copy">
                        <strong>{{ $notification->title }}</strong>
                        <p>{{ $notification->body }}</p>
                        @php($downloadUrl = data_get($notification->metadata, 'download_url'))
                        @if($downloadUrl)
                            <a class="btn btn-secondary" style="min-height:32px;padding:6px 10px;font-size:12px;margin:6px 0;" href="{{ $downloadUrl }}">Download update</a>
                        @endif
                        <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->format('d M Y, H:i') }}</time>
                    </div>
                    @unless($notification->is_read)
                        <form method="POST" action="{{ route('admin.notifications.read', $notification) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-secondary" type="submit">Mark read</button>
                        </form>
                    @endunless
                </article>
            @empty
                <div class="notification-empty">No {{ $group === 'unread' ? 'unread ' : '' }}notifications to show.</div>
            @endforelse
        </div>
    </section>
@endforeach

<div class="pagination">{{ $notifications->links() }}</div>
@endsection
