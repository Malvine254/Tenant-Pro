'use client';

import { useState } from 'react';
import { EventCountdown } from './event-countdown';
import { EventRegistrationModal } from './event-registration-modal';

export type Event = {
  id: string;
  title: string;
  date: string; // ISO string
  displayDate: string;
  time: string;
  location: string;
  type: 'Webinar' | 'Workshop' | 'Showcase' | 'Demo';
  description: string;
  capacity: number;
  spots: number;
  accentClass: string;
  tagColor: string;
  icon: React.ReactNode;
};

const CalendarIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-5 w-5">
    <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
  </svg>
);

const ClockIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-4 w-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
  </svg>
);

const MapPinIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-4 w-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
  </svg>
);

const UsersIcon = () => (
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-4 w-4">
    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
  </svg>
);

const events: Event[] = [
  {
    id: 'webinar-tenant-ops-may',
    title: 'Tenant Operations Deep Dive',
    date: '2026-05-14T10:00:00',
    displayDate: 'Wednesday, 14 May 2026',
    time: '10:00 AM – 11:30 AM WAT',
    location: 'Online (Zoom)',
    type: 'Webinar',
    description:
      'A live walkthrough of the Starmax Tenant Management System — covering rent tracking, maintenance workflows, and tenant communication tools for property managers.',
    capacity: 200,
    spots: 47,
    accentClass: 'from-blue-500 via-indigo-500 to-violet-500',
    tagColor: 'bg-blue-50 text-blue-700 ring-blue-700/20',
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-7 w-7">
        <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21" />
      </svg>
    ),
  },
  {
    id: 'workshop-elearning-june',
    title: 'Building Your E-learning Platform',
    date: '2026-06-03T09:00:00',
    displayDate: 'Wednesday, 3 June 2026',
    time: '9:00 AM – 1:00 PM WAT',
    location: 'Hybrid — Abuja + Online',
    type: 'Workshop',
    description:
      'A hands-on session walking through course design, learner analytics dashboards, and the technical setup of a Starmax-powered e-learning environment for institutions.',
    capacity: 60,
    spots: 18,
    accentClass: 'from-violet-500 via-purple-500 to-indigo-500',
    tagColor: 'bg-violet-50 text-violet-700 ring-violet-700/20',
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-7 w-7">
        <path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
      </svg>
    ),
  },
  {
    id: 'showcase-custom-systems-july',
    title: 'Custom Business Systems Showcase',
    date: '2026-07-22T14:00:00',
    displayDate: 'Wednesday, 22 July 2026',
    time: '2:00 PM – 3:30 PM WAT',
    location: 'Online (Google Meet)',
    type: 'Showcase',
    description:
      'See live demos of Starmax custom portals and business dashboards — from workflow automation to advanced reporting tools tailored to specific operational needs.',
    capacity: 150,
    spots: 102,
    accentClass: 'from-cyan-400 via-blue-500 to-indigo-500',
    tagColor: 'bg-cyan-50 text-cyan-700 ring-cyan-700/20',
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-7 w-7">
        <path strokeLinecap="round" strokeLinejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    ),
  },
];

function SpotsBar({ spots, capacity }: { spots: number; capacity: number }) {
  const pct = Math.round(((capacity - spots) / capacity) * 100);
  const low = spots <= 20;
  return (
    <div>
      <div className="flex items-center justify-between text-[11px]">
        <span className="flex items-center gap-1.5 font-medium text-zinc-400">
          <UsersIcon />
          {spots} spots left
        </span>
        <span className="text-zinc-600">{capacity - spots}/{capacity} registered</span>
      </div>
      <div className="mt-1.5 h-1 w-full overflow-hidden rounded-full bg-zinc-800">
        <div
          className={`h-full rounded-full transition-all duration-500 ${low ? 'bg-rose-500' : 'bg-gradient-to-r from-blue-500 to-indigo-500'}`}
          style={{ width: `${pct}%` }}
        />
      </div>
      {low && (
        <p className="mt-1 flex items-center gap-1.5 text-[11px] font-semibold text-rose-400">
          <span className="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-rose-500" />
          Almost full!
        </p>
      )}
    </div>
  );
}

function FeaturedEventCard({ event, onRegister }: { event: Event; onRegister: (e: Event) => void }) {
  const d = new Date(event.date);
  const month = d.toLocaleString('default', { month: 'short' }).toUpperCase();
  const day = d.getDate();
  const year = d.getFullYear();

  return (
    <article className="group overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm transition duration-300 hover:shadow-2xl hover:border-indigo-200">
      <div className="flex flex-col md:flex-row">
        {/* Left gradient panel */}
        <div className={`relative flex flex-col items-center justify-center gap-8 bg-gradient-to-br ${event.accentClass} p-10 text-white md:w-72 md:shrink-0`}>
          <div
            className="absolute inset-0 opacity-[0.07]"
            style={{ backgroundImage: 'radial-gradient(circle, white 1px, transparent 1px)', backgroundSize: '22px 22px' }}
          />
          {/* Date block */}
          <div className="relative flex flex-col items-center rounded-2xl bg-white/15 px-8 py-5 ring-1 ring-white/25 backdrop-blur-sm">
            <span className="text-xs font-bold uppercase tracking-[0.3em] text-white/70">{month}</span>
            <span className="text-6xl font-black leading-none text-white">{day}</span>
            <span className="mt-1 text-xs font-medium text-white/60">{year}</span>
          </div>
          {/* Icon + type badge */}
          <div className="relative flex flex-col items-center gap-3">
            <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30 shadow-inner">
              {event.icon}
            </div>
            <span className="inline-flex items-center rounded-full bg-white/20 px-3.5 py-1 text-xs font-bold uppercase tracking-wider text-white ring-1 ring-white/30">
              {event.type}
            </span>
          </div>
        </div>

        {/* Right content */}
        <div className="flex flex-1 flex-col p-7 sm:p-9">
          <div>
            <p className="text-xs font-bold uppercase tracking-[0.25em] text-indigo-500">Featured Event</p>
            <h2 className="mt-2 text-2xl font-bold leading-tight text-zinc-900 sm:text-3xl">{event.title}</h2>
            {/* Meta pills */}
            <div className="mt-5 flex flex-wrap gap-2">
              <span className="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-600">
                <CalendarIcon /> {event.displayDate}
              </span>
              <span className="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-600">
                <ClockIcon /> {event.time}
              </span>
              <span className="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-600">
                <MapPinIcon /> {event.location}
              </span>
            </div>
            <p className="mt-5 text-sm leading-7 text-zinc-600">{event.description}</p>
          </div>

          {/* Countdown box */}
          <div className="mt-6 rounded-2xl border border-zinc-100 bg-zinc-50 p-5">
            <p className="mb-3 text-xs font-bold uppercase tracking-[0.25em] text-zinc-400">Time until event</p>
            <EventCountdown targetDate={event.date} accentClass={event.accentClass} />
          </div>

          {/* Spots */}
          <div className="mt-5">
            <SpotsBar spots={event.spots} capacity={event.capacity} />
          </div>

          {/* CTA */}
          <div className="mt-6">
            <button
              type="button"
              onClick={() => onRegister(event)}
              className={`inline-flex items-center gap-2 rounded-full bg-gradient-to-r ${event.accentClass} px-7 py-3 text-sm font-semibold text-white shadow-lg transition duration-200 hover:scale-[1.02] hover:shadow-xl`}
            >
              Reserve your spot
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5} className="h-4 w-4">
                <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </article>
  );
}

function EventCard({ event, onRegister }: { event: Event; onRegister: (e: Event) => void }) {
  const d = new Date(event.date);
  const month = d.toLocaleString('default', { month: 'short' }).toUpperCase();
  const day = d.getDate();

  return (
    <article className="group flex flex-col overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900 shadow-lg transition duration-300 hover:-translate-y-1 hover:border-zinc-700 hover:shadow-2xl">
      {/* Gradient header */}
      <div className={`relative bg-gradient-to-br ${event.accentClass} p-5`}>
        <div
          className="absolute inset-0 opacity-[0.08]"
          style={{ backgroundImage: 'radial-gradient(circle, white 1px, transparent 1px)', backgroundSize: '18px 18px' }}
        />
        <div className="relative flex items-start justify-between">
          <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/20 text-white ring-1 ring-white/30 shadow">
            {event.icon}
          </div>
          <div className="text-right">
            <span className="inline-flex items-center rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white ring-1 ring-white/30">
              {event.type}
            </span>
            <div className="mt-1.5 flex flex-col items-end leading-none">
              <span className="text-3xl font-black text-white">{day}</span>
              <span className="text-[10px] font-bold uppercase tracking-widest text-white/70">{month}</span>
            </div>
          </div>
        </div>
      </div>

      {/* Body */}
      <div className="flex flex-1 flex-col px-5 pt-4 pb-5">
        <h2 className="text-sm font-bold leading-snug text-white">{event.title}</h2>
        <div className="mt-2 flex flex-col gap-1 text-[11px] text-zinc-400">
          <span className="flex items-center gap-1.5"><ClockIcon /> {event.time}</span>
          <span className="flex items-center gap-1.5"><MapPinIcon /> {event.location}</span>
        </div>
        <p className="mt-2.5 line-clamp-2 text-xs leading-5 text-zinc-500">{event.description}</p>

        {/* Countdown */}
        <div className="mt-3 rounded-xl border border-zinc-800 bg-zinc-950 p-3">
          <p className="mb-2 text-[10px] font-bold uppercase tracking-[0.25em] text-zinc-600">Time until event</p>
          <EventCountdown targetDate={event.date} accentClass={event.accentClass} />
        </div>

        {/* Footer */}
        <div className="mt-auto pt-4">
          <SpotsBar spots={event.spots} capacity={event.capacity} />
          <button
            type="button"
            onClick={() => onRegister(event)}
            className={`mt-3.5 flex w-full items-center justify-center rounded-full bg-gradient-to-r ${event.accentClass} py-2 text-xs font-semibold text-white shadow transition duration-200 hover:scale-[1.01] hover:shadow-md`}
          >
            Register
          </button>
        </div>
      </div>
    </article>
  );
}

export function EventsClientSection() {
  const [selectedEvent, setSelectedEvent] = useState<Event | null>(null);

  return (
    <>
      <section className="bg-white px-6 py-16">
        <div className="mx-auto max-w-6xl">
        {/* All events */}
        <div className="mb-8">
          <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">All upcoming events</p>
          <h2 className="mt-2 text-2xl font-semibold text-zinc-900">What's on the calendar</h2>
        </div>
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {events.map((event) => (
            <EventCard key={event.id} event={event} onRegister={setSelectedEvent} />
          ))}
        </div>

        {/* CTA banner */}
        <div className="mt-14 overflow-hidden rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-violet-50 p-8 shadow-sm md:p-10">
          <div className="grid gap-6 md:grid-cols-[1fr_auto] md:items-center">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-500">Stay in the loop</p>
              <h3 className="mt-2 text-2xl font-semibold text-zinc-900">Never miss a Starmax event</h3>
              <p className="mt-2 text-sm leading-6 text-zinc-600">
                Want event notifications, early-access spots, or a private walkthrough for your team? Drop us a message.
              </p>
            </div>
            <div className="flex gap-3">
              <a
                href="/contact"
                className="rounded-full bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500 px-5 py-3 text-sm font-semibold text-white shadow transition hover:scale-[1.02]"
              >
                Contact us
              </a>
              <a
                href="/contact"
                className="rounded-full border border-zinc-300 px-5 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100"
              >
                Send brief
              </a>
            </div>
          </div>
        </div>
        </div>
      </section>

      {selectedEvent && (
        <EventRegistrationModal
          eventTitle={selectedEvent.title}
          eventDate={`${selectedEvent.displayDate} · ${selectedEvent.time}`}
          eventLocation={selectedEvent.location}
          accentClass={selectedEvent.accentClass}
          onClose={() => setSelectedEvent(null)}
        />
      )}
    </>
  );
}
