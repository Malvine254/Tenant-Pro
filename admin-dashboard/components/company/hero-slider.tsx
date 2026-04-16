'use client';

import Link from 'next/link';
import { useEffect, useRef, useState } from 'react';

const slides = [
  {
    id: 'tenant',
    eyebrow: 'Tenant Management',
    headline: 'Modern tools for smarter rental operations.',
    subtext:
      'Track rent, handle maintenance, and communicate with tenants through a single streamlined dashboard.',
    primaryCta: { label: 'Start your brief', href: '#requirements' },
    secondaryCta: { label: 'Explore services', href: '/services' },
    accentClass: 'from-blue-500 via-indigo-500 to-violet-500',
    bgFrom: '#0c1445',
    bgMid: '#1e1b4b',
    bgTo: '#0f172a',
    glowColor: 'rgba(99,102,241,0.25)',
    features: ['Rent tracking & reminders', 'Maintenance workflows', 'Tenant communications'],
    badge: '98% SLA response rate',
  },
  {
    id: 'property-ops',
    eyebrow: 'Property Operations',
    headline: 'Full portfolio visibility in one intelligent hub.',
    subtext:
      'Occupancy metrics, billing reports, and operational insights consolidated so landlords stay in control at all times.',
    primaryCta: { label: 'View solutions', href: '#rental-system' },
    secondaryCta: { label: 'Our portfolio', href: '/portfolio' },
    accentClass: 'from-cyan-400 via-blue-500 to-indigo-600',
    bgFrom: '#051c2e',
    bgMid: '#0c2a4a',
    bgTo: '#0f172a',
    glowColor: 'rgba(6,182,212,0.2)',
    features: ['Occupancy tracking', 'Billing coordination', 'Portfolio reporting'],
    badge: '120+ units supported',
  },
  {
    id: 'elearning',
    eyebrow: 'E-learning Platforms',
    headline: 'Digital learning that scales with your organisation.',
    subtext:
      'Course delivery, learner analytics, and resource management built for institutions and growing businesses from day one.',
    primaryCta: { label: 'Start a project', href: '#requirements' },
    secondaryCta: { label: 'See products', href: '/products' },
    accentClass: 'from-violet-400 via-purple-500 to-indigo-500',
    bgFrom: '#170a2e',
    bgMid: '#2e1065',
    bgTo: '#1e1b4b',
    glowColor: 'rgba(167,139,250,0.2)',
    features: ['Course delivery', 'Progress tracking', 'Resource management'],
    badge: 'Scalable learning infrastructure',
  },
  {
    id: 'custom-systems',
    eyebrow: 'Custom Business Systems',
    headline: 'Tailored dashboards built around your workflow.',
    subtext:
      'Transform fragmented manual processes into clean, efficient digital systems delivered with SaaS-grade polish.',
    primaryCta: { label: 'Discuss requirements', href: '#requirements' },
    secondaryCta: { label: 'About Starmax', href: '/about' },
    accentClass: 'from-amber-400 via-orange-500 to-rose-500',
    bgFrom: '#1c0a0a',
    bgMid: '#1f1408',
    bgTo: '#0f172a',
    glowColor: 'rgba(251,146,60,0.2)',
    features: ['Custom workflows', 'Reporting tools', 'Scalable modules'],
    badge: 'SaaS-inspired UX quality',
  },
];

function ChevronLeftIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="h-5 w-5">
      <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
    </svg>
  );
}

function ChevronRightIcon() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="h-5 w-5">
      <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
    </svg>
  );
}

function CheckIcon() {
  return (
    <svg viewBox="0 0 20 20" fill="currentColor" className="h-3 w-3">
      <path
        fillRule="evenodd"
        d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
        clipRule="evenodd"
      />
    </svg>
  );
}

export function HeroSlider() {
  const [current, setCurrent] = useState(0);
  const [fading, setFading] = useState(false);
  const [dir, setDir] = useState<'next' | 'prev'>('next');
  const pausedRef = useRef(false);

  const goTo = (index: number, direction: 'next' | 'prev' = 'next') => {
    if (fading || index === current) return;
    setDir(direction);
    setFading(true);
    setTimeout(() => {
      setCurrent(index);
      setFading(false);
    }, 350);
  };

  const next = () => goTo((current + 1) % slides.length, 'next');
  const prev = () => goTo((current - 1 + slides.length) % slides.length, 'prev');

  useEffect(() => {
    const id = setInterval(() => {
      if (!pausedRef.current) {
        setCurrent((c) => (c + 1) % slides.length);
      }
    }, 6000);
    return () => clearInterval(id);
  }, []);

  const slide = slides[current];
  const translateClass = fading
    ? dir === 'next'
      ? 'opacity-0 translate-x-4'
      : 'opacity-0 -translate-x-4'
    : 'opacity-100 translate-x-0';

  return (
    <section
      className="relative overflow-hidden border-b border-white/5 text-white transition-colors duration-700"
      style={{
        background: `linear-gradient(135deg, ${slide.bgFrom} 0%, ${slide.bgMid} 55%, ${slide.bgTo} 100%)`,
      }}
      onMouseEnter={() => { pausedRef.current = true; }}
      onMouseLeave={() => { pausedRef.current = false; }}
      aria-label="Hero slider"
    >
      {/* Dot grid overlay */}
      <div
        className="pointer-events-none absolute inset-0 opacity-[0.035]"
        style={{
          backgroundImage: 'radial-gradient(circle at 1px 1px, white 1px, transparent 0)',
          backgroundSize: '28px 28px',
        }}
      />
      {/* Glow blobs */}
      <div
        className="pointer-events-none absolute -right-40 -top-40 h-[640px] w-[640px] rounded-full blur-[140px] transition-colors duration-700"
        style={{ backgroundColor: slide.glowColor }}
      />
      <div
        className="pointer-events-none absolute -bottom-40 -left-40 h-[500px] w-[500px] rounded-full blur-[120px] transition-colors duration-700"
        style={{ backgroundColor: slide.glowColor }}
      />

      {/* Slide content */}
      <div className="relative mx-auto max-w-6xl px-6 pb-14 pt-20 lg:pt-28">
        <div
          className={`grid gap-10 transition-all duration-350 ease-in-out lg:grid-cols-[1.25fr_0.75fr] lg:items-center ${translateClass}`}
        >
          {/* Left: caption */}
          <div>
            {/* Badge */}
            <span
              className={`inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold text-blue-100 backdrop-blur-sm`}
            >
              <span
                className={`inline-block h-2 w-2 rounded-full bg-gradient-to-r ${slide.accentClass}`}
              />
              {slide.eyebrow}
            </span>

            <h1 className="mt-5 text-4xl font-semibold tracking-tight sm:text-5xl lg:text-[3.25rem] lg:leading-[1.12]">
              {slide.headline}
            </h1>

            <p className="mt-5 max-w-xl text-base leading-[1.75] text-zinc-300">
              {slide.subtext}
            </p>

            <div className="mt-8 flex flex-wrap gap-3">
              <a
                href={slide.primaryCta.href}
                className={`rounded-full bg-gradient-to-r ${slide.accentClass} px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-black/20 transition duration-200 hover:scale-[1.03] hover:shadow-indigo-500/30`}
              >
                {slide.primaryCta.label}
              </a>
              <Link
                href={slide.secondaryCta.href}
                className="rounded-full border border-white/20 px-5 py-2.5 text-sm font-medium text-white transition duration-200 hover:bg-white/10"
              >
                {slide.secondaryCta.label}
              </Link>
            </div>

            {/* Trust badge */}
            <p className="mt-6 text-xs text-zinc-500">
              <span className={`bg-gradient-to-r ${slide.accentClass} bg-clip-text text-transparent font-semibold`}>
                ✦&nbsp;
              </span>
              {slide.badge}
            </p>
          </div>

          {/* Right: Feature card */}
          <div className="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl backdrop-blur-sm">
            <p className="text-xs font-semibold uppercase tracking-[0.25em] text-zinc-400">
              Key capabilities
            </p>
            <ul className="mt-5 space-y-3">
              {slide.features.map((feature) => (
                <li key={feature} className="flex items-center gap-3 text-sm text-zinc-200">
                  <span
                    className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gradient-to-br ${slide.accentClass} text-white`}
                  >
                    <CheckIcon />
                  </span>
                  {feature}
                </li>
              ))}
            </ul>
            <div className={`mt-6 h-px bg-gradient-to-r ${slide.accentClass} opacity-30`} />
            <p className="mt-4 text-xs text-zinc-600">
              Solution {current + 1} of {slides.length} — Starmax Digital
            </p>
          </div>
        </div>

        {/* Controls bar */}
        <div className="mt-10 flex items-center justify-between">
          {/* Dot indicators */}
          <div className="flex items-center gap-2">
            {slides.map((s, i) => (
              <button
                key={s.id}
                type="button"
                onClick={() => goTo(i, i > current ? 'next' : 'prev')}
                aria-label={`Go to slide ${i + 1}`}
                className={`rounded-full transition-all duration-300 ${
                  i === current
                    ? `h-2.5 w-10 bg-gradient-to-r ${slide.accentClass}`
                    : 'h-2.5 w-2.5 bg-white/25 hover:bg-white/50'
                }`}
              />
            ))}
          </div>

          {/* Arrow buttons */}
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={prev}
              aria-label="Previous slide"
              className="flex h-9 w-9 items-center justify-center rounded-full border border-white/20 text-white/70 transition hover:bg-white/10 hover:text-white"
            >
              <ChevronLeftIcon />
            </button>
            <button
              type="button"
              onClick={next}
              aria-label="Next slide"
              className="flex h-9 w-9 items-center justify-center rounded-full border border-white/20 text-white/70 transition hover:bg-white/10 hover:text-white"
            >
              <ChevronRightIcon />
            </button>
          </div>
        </div>
      </div>

      {/* Progress bar */}
      <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-white/5">
        <div
          key={`${current}-progress`}
          className={`h-full bg-gradient-to-r ${slide.accentClass} animate-[grow_6s_linear_forwards]`}
          style={{ animationPlayState: pausedRef.current ? 'paused' : 'running' }}
        />
      </div>
    </section>
  );
}
