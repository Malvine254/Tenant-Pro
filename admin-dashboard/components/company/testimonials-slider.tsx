"use client";

import Image from 'next/image';
import { useEffect, useMemo, useState } from 'react';

type Testimonial = {
  name: string;
  role: string;
  company: string;
  quote: string;
  rating: number;
  avatar?: string;
};

function StarRating({ rating }: { rating: number }) {
  return (
    <div className="flex items-center gap-1 text-amber-500" aria-label={`${rating} out of 5 stars`}>
      {Array.from({ length: 5 }).map((_, index) => (
        <svg
          key={index}
          viewBox="0 0 20 20"
          fill={index < rating ? 'currentColor' : 'none'}
          stroke="currentColor"
          className="h-4 w-4"
        >
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.922-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.176 0l-2.8 2.034c-.784.57-1.838-.196-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81H7.03a1 1 0 0 0 .951-.69l1.07-3.292Z" />
        </svg>
      ))}
    </div>
  );
}

export function TestimonialsSlider() {
  const [items, setItems] = useState<Testimonial[]>([]);
  const [activeIndex, setActiveIndex] = useState(0);

  useEffect(() => {
    fetch('/api/company-data/testimonials')
      .then((response) => response.json())
      .then((data: Testimonial[]) => setItems(data))
      .catch(() => setItems([]));
  }, []);

  useEffect(() => {
    if (items.length <= 1) return;
    const timer = window.setInterval(() => {
      setActiveIndex((current) => (current + 1) % items.length);
    }, 5000);
    return () => window.clearInterval(timer);
  }, [items]);

  const active = useMemo(() => items[activeIndex], [items, activeIndex]);

  if (!active) {
    return <div className="tp-skeleton h-56 rounded-3xl border border-zinc-200" />;
  }

  return (
    <section className="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-[0_20px_60px_-24px_rgba(15,23,42,0.35)] md:p-8">
      <div className="flex items-center justify-between gap-3">
        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Testimonials</p>
          <h3 className="mt-1 text-2xl font-semibold text-zinc-900">What clients say</h3>
        </div>
        <div className="flex items-center gap-2">
          {items.map((_, index) => (
            <button
              key={index}
              type="button"
              onClick={() => setActiveIndex(index)}
              aria-label={`Show testimonial ${index + 1}`}
              className={`h-2.5 w-2.5 rounded-full transition ${activeIndex === index ? 'bg-indigo-600' : 'bg-zinc-300'}`}
            />
          ))}
        </div>
      </div>

      <div className="mt-6 rounded-3xl bg-gradient-to-br from-zinc-50 to-white p-5 ring-1 ring-zinc-100 md:p-6">
        <StarRating rating={active.rating} />
        <p className="mt-4 text-lg leading-8 text-zinc-700 md:text-xl">“{active.quote}”</p>

        <div className="mt-6 flex items-center gap-4">
          <div className="relative h-14 w-14 overflow-hidden rounded-full border border-zinc-200 bg-gradient-to-br from-blue-100 via-indigo-100 to-violet-100">
            {active.avatar ? (
              <Image src={active.avatar} alt={active.name} fill className="object-cover" unoptimized />
            ) : null}
          </div>
          <div>
            <p className="font-semibold text-zinc-900">{active.name}</p>
            <p className="text-sm text-zinc-600">{active.role}</p>
            <p className="text-sm font-medium text-indigo-600">{active.company}</p>
          </div>
        </div>
      </div>
    </section>
  );
}
