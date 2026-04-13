@extends('site.layout')

@section('content')
<p class="eyebrow">Product ecosystem</p>
<h2>One platform, two experiences, zero confusion.</h2>
<p>TenantPro pairs an operational web console for managers with a mobile app experience for tenants, keeping everyone synchronized in real time.</p>

<div class="grid grid-2" style="margin-top:20px;">
    <article class="tile reveal">
        <h3>TenantPro Web</h3>
        <p>Control center for landlords and operations teams.</p>
        <ul class="list">
            <li>Property and unit administration</li>
            <li>Tenant onboarding and account lifecycle</li>
            <li>Invoice creation and payment monitoring</li>
            <li>Maintenance and support coordination</li>
        </ul>
        <div class="stack">
            <a href="/admin/login" class="btn btn-primary">Access Dashboard</a>
        </div>
    </article>

    <article class="tile reveal">
        <h3>TenantPro Mobile</h3>
        <p>Tenant self-service experience built for fast action.</p>
        <ul class="list">
            <li>View and settle invoices</li>
            <li>Submit maintenance requests with updates</li>
            <li>Message management teams directly</li>
            <li>Access unit and property details on demand</li>
        </ul>
        <p class="subtle">Android app remains active for tenants as requested in your architecture.</p>
        <div class="stack">
            <a href="{{ config('app.tenant_demo_url') }}" class="btn btn-primary" target="_blank" rel="noopener">Try Tenant Live Demo</a>
        </div>
    </article>
</div>
@endsection
