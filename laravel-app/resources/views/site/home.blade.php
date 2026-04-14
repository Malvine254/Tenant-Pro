@extends('site.layout')

@section('content')
<!-- Hero -->
<div class="hero-section">
    <div class="hero-content hero-slider js-hero-slider" data-autoplay="true" data-interval="5200">
        <div class="hero-slider-track">
            <div class="hero-slide">
                <p class="eyebrow">Digital Solutions Partner</p>
                <h2>We build software that moves businesses forward.</h2>
                <p>From web platforms and Android apps to AI-powered agents and tenant management systems — Starmax delivers end-to-end digital solutions for modern enterprises.</p>
                <div class="stack">
                    <a href="/contact" class="btn btn-primary">Start a Project</a>
                    <a href="/services" class="btn btn-secondary" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2);color:#fff;">Our Services</a>
                </div>
            </div>

            <div class="hero-slide">
                <p class="eyebrow">Web, Mobile, AI</p>
                <h2>One team for product strategy, build, and scale.</h2>
                <p>We design and ship modern digital platforms with clean architecture, polished UX, and production-grade deployment pipelines.</p>
                <div class="stack">
                    <a href="/services" class="btn btn-primary">View Capabilities</a>
                    <a href="/portfolio" class="btn btn-secondary" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2);color:#fff;">See Portfolio</a>
                </div>
            </div>

            <div class="hero-slide">
                <p class="eyebrow">Built For Outcomes</p>
                <h2>From idea to launch, we deliver measurable impact.</h2>
                <p>Whether you are modernizing operations or launching a new product, we turn business goals into reliable software systems.</p>
                <div class="stack">
                    <a href="/contact" class="btn btn-primary">Book a Consultation</a>
                    <a href="/products" class="btn btn-secondary" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2);color:#fff;">Explore Products</a>
                </div>
            </div>
        </div>

        <div class="hero-slider-controls">
            <div class="hero-slider-nav">
                <button type="button" class="hero-slider-btn js-hero-prev" aria-label="Previous hero slide"><i data-lucide="arrow-left"></i></button>
                <button type="button" class="hero-slider-btn js-hero-next" aria-label="Next hero slide"><i data-lucide="arrow-right"></i></button>
            </div>
            <div class="hero-slider-dots">
                <button type="button" class="hero-slider-dot active" aria-label="Go to hero slide 1"></button>
                <button type="button" class="hero-slider-dot" aria-label="Go to hero slide 2"></button>
                <button type="button" class="hero-slider-dot" aria-label="Go to hero slide 3"></button>
            </div>
        </div>
    </div>
    <div class="hero-stats">
        <div class="hero-stat reveal">
            <p class="kpi">50+</p>
            <p class="kpi-label">Projects delivered across web, mobile, and AI</p>
        </div>
        <div class="hero-stat reveal">
            <p class="kpi">6</p>
            <p class="kpi-label">Core service verticals</p>
        </div>
        <div class="hero-stat reveal">
            <p class="kpi">24/7</p>
            <p class="kpi-label">Monitoring & support for live systems</p>
        </div>
    </div>
</div>

<!-- Modern Slider -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">Featured Solutions</p>
        <h2>What we build, beautifully delivered.</h2>
        <p>A modern showcase of our strongest capabilities across web, mobile, and AI services.</p>
    </div>

    <div class="modern-slider js-modern-slider reveal" data-autoplay="true" data-interval="5000">
        <div class="modern-slider-track">
            <article class="modern-slide">
                <div class="modern-slide-content">
                    <span class="tag">Web Platforms</span>
                    <h3>Scalable web applications for serious operations.</h3>
                    <p>We build high-performance dashboards and business platforms with secure APIs, real-time updates, and maintainable architecture.</p>
                    <ul class="list">
                        <li>Admin portals and internal tools</li>
                        <li>SaaS products and customer-facing apps</li>
                        <li>API-first architecture with robust auth</li>
                    </ul>
                    <div class="stack"><a href="/services" class="btn btn-primary">See Web Services</a></div>
                </div>
                <div class="modern-slide-media">
                    <img src="{{ asset('images/sample-web-service.svg') }}" alt="Web development sample" class="modern-slide-image">
                    <div class="modern-slide-overlay">
                        <p class="media-kpi">99.9%</p>
                        <p class="media-label">Platform uptime target</p>
                        <div class="modern-slide-badges">
                            <span>Laravel</span><span>NestJS</span><span>Next.js</span><span>PostgreSQL</span>
                        </div>
                    </div>
                </div>
            </article>

            <article class="modern-slide">
                <div class="modern-slide-content">
                    <span class="tag teal">Android Apps</span>
                    <h3>Native mobile experiences your users actually enjoy.</h3>
                    <p>From tenant self-service apps to field-operations tools, we deliver smooth and reliable Kotlin apps ready for production.</p>
                    <ul class="list">
                        <li>MVVM and clean architecture</li>
                        <li>Offline-first patterns with sync</li>
                        <li>Push notifications and analytics</li>
                    </ul>
                    <div class="stack"><a href="/services" class="btn btn-primary">See Mobile Services</a></div>
                </div>
                <div class="modern-slide-media">
                    <img src="{{ asset('images/sample-mobile-service.svg') }}" alt="Android app sample" class="modern-slide-image">
                    <div class="modern-slide-overlay">
                        <p class="media-kpi">4.8★</p>
                        <p class="media-label">Target app experience quality</p>
                        <div class="modern-slide-badges">
                            <span>Kotlin</span><span>Jetpack Compose</span><span>Hilt</span><span>Retrofit</span>
                        </div>
                    </div>
                </div>
            </article>

            <article class="modern-slide">
                <div class="modern-slide-content">
                    <span class="tag orange">AI & Automation</span>
                    <h3>AI workflows that reduce manual work from day one.</h3>
                    <p>We design practical AI assistants and automation pipelines for support, operations, and document-heavy workflows.</p>
                    <ul class="list">
                        <li>LLM-powered assistants and copilots</li>
                        <li>Document extraction and routing</li>
                        <li>Knowledge-aware chat with RAG</li>
                    </ul>
                    <div class="stack"><a href="/services" class="btn btn-primary">See AI Services</a></div>
                </div>
                <div class="modern-slide-media">
                    <img src="{{ asset('images/sample-ai-service.svg') }}" alt="AI automation sample" class="modern-slide-image">
                    <div class="modern-slide-overlay">
                        <p class="media-kpi">60%</p>
                        <p class="media-label">Typical reduction in repetitive tasks</p>
                        <div class="modern-slide-badges">
                            <span>OpenAI</span><span>Claude</span><span>Python</span><span>RAG</span>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <div class="modern-slider-controls">
            <div class="modern-slider-nav">
                <button type="button" class="modern-slider-btn js-slider-prev" aria-label="Previous slide"><i data-lucide="arrow-left"></i></button>
                <button type="button" class="modern-slider-btn js-slider-next" aria-label="Next slide"><i data-lucide="arrow-right"></i></button>
            </div>
            <div class="modern-slider-dots">
                <button type="button" class="modern-slider-dot active" aria-label="Go to slide 1"></button>
                <button type="button" class="modern-slider-dot" aria-label="Go to slide 2"></button>
                <button type="button" class="modern-slider-dot" aria-label="Go to slide 3"></button>
            </div>
        </div>
    </div>
</div>

<!-- Services Preview -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">What We Do</p>
        <h2>Full-stack digital services.</h2>
        <p>We cover the entire lifecycle — from strategy and design to development, deployment, and ongoing support.</p>
    </div>
    <div class="grid grid-3">
        <a href="/services" class="service-preview-card reveal">
            <div class="service-preview-top">
                <div class="card-icon purple"><i data-lucide="globe"></i></div>
                <h3>Web Development</h3>
                <p>Responsive web applications built with Laravel, Next.js, and modern frameworks.</p>
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
                <p>Native Kotlin apps with MVVM architecture, Jetpack Compose, and seamless API integration.</p>
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
                <p>Intelligent automation using LLMs, custom agents, and workflow orchestration.</p>
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
                <p>Architecture reviews, technology strategy, and digital transformation advisory.</p>
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
                <p>End-to-end property operations platform with invoicing and tenant self-service.</p>
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
                <p>Bespoke business systems — inventory, CRM, booking engines, and integrations.</p>
            </div>
            <div class="service-preview-bottom">
                <span>Learn more</span>
                <i data-lucide="arrow-right"></i>
            </div>
        </a>
    </div>
    <div class="stack" style="margin-top:24px;">
        <a href="/services" class="btn btn-primary">Explore All Services →</a>
    </div>
</div>

<div class="divider"></div>

<!-- Flagship Products -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">Flagship Product</p>
        <h2>TenantPro — property ops made simple.</h2>
        <p>A complete tenant management ecosystem with a web dashboard for landlords and a native Android app for tenants.</p>
    </div>
    <div class="grid grid-2">
        <article class="card reveal">
            <span class="tag">Web Dashboard</span>
            <h3 style="margin-top:14px;">TenantPro Admin</h3>
            <p>Manage properties, units, tenants, invoices, maintenance, and analytics from a single modern interface.</p>
            <ul class="list">
                <li>Real-time occupancy & revenue dashboards</li>
                <li>Automated invoice generation & reminders</li>
                <li>Maintenance SLA tracking</li>
            </ul>
            <div class="stack"><a href="/products" class="btn btn-secondary">Learn More</a></div>
        </article>
        <article class="card reveal">
            <span class="tag teal">Android App</span>
            <h3 style="margin-top:14px;">TenantPro Mobile</h3>
            <p>Tenants view invoices, make payments, submit maintenance requests, and message management — all from their phone.</p>
            <ul class="list">
                <li>Native Kotlin with Material 3 design</li>
                <li>Offline-capable with sync</li>
                <li>Push notifications for updates</li>
            </ul>
            <div class="stack"><a href="/products" class="btn btn-secondary">Learn More</a></div>
        </article>
    </div>
</div>

<div class="divider"></div>

<!-- Why Starmax -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">Why Starmax</p>
        <h2>Built different, delivered consistently.</h2>
    </div>
    <div class="grid grid-3">
        <article class="card reveal">
            <div class="card-icon emerald"><i data-lucide="map-pin"></i></div>
            <h3>East Africa Focus</h3>
            <p>Deeply rooted in the Nairobi tech ecosystem. We understand local market dynamics, payments, and infrastructure challenges.</p>
        </article>
        <article class="card reveal">
            <div class="card-icon blue"><i data-lucide="wrench"></i></div>
            <h3>Full Lifecycle</h3>
            <p>Strategy → Design → Build → Deploy → Support. One team that owns the delivery from concept to production.</p>
        </article>
        <article class="card reveal">
            <div class="card-icon purple"><i data-lucide="rocket"></i></div>
            <h3>Modern Stack</h3>
            <p>NestJS, Next.js, Laravel, Kotlin, Python, PostgreSQL — we choose the right tools, not the trendy ones.</p>
        </article>
    </div>
</div>

<!-- CTA -->
<div class="cta-banner reveal">
    <h2>Ready to build something great?</h2>
    <p>Tell us about your project and let's explore what we can create together.</p>
    <a href="/contact" class="btn" style="background:#fff;color:#18181b;font-weight:700;">Get in Touch →</a>
</div>
@endsection
