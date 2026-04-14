@extends('site.layout')

@section('content')
<!-- Hero -->
<div class="hero-section">
    <div class="hero-content">
        <p class="eyebrow">Products</p>
        <h2>Software products built by Starmax.</h2>
        <p>Production-grade platforms designed for real users, shipped with modern architecture, and supported long-term.</p>
    </div>
</div>

<!-- TenantPro Platform -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">Flagship Product</p>
        <h2>TenantPro — Property Management Platform</h2>
        <p>A complete ecosystem for property operations: a powerful web dashboard for landlords and an intuitive Android app for tenants.</p>
    </div>

    <div class="grid grid-2">
        <article class="card card-accent reveal">
            <span class="tag">Web Dashboard</span>
            <h3 style="margin-top:14px;">TenantPro Admin Console</h3>
            <p>The command center for landlords, property managers, and operations teams. Built with NestJS, Next.js, and PostgreSQL.</p>
            <ul class="list">
                <li>Property & unit lifecycle management</li>
                <li>Tenant onboarding with digital invitations</li>
                <li>Automated invoicing & payment reconciliation</li>
                <li>Maintenance workflow with SLA tracking</li>
                <li>Revenue, occupancy & analytics dashboards</li>
                <li>Role-based access (admin, manager, caretaker)</li>
            </ul>
            <div class="stack">
                <a href="/admin/login" class="btn btn-primary">Access Dashboard</a>
                <a href="/services" class="btn btn-secondary">Learn More</a>
            </div>
        </article>

        <article class="card card-accent reveal">
            <span class="tag teal">Android App</span>
            <h3 style="margin-top:14px;">TenantPro Mobile</h3>
            <p>A self-service app for tenants built with native Kotlin. View invoices, pay rent, report issues, and communicate with management.</p>
            <ul class="list">
                <li>Invoice viewing & payment tracking</li>
                <li>Maintenance request submission</li>
                <li>Direct messaging with management</li>
                <li>Push notifications for updates</li>
                <li>Material 3 design with dark mode</li>
                <li>Works on Android 8.0+ (API 26+)</li>
            </ul>
            <div class="stack">
                <a href="{{ config('app.tenant_demo_url') }}" class="btn btn-primary" target="_blank" rel="noopener">Try Live Demo</a>
                <a href="/contact" class="btn btn-secondary">Request APK</a>
            </div>
        </article>
    </div>
</div>

<div class="divider"></div>

<!-- Tech Stack -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">Under the Hood</p>
        <h2>Built with proven technology.</h2>
    </div>
    <div class="grid grid-4">
        <article class="card reveal" style="text-align:center;">
            <div class="card-icon blue" style="margin:0 auto 12px;"><i data-lucide="settings"></i></div>
            <h3>NestJS</h3>
            <p class="text-muted">Backend API framework</p>
        </article>
        <article class="card reveal" style="text-align:center;">
            <div class="card-icon purple" style="margin:0 auto 12px;"><i data-lucide="monitor"></i></div>
            <h3>Next.js</h3>
            <p class="text-muted">Admin dashboard frontend</p>
        </article>
        <article class="card reveal" style="text-align:center;">
            <div class="card-icon teal" style="margin:0 auto 12px;"><i data-lucide="smartphone"></i></div>
            <h3>Kotlin</h3>
            <p class="text-muted">Native Android development</p>
        </article>
        <article class="card reveal" style="text-align:center;">
            <div class="card-icon emerald" style="margin:0 auto 12px;"><i data-lucide="database"></i></div>
            <h3>PostgreSQL</h3>
            <p class="text-muted">Relational database</p>
        </article>
    </div>
</div>

<!-- Coming Soon -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">On The Roadmap</p>
        <h2>More products in development.</h2>
    </div>
    <div class="grid grid-3">
        <article class="card reveal">
            <div class="card-icon orange"><i data-lucide="bot"></i></div>
            <h3>AI Support Agent</h3>
            <p>An intelligent assistant that handles tenant queries, triages maintenance requests, and surfaces insights from property data.</p>
            <span class="tag orange">Coming Soon</span>
        </article>
        <article class="card reveal">
            <div class="card-icon rose"><i data-lucide="bar-chart-3"></i></div>
            <h3>Portfolio Analytics</h3>
            <p>Advanced reporting with trend forecasting, benchmarking, and automated investor-ready reports.</p>
            <span class="tag rose">In Development</span>
        </article>
        <article class="card reveal">
            <div class="card-icon sky"><i data-lucide="credit-card"></i></div>
            <h3>M-Pesa Integration</h3>
            <p>Native mobile money payments with automatic reconciliation and real-time confirmation for tenants.</p>
            <span class="tag">Planned</span>
        </article>
    </div>
</div>

<!-- CTA -->
<div class="cta-banner reveal">
    <h2>Want a custom product built?</h2>
    <p>We build bespoke software products for businesses. Let's discuss your idea.</p>
    <a href="/contact" class="btn" style="background:#fff;color:#18181b;font-weight:700;">Talk to Us →</a>
</div>
@endsection
