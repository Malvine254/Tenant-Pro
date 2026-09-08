@extends('tenant-marketplace.layout')
@section('title', 'Explore rental neighbourhoods | Starmax Homes')
@section('meta_description', 'Explore neighbourhoods with available Starmax rental homes, compare listings and read local notes supplied by property managers.')
@section('content')
<section class="inner-hero"><div class="market-shell"><span class="hero-kicker">Find your area</span><h1>A home starts with its neighbourhood.</h1><p>Explore areas with current listings, then check your commute and everyday essentials in person.</p></div></section>
<section class="market-shell editorial-section">
    <div class="area-guide-callout">
        <strong>How these areas are grouped</strong>
        <p>Homes are grouped by the city and neighbourhood supplied by each property manager. This is an area directory, not a live map or GPS radius, so use the address and arrange a viewing to confirm how close a home is to the places that matter to you.</p>
    </div>
    <div class="area-grid">@forelse($areas as $area)
        <a class="area-card" href="{{ route('marketplace.neighbourhood', $area->neighbourhood_slug) }}"><span>{{ $area->city }}</span><h2>{{ $area->neighbourhood }}</h2><p>Homes listed in this named area <span aria-hidden="true">&rarr;</span></p></a>
    @empty
        <div class="empty-results"><h2>Local guides are on the way</h2><p>Neighbourhoods appear here when managers add an area to an available listing.</p><a class="primary-action" href="{{ route('marketplace.index') }}">Browse all homes</a></div>
    @endforelse</div>
</section>
@endsection
