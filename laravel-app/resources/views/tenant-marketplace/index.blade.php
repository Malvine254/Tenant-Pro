@extends('tenant-marketplace.layout')

@section('title', 'Find available homes to rent | Starmax Homes')

@section('content')
<section class="search-hero">
    <div class="hero-skyline" aria-hidden="true">
        <svg viewBox="0 0 1600 420" preserveAspectRatio="xMidYMax slice" xmlns="http://www.w3.org/2000/svg">
            <rect x="40" y="200" width="150" height="220" rx="4"/>
            <rect x="70" y="160" width="30" height="40" rx="2"/>
            <rect x="210" y="260" width="120" height="160" rx="4"/>
            <polygon points="210,260 270,220 330,260"/>
            <rect x="360" y="150" width="90" height="270"/>
            <rect x="470" y="230" width="140" height="190" rx="4"/>
            <polygon points="470,230 540,185 610,230"/>
            <rect x="640" y="180" width="70" height="240"/>
            <rect x="730" y="240" width="160" height="180" rx="4"/>
            <rect x="770" y="200" width="35" height="45" rx="2"/>
            <rect x="920" y="140" width="100" height="280"/>
            <rect x="1040" y="255" width="130" height="165" rx="4"/>
            <polygon points="1040,255 1105,210 1170,255"/>
            <rect x="1190" y="190" width="80" height="230"/>
            <rect x="1290" y="245" width="150" height="175" rx="4"/>
            <rect x="1330" y="205" width="34" height="44" rx="2"/>
            <rect x="1460" y="170" width="100" height="250"/>
        </svg>
    </div>
    @if($heroImageUrl)
        <div class="hero-media" aria-hidden="true">
            <img src="{{ $heroImageUrl }}" alt="">
        </div>
    @endif
    <div class="hero-scrim" aria-hidden="true"></div>
    <div class="market-shell hero-inner">
        <div class="hero-copy">
            <span class="hero-kicker">Homes managed with Starmax</span>
            <h1>Find a place that feels right.</h1>
            <p>Explore current vacancies, compare monthly rent, and request a viewing directly from a verified property manager.</p>
        </div>

        <form class="search-panel" action="{{ route('marketplace.index') }}" method="GET">
            <div class="search-field search-field-main">
                <svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <div>
                    <label for="market-q">What are you looking for?</label>
                    <input id="market-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Property name, estate or neighbourhood">
                </div>
            </div>
            <div class="search-field">
                <svg class="field-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                <div>
                    <label for="market-location">Location</label>
                    <select id="market-location" name="location">
                        <option value="">All locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location }}" @selected(($filters['location'] ?? '') === $location)>{{ $location }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>Search homes</button>
        </form>
    </div>
</section>

<section class="market-shell listings-section">
    <div class="results-heading">
        <div>
            <span class="section-kicker">Live availability</span>
            <h2>{{ $properties->total() }} {{ str('home')->plural($properties->total()) }} available</h2>
            <p>Availability comes directly from properties managed on Starmax.</p>
        </div>
        <form method="GET" class="sort-form">
            @foreach(request()->except('sort', 'page') as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <label for="market-sort">Sort by</label>
            <select id="market-sort" name="sort" onchange="this.form.submit()">
                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                <option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>Lowest price</option>
                <option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>Highest price</option>
            </select>
        </form>
    </div>

    @if(request()->hasAny(['q', 'location', 'min_price', 'max_price']))
        <div class="active-search">
            <span>Showing filtered results</span>
            <a href="{{ route('marketplace.index') }}">Clear filters</a>
        </div>
    @endif

    <div class="listing-grid">
        @forelse($properties as $property)
            @php($unitPreview = $property->units->take(3))
            @php($remainingUnits = $property->available_units_count - $unitPreview->count())
            <article class="listing-card">
                <a href="{{ route('marketplace.show', $property) }}" class="listing-image" aria-label="View {{ $property->name }}">
                    @if($property->cover_image_url)
                        <img src="{{ $property->cover_image_url }}" alt="{{ $property->name }} in {{ $property->city }}" loading="lazy">
                    @else
                        <div class="image-placeholder"><span>SM</span><small>Photo coming soon</small></div>
                    @endif
                    <span class="availability-pill">{{ $property->available_units_count }} available</span>
                </a>
                <div class="listing-body">
                    <div class="location-line">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                        {{ collect([$property->city, $property->state])->filter()->join(', ') }}
                    </div>
                    <h3><a href="{{ route('marketplace.show', $property) }}">{{ $property->name }}</a></h3>
                    <p class="address">{{ $property->address_line }}</p>
                    <div class="listing-meta">
                        <div><strong>KSh {{ number_format((float) $property->minimum_rent) }}</strong><span>/ month</span></div>
                        @if($property->minimum_rent != $property->maximum_rent)
                            <small>Up to KSh {{ number_format((float) $property->maximum_rent) }}</small>
                        @endif
                    </div>

                    @if($unitPreview->isNotEmpty())
                        <div class="unit-chip-row">
                            @foreach($unitPreview as $unit)
                                <span class="unit-chip">Unit {{ $unit->unit_number }} · KSh {{ number_format((float) $unit->rent_amount) }}</span>
                            @endforeach
                            @if($remainingUnits > 0)
                                <span class="unit-chip unit-chip-more">+{{ $remainingUnits }} more</span>
                            @endif
                        </div>
                    @endif

                    <div class="card-actions">
                        <a href="{{ route('marketplace.show', $property) }}" class="card-action">View details <span aria-hidden="true">&rarr;</span></a>
                        <a href="{{ route('marketplace.show', $property) }}#request-viewing" class="card-action card-action-ghost">Contact</a>
                    </div>
                </div>
            </article>
        @empty
            <div class="empty-results">
                <div class="empty-icon">⌂</div>
                <h3>No matching homes right now</h3>
                <p>Try a broader location or remove your search terms. New vacancies will appear here as property managers publish them.</p>
                <a href="{{ route('marketplace.index') }}" class="primary-action">View all homes</a>
            </div>
        @endforelse
    </div>

    @if($properties->hasPages())
        <nav class="market-pagination" aria-label="Listing pages">
            @if($properties->onFirstPage())<span>Previous</span>@else<a href="{{ $properties->previousPageUrl() }}">Previous</a>@endif
            <strong>Page {{ $properties->currentPage() }} of {{ $properties->lastPage() }}</strong>
            @if($properties->hasMorePages())<a href="{{ $properties->nextPageUrl() }}">Next</a>@else<span>Next</span>@endif
        </nav>
    @endif
</section>

<section class="trust-band">
    <div class="market-shell trust-grid">
        <div><strong>Current availability</strong><p>Occupied and maintenance units are removed from public results.</p></div>
        <div><strong>Private by design</strong><p>Your enquiry details go only to the relevant property manager.</p></div>
        <div><strong>Clear monthly pricing</strong><p>Compare the recorded rent before requesting a viewing.</p></div>
    </div>
</section>
@endsection
