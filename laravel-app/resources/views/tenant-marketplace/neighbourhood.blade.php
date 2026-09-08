@extends('tenant-marketplace.layout')
@section('title', 'Homes in '.$area->neighbourhood.', '.$area->city.' | Starmax Homes')
@section('meta_description', 'Explore available homes in '.$area->neighbourhood.', '.$area->city.'. Compare rent, read manager-supplied area notes and plan a viewing.')
@section('robots', $notes->isEmpty() ? 'noindex,follow' : 'index,follow,max-image-preview:large')
@section('content')
<section class="inner-hero"><div class="market-shell"><a class="eyebrow-link" href="{{ route('marketplace.neighbourhoods') }}">All neighbourhoods</a><span class="hero-kicker">{{ $area->city }}</span><h1>Make yourself at home in {{ $area->neighbourhood }}.</h1><p>{{ $properties->total() }} {{ str('property')->plural($properties->total()) }} with listed vacancies. Explore the homes and get to know the area before deciding.</p></div></section>
<section class="market-shell editorial-section">
    <div class="section-title"><h2>Local knowledge</h2><p>Notes supplied by property managers. Transport, businesses and services can change; check the details that matter to you.</p></div>
    <div class="area-notes">@forelse($notes as $note)<article><h3>Near {{ $note->name }}</h3><p style="white-space:pre-line;">{{ $note->area_notes }}</p><small>Manager notes · updated {{ $note->updated_at->format('j M Y') }}</small></article>@empty<p>Managers have not supplied local notes yet. Use the checklist below during your visit.</p>@endforelse</div>
    <div class="area-checklist"><h3>Try the area before you commit</h3><ul><li>Test your usual commute at the time you would travel.</li><li>Locate shops, healthcare and the transport stops you would use.</li><li>Ask about water reliability, power backup and internet providers.</li><li>Visit at different times to judge traffic, noise, lighting and access for yourself.</li></ul></div>
    <div class="section-title"><h2>Available homes</h2></div><div class="listing-grid">@foreach($properties as $property)@include('tenant-marketplace.partials.property-card')@endforeach</div>
    @if($properties->hasPages())<nav class="market-pagination" aria-label="Listing pages">@if($properties->previousPageUrl())<a href="{{ $properties->previousPageUrl() }}">Previous</a>@endif<span>Page {{ $properties->currentPage() }} of {{ $properties->lastPage() }}</span>@if($properties->nextPageUrl())<a href="{{ $properties->nextPageUrl() }}">Next</a>@endif</nav>@endif
</section>
@endsection
