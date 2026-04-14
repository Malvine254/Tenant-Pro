@extends('site.layout')

@section('content')
<p class="eyebrow">Get in touch</p>
<h2>Let’s talk about your property goals.</h2>
<p>Share your portfolio size, current challenges, or support needs and our team will respond with a practical next-step plan.</p>

@if(session('success'))
    <div class="success-box">
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="/contact" class="form-wrap">
    @csrf
    <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" required value="{{ old('name') }}">
        @error('name')<p class="error-text">{{ $message }}</p>@enderror
    </div>
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required value="{{ old('email') }}">
        @error('email')<p class="error-text">{{ $message }}</p>@enderror
    </div>
    <div class="form-group">
        <label>Message</label>
        <textarea name="message" rows="5" required>{{ old('message') }}</textarea>
        @error('message')<p class="error-text">{{ $message }}</p>@enderror
    </div>
    <button type="submit" class="btn btn-primary" style="border:none;cursor:pointer;">Send Message</button>
</form>

<div class="tile" style="margin-top:18px;">
    <h3>Alternative Contacts</h3>
    <p class="subtle" style="margin:4px 0;">Email: <a href="mailto:info@starmaxltd.com">info@starmaxltd.com</a></p>
    <p class="subtle" style="margin:4px 0;">Location: Nairobi, Kenya</p>
</div>
@endsection
