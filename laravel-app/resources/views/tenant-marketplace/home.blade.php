@extends('tenant-marketplace.layout')

@section('title', 'Find your next home in Kenya | Starmax Homes')
@section('meta_description', 'Search current rental homes across Kenya, compare clear monthly prices, and request a viewing directly from the property manager.')

@section('content')
<section class="market-home-hero">
    <div class="market-shell home-hero-inner">
        <div class="home-hero-copy">
            <span class="hero-kicker">Rental homes, made simpler</span>
            <h1>A better way to find your <em>next home.</em></h1>
            <p>Search live vacancies, compare the monthly rent and arrange a viewing with the property manager—all in one place.</p>
            <form class="home-search" action="{{ route('marketplace.index') }}" method="GET">
                <label><span>Where do you want to live?</span><select name="location"><option value="">Any location in Kenya</option>@foreach($locations as $location)<option value="{{ $location }}">{{ $location }}</option>@endforeach</select></label>
                <label><span>Your maximum monthly rent</span><input type="number" name="max_price" min="0" step="500" placeholder="e.g. 30,000"></label>
                <label><span>Bedrooms</span><select name="bedrooms"><option value="">Any</option><option value="0">Studio</option><option value="1">1 bedroom</option><option value="2">2 bedrooms</option><option value="3">3 bedrooms</option><option value="4">4 bedrooms</option></select></label>
                <button type="submit">Search homes <span aria-hidden="true">→</span></button>
            </form>
            <div class="home-trust-row"><span>✓ Current availability</span><span>✓ Clear monthly rent</span><span>✓ Private enquiries</span></div>
        </div>
        <div class="home-visual" aria-hidden="true">
            @if($featuredProperties->first()?->cover_image_url)
                <img src="{{ $featuredProperties->first()->cover_image_url }}" alt="">
            @else
                <div class="home-visual-placeholder"><span>Find a place<br>that feels like you.</span></div>
            @endif
            <div class="floating-stat"><strong>{{ $availableHomes ?: 'New' }}</strong><span>{{ $availableHomes === 1 ? 'home' : 'homes' }} available now</span></div>
        </div>
    </div>
</section>

<section class="market-shell home-section">
    <div class="home-section-heading"><div><span class="section-kicker">Fresh on Starmax</span><h2>Homes ready to explore</h2><p>Real vacancies from properties managed on Starmax.</p></div><a href="{{ route('marketplace.index') }}">See all homes →</a></div>
    <div class="listing-grid home-listing-grid">
        @forelse($featuredProperties as $property)
            @include('tenant-marketplace.partials.property-card', ['property' => $property])
        @empty
            <div class="empty-results"><h3>New homes are on the way</h3><p>Property managers are preparing their vacancies. Check again soon.</p></div>
        @endforelse
    </div>
</section>

<section class="journey-section">
    <div class="market-shell">
        <div class="home-section-heading"><div><span class="section-kicker">Three simple steps</span><h2>From search to front door</h2></div><a href="{{ route('marketplace.how-it-works') }}">See how it works →</a></div>
        <div class="journey-grid"><article><span>01</span><h3>Find your fit</h3><p>Use location, rent and bedrooms to narrow the homes to the ones that work for you.</p></article><article><span>02</span><h3>Compare clearly</h3><p>Check current units, monthly rent and property details before you enquire.</p></article><article><span>03</span><h3>View before you pay</h3><p>Send a private request and arrange an in-person viewing with the manager.</p></article></div>
    </div>
</section>

<section class="owner-cta"><div class="market-shell owner-cta-inner"><div><span class="section-kicker">Have a vacant home?</span><h2>Meet your next tenant.</h2><p>Publish your availability and receive organised enquiries while Starmax helps you manage what comes next.</p></div><a href="{{ route('marketplace.advertise') }}">Advertise your property →</a></div></section>
@endsection
