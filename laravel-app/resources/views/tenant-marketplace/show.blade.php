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
                    @php
                        $unitImages = collect($unit->image_urls ?? [])->filter(fn ($image) => is_string($image) && trim($image) !== '');
                        $unitPreviewImage = $unitImages->first();
                        $unitPreviewImage = $unitPreviewImage
                            ? (\Illuminate\Support\Str::startsWith($unitPreviewImage, ['http://', 'https://', 'data:']) ? $unitPreviewImage : asset(ltrim($unitPreviewImage, '/')))
                            : null;
                    @endphp
                    <button type="button" class="unit-box" data-unit-modal="unit-modal-{{ $unit->id }}">
                        @if($unitPreviewImage)
                            <span class="unit-box-image"><img src="{{ $unitPreviewImage }}" alt="" loading="lazy"><small>{{ $unitImages->count() }} {{ str('photo')->plural($unitImages->count()) }}</small></span>
                        @endif
                        <span class="unit-box-dot" aria-hidden="true"></span>
                        <strong>Unit {{ $unit->unit_number }}</strong>
                        @if($unit->bedrooms_label)
                            <span class="unit-box-beds">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 18v-7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v7"/><path d="M3 18h18"/><path d="M5 11V7a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v4"/></svg>
                                {{ $unit->bedrooms_label }}
                            </span>
                        @endif
                        <span class="unit-box-price">KSh {{ number_format((float) $unit->rent_amount) }}</span>
                        <span class="unit-box-more">View unit details <span aria-hidden="true">&rarr;</span></span>
                    </button>
                @endforeach
            </div>

            @foreach($property->units as $unit)
                @php
                    $unitImages = collect($unit->image_urls ?? [])->filter(fn ($image) => is_string($image) && trim($image) !== '')->values();
                    $displayImages = $unitImages->isNotEmpty() ? $unitImages : collect([$property->cover_image_url])->filter();
                @endphp
                <dialog id="unit-modal-{{ $unit->id }}" class="unit-dialog">
                    <form method="dialog" class="unit-dialog-close"><button type="submit" aria-label="Close unit details">&times;</button></form>
                    <div class="unit-dialog-layout">
                        <div class="unit-dialog-media">
                            @if($displayImages->isNotEmpty())
                                <div class="unit-photo-stage">
                                    @foreach($displayImages as $image)
                                        @php($imageUrl = \Illuminate\Support\Str::startsWith($image, ['http://', 'https://', 'data:']) ? $image : asset(ltrim($image, '/')))
                                        <img src="{{ $imageUrl }}" alt="{{ $unitImages->isNotEmpty() ? 'Unit '.$unit->unit_number.' photo '.($loop->iteration) : $property->name.' property photo' }}" class="unit-photo @if(!$loop->first) is-hidden @endif" data-unit-photo loading="lazy">
                                    @endforeach
                                </div>
                                @if($displayImages->count() > 1)
                                    <div class="unit-photo-thumbs" aria-label="Unit photos">
                                        @foreach($displayImages as $image)
                                            @php($thumbUrl = \Illuminate\Support\Str::startsWith($image, ['http://', 'https://', 'data:']) ? $image : asset(ltrim($image, '/')))
                                            <button type="button" class="unit-photo-thumb @if($loop->first) is-active @endif" data-unit-photo-index="{{ $loop->index }}" aria-label="Show photo {{ $loop->iteration }}"><img src="{{ $thumbUrl }}" alt=""></button>
                                        @endforeach
                                    </div>
                                @endif
                                @if($unitImages->isEmpty())<small class="unit-photo-note">Property photo shown — ask the manager for unit-specific photos.</small>@endif
                            @else
                                <div class="unit-photo-empty"><span>SM</span><strong>Photos coming soon</strong><small>Request current photos from the property manager.</small></div>
                            @endif
                        </div>
                        <div class="unit-dialog-content">
                            <span class="unit-label">Available now</span>
                            <p class="unit-property-name">{{ $property->name }}</p>
                            <h3>Unit {{ $unit->unit_number }}</h3>
                            <p class="unit-dialog-location">{{ collect([$property->address_line, $property->city, $property->state])->filter()->join(', ') }}</p>
                            <dl class="unit-dialog-facts">
                                <div><dt>Monthly rent</dt><dd>KSh {{ number_format((float) $unit->rent_amount) }}</dd></div>
                                <div><dt>Bedrooms</dt><dd>{{ $unit->bedrooms_label ?: 'Ask manager' }}</dd></div>
                                <div><dt>Floor</dt><dd>{{ $unit->floor === null ? 'Ask manager' : ($unit->floor == 0 ? 'Ground floor' : 'Floor '.$unit->floor) }}</dd></div>
                                <div><dt>Status</dt><dd class="unit-status-available">Available</dd></div>
                            </dl>
                            @if($property->description)
                                <div class="unit-dialog-about"><strong>About this property</strong><p>{{ $property->description }}</p></div>
                            @endif
                            <p class="unit-dialog-help">Need details about the deposit, utilities, amenities or viewing times? The property manager can confirm them for you.</p>
                            <a href="#request-viewing" data-unit-select="{{ $unit->id }}" data-unit-dialog-confirm>Ask about Unit {{ $unit->unit_number }}</a>
                        </div>
                    </div>
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

document.querySelectorAll('.unit-photo-thumb').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
        var dialog = thumb.closest('.unit-dialog');
        if (!dialog) return;
        dialog.querySelectorAll('[data-unit-photo]').forEach(function (photo, index) {
            photo.classList.toggle('is-hidden', index !== Number(thumb.dataset.unitPhotoIndex));
        });
        dialog.querySelectorAll('.unit-photo-thumb').forEach(function (item) {
            item.classList.toggle('is-active', item === thumb);
        });
    });
});

document.querySelectorAll('.unit-dialog').forEach(function (dialog) {
    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) dialog.close();
    });
});
</script>
@endsection
