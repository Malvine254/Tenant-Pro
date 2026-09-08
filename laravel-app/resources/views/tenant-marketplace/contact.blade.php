@extends('tenant-marketplace.layout')
@section('title', 'Contact Starmax support | Starmax Homes')
@section('meta_description', 'Contact Starmax support about a listing, viewing request, account question or privacy request.')
@section('content')
<section class="inner-hero"><div class="market-shell"><span class="hero-kicker">Starmax support</span><h1>We are here to help with your home search.</h1><p>Ask about a listing, a viewing request, your account or information you submitted through Starmax Homes.</p></div></section>
<section class="market-shell editorial-section support-section">
    <div class="support-grid">
        <div>
            <div class="section-title"><span class="section-kicker">Before you write</span><h2>Include the detail that helps us find the answer.</h2></div>
            <ul class="support-list">
                <li>Share the listing or neighbourhood name when your question is about a home.</li>
                <li>For a viewing enquiry, include the name and email used on the request.</li>
                <li>Never send your M-Pesa PIN, password, one-time code or identity documents.</li>
            </ul>
            <div class="area-callout support-note"><div><strong>Need to report a suspicious listing?</strong><p>Use <em>Report this listing</em> on the property page so the review team receives the listing details with your report.</p></div></div>
        </div>
        <form action="{{ route('marketplace.contact.submit') }}" method="POST" class="support-form" data-ajax-form>
            @csrf
            @if(session('success'))<div class="success-message" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="field-error" role="alert">{{ $errors->first() }}</div>@endif
            <label>Your name<input name="name" required value="{{ old('name') }}" autocomplete="name"></label>
            <label>Email address<input type="email" name="email" required value="{{ old('email') }}" autocomplete="email"></label>
            <label>Phone number <span class="support-optional">Optional</span><input name="phone" value="{{ old('phone') }}" autocomplete="tel"></label>
            <label>What can we help with?<select name="topic" required><option value="">Choose a topic</option><option value="listing" @selected(old('topic') === 'listing')>A listing or property detail</option><option value="viewing" @selected(old('topic') === 'viewing')>A viewing request</option><option value="account" @selected(old('topic') === 'account')>My account or app</option><option value="privacy" @selected(old('topic') === 'privacy')>Privacy or my information</option><option value="other" @selected(old('topic') === 'other')>Something else</option></select></label>
            <label>Message<textarea name="message" required rows="6" maxlength="3000" placeholder="Tell us what happened and what you need help with.">{{ old('message') }}</textarea></label>
            <button class="primary-action" type="submit">Send to support <span aria-hidden="true">→</span></button>
            <small>We use these details only to respond to your support request.</small>
        </form>
    </div>
</section>
@endsection
