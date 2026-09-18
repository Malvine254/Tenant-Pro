<style>
    .unit-listing-grid { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 14px; }
    @media (max-width:640px) { .unit-listing-grid { grid-template-columns:1fr; } }
</style>
<fieldset style="border:1px solid #cbd5e1;border-radius:12px;padding:18px;margin-bottom:20px;">
    <legend>Public listing details</legend>
    <p>Leave unknown amounts blank. Enter 0 only when there is no charge. These advertised costs do not change invoice settings.</p>
    <div class="unit-listing-grid">
        @foreach(['bathrooms' => 'Bathrooms', 'deposit_amount' => 'Security deposit (KSh)', 'service_charge' => 'Service charge (KSh / month)', 'other_move_in_cost' => 'Other one-time move-in charges (KSh)'] as $field => $label)
            <div class="form-group"><label for="listing-{{ $field }}">{{ $label }}</label><input id="listing-{{ $field }}" type="number" name="{{ $field }}" min="0" step="{{ $field === 'bathrooms' ? '1' : '0.01' }}" value="{{ old($field, isset($unit) ? $unit->$field : '') }}">@error($field)<div class="form-error">{{ $message }}</div>@enderror</div>
        @endforeach
        <div class="form-group"><label for="other-move-in-label">What do the other one-time charges cover?</label><input id="other-move-in-label" name="other_move_in_label" maxlength="100" value="{{ old('other_move_in_label', $unit->other_move_in_label ?? '') }}" placeholder="For example, keys and utility connection">@error('other_move_in_label')<div class="form-error">{{ $message }}</div>@enderror</div>
        <div class="form-group"><label for="available-from">Earliest move-in date</label><input id="available-from" type="date" name="available_from" value="{{ old('available_from', isset($unit) ? $unit->available_from?->format('Y-m-d') : '') }}">@error('available_from')<div class="form-error">{{ $message }}</div>@enderror</div>
    </div>
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

        <label class="check-row" for="apply-media-to-property-units">
            <input id="apply-media-to-property-units" type="checkbox" name="apply_media_to_property_units" value="1" @checked(old('apply_media_to_property_units'))>
            <span>Replace media on every other unit in this property with this unit's final photos and interior gallery.</span>
        </label>
        <small>This copies files so later media changes on one unit do not affect the others.</small>

        <h3>Interior gallery for tenants</h3>
        <p>Label photos by area so tenants can understand their home at a glance. Keep up to 24 photos total.</p>
        @if(!empty($unit->interior_gallery))
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px;">
                @foreach($unit->interior_gallery as $index => $photo)
                    @php($photoUrl = data_get($photo, 'url'))
                    @if($photoUrl)
                        <label style="display:block;">
                            <img src="{{ str_starts_with($photoUrl, 'http') ? $photoUrl : asset(ltrim($photoUrl, '/')) }}" alt="{{ data_get($photo, 'label', 'Interior') }}" width="160" height="110" style="width:100%;object-fit:cover;border-radius:8px;">
                            <strong style="display:block;font-size:12px;margin-top:5px;">{{ data_get($photo, 'label', 'Other') }}</strong>
                            <span style="display:block;font-size:12px;"><input style="width:auto;" type="checkbox" name="remove_interior_photos[]" value="{{ $index }}" @checked(in_array($index, old('remove_interior_photos', [])))> Remove</span>
                        </label>
                    @endif
                @endforeach
            </div>
        @endif
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;">
            @foreach(\App\Services\MarketplaceUnitDetails::INTERIOR_AREAS as $area => $label)
                <div class="form-group">
                    <label for="interior-{{ $area }}">{{ $label }}</label>
                    <input id="interior-{{ $area }}" type="file" name="interior_photos[{{ $area }}][]" accept="image/jpeg,image/png,image/webp" multiple>
                    <small>Up to 6 photos for this area, 5 MB each.</small>
                    @error("interior_photos.$area.*")<div class="form-error">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
        @error('interior_photos')<div class="form-error">{{ $message }}</div>@enderror
    @else
        <p>For several units, these details apply to every unit created. Add individual unit photos by editing each unit after creation.</p>
    @endif
</fieldset>
