@extends('tenant-marketplace.layout')
@section('title', 'Saved homes and comparisons | Starmax Homes')
@section('robots', 'noindex,follow')
@section('content')
<section class="inner-hero"><div class="market-shell"><span class="hero-kicker">Your shortlist</span><h1>Keep your favourites in one place.</h1><p>Save individual units while you browse. Compare up to four at a time, including the costs that matter before moving in.</p></div></section>
<section class="market-shell editorial-section" data-saved-page data-has-selection="{{ request()->has('units') ? 'true' : 'false' }}">
    <p class="fact-note">Saved on this browser for up to 180 days. No account needed. <a href="{{ route('marketplace.saved') }}" data-saved-link>Open my saved homes</a> · <button type="button" class="text-button" data-clear-saved hidden>Clear saved homes</button></p>
    <p data-shortlist-status role="status"></p>
    @if($missingCount)<p class="success-message">{{ $missingCount }} selected {{ str('home')->plural($missingCount) }} no longer {{ $missingCount === 1 ? 'appears' : 'appear' }} in public availability. Private or unavailable details are not displayed.</p>@endif
    @if($units->isEmpty())
        <div class="empty-results"><h2>Your next home could be one save away.</h2><p>Open a listing, choose a unit and select Save this home. Your choices will appear here.</p><a class="primary-action" href="{{ route('marketplace.index') }}">Find a home</a></div>
    @else
        <form method="GET" action="{{ route('marketplace.saved') }}" class="comparison-picker">
            @foreach($units as $unit)<input type="hidden" name="units[]" value="{{ $unit->id }}">@endforeach
            <fieldset><legend>Choose up to four homes to compare</legend>
            @foreach($units as $unit)<label><input type="checkbox" name="compare[]" value="{{ $unit->id }}" @checked($compared->contains('id', $unit->id))>{{ $unit->property->name }} · Unit {{ $unit->unit_number }}</label>@endforeach
            </fieldset><button class="secondary-action" type="submit">Update comparison</button>
            @error('compare')<p class="field-error">{{ $message }}</p>@enderror
        </form>
        <div class="comparison-scroll" tabindex="0" role="region" aria-label="Home comparison; scroll horizontally to see all homes">
            <table class="comparison-table"><caption>Compare listed details. Unknown costs need confirmation.</caption><thead><tr><th scope="col">Your priorities</th>@foreach($compared as $unit)<th scope="col"><a href="{{ route('marketplace.show', $unit->property) }}#unit-{{ $unit->id }}">{{ $unit->property->name }}<br>Unit {{ $unit->unit_number }}</a><button type="button" class="text-button" data-save-unit="{{ $unit->id }}" hidden>Save this home</button><span data-save-status role="status"></span></th>@endforeach</tr></thead>
            <tbody>
            @foreach(['Location', 'Monthly rent', 'Deposit', 'Monthly service charge', 'Move-in costs', 'Bedrooms', 'Bathrooms', 'Move-in date', 'Amenities', 'Availability checked'] as $label)
                <tr><th scope="row">{{ $label }}</th>@foreach($compared as $unit)<td>
                    @switch($label)
                    @case('Location'){{ collect([$unit->property->neighbourhood, $unit->property->city])->filter()->join(', ') }}@break
                    @case('Monthly rent')KSh {{ number_format((float) $unit->rent_amount, 2) }}@break
                    @case('Deposit'){{ $unit->deposit_amount === null ? 'Ask manager' : 'KSh '.number_format((float) $unit->deposit_amount, 2) }}@break
                    @case('Monthly service charge'){{ $unit->service_charge === null ? 'Ask manager' : 'KSh '.number_format((float) $unit->service_charge, 2) }}@break
                    @case('Move-in costs')@php($costs = $unit->moveInCosts())<strong>KSh {{ number_format($costs['total'], 2) }}</strong><br><small>{{ $costs['complete'] ? 'Total listed costs' : 'Known subtotal; fees missing' }}</small>@break
                    @case('Bedrooms'){{ $unit->bedrooms_label ?: 'Ask manager' }}@break
                    @case('Bathrooms'){{ $unit->bathrooms ?? 'Ask manager' }}@break
                    @case('Move-in date'){{ $unit->available_from?->format('j M Y') ?? 'Ask manager' }}@break
                    @case('Amenities'){{ $unit->amenities ? implode(', ', $unit->amenities) : 'Ask manager' }}@break
                    @case('Availability checked'){{ $unit->availability_confirmed_at?->format('j M Y') ?? 'Not yet confirmed' }}@break
                    @endswitch
                </td>@endforeach</tr>
            @endforeach
            </tbody></table>
        </div>
        <p class="fact-note">Move-in costs cover the first month of listed recurring charges, deposit and listed one-time fees. Electricity, usage-based bills and your own moving expenses are excluded. Ask the manager for a full written breakdown.</p>
    @endif
    <noscript><p>Automatic saving needs JavaScript. You can still bookmark this comparison URL and use the comparison form.</p></noscript>
</section>
@endsection
