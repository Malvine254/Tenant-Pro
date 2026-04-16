"use client";

import Link from 'next/link';
import { useEffect, useState } from 'react';

type Service = {
  title: string;
  description: string;
  icon: string;
  link?: string;
};

type DynamicServicesGridProps = {
  preview?: boolean;
};

function LoadingCard() {
  return <div className="tp-skeleton h-48 rounded-2xl border border-zinc-200" />;
}

function ServiceIcon({ name }: { name: string }) {
  const common = 'h-5 w-5 text-white';

  switch (name) {
    case 'building':
      return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className={common}>
          <path d="M4 20V6.5A1.5 1.5 0 0 1 5.5 5H14v15" />
          <path d="M14 9h4.5A1.5 1.5 0 0 1 20 10.5V20" />
          <path d="M8 9h2M8 12h2M8 15h2M16 13h2M16 16h2M10 20v-3h2v3" />
        </svg>
      );
    case 'learning':
      return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className={common}>
          <path d="M3 7.5 12 4l9 3.5L12 11 3 7.5Z" />
          <path d="M7 10v4.5c0 1.4 2.2 2.5 5 2.5s5-1.1 5-2.5V10" />
          <path d="M21 8v5" />
        </svg>
      );
    case 'code':
      return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className={common}>
          <path d="m8 8-4 4 4 4M16 8l4 4-4 4M13.5 5 10.5 19" />
        </svg>
      );
    case 'android':
      return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className={common}>
          <path d="M8 9a4 4 0 0 1 8 0v6a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2V9Z" />
          <path d="M9 5 7.5 3.5M15 5l1.5-1.5M9 11h.01M15 11h.01M6 10v5M18 10v5" />
        </svg>
      );
    case 'grid':
      return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className={common}>
          <rect x="4" y="4" width="7" height="7" rx="1.5" />
          <rect x="13" y="4" width="7" height="7" rx="1.5" />
          <rect x="4" y="13" width="7" height="7" rx="1.5" />
          <rect x="13" y="13" width="7" height="7" rx="1.5" />
        </svg>
      );
    case 'consulting':
      return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className={common}>
          <path d="M12 3v6M9 6h6M6.5 20a5.5 5.5 0 1 1 11 0" />
          <path d="M7 20h10" />
        </svg>
      );
    default:
      return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" className={common}>
          <circle cx="12" cy="12" r="8" />
          <path d="M12 8v4l2.5 2.5" />
        </svg>
      );
  }
}

export function DynamicServicesGrid({ preview = false }: DynamicServicesGridProps) {
  const [services, setServices] = useState<Service[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;

    fetch('/api/company-data/services')
      .then((response) => response.json())
      .then((data: Service[]) => {
        if (active) {
          setServices(data);
          setLoading(false);
        }
      })
      .catch(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  const displayItems = preview ? services.slice(0, 3) : services;

  const accentByIcon: Record<string, string> = {
    building:    'from-blue-500 via-indigo-500 to-violet-500',
    learning:    'from-violet-500 via-purple-500 to-indigo-500',
    code:        'from-cyan-400 via-blue-500 to-indigo-500',
    android:     'from-emerald-400 via-teal-500 to-cyan-500',
    grid:        'from-orange-400 via-rose-400 to-pink-500',
    consulting:  'from-amber-400 via-orange-400 to-rose-400',
  };

  return (
    <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
      {loading
        ? Array.from({ length: preview ? 3 : 6 }).map((_, index) => <LoadingCard key={index} />)
        : displayItems.map((service, index) => {
            const accent = accentByIcon[service.icon] ?? 'from-indigo-500 via-purple-500 to-violet-500';
            return (
              <article
                id={service.title.toLowerCase().replace(/[^a-z0-9]+/g, '-')}
                key={service.title}
                className="group relative flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-indigo-100 hover:shadow-xl"
              >
                {/* Gradient top bar on hover */}
                <div className={`absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r ${accent} opacity-0 transition duration-300 group-hover:opacity-100`} />

                <div className="flex flex-1 flex-col p-6">
                  {/* Number + icon row */}
                  <div className="flex items-start justify-between">
                    <div className={`flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br ${accent} shadow-sm`}>
                      <ServiceIcon name={service.icon} />
                    </div>
                    <span className="text-3xl font-black text-zinc-100 select-none">
                      {String(index + 1).padStart(2, '0')}
                    </span>
                  </div>

                  <h3 className="mt-5 text-lg font-bold text-zinc-900">{service.title}</h3>
                  <p className="mt-2 flex-1 text-sm leading-6 text-zinc-600">{service.description}</p>

                  {service.link ? (
                    <Link
                      href={service.link}
                      className="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 transition group-hover:gap-2.5"
                    >
                      Learn more
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5} className="h-3.5 w-3.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                      </svg>
                    </Link>
                  ) : (
                    <div className="mt-5 h-px w-0 bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-300 group-hover:w-full" />
                  )}
                </div>
              </article>
            );
          })}
    </div>
  );
}
