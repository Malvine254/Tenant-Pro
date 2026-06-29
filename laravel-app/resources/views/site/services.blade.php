@extends('site.layout')

@section('content')
<!-- Hero -->
<div class="hero-section">
    <div class="hero-content">
        <p class="eyebrow">Our Services</p>
        <h2>End-to-end digital services for ambitious teams.</h2>
        <p>Whether you need a web platform, a mobile app, AI automation, or strategic consulting — we design, build, and support production-grade solutions.</p>
    </div>
</div>

<!-- Services Accordion -->
<div class="section">
    <div class="section-header">
        <p class="eyebrow">What We Offer</p>
        <h2>Explore our services.</h2>
        <p>Click any service to see the full details, technologies, and what we deliver.</p>
    </div>

    <div class="service-accordion">
        <!-- Web Development -->
        <div class="service-item open reveal">
            <button class="service-trigger" type="button">
                <div class="card-icon purple"><i data-lucide="globe"></i></div>
                <div class="service-trigger-text">
                    <h3>Web Application Development</h3>
                    <p>Full-stack web platforms — from admin dashboards to customer-facing SaaS products.</p>
                </div>
                <div class="service-chevron"><i data-lucide="chevron-down"></i></div>
            </button>
            <div class="service-body">
                <div class="service-body-inner">
                    <div class="sb-col">
                        <p>We build responsive, performant, and secure web applications using modern frameworks. Whether you need an internal tool, a public platform, or a real-time dashboard — we handle the full stack from database to deployment.</p>
                        <div class="sb-tags">
                            <span class="sb-tag">Laravel</span>
                            <span class="sb-tag">NestJS</span>
                            <span class="sb-tag">Next.js</span>
                            <span class="sb-tag">React</span>
                            <span class="sb-tag">PostgreSQL</span>
                            <span class="sb-tag">Redis</span>
                        </div>
                    </div>
                    <div class="sb-col">
                        <ul class="list">
                            <li>REST & GraphQL API development</li>
                            <li>Admin panels & internal tools</li>
                            <li>Real-time features & WebSockets</li>
                            <li>CI/CD pipelines & cloud deployment</li>
                            <li>Performance optimization & caching</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Android Development -->
        <div class="service-item reveal">
            <button class="service-trigger" type="button">
                <div class="card-icon blue"><i data-lucide="smartphone"></i></div>
                <div class="service-trigger-text">
                    <h3>Android App Development</h3>
                    <p>Native Kotlin apps with modern architecture and seamless backend integration.</p>
                </div>
                <div class="service-chevron"><i data-lucide="chevron-down"></i></div>
            </button>
            <div class="service-body">
                <div class="service-body-inner">
                    <div class="sb-col">
                        <p>We build native Android applications that feel smooth, work offline, and integrate deeply with your backend systems. Our apps follow Clean Architecture patterns and use the latest Jetpack libraries.</p>
                        <div class="sb-tags">
                            <span class="sb-tag">Kotlin</span>
                            <span class="sb-tag">Jetpack Compose</span>
                            <span class="sb-tag">Material 3</span>
                            <span class="sb-tag">Hilt</span>
                            <span class="sb-tag">Retrofit</span>
                        </div>
                    </div>
                    <div class="sb-col">
                        <ul class="list">
                            <li>MVVM & Clean Architecture</li>
                            <li>Jetpack Compose UI development</li>
                            <li>Hilt dependency injection</li>
                            <li>Push notifications & background sync</li>
                            <li>Play Store publishing & updates</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Agents -->
        <div class="service-item reveal">
            <button class="service-trigger" type="button">
                <div class="card-icon teal"><i data-lucide="bot"></i></div>
                <div class="service-trigger-text">
                    <h3>AI Agents & Automation</h3>
                    <p>Intelligent agents that automate workflows, analyze data, and assist your team.</p>
                </div>
                <div class="service-chevron"><i data-lucide="chevron-down"></i></div>
            </button>
            <div class="service-body">
                <div class="service-body-inner">
                    <div class="sb-col">
                        <p>We build practical AI that solves real business problems — not demos. From document processing to customer support bots, our agents integrate with your existing systems and deliver measurable ROI.</p>
                        <div class="sb-tags">
                            <span class="sb-tag">OpenAI</span>
                            <span class="sb-tag">Claude</span>
                            <span class="sb-tag">Python</span>
                            <span class="sb-tag">LangChain</span>
                            <span class="sb-tag">RAG</span>
                        </div>
                    </div>
                    <div class="sb-col">
                        <ul class="list">
                            <li>Custom LLM integrations</li>
                            <li>Tool-using agents & multi-step workflows</li>
                            <li>Document processing & extraction</li>
                            <li>Chatbots with domain-specific knowledge</li>
                            <li>RAG pipelines for internal knowledge bases</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- IT Consulting -->
        <div class="service-item reveal">
            <button class="service-trigger" type="button">
                <div class="card-icon orange"><i data-lucide="briefcase"></i></div>
                <div class="service-trigger-text">
                    <h3>IT Consulting & Strategy</h3>
                    <p>Strategic guidance for technology decisions and digital transformation.</p>
                </div>
                <div class="service-chevron"><i data-lucide="chevron-down"></i></div>
            </button>
            <div class="service-body">
                <div class="service-body-inner">
                    <div class="sb-col">
                        <p>We help teams choose the right architecture, evaluate vendors, and build roadmaps for digital transformation. Our consultants bring hands-on engineering experience to every recommendation.</p>
                        <div class="sb-tags">
                            <span class="sb-tag">Architecture Design</span>
                            <span class="sb-tag">Cloud Strategy</span>
                            <span class="sb-tag">Security Audits</span>
                        </div>
                    </div>
                    <div class="sb-col">
                        <ul class="list">
                            <li>Architecture design & system reviews</li>
                            <li>Technology stack evaluation</li>
                            <li>Digital transformation roadmaps</li>
                            <li>Team augmentation & mentoring</li>
                            <li>Security audits & best-practice reviews</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tenant Management -->
        <div class="service-item reveal">
            <button class="service-trigger" type="button">
                <div class="card-icon emerald"><i data-lucide="building-2"></i></div>
                <div class="service-trigger-text">
                    <h3>Tenant & Property Management</h3>
                    <p>Complete operational platform for landlords, property managers, and tenants.</p>
                </div>
                <div class="service-chevron"><i data-lucide="chevron-down"></i></div>
            </button>
            <div class="service-body">
                <div class="service-body-inner">
                    <div class="sb-col">
                        <p>Our flagship domain. We've built a production-grade platform covering billing, maintenance, tenant communication, and portfolio analytics — with a web dashboard and native Android app.</p>
                        <div class="sb-tags">
                            <span class="sb-tag">NestJS</span>
                            <span class="sb-tag">Next.js</span>
                            <span class="sb-tag">Kotlin</span>
                            <span class="sb-tag">PostgreSQL</span>
                            <span class="sb-tag">Prisma</span>
                        </div>
                    </div>
                    <div class="sb-col">
                        <ul class="list">
                            <li>Property & unit lifecycle management</li>
                            <li>Automated invoicing & payment tracking</li>
                            <li>Maintenance request workflows & SLA</li>
                            <li>Tenant onboarding & self-service portal</li>
                            <li>Analytics dashboards for portfolio insights</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Software -->
        <div class="service-item reveal">
            <button class="service-trigger" type="button">
                <div class="card-icon rose"><i data-lucide="zap"></i></div>
                <div class="service-trigger-text">
                    <h3>Custom Business Software</h3>
                    <p>Bespoke solutions when off-the-shelf doesn't fit your workflows.</p>
                </div>
                <div class="service-chevron"><i data-lucide="chevron-down"></i></div>
            </button>
            <div class="service-body">
                <div class="service-body-inner">
                    <div class="sb-col">
                        <p>We build systems tailored to your exact workflows — from inventory management to booking engines. If existing tools don't cover your use case, we design and build a custom solution that does.</p>
                        <div class="sb-tags">
                            <span class="sb-tag">CRM</span>
                            <span class="sb-tag">Booking Systems</span>
                            <span class="sb-tag">API Integrations</span>
                            <span class="sb-tag">Data Pipelines</span>
                        </div>
                    </div>
                    <div class="sb-col">
                        <ul class="list">
                            <li>CRM & customer management systems</li>
                            <li>Booking & scheduling platforms</li>
                            <li>Inventory & logistics tracking</li>
                            <li>Third-party API integrations</li>
                            <li>Reporting dashboards & data pipelines</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Process -->
<div class="divider"></div>
<div class="section">
    <div class="section-header">
        <p class="eyebrow">How We Work</p>
        <h2>A process you can trust.</h2>
    </div>
    <div class="grid grid-4">
        <article class="card reveal" style="text-align:center;">
            <div class="card-icon purple" style="margin:0 auto 12px;"><i data-lucide="search"></i></div>
            <h3>Discovery</h3>
            <p class="text-muted">Understand your goals, constraints, and users.</p>
        </article>
        <article class="card reveal" style="text-align:center;">
            <div class="card-icon blue" style="margin:0 auto 12px;"><i data-lucide="pen-tool"></i></div>
            <h3>Design</h3>
            <p class="text-muted">Architecture and wireframes before code.</p>
        </article>
        <article class="card reveal" style="text-align:center;">
            <div class="card-icon teal" style="margin:0 auto 12px;"><i data-lucide="code-2"></i></div>
            <h3>Build</h3>
            <p class="text-muted">Iterative development with regular check-ins.</p>
        </article>
        <article class="card reveal" style="text-align:center;">
            <div class="card-icon emerald" style="margin:0 auto 12px;"><i data-lucide="rocket"></i></div>
            <h3>Launch & Support</h3>
            <p class="text-muted">Deploy, monitor, and evolve together.</p>
        </article>
    </div>
</div>

<!-- CTA -->
<div class="cta-banner reveal">
    <h2>Have a project in mind?</h2>
    <p>Let's discuss the scope and build something that works for your team.</p>
    <a href="/contact" class="btn" style="background:#fff;color:#18181b;font-weight:700;">Get a Free Consultation →</a>
</div>
@endsection
