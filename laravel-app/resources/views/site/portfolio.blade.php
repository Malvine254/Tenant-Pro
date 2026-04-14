@extends('site.layout')

@section('content')
<!-- Hero -->
<div class="hero-section">
    <div class="hero-content">
        <p class="eyebrow">Our Work</p>
        <h2>Projects that speak for themselves.</h2>
        <p>A selection of web platforms, mobile apps, AI solutions, and property systems we've designed and delivered for clients across East Africa.</p>
    </div>
</div>

<!-- Web Development Projects -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">Web Development</p>
        <h2>Web platforms & dashboards.</h2>
    </div>
    <div class="grid grid-3">
        <article class="card card-accent reveal">
            <span class="tag">Web App</span>
            <h3 style="margin-top:12px;">TenantPro Admin Dashboard</h3>
            <p class="text-muted" style="margin-bottom:8px;">Property Management • Next.js + NestJS</p>
            <p>Full-featured admin console for landlords — property management, invoicing, maintenance tracking, tenant onboarding, and analytics. Built with Next.js 16 and server components.</p>
        </article>
        <article class="card card-accent reveal">
            <span class="tag">Web App</span>
            <h3 style="margin-top:12px;">E-Commerce Platform</h3>
            <p class="text-muted" style="margin-bottom:8px;">Retail • Laravel + Vue.js</p>
            <p>Multi-vendor marketplace with inventory management, order processing, M-Pesa payment integration, and real-time delivery tracking for a Nairobi-based retailer.</p>
        </article>
        <article class="card card-accent reveal">
            <span class="tag">Web App</span>
            <h3 style="margin-top:12px;">Corporate Portal</h3>
            <p class="text-muted" style="margin-bottom:8px;">Enterprise • React + Node.js</p>
            <p>Internal operations portal for a logistics company. HR management, fleet tracking, document workflows, and custom reporting dashboards.</p>
        </article>
    </div>
</div>

<!-- Android Projects -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">Android Development</p>
        <h2>Native Android applications.</h2>
    </div>
    <div class="grid grid-3">
        <article class="card card-accent reveal">
            <span class="tag teal">Android</span>
            <h3 style="margin-top:12px;">TenantPro Mobile</h3>
            <p class="text-muted" style="margin-bottom:8px;">Property • Kotlin + Jetpack</p>
            <p>Tenant self-service app with invoice viewing, payment tracking, maintenance requests, and direct messaging. MVVM architecture with Hilt DI and Retrofit.</p>
        </article>
        <article class="card card-accent reveal">
            <span class="tag teal">Android</span>
            <h3 style="margin-top:12px;">Field Service App</h3>
            <p class="text-muted" style="margin-bottom:8px;">Utilities • Kotlin + Maps SDK</p>
            <p>GPS-enabled field worker app for a utility company. Job assignment, route optimization, photo documentation, and offline task completion with background sync.</p>
        </article>
        <article class="card card-accent reveal">
            <span class="tag teal">Android</span>
            <h3 style="margin-top:12px;">Health Check-In</h3>
            <p class="text-muted" style="margin-bottom:8px;">Healthcare • Kotlin + Compose</p>
            <p>Patient intake and appointment management app for a clinic chain. Biometric auth, appointment scheduling, and health record access with HIPAA-compliant data handling.</p>
        </article>
    </div>
</div>

<!-- AI & Automation Projects -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">AI & Automation</p>
        <h2>Intelligent solutions.</h2>
    </div>
    <div class="grid grid-2">
        <article class="card card-accent reveal">
            <span class="tag orange">AI Agent</span>
            <h3 style="margin-top:12px;">Document Processing Agent</h3>
            <p class="text-muted" style="margin-bottom:8px;">Finance • Python + LLM</p>
            <p>Automated extraction of data from scanned invoices, receipts, and contracts. Multi-step agent with tool-use capabilities, validation checks, and integration with accounting systems. Reduced manual data entry by 80%.</p>
        </article>
        <article class="card card-accent reveal">
            <span class="tag orange">AI Agent</span>
            <h3 style="margin-top:12px;">Customer Support Bot</h3>
            <p class="text-muted" style="margin-bottom:8px;">Telecom • RAG + Claude</p>
            <p>Knowledge-base powered chatbot for a telecom provider. RAG pipeline with custom embeddings, escalation routing, and sentiment analysis. Handles 60% of tier-1 support tickets autonomously.</p>
        </article>
    </div>
</div>

<!-- Property Management Projects -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">Tenant Management</p>
        <h2>Properties under active management.</h2>
    </div>
    <div class="grid grid-3">
        <article class="card reveal">
            <div class="card-icon emerald"><i data-lucide="building-2"></i></div>
            <h3>Westlands Apartments</h3>
            <p class="text-muted" style="margin-bottom:8px;">Nairobi, Kenya • 12 Units</p>
            <p>Residential complex with monthly rent dashboards, automated invoice generation, and maintenance SLA tracking. 99% on-time collection rate.</p>
        </article>
        <article class="card reveal">
            <div class="card-icon emerald"><i data-lucide="home"></i></div>
            <h3>Kileleshwa Residences</h3>
            <p class="text-muted" style="margin-bottom:8px;">Nairobi, Kenya • 8 Units</p>
            <p>Premium residential property with centralized tenant communication, digital onboarding, and recurring invoice automation.</p>
        </article>
        <article class="card reveal">
            <div class="card-icon emerald"><i data-lucide="store"></i></div>
            <h3>Mombasa Commercial Plaza</h3>
            <p class="text-muted" style="margin-bottom:8px;">Mombasa, Kenya • 20 Units</p>
            <p>Mixed-use commercial property with occupancy trend analytics, coordinated support operations, and investor reporting.</p>
        </article>
    </div>
</div>

<!-- CTA -->
<div class="cta-banner reveal">
    <h2>Let's add your project to this list.</h2>
    <p>We'd love to hear about your idea and show you what we can build together.</p>
    <a href="/contact" class="btn" style="background:#fff;color:#18181b;font-weight:700;">Start a Conversation →</a>
</div>
@endsection
