@php($costs = $unit->moveInCosts())
<dl class="home-facts">
    <div><dt>Bathrooms</dt><dd>{{ $unit->bathrooms ?? 'Ask manager' }}</dd></div>
    <div><dt>Move-in date</dt><dd>{{ $unit->available_from ? ($unit->available_from->isFuture() ? $unit->available_from->format('j M Y') : 'Available to move in') : 'Ask manager' }}</dd></div>
    <div><dt>Availability checked</dt><dd>{{ $unit->availability_confirmed_at ? $unit->availability_confirmed_at->format('j M Y') : 'Not yet confirmed' }}</dd></div>
</dl>
<p class="fact-note">Availability is supplied by the manager. Confirm it again before travelling or paying.</p>
@if($unit->amenities)
    <h4>Amenities</h4><ul class="amenity-list">@foreach($unit->amenities as $amenity)<li>{{ $amenity }}</li>@endforeach</ul>
@else
    <p class="fact-note">Amenities have not been added. Ask about water, parking, internet and access.</p>
@endif
<details class="cost-breakdown" open>
    <summary>Move-in cost breakdown</summary>
    <dl>@foreach($costs['items'] as $label => $amount)<div><dt>{{ $label }}</dt><dd>{{ $amount === null ? 'Ask manager' : 'KSh '.number_format((float) $amount, 2) }}</dd></div>@endforeach</dl>
    <p class="cost-total"><span>{{ $costs['complete'] ? 'Total listed move-in cost' : 'Known move-in subtotal' }}</span><strong>KSh {{ number_format($costs['total'], 2) }}</strong></p>
    <p class="fact-note">{{ $costs['complete'] ? 'Includes one month of the listed recurring charges, deposit and listed one-time fees.' : 'This is not a complete total. Some charges still need confirmation.' }} Electricity, usage-based bills and your own moving expenses are excluded. Confirm all charges and deposit terms in writing.</p>
</details>
<button class="secondary-action" type="button" data-save-unit="{{ $unit->id }}" hidden>Save this home</button>
<a class="text-link" href="{{ route('marketplace.saved', ['units' => [$unit->id]]) }}">Compare this home</a>
<span class="fact-note" data-save-status role="status"></span>
