@extends('tenant-marketplace.layout')

@section('title', $property->name.' in '.$property->city.' | Starmax Homes')
@section('meta_description', str($property->description ?: 'Available rental homes at '.$property->name.' in '.$property->city)->limit(155))

@push('head')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Homes', 'item' => route('marketplace.index')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $property->city, 'item' => route('marketplace.index', ['location' => $property->city])],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $property->name],
    ],
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<div class="market-shell detail-page">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="{{ route('marketplace.index') }}">Homes</a><span>/</span>
        <a href="{{ route('marketplace.index', ['location' => $property->city]) }}">{{ $property->city }}</a><span>/</span>
        <span>{{ $property->name }}</span>
    </nav>

    <div class="detail-hero">
        <div class="detail-image">
            @if($property->cover_image_url)
                <img src="{{ $property->cover_image_url }}" alt="{{ $property->name }} in {{ $property->city }}">
            @else
                <div class="image-placeholder"><span>SM</span><small>Photo coming soon</small></div>
            @endif
        </div>
        <div class="detail-summary">
            <span class="availability-pill">{{ $property->units->count() }} {{ str('home')->plural($property->units->count()) }} available</span>
            <h1>{{ $property->name }}</h1>
            <p class="detail-location">{{ $property->address_line }}, {{ collect([$property->city, $property->state, $property->country])->filter()->join(', ') }}</p>
            <p class="detail-description">{{ $property->description ?: 'Well-managed rental homes with current availability shown directly from Starmax.' }}</p>
            <div class="verified-manager"><span>✓</span><div><strong>Managed on Starmax</strong><small>Enquiries are sent securely to the property manager.</small></div></div>
        </div>
    </div>

    <div class="detail-columns">
        <section>
            <div class="section-title"><span class="section-kicker">Choose your home</span><h2>Available units</h2></div>
            <div class="unit-list">
                @foreach($property->units as $unit)
                    <article class="unit-row">
                        <div>
                            <span class="unit-label">Available now</span>
                            <h3>Unit {{ $unit->unit_number }}</h3>
                            <p>{{ $unit->floor === null ? 'Floor information available on request' : ($unit->floor == 0 ? 'Ground floor' : 'Floor '.$unit->floor) }}</p>
                        </div>
                        <div class="unit-price"><strong>KSh {{ number_format((float) $unit->rent_amount) }}</strong><span>per month</span></div>
                        <a href="#request-viewing" data-unit-select="{{ $unit->id }}">Request viewing</a>
                    </article>
                @endforeach
            </div>
        </section>

        <aside id="request-viewing" class="enquiry-card">
            <span class="section-kicker">Interested?</span>
            <h2>Request a viewing</h2>
            <p>Share your contact details and the property manager will follow up with you.</p>

            @if(session('marketplace_success'))
                <div class="success-message" role="status">{{ session('marketplace_success') }}</div>
            @endif

            <form method="POST" action="{{ route('marketplace.enquiries.store', $property) }}">
                @csrf
                <div class="honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
                <label for="enquiry-name">Your name</label>
                <input id="enquiry-name" name="name" value="{{ old('name') }}" required autocomplete="name">
                @error('name')<small class="field-error">{{ $message }}</small>@enderror

                <div class="two-fields">
                    <div><label for="enquiry-phone">Phone</label><input id="enquiry-phone" name="phone_number" value="{{ old('phone_number') }}" autocomplete="tel" placeholder="07… or +254…"></div>
                    <div><label for="enquiry-email">Email</label><input id="enquiry-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email"></div>
                </div>
                @error('phone_number')<small class="field-error">{{ $message }}</small>@enderror
                @error('email')<small class="field-error">{{ $message }}</small>@enderror

                <label for="enquiry-unit">Preferred home</label>
                <select id="enquiry-unit" name="unit_id">
                    <option value="">Any available unit</option>
                    @foreach($property->units as $unit)
                        <option value="{{ $unit->id }}" @selected(old('unit_id') === $unit->id)>Unit {{ $unit->unit_number }} — KSh {{ number_format((float) $unit->rent_amount) }}</option>
                    @endforeach
                </select>
                @error('unit_id')<small class="field-error">{{ $message }}</small>@enderror

                <label for="enquiry-message">Message <span>(optional)</span></label>
                <textarea id="enquiry-message" name="message" rows="4" placeholder="When would you like to view the home?">{{ old('message') }}</textarea>
                @error('message')<small class="field-error">{{ $message }}</small>@enderror
                <button type="submit">Send viewing request</button>
                <small class="privacy-note">Use either a phone number or email. Your contact details are not displayed publicly.</small>
            </form>
        </aside>
    </div>
</div>

<script>
document.querySelectorAll('[data-unit-select]').forEach(function (link) {
    link.addEventListener('click', function () {
        var select = document.getElementById('enquiry-unit');
        if (select) select.value = link.dataset.unitSelect;
    });
});
</script>
@endsection
