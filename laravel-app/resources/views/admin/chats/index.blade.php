@extends('admin.layout')
@section('page-title', 'Chats')
@section('content')
@php
$initials=fn($name)=>collect(explode(' ',trim((string)$name)))->filter()->take(2)->map(fn($p)=>strtoupper(substr($p,0,1)))->implode('')?:'U';
$media=fn($url)=>str_starts_with((string)$url,'http')?$url:asset(ltrim((string)$url,'/'));
$chatDateLabel=function($date) {
    if (!$date) return 'Earlier';
    $date = \Illuminate\Support\Carbon::parse($date);
    if ($date->isToday()) return 'Today';
    if ($date->isYesterday()) return 'Yesterday';
    return $date->format('j M Y');
};
$chatTime=fn($date)=>$date ? \Illuminate\Support\Carbon::parse($date)->format('H:i') : '';
$conversationGroups=$conversations->getCollection()->groupBy(fn($conversation) => $chatDateLabel($conversation->messages->first()?->created_at ?? $conversation->updated_at));
$messageGroups=$selectedConversation?->messages->sortBy('created_at')->groupBy(fn($message) => $message->created_at?->toDateString() ?? 'earlier') ?? collect();
@endphp
<style>
.chat-shell {
    height: calc(100dvh - 120px);
    min-height: 560px;
    display: grid;
    grid-template-columns: 330px 1fr;
    background: linear-gradient(180deg, #0f172a, #0b1220);
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 18px 42px rgba(2, 6, 23, .32);
}

.chat-sidebar {
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: rgba(15, 23, 42, .92);
    border-right: 1px solid rgba(148, 163, 184, .2);
}

.chat-search {
    padding: 14px;
    border-bottom: 1px solid rgba(148, 163, 184, .2);
}

.chat-search input,
.composer textarea {
    width: 100%;
    padding: 11px 13px;
    border: 1px solid rgba(255, 255, 255, .65);
    border-radius: 12px;
    background: transparent;
    color: #f8fafc;
    outline: none;
}

.chat-search input::placeholder,
.composer textarea::placeholder {
    color: rgba(248, 250, 252, .7);
}

.chat-search input:focus,
.composer textarea:focus {
    border-color: #fff;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, .15);
}

.chat-list {
    overflow: auto;
}

.conversation {
    display: flex;
    gap: 11px;
    padding: 13px 14px;
    color: #e2e8f0;
    text-decoration: none;
    border-bottom: 1px solid rgba(148, 163, 184, .14);
}

.conversation:hover,
.conversation.active {
    background: rgba(96, 165, 250, .16);
}

.avatar {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 50%;
    overflow: hidden;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #60a5fa, #2563eb);
    color: #fff;
    font-size: 12px;
    font-weight: 900;
}

.avatar img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    border-radius: 50%;
}

.conversation-copy {
    min-width: 0;
    flex: 1;
}

.conversation-copy strong,
.conversation-copy span {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-copy strong {
    font-size: 13px;
    color: #f8fafc;
}

.conversation-copy .conversation-title {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 8px;
}

.conversation-time {
    flex: 0 0 auto;
    font-size: 10px;
    color: #93c5fd;
}

.conversation-group-label,
.date-divider {
    color: #cbd5e1;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.conversation-group-label {
    padding: 14px 14px 6px;
    background: rgba(15, 23, 42, .96);
    border-bottom: 1px solid rgba(148, 163, 184, .14);
}

.conversation-copy span {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 3px;
}

.chat-main {
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: rgba(2, 6, 23, .55);
}

.chat-head {
    height: 68px;
    flex: 0 0 68px;
    padding: 0 18px;
    display: flex;
    align-items: center;
    gap: 11px;
    background: rgba(15, 23, 42, .92);
    border-bottom: 1px solid rgba(148, 163, 184, .2);
}

.chat-head h3 {
    font-size: 15px;
    color: #f8fafc;
}

.chat-head p {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 3px;
}

.stream {
    flex: 1;
    overflow: auto;
    padding: 20px;
}

.row {
    display: flex;
    gap: 7px;
    align-items: flex-end;
    margin: 9px 0;
}

.row.mine {
    justify-content: flex-end;
}

.date-divider {
    width: fit-content;
    margin: 18px auto 12px;
    padding: 5px 9px;
    border: 1px solid rgba(148, 163, 184, .3);
    border-radius: 999px;
    background: rgba(15, 23, 42, .9);
    text-transform: none;
    letter-spacing: 0;
}

.row .avatar {
    width: 29px;
    height: 29px;
    flex-basis: 29px;
    font-size: 9px;
}

.bubble {
    max-width: 70%;
    padding: 10px 12px;
    background: rgba(15, 23, 42, .82);
    border: 1px solid rgba(148, 163, 184, .26);
    border-radius: 15px 15px 15px 4px;
    font-size: 13px;
    overflow-wrap: anywhere;
    color: #e2e8f0;
}

.mine .bubble {
    background: linear-gradient(180deg, #2563eb, #1d4ed8);
    border: 0;
    color: #eff6ff;
    border-radius: 15px 15px 4px 15px;
}

.bubble .meta {
    margin-top: 5px;
    font-size: 10px;
    color: #94a3b8;
}

.mine .bubble .meta {
    color: #dbeafe;
}

.bubble img {
    display: block;
    max-width: min(280px, 62vw);
    width: auto;
    height: auto;
    max-height: 260px;
    object-fit: cover;
    border-radius: 11px;
    margin-top: 8px;
    border: 1px solid rgba(148, 163, 184, .26);
}

.bubble .file {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    color: #bfdbfe;
    text-decoration: none;
    font-weight: 700;
}

.attachment-card {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 210px;
    margin-top: 8px;
    padding: 9px;
    border: 1px solid rgba(191, 219, 254, .38);
    border-radius: 10px;
    color: inherit;
    text-decoration: none;
}

.attachment-icon {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    flex: 0 0 32px;
    border-radius: 7px;
    background: rgba(255, 255, 255, .14);
    font-size: 15px;
}

.attachment-copy {
    min-width: 0;
}

.attachment-copy strong,
.attachment-copy span {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.attachment-copy strong { font-size: 12px; }
.attachment-copy span { margin-top: 2px; font-size: 10px; opacity: .8; }
.attachment-audio { display: block; width: min(260px, 58vw); margin-top: 8px; }

.composer {
    padding: 11px;
    border-top: 1px solid rgba(148, 163, 184, .2);
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px 42px;
    grid-template-rows: auto auto;
    gap: 7px 9px;
    align-items: center;
    background: rgba(15, 23, 42, .92);
}

.composer textarea {
    grid-column: 1;
    grid-row: 1;
    min-width: 0;
    box-sizing: border-box;
    resize: none;
    max-height: 120px;
    font-size: 13px;
    line-height: 1.35;
}

.composer .icon-btn {
    position: static !important;
    inset: auto !important;
    transform: none !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 42px !important;
    height: 42px !important;
    min-width: 42px !important;
    min-height: 42px !important;
    display: grid !important;
    place-items: center;
    align-self: center;
    line-height: 1;
    border: 1px solid rgba(255, 255, 255, .65);
    border-radius: 12px;
    font-size: 22px;
    cursor: pointer;
    background: transparent;
    color: #f8fafc;
}

.composer .icon-btn:hover {
    border-color: #fff;
    background: rgba(255, 255, 255, .08);
}

.composer .attach {
    grid-column: 2 !important;
    grid-row: 1 !important;
}

.composer .send {
    grid-column: 3 !important;
    grid-row: 1 !important;
}

.composer .selected-file {
    grid-column: 1 / 4;
    grid-row: 2;
    padding-left: 3px;
    font-size: 11px;
    color: #cbd5e1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.empty {
    margin: auto;
    color: #94a3b8;
    text-align: center;
}

.empty strong {
    display: block;
    margin-bottom: 5px;
    color: #f8fafc;
}

body.admin-chat-page {
    overflow: hidden;
}

.admin-chat-page .content {
    overflow: hidden;
    padding: 14px;
}

.chat-shell {
    height: calc(100dvh - 92px);
    min-height: 0;
    grid-template-columns: 300px minmax(0, 1fr);
    border-radius: 16px;
}

.chat-main {
    min-height: 0;
}

.stream {
    min-height: 0;
}

.presence {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 3px;
    font-size: 10px;
    color: #94a3b8;
}

.presence-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #94a3b8;
}

.presence.online {
    color: #34d399;
}

.presence.online .presence-dot {
    background: #34d399;
    box-shadow: 0 0 0 3px rgba(52, 211, 153, .2);
}

.typing {
    height: 14px;
    color: #93c5fd;
    font-size: 10px;
    font-weight: 700;
    visibility: hidden;
}

.typing.show {
    visibility: visible;
}

.conversation-typing {
    display: none !important;
    align-items: center;
    gap: 5px;
    color: #93c5fd !important;
    font-weight: 800;
}

.conversation-typing.show {
    display: flex !important;
}

.typing-dots {
    display: inline-flex;
    gap: 2px;
}

.typing-dots i {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #93c5fd;
    animation: typingBounce 1s infinite ease-in-out;
}

.typing-dots i:nth-child(2) {
    animation-delay: .15s;
}

.typing-dots i:nth-child(3) {
    animation-delay: .3s;
}

@keyframes typingBounce {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: .45;
    }

    30% {
        transform: translateY(-3px);
        opacity: 1;
    }
}

@media (max-width: 760px) {
    .composer {
        grid-template-columns: minmax(0, 1fr) 40px 40px;
        padding: 9px;
        gap: 6px;
    }

    .composer .icon-btn {
        width: 40px !important;
        height: 40px !important;
        min-width: 40px !important;
        min-height: 40px !important;
    }
}

@media (max-width: 700px) {
    .chat-shell {
        grid-template-columns: 105px minmax(0, 1fr);
        height: calc(100dvh - 84px);
    }
}
</style>
<div class="chat-shell" id="chatShell">
 <aside class="chat-sidebar"><form class="chat-search" method="GET"><input name="search" value="{{ request('search') }}" placeholder="Search chats"></form><div class="chat-list">
 @forelse($conversationGroups as $groupLabel => $group)<div class="conversation-group-label">{{ $groupLabel }}</div>@foreach($group as $c) @php $u=$c->tenant;$property=$c->property;$last=$c->messages->first(); @endphp
 <a class="conversation {{ $selectedConversation?->id===$c->id?'active':'' }}" data-conversation-id="{{ $c->id }}" href="{{ route('admin.chats.index',['conversation_id'=>$c->id]) }}"><span class="avatar">@if($u?->profile_image_url)<img src="{{ $media($u->profile_image_url) }}" alt="">@else{{ $initials($u?->name) }}@endif</span><span class="conversation-copy"><span class="conversation-title"><strong>{{ $u?->name??'Unknown tenant' }}</strong><time class="conversation-time">{{ $chatTime($last?->created_at ?? $c->updated_at) }}</time></span><span>{{ $property?->name??'No property' }}</span><span class="conversation-preview">{{ $last?->body?:($last?->attachment_name ? 'Attachment: '.$last->attachment_name : 'No messages') }}</span><span class="conversation-typing"><span class="typing-dots"><i></i><i></i><i></i></span> typing…</span></span></a>
 @endforeach
 @empty <div class="empty" style="padding:30px">No chats yet.</div> @endforelse
 </div></aside>
 <main class="chat-main">@if($selectedConversation) @php $u=$selectedConversation->tenant;$property=$selectedConversation->property; @endphp
 <header class="chat-head"><span class="avatar">@if($u?->profile_image_url)<img src="{{ $media($u->profile_image_url) }}" alt="">@else{{ $initials($u?->name) }}@endif</span><div><h3>{{ $u?->name??'Unknown tenant' }}</h3><p>{{ ucfirst(strtolower($u?->role?->name??'Tenant')) }} · {{ $property?->name??'No property' }}</p></div></header>
 <div class="stream" id="stream">@foreach($messageGroups as $date => $messages)<div class="date-divider">{{ $chatDateLabel($messages->first()?->created_at) }}</div>@foreach($messages as $m) @php $sender=$m->sender;$url=$m->attachment_uri?$media($m->attachment_uri):null; @endphp
 @php
   $visibleBody = trim((string) $m->body) !== '' && strcasecmp(trim((string) $m->body), 'Attachment shared') !== 0;
   $extension = strtolower(pathinfo(parse_url((string) $m->attachment_uri, PHP_URL_PATH) ?: (string) $m->attachment_name, PATHINFO_EXTENSION));
   $isImage = $m->message_type === 'image' || str_starts_with((string) $m->attachment_mime_type, 'image/') || in_array($extension, ['jpg','jpeg','png','gif','webp'], true);
   $isAudio = $m->message_type === 'audio' || str_starts_with((string) $m->attachment_mime_type, 'audio/');
   $fileSize = $m->attachment_size ? number_format($m->attachment_size / 1024, $m->attachment_size >= 1024 * 1024 ? 1 : 0).' '.($m->attachment_size >= 1024 * 1024 ? 'MB' : 'KB') : null;
 @endphp
 <div class="row {{ $m->is_from_tenant?'':'mine' }}">@if($m->is_from_tenant)<span class="avatar">@if($sender?->profile_image_url)<img src="{{ $media($sender->profile_image_url) }}" alt="">@else{{ $initials($sender?->name) }}@endif</span>@endif<div class="bubble">@if($visibleBody)<div>{{ $m->body }}</div>@endif @if($url) @if($isImage)<img src="{{ $url }}" alt="{{ $m->attachment_name ?: 'Shared image' }}" loading="lazy">@elseif($isAudio)<audio class="attachment-audio" controls preload="metadata"><source src="{{ $url }}" type="{{ $m->attachment_mime_type ?: 'audio/mpeg' }}">Audio playback is unavailable. <a class="file" href="{{ $url }}" target="_blank" rel="noopener">Download audio</a></audio>@else<a class="attachment-card" href="{{ $url }}" target="_blank" rel="noopener"><span class="attachment-icon">📎</span><span class="attachment-copy"><strong>{{ $m->attachment_name ?: 'Attachment' }}</strong><span>{{ collect([$m->attachment_mime_type, $fileSize])->filter()->implode(' · ') ?: 'Open attachment' }}</span></span></a>@endif @endif<div class="meta"><time datetime="{{ $m->created_at?->toIso8601String() }}">{{ $chatTime($m->created_at) }}</time> · {{ $m->status }}</div></div></div>
 @endforeach @endforeach</div>
 <form class="composer" id="composer" action="{{ route('admin.chats.reply',$selectedConversation) }}" method="POST" enctype="multipart/form-data">@csrf<input id="file" name="file" type="file" hidden accept="image/*,.pdf,.doc,.docx,.txt,audio/*"><button class="icon-btn attach" type="button" id="attach" aria-label="Attach file">+</button><textarea name="body" placeholder="Write a message…" maxlength="5000"></textarea><button class="icon-btn send" type="submit" aria-label="Send">➤</button><span class="selected-file" id="fileName" hidden></span></form>
 @else<div class="empty"><strong>Select a chat</strong><p>Choose a tenant conversation.</p></div>@endif</main>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{const stream=document.querySelector('#stream');if(stream)stream.scrollTop=stream.scrollHeight;const form=document.querySelector('#composer');if(!form)return;const input=form.querySelector('textarea'),file=document.querySelector('#file'),name=document.querySelector('#fileName'),send=form.querySelector('.send');const refreshMessages=async()=>{const response=await fetch(window.location.href,{headers:{'X-Requested-With':'XMLHttpRequest'},cache:'no-store'});if(!response.ok)throw new Error();const doc=new DOMParser().parseFromString(await response.text(),'text/html'),next=doc.querySelector('#stream');if(stream&&next){stream.innerHTML=next.innerHTML;stream.scrollTop=stream.scrollHeight}};document.querySelector('#attach').onclick=()=>file.click();file.onchange=()=>{const f=file.files[0];if(f&&f.size>20*1024*1024){file.value='';alert('File must be 20 MB or smaller.');return}name.hidden=!f;name.textContent=f?f.name:''};input.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();form.requestSubmit()}});form.addEventListener('submit',async e=>{e.preventDefault();if(!input.value.trim()&&!file.files.length)return;send.disabled=true;try{const r=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});if(!r.ok)throw new Error();input.value='';file.value='';name.hidden=true;await refreshMessages();input.focus()}catch(_){alert('Message could not be sent. Please try again.')}finally{send.disabled=false}})});
</script>
@if($selectedConversation)
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const input=document.querySelector('#composer textarea');
 if(!input)return;
 const url=@json(route('admin.chats.typing',$selectedConversation));
 const token=document.querySelector('#composer input[name="_token"]')?.value;
 let stopTimer=null,lastState=false,lastPublishedAt=0;
 const publish=async typing=>{
   const now=Date.now();
   if(lastState===typing&&(!typing||now-lastPublishedAt<1000))return;
   lastState=typing;
   lastPublishedAt=now;
   try{await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({typing})})}catch(_){}
 };
 const handleTyping=()=>{
   const active=input.value.trim().length>0;
   publish(active);
   clearTimeout(stopTimer);
   if(active)stopTimer=setTimeout(()=>publish(false),2500);
 };
 input.addEventListener('input',handleTyping);
 input.addEventListener('keyup',handleTyping);
 document.querySelector('#composer')?.addEventListener('submit',()=>publish(false));
 window.addEventListener('pagehide',()=>{if(lastState)navigator.sendBeacon(url,new Blob([JSON.stringify({typing:false,_token:token})],{type:'application/json'}))});
});
</script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 document.body.classList.add('admin-chat-page');
 const headerCopy=document.querySelector('.chat-head>div');
 if(!headerCopy)return;
 const presence=document.createElement('div');presence.className='presence';presence.innerHTML='<i class="presence-dot"></i><span>Offline</span>';
 const typing=document.createElement('div');typing.className='typing';typing.textContent=@json(($selectedConversation->tenant?->name??'Tenant').' is typing…');
 headerCopy.append(presence,typing);
 const refresh=async()=>{try{const response=await fetch(@json(route('admin.chats.state',$selectedConversation)),{headers:{Accept:'application/json'},cache:'no-store'});if(!response.ok)return;const state=await response.json();presence.classList.toggle('online',state.online);presence.querySelector('span').textContent=state.online?'Online':'Offline';typing.classList.toggle('show',state.typing);const activeRow=document.querySelector('[data-conversation-id="{{ $selectedConversation->id }}"]'),rowTyping=activeRow?.querySelector('.conversation-typing'),rowPreview=activeRow?.querySelector('.conversation-preview');rowTyping?.classList.toggle('show',state.typing);if(rowPreview)rowPreview.style.display=state.typing?'none':''}catch(_){presence.classList.remove('online');presence.querySelector('span').textContent='Offline'}};
 refresh();window.setInterval(refresh,2000);

 // Incremental live sync: update only the messages and conversation list.
 // This keeps the fixed chat workspace mounted and never reloads the page.
 let syncing=false;
 const syncChat=async()=>{
   if(syncing||document.hidden)return;
   syncing=true;
   try{
     const response=await fetch(window.location.href,{headers:{'X-Requested-With':'XMLHttpRequest'},cache:'no-store'});
     if(!response.ok)return;
     const doc=new DOMParser().parseFromString(await response.text(),'text/html');
     const currentStream=document.querySelector('#stream'),nextStream=doc.querySelector('#stream');
     if(currentStream&&nextStream&&currentStream.innerHTML!==nextStream.innerHTML){
       const nearBottom=currentStream.scrollHeight-currentStream.scrollTop-currentStream.clientHeight<100;
       currentStream.innerHTML=nextStream.innerHTML;
       if(nearBottom)currentStream.scrollTop=currentStream.scrollHeight;
     }
     const currentList=document.querySelector('.chat-list'),nextList=doc.querySelector('.chat-list');
     if(currentList&&nextList&&currentList.innerHTML!==nextList.innerHTML)currentList.innerHTML=nextList.innerHTML;
   }catch(_){/* Keep the current chat usable during temporary disconnects. */}
   finally{syncing=false}
 };
 window.setInterval(syncChat,2000);
 document.addEventListener('visibilitychange',()=>{if(!document.hidden){refresh();syncChat()}});
});
</script>
@else
<script>document.addEventListener('DOMContentLoaded',()=>document.body.classList.add('admin-chat-page'));</script>
@endif
@endsection
