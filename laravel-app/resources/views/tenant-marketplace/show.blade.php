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
            <div class="unit-grid">
                @foreach($property->units as $unit)
                    <button type="button" class="unit-box" data-unit-modal="unit-modal-{{ $unit->id }}">
                        <span class="unit-box-dot" aria-hidden="true"></span>
                        <strong>Unit {{ $unit->unit_number }}</strong>
                        @if($unit->bedrooms_label)
                            <span class="unit-box-beds">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7"/><path d="M3 18h18"/><path d="M5 11V7a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v4"/></svg>
                                {{ $unit->bedrooms_label }}
                            </span>
                        @endif
                        <span class="unit-box-price">KSh {{ number_format((float) $unit->rent_amount) }}</span>
                    </button>
                @endforeach
            </div>

            @foreach($property->units as $unit)
                <dialog id="unit-modal-{{ $unit->id }}" class="unit-dialog">
                    <form method="dialog" class="unit-dialog-close"><button type="submit" aria-label="Close">&times;</button></form>
                    <span class="unit-label">Available now</span>
                    <h3>Unit {{ $unit->unit_number }}</h3>
                    <dl class="unit-dialog-facts">
                        @if($unit->bedrooms_label)<div><dt>Bedrooms</dt><dd>{{ $unit->bedrooms_label }}</dd></div>@endif
                        <div><dt>Floor</dt><dd>{{ $unit->floor === null ? 'On request' : ($unit->floor == 0 ? 'Ground floor' : $unit->floor) }}</dd></div>
                        <div><dt>Monthly rent</dt><dd>KSh {{ number_format((float) $unit->rent_amount) }}</dd></div>
                    </dl>
                    <a href="#request-viewing" data-unit-select="{{ $unit->id }}" data-unit-dialog-confirm>Contact about this unit</a>
                </dialog>
            @endforeach
        </section>

        <aside id="request-viewing" class="enquiry-card">
            <span class="section-kicker">Interested?</span>
            <h2>Contact the property manager</h2>
            <p>Share your contact details and the property manager will follow up with more information and a viewing time.</p>

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
document.querySelectorAll('[data-unit-modal]').forEach(function (box) {
    box.addEventListener('click', function () {
        var dialog = document.getElementById(box.dataset.unitModal);
        if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
    });
});

document.querySelectorAll('[data-unit-select]').forEach(function (link) {
    link.addEventListener('click', function () {
        var select = document.getElementById('enquiry-unit');
        if (select) select.value = link.dataset.unitSelect;
        if (link.hasAttribute('data-unit-dialog-confirm')) {
            var dialog = link.closest('dialog');
            if (dialog) dialog.close();
        }
    });
});
</script>
@endsection
