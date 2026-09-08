<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#18181b">
    <title>@yield('title', 'Homes to rent in Kenya | Starmax')</title>
    <meta name="description" content="@yield('meta_description', 'Find available rental homes managed through Starmax. Search by location, compare monthly rent, and request a viewing safely.')">
    @php
        $canonical = request()->routeIs('marketplace.index')
            ? route('marketplace.index', array_filter(request()->only(['q', 'location', 'min_price', 'max_price', 'bedrooms', 'sort', 'page']), fn ($value) => is_scalar($value) && (string) $value !== ''))
            : (request()->routeIs('marketplace.neighbourhood') && request()->integer('page') > 1 ? url()->current().'?page='.request()->integer('page') : url()->current());
    @endphp
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="robots" content="@yield('robots', request()->routeIs('marketplace.index') && collect(request()->only(['q', 'location', 'min_price', 'max_price', 'bedrooms', 'sort']))->contains(fn ($value) => filled($value)) ? 'noindex,follow' : 'index,follow,max-image-preview:large')">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Starmax Homes">
    <meta property="og:locale" content="en_KE">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:title" content="@yield('title', 'Homes to rent in Kenya | Starmax')">
    <meta property="og:description" content="@yield('meta_description', 'Find rental homes in Kenya. Compare monthly rent and request a viewing with the property manager.')">
    <meta property="og:image" content="@yield('share_image', asset('images/starmax-tenant-logo.png'))">
    <meta property="og:image:alt" content="@yield('share_image_alt', 'Starmax Homes')">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Homes to rent in Kenya | Starmax')">
    <meta name="twitter:description" content="@yield('meta_description', 'Find rental homes in Kenya. Compare monthly rent and request a viewing with the property manager.')">
    <meta name="twitter:image" content="@yield('share_image', asset('images/starmax-tenant-logo.png'))">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="stylesheet" href="{{ asset('css/tenant-marketplace.css') }}?v={{ @filemtime(public_path('css/tenant-marketplace.css')) }}">
    @stack('head')
</head>
<body>
<header class="market-header">
    <div class="market-shell market-nav">
        <a class="market-brand" href="{{ route('marketplace.index') }}" aria-label="Starmax homes">
            <img src="{{ asset('images/starmax-tenant-logo.png') }}" alt="Starmax Tenant Services">
            <span>Homes</span>
        </a>
        <nav class="desktop-market-nav" aria-label="Main navigation">
            <a @class(['active' => request()->routeIs('marketplace.index', 'marketplace.show')]) href="{{ route('marketplace.index') }}">Find a home</a>
            <a @class(['active' => request()->routeIs('marketplace.how-it-works')]) href="{{ route('marketplace.how-it-works') }}">How it works</a>
            <a @class(['active' => request()->routeIs('marketplace.safety')]) href="{{ route('marketplace.safety') }}">Rent safely</a>
            <a @class(['nav-advertise' => true, 'active' => request()->routeIs('marketplace.advertise')]) href="{{ route('marketplace.advertise') }}">Advertise your home</a>
            <a @class(['active' => request()->routeIs('marketplace.neighbourhoods', 'marketplace.neighbourhood')]) href="{{ route('marketplace.neighbourhoods') }}">Neighbourhoods</a>
            <a @class(['active' => request()->routeIs('marketplace.saved')]) href="{{ route('marketplace.saved') }}" data-saved-link>Saved homes</a>
            <a class="nav-sign-in" href="{{ route('admin.login') }}" target="_blank" rel="noopener noreferrer" aria-label="Property manager sign in (opens in a new tab)">Property manager sign in</a>
        </nav>
        <details class="mobile-market-nav">
            <summary aria-label="Open navigation">Menu</summary>
            <div>
                <a @class(['active' => request()->routeIs('marketplace.index', 'marketplace.show')]) href="{{ route('marketplace.index') }}">Find a home</a>
                <a @class(['active' => request()->routeIs('marketplace.how-it-works')]) href="{{ route('marketplace.how-it-works') }}">How it works</a>
                <a @class(['active' => request()->routeIs('marketplace.safety')]) href="{{ route('marketplace.safety') }}">Rent safely</a>
                <a @class(['active' => request()->routeIs('marketplace.advertise')]) href="{{ route('marketplace.advertise') }}">Advertise your home</a>
                <a @class(['active' => request()->routeIs('marketplace.neighbourhoods', 'marketplace.neighbourhood')]) href="{{ route('marketplace.neighbourhoods') }}">Neighbourhoods</a>
                <a @class(['active' => request()->routeIs('marketplace.saved')]) href="{{ route('marketplace.saved') }}" data-saved-link>Saved homes</a>
                <a href="{{ route('admin.login') }}" target="_blank" rel="noopener noreferrer" aria-label="Property manager sign in (opens in a new tab)">Property manager sign in</a>
            </div>
        </details>
    </div>
</header>

<main>
    @yield('content')
</main>

<footer class="market-footer">
    <div class="market-shell footer-grid">
        <div>
            <img src="{{ asset('images/starmax-tenant-logo.png') }}" alt="Starmax Tenant Services" class="footer-logo">
            <p>Helping tenants discover professionally managed rental homes across Kenya.</p>
        </div>
        <div>
            <strong>Explore</strong>
            <a href="{{ route('marketplace.index') }}">Available homes</a>
            <a href="{{ route('marketplace.how-it-works') }}">How it works</a>
            <a href="{{ route('marketplace.safety') }}">Rent safely</a>
            <a href="https://starmaxltd.com" target="_blank" rel="noopener">Starmax Ltd</a>
            <a @class(['active' => request()->routeIs('marketplace.contact')]) href="{{ route('marketplace.contact') }}">Contact support</a>
            <a href="{{ route('marketplace.cookies') }}">Cookies and privacy</a>
            <button type="button" class="cookie-settings" data-cookie-settings hidden>Cookie settings</button>
        </div>
        <div>
            <strong>For property managers</strong>
            <a href="{{ route('admin.login') }}" target="_blank" rel="noopener noreferrer" aria-label="Property manager sign in (opens in a new tab)">Sign in</a>
            <a href="{{ route('marketplace.advertise') }}">Advertise a home</a>
        </div>
    </div>
    <div class="market-shell footer-bottom">&copy; {{ date('Y') }} Starmax Ltd. Listing availability is confirmed by each property manager.</div>
</footer>
<script src="{{ asset('js/marketplace-shortlist.js') }}?v={{ @filemtime(public_path('js/marketplace-shortlist.js')) }}" defer></script>
@include('tenant-marketplace.partials.cookie-consent')
<script src="{{ asset('js/marketplace.js') }}?v={{ @filemtime(public_path('js/marketplace.js')) }}" defer></script>
</body>
</html>
