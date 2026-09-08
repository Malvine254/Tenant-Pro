@php($unitPreview = $property->units->take(2))
<article class="listing-card">
    <a href="{{ route('marketplace.show', $property) }}" class="listing-image" aria-label="View {{ $property->name }}">
        @if($property->cover_image_url)
            <img src="{{ $property->cover_image_url }}" alt="{{ $property->name }} in {{ $property->city }}" loading="lazy">
        @else
            <div class="image-placeholder"><span>SM</span><small>Photo coming soon</small></div>
        @endif
        <span class="availability-pill">{{ $property->available_units_count }} {{ str('home')->plural($property->available_units_count) }} available</span>
    </a>
    <div class="listing-body">
        <div class="location-line">{{ collect([$property->city, $property->state])->filter()->join(', ') }}</div>
        <h3><a href="{{ route('marketplace.show', $property) }}">{{ $property->name }}</a></h3>
        <p class="address">{{ $property->address_line }}</p>
        <div class="listing-meta">
            <div><strong>KSh {{ number_format((float) $property->minimum_rent) }}</strong><span>/ month</span></div>
            @if($property->minimum_bedrooms !== null)
                <span class="bedroom-badge">{{ $property->minimum_bedrooms == $property->maximum_bedrooms ? ($property->minimum_bedrooms == 0 ? 'Studio' : $property->minimum_bedrooms.' bed') : $property->minimum_bedrooms.'–'.$property->maximum_bedrooms.' beds' }}</span>
            @endif
        </div>
        @if($unitPreview->isNotEmpty())
            <div class="unit-chip-row">
                @foreach($unitPreview as $unit)
                    <span class="unit-chip">{{ $unit->bedrooms_label ?: 'Home' }} · KSh {{ number_format((float) $unit->rent_amount) }}</span>
                @endforeach
            </div>
        @endif
        <div class="card-actions">
            <a href="{{ route('marketplace.show', $property) }}" class="card-action">View home <span aria-hidden="true">→</span></a>
            <a href="{{ route('marketplace.show', $property) }}#request-viewing" class="card-action card-action-ghost">Request viewing</a>
        </div>
    </div>
</article>
