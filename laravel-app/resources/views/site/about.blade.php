@extends('site.layout')

@section('content')
<!-- Hero -->
<div class="hero-section">
    <div class="hero-content">
        <p class="eyebrow">About Starmax</p>
        <h2>A digital-first company building for East Africa.</h2>
        <p>We design and deliver web platforms, mobile apps, AI agents, and business software. Based in Nairobi, we work with startups, enterprises, and property teams to turn ideas into production systems.</p>
    </div>
    <div class="hero-stats">
        <div class="hero-stat reveal">
            <p class="kpi">2024</p>
            <p class="kpi-label">Founded in Nairobi</p>
        </div>
        <div class="hero-stat reveal">
            <p class="kpi">50+</p>
            <p class="kpi-label">Projects delivered</p>
        </div>
        <div class="hero-stat reveal">
            <p class="kpi">6</p>
            <p class="kpi-label">Service verticals</p>
        </div>
    </div>
</div>

<!-- Mission & Vision -->
<div class="section">
    <div class="grid grid-2">
        <article class="card card-accent reveal">
            <div class="card-icon purple"><i data-lucide="target"></i></div>
            <h3>Our Mission</h3>
            <p>To deliver practical, high-quality digital solutions that help businesses operate more efficiently. We believe great software should be accessible to companies of every size — not just those with Silicon Valley budgets.</p>
        </article>
        <article class="card card-accent reveal">
            <div class="card-icon blue"><i data-lucide="telescope"></i></div>
            <h3>Our Vision</h3>
            <p>To become East Africa's most trusted technology partner — known for clean architecture, honest timelines, and software that actually works in production. We measure success by long-term client relationships, not short-term contracts.</p>
        </article>
    </div>
</div>

<!-- What We Do -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">What We Do</p>
        <h2>Multiple disciplines, one team.</h2>
        <p>We don't just write code — we solve problems. Our services span the full digital product lifecycle.</p>
    </div>
    <div class="grid grid-3">
        <a href="/services" class="service-preview-card reveal">
            <div class="service-preview-top">
                <div class="card-icon purple"><i data-lucide="globe"></i></div>
                <h3>Web Development</h3>
                <p>Full-stack web applications with Laravel, NestJS, Next.js, and React.</p>
            </div>
            <div class="service-preview-bottom">
                <span>Learn more</span>
                <i data-lucide="arrow-right"></i>
            </div>
        </a>
        <a href="/services" class="service-preview-card reveal">
            <div class="service-preview-top">
                <div class="card-icon blue"><i data-lucide="smartphone"></i></div>
                <h3>Android Development</h3>
                <p>Native Kotlin applications with Jetpack Compose, Hilt, and Material 3.</p>
            </div>
            <div class="service-preview-bottom">
                <span>Learn more</span>
                <i data-lucide="arrow-right"></i>
            </div>
        </a>
        <a href="/services" class="service-preview-card reveal">
            <div class="service-preview-top">
                <div class="card-icon teal"><i data-lucide="bot"></i></div>
                <h3>AI Agents</h3>
                <p>LLM-powered automation, document processing, and intelligent workflow agents.</p>
            </div>
            <div class="service-preview-bottom">
                <span>Learn more</span>
                <i data-lucide="arrow-right"></i>
            </div>
        </a>
        <a href="/services" class="service-preview-card reveal">
            <div class="service-preview-top">
                <div class="card-icon orange"><i data-lucide="briefcase"></i></div>
                <h3>IT Consulting</h3>
                <p>Architecture reviews, technology strategy, and transformation guidance.</p>
            </div>
            <div class="service-preview-bottom">
                <span>Learn more</span>
                <i data-lucide="arrow-right"></i>
            </div>
        </a>
        <a href="/services" class="service-preview-card reveal">
            <div class="service-preview-top">
                <div class="card-icon emerald"><i data-lucide="building-2"></i></div>
                <h3>Tenant Management</h3>
                <p>Property operations platform with invoicing, maintenance, and self-service.</p>
            </div>
            <div class="service-preview-bottom">
                <span>Learn more</span>
                <i data-lucide="arrow-right"></i>
            </div>
        </a>
        <a href="/services" class="service-preview-card reveal">
            <div class="service-preview-top">
                <div class="card-icon rose"><i data-lucide="zap"></i></div>
                <h3>Custom Software</h3>
                <p>Bespoke business systems — CRM, booking engines, and integrations.</p>
            </div>
            <div class="service-preview-bottom">
                <span>Learn more</span>
                <i data-lucide="arrow-right"></i>
            </div>
        </a>
    </div>
</div>

<div class="divider"></div>

<!-- Core Values -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">What Drives Us</p>
        <h2>Core values.</h2>
    </div>
    <div class="grid grid-2">
        <article class="card reveal">
            <div class="card-icon purple"><i data-lucide="eye"></i></div>
            <h3>Transparency</h3>
            <p>Clear communication, honest estimates, and auditable deliverables. No black boxes. You always know where your project stands.</p>
        </article>
        <article class="card reveal">
            <div class="card-icon orange"><i data-lucide="shield-check"></i></div>
            <h3>Reliability</h3>
            <p>Disciplined timelines, accountable follow-through, and battle-tested deployment practices. We ship on time.</p>
        </article>
        <article class="card reveal">
            <div class="card-icon teal"><i data-lucide="rocket"></i></div>
            <h3>Innovation</h3>
            <p>We stay current with the latest frameworks, AI capabilities, and DevOps practices — then apply them pragmatically.</p>
        </article>
        <article class="card reveal">
            <div class="card-icon blue"><i data-lucide="handshake"></i></div>
            <h3>Integrity</h3>
            <p>Consistent, fair processes. We recommend what's right for your business, not what's easiest for us to build.</p>
        </article>
    </div>
</div>

<!-- CTA -->
<div class="cta-banner reveal">
    <h2>Want to work with us?</h2>
    <p>We'd love to hear about your project and explore how we can help.</p>
    <div class="stack" style="justify-content:center;">
        <a href="/contact" class="btn" style="background:#fff;color:#18181b;font-weight:700;">Get in Touch →</a>
        <a href="/portfolio" class="btn" style="border:1px solid rgba(255,255,255,0.3);color:#fff;">View Our Work</a>
    </div>
</div>
@endsection
