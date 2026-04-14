@extends('site.layout')

@section('content')
<!-- Hero -->
<div class="hero-section">
    <div class="hero-content">
        <p class="eyebrow">Contact Us</p>
        <h2>Let's build something together.</h2>
        <p>Whether you're starting a new project, need consulting, or want to discuss a partnership — we're ready to listen.</p>
    </div>
</div>

<div class="grid grid-2" style="gap:32px;">
    <!-- Contact Form -->
    <div>
        @if(session('success'))
            <div class="success-box">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="/contact" class="form-wrap" style="max-width:100%;">
            @csrf
            <div class="card" style="padding:32px;">
                <h3 style="margin-bottom:20px;">Send us a message</h3>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="Your full name">
                    @error('name')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required value="{{ old('email') }}" placeholder="you@company.com">
                    @error('email')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label>Service Interest</label>
                    <select name="service">
                        <option value="">Select a service (optional)</option>
                        <option value="web">Web Development</option>
                        <option value="android">Android Development</option>
                        <option value="ai">AI Agents & Automation</option>
                        <option value="consulting">IT Consulting</option>
                        <option value="tenant">Tenant Management</option>
                        <option value="custom">Custom Software</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" rows="5" required placeholder="Tell us about your project...">{{ old('message') }}</textarea>
                    @error('message')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Send Message →</button>
            </div>
        </form>
    </div>

    <!-- Contact Info -->
    <div>
        <div class="card reveal" style="margin-bottom:20px;">
            <div class="card-icon purple"><i data-lucide="mail"></i></div>
            <h3>Email</h3>
            <p><a href="mailto:info@starmaxltd.com" style="color:#18181b;text-decoration:none;font-weight:600;">info@starmaxltd.com</a></p>
        </div>
        <div class="card reveal" style="margin-bottom:20px;">
            <div class="card-icon blue"><i data-lucide="phone"></i></div>
            <h3>Phone</h3>
            <p>+254 700 123 456</p>
        </div>
        <div class="card reveal" style="margin-bottom:20px;">
            <div class="card-icon teal"><i data-lucide="map-pin"></i></div>
            <h3>Location</h3>
            <p>Nairobi, Kenya</p>
        </div>
        <div class="card reveal" style="margin-bottom:20px;">
            <div class="card-icon orange"><i data-lucide="clock"></i></div>
            <h3>Working Hours</h3>
            <p>Mon – Fri: 8:00 AM – 6:00 PM (EAT)<br>Weekend: By appointment</p>
        </div>
    </div>
</div>
@endsection
