@extends('tenant-marketplace.layout')

@section('title', 'Find available homes to rent | Starmax Homes')

@section('content')
<section class="search-hero">
    <div class="market-shell hero-grid">
        <div class="hero-copy">
            <span class="hero-kicker">A clearer way to rent</span>
            <h1>Your next home, without the guesswork.</h1>
            <p>Search current vacancies across Kenya, see the recorded monthly rent, and reach the responsible property manager in one secure step.</p>

            <div class="hero-proof" aria-label="Marketplace benefits">
                <span><strong>Live</strong> availability</span>
                <span><strong>Clear</strong> monthly rent</span>
                <span><strong>Private</strong> enquiries</span>
            </div>

            <form class="search-panel" action="{{ route('marketplace.index') }}" method="GET">
                <div class="search-field">
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
                <div class="search-actions">
                    <button type="submit"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>Search homes</button>
                    <details class="advanced-filters" @if(isset($filters['min_price']) || isset($filters['max_price'])) open @endif>
                        <summary>More filters</summary>
                        <div class="advanced-filter-card">
                            <div>
                                <label for="market-min-price">Minimum monthly rent</label>
                                <input id="market-min-price" type="number" name="min_price" value="{{ $filters['min_price'] ?? '' }}" min="0" step="500" placeholder="Any minimum">
                            </div>
                            <div>
                                <label for="market-max-price">Maximum monthly rent</label>
                                <input id="market-max-price" type="number" name="max_price" value="{{ $filters['max_price'] ?? '' }}" min="0" step="500" placeholder="Any maximum">
                            </div>
                            <a href="{{ route('marketplace.index') }}">Clear all filters</a>
                        </div>
                    </details>
                </div>
            </form>
        </div>

        <div class="hero-gallery" aria-hidden="true">
            @for($i = 0; $i < 5; $i++)
                <div class="gallery-tile gallery-tile-{{ $i + 1 }}">
                    @if($heroPhotos->get($i))
                        <img src="{{ $heroPhotos->get($i) }}" alt="" loading="eager">
                    @else
                        <svg viewBox="0 0 24 24"><path d="M4 21V10l8-6 8 6v11h-5v-6H9v6z"/></svg>
                    @endif
                </div>
            @endfor
        </div>
    </div>
</section>

<section class="market-shell listings-section">
    <div class="results-heading">
        <div>
            <span class="section-kicker">Live availability</span>
            <h2>{{ $properties->total() }} {{ str('property')->plural($properties->total()) }} available</h2>
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
            @php($unitPreview = $property->units->take(2))
            @php($remainingUnits = $property->available_units_count - $unitPreview->count())
            <article class="listing-card">
                <a href="{{ route('marketplace.show', $property) }}" class="listing-image" aria-label="View {{ $property->name }}">
                    @if($property->cover_image_url)
                        <img src="{{ $property->cover_image_url }}" alt="{{ $property->name }} in {{ $property->city }}" loading="lazy">
                    @else
                        <div class="image-placeholder"><span>SM</span><small>Photo coming soon</small></div>
                    @endif
                    <span class="availability-pill">{{ $property->available_units_count }} {{ str('home')->plural($property->available_units_count) }}</span>
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
                        @if($property->minimum_bedrooms !== null)
                            <span class="bedroom-badge">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7"/><path d="M3 18h18"/><path d="M5 11V7a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v4"/></svg>
                                {{ $property->minimum_bedrooms == $property->maximum_bedrooms ? ($property->minimum_bedrooms == 0 ? 'Studio' : $property->minimum_bedrooms.' bed') : $property->minimum_bedrooms.'-'.$property->maximum_bedrooms.' beds' }}
                            </span>
                        @endif
                    </div>

                    @if($unitPreview->isNotEmpty())
                        <div class="unit-chip-row">
                            @foreach($unitPreview as $unit)
                                <span class="unit-chip">Unit {{ $unit->unit_number }}{{ $unit->bedrooms_label ? ' · '.$unit->bedrooms_label : '' }} · KSh {{ number_format((float) $unit->rent_amount) }}</span>
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

<section class="trust-band" id="how-it-works">
    <div class="market-shell trust-grid">
        <div><strong>Current availability</strong><p>Occupied and maintenance units are removed from public results.</p></div>
        <div><strong>Private by design</strong><p>Your enquiry details are not displayed publicly and are sent securely to the relevant manager.</p></div>
        <div><strong>Clear monthly pricing</strong><p>Compare the recorded rent before requesting a viewing.</p></div>
    </div>
</section>

<section class="market-guide" id="tenant-safety">
    <div class="market-shell guide-inner">
        <div>
            <span class="section-kicker">Rent with confidence</span>
            <h2>See it first. Confirm it. Then pay.</h2>
        </div>
        <div>
            <p>Request a physical viewing and confirm who manages the property before making a commitment.</p>
            <p>Never send a deposit to an unfamiliar personal number. Use payment instructions confirmed by the property manager and retain your receipt.</p>
        </div>
    </div>
</section>
@endsection
