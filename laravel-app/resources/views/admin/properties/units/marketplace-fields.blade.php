<fieldset style="border:1px solid #cbd5e1;border-radius:12px;padding:18px;margin-bottom:20px;">
    <legend>Public listing details</legend>
    <p>Leave unknown amounts blank. Enter 0 only when there is no charge. These advertised costs do not change invoice settings.</p>
    @foreach(['bathrooms' => 'Bathrooms', 'deposit_amount' => 'Security deposit (KSh)', 'service_charge' => 'Service charge (KSh / month)', 'other_move_in_cost' => 'Other one-time move-in charges (KSh)'] as $field => $label)
        <div class="form-group"><label for="listing-{{ $field }}">{{ $label }}</label><input id="listing-{{ $field }}" type="number" name="{{ $field }}" min="0" step="{{ $field === 'bathrooms' ? '1' : '0.01' }}" value="{{ old($field, isset($unit) ? $unit->$field : '') }}">@error($field)<div class="form-error">{{ $message }}</div>@enderror</div>
    @endforeach
    <div class="form-group"><label for="other-move-in-label">What do the other one-time charges cover?</label><input id="other-move-in-label" name="other_move_in_label" maxlength="100" value="{{ old('other_move_in_label', $unit->other_move_in_label ?? '') }}" placeholder="For example, keys and utility connection">@error('other_move_in_label')<div class="form-error">{{ $message }}</div>@enderror</div>
    <div class="form-group"><label for="available-from">Earliest move-in date</label><input id="available-from" type="date" name="available_from" value="{{ old('available_from', isset($unit) ? $unit->available_from?->format('Y-m-d') : '') }}">@error('available_from')<div class="form-error">{{ $message }}</div>@enderror</div>
    <fieldset style="border:0;padding:0;margin-bottom:18px;"><legend>Amenities confirmed for this unit</legend>
        @foreach(\App\Services\MarketplaceUnitDetails::AMENITIES as $amenity)
            <label style="display:inline-flex;align-items:center;gap:8px;margin:8px 16px 0 0;"><input style="width:auto;" type="checkbox" name="amenities[]" value="{{ $amenity }}" @checked(in_array($amenity, old('amenities', $unit->amenities ?? [])))>{{ $amenity }}</label>
        @endforeach
        @error('amenities.*')<div class="form-error">{{ $message }}</div>@enderror
    </fieldset>
    <label style="display:flex;align-items:flex-start;gap:8px;"><input style="width:auto;" type="checkbox" name="confirm_availability" value="1" @checked(old('confirm_availability'))>I have checked that this unit is available to rent. Show today's confirmation date on its listing.</label>
    <p style="font-size:12px;">{{ isset($unit) && $unit->availability_confirmed_at ? 'Last confirmed '.$unit->availability_confirmed_at->format('j M Y').'.' : 'No availability confirmation recorded.' }} A confirmation is shown only for units marked Available.</p>
    @if(isset($unit))
        <h3>Unit photos</h3><p>Show this exact unit in natural light: living space, kitchen, bedroom, bathroom and entrance. Up to 12 JPG, PNG or WebP photos, 5 MB each. The first retained photo is the cover.</p>
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
        @foreach($unit->image_urls ?? [] as $index => $photo)
            <label><img src="{{ str_starts_with($photo, 'http') ? $photo : asset(ltrim($photo, '/')) }}" alt="Unit photo {{ $index + 1 }}" width="120" height="90" style="object-fit:cover;border-radius:8px;"><span style="display:block;"><input style="width:auto;" type="checkbox" name="remove_photos[]" value="{{ $index }}" @checked(in_array($index, old('remove_photos', [])))> Remove photo {{ $index + 1 }}</span></label>
        @endforeach
        </div>
        <div class="form-group"><label for="unit-photos">Add current photos</label><input id="unit-photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>@error('photos')<div class="form-error">{{ $message }}</div>@enderror @error('photos.*')<div class="form-error">{{ $message }}</div>@enderror</div>
    @else
        <p>For several units, these details apply to every unit created. Add individual unit photos by editing each unit after creation.</p>
    @endif
</fieldset>
