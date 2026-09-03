<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#18181b">
    <title>@yield('title', 'Homes to rent in Kenya | Starmax')</title>
    <meta name="description" content="@yield('meta_description', 'Find available rental homes managed through Starmax. Search by location, compare monthly rent, and request a viewing safely.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="stylesheet" href="{{ asset('css/tenant-marketplace.css') }}">
    @stack('head')
</head>
<body>
<header class="market-header">
    <div class="market-shell market-nav">
        <a class="market-brand" href="{{ route('marketplace.home') }}" aria-label="Starmax homes">
            <img src="{{ asset('images/starmax-tenant-logo.png') }}" alt="Starmax Tenant Services">
            <span>Homes</span>
        </a>
        <nav class="desktop-market-nav" aria-label="Main navigation">
            <a href="{{ route('marketplace.index') }}">Find a home</a>
            <a href="{{ route('marketplace.home') }}#how-it-works">How it works</a>
            <a href="{{ route('marketplace.home') }}#tenant-safety">Tenant safety</a>
            <a href="/contact?topic=list-property">List a property</a>
            <a href="https://starmaxltd.com" target="_blank" rel="noopener">About Starmax</a>
            <a class="nav-sign-in" href="{{ route('admin.login') }}">Property manager sign in</a>
        </nav>
        <details class="mobile-market-nav">
            <summary aria-label="Open navigation">Menu</summary>
            <div>
                <a href="{{ route('marketplace.index') }}">Find a home</a>
                <a href="{{ route('marketplace.home') }}#how-it-works">How it works</a>
                <a href="{{ route('marketplace.home') }}#tenant-safety">Tenant safety</a>
                <a href="/contact?topic=list-property">List a property</a>
                <a href="https://starmaxltd.com" target="_blank" rel="noopener">About Starmax</a>
                <a href="{{ route('admin.login') }}">Property manager sign in</a>
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
            <a href="https://starmaxltd.com" target="_blank" rel="noopener">Starmax Ltd</a>
            <a href="/contact">Contact support</a>
        </div>
        <div>
            <strong>For property managers</strong>
            <a href="{{ route('admin.login') }}">Sign in</a>
            <a href="/contact">List with Starmax</a>
        </div>
    </div>
    <div class="market-shell footer-bottom">&copy; {{ date('Y') }} Starmax Ltd. Listing availability is confirmed by each property manager.</div>
</footer>
</body>
</html>
