<section id="report-listing" class="report-section">
    <details @if($errors->report->any() || session('report_success')) open @endif>
        <summary>Something incorrect? Report this listing</summary>
        <p>Tell the Starmax review team about incorrect availability, fees, photos or a suspicious request. Your report is not shown publicly.</p>
        @if(session('report_success'))<p class="success-message" role="status">{{ session('report_success') }}</p>@endif
        @if($errors->report->any())<ul class="field-error" role="alert">@foreach($errors->report->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
        <form method="POST" action="{{ route('marketplace.reports.store', $property) }}" class="report-form">@csrf
            <label>What needs checking?<select name="reason" required><option value="">Choose a reason</option>@foreach(\App\Models\ListingReport::REASONS as $value => $label)<option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Details<textarea name="details" required minlength="10" maxlength="2000" rows="4" placeholder="Include the unit number and what you found. Do not include PINs, passwords or identity documents.">{{ old('details') }}</textarea></label>
            <label>Email for follow-up (optional)<input type="email" name="report_email" maxlength="255" value="{{ old('report_email') }}" autocomplete="email"></label>
            <div class="honeypot" aria-hidden="true"><label>Website<input name="report_website" tabindex="-1" autocomplete="off"></label></div>
            <button class="primary-action" type="submit">Submit report</button>
            <p class="fact-note">If you have already sent money to someone you suspect is fraudulent, contact your payment provider promptly. A listing report does not initiate a payment reversal.</p>
        </form>
    </details>
</section>
