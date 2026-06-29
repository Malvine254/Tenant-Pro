"use client";

import { useEffect, useState } from 'react';
import Link from 'next/link';

interface Counts {
  blog: number;
  projects: number;
  services: number;
  testimonials: number;
  contacts: number;
}

const SECTIONS = [
  { key: 'blog', label: 'Blog Posts', icon: '✦', href: '/admin/dashboard/content?tab=blog', color: 'from-blue-600 to-indigo-600' },
  { key: 'projects', label: 'Portfolio Projects', icon: '⬡', href: '/admin/dashboard/content?tab=projects', color: 'from-violet-600 to-purple-600' },
  { key: 'services', label: 'Services', icon: '◈', href: '/admin/dashboard/content?tab=services', color: 'from-emerald-600 to-teal-600' },
  { key: 'testimonials', label: 'Testimonials', icon: '❝', href: '/admin/dashboard/content?tab=testimonials', color: 'from-amber-500 to-orange-500' },
  { key: 'contacts', label: 'Contact Submissions', icon: '✉', href: '/admin/dashboard/contacts', color: 'from-rose-600 to-pink-600' },
] as const;

export default function StarmaxAdminDashboardPage() {
  const [counts, setCounts] = useState<Counts>({ blog: 0, projects: 0, services: 0, testimonials: 0, contacts: 0 });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const types: (keyof Omit<Counts, 'contacts'>)[] = ['blog', 'projects', 'services', 'testimonials'];
    Promise.all([
      ...types.map((t) => fetch(`/api/content/${t}/`).then((r) => r.json()).catch(() => [])),
      fetch('/api/contact/').then((r) => r.json()).catch(() => []),
    ]).then(([blog, projects, services, testimonials, contacts]) => {
      setCounts({
        blog: Array.isArray(blog) ? blog.length : 0,
        projects: Array.isArray(projects) ? projects.length : 0,
        services: Array.isArray(services) ? services.length : 0,
        testimonials: Array.isArray(testimonials) ? testimonials.length : 0,
        contacts: Array.isArray(contacts) ? contacts.length : 0,
      });
      setLoading(false);
    });
  }, []);

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-white">Website Overview</h1>
        <p className="mt-1 text-sm text-white/50">
          Manage all public-facing content on the Starmax company website.
        </p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {SECTIONS.map((section) => (
          <Link
            key={section.key}
            href={section.href}
            className="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:bg-white/10 hover:border-white/20"
          >
            <div className={`absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r ${section.color}`} />
            <div className="flex items-start justify-between">
              <div>
                <p className="text-xs font-semibold uppercase tracking-widest text-white/40">{section.label}</p>
                <p className="mt-2 text-3xl font-bold text-white">
                  {loading ? <span className="inline-block h-8 w-10 animate-pulse rounded-lg bg-white/10" /> : counts[section.key as keyof Counts]}
                </p>
                <p className="mt-1 text-xs text-white/40">
                  {counts[section.key as keyof Counts] === 1 ? 'item' : 'items'} published
                </p>
              </div>
              <span className={`flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br ${section.color} text-lg shadow-lg`}>
                {section.icon}
              </span>
            </div>
            <p className="mt-4 text-xs text-white/40 group-hover:text-white/60 transition">
              Manage → 
            </p>
          </Link>
        ))}
      </div>

      <div className="mt-8 rounded-2xl border border-white/10 bg-white/5 p-5">
        <p className="text-sm font-semibold text-white">Quick actions</p>
        <div className="mt-3 flex flex-wrap gap-2">
          <Link href="/admin/dashboard/content?tab=blog" className="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs text-white/60 hover:bg-white/10 hover:text-white transition">
            + New blog post
          </Link>
          <Link href="/admin/dashboard/content?tab=projects" className="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs text-white/60 hover:bg-white/10 hover:text-white transition">
            + New portfolio project
          </Link>
          <Link href="/admin/dashboard/content?tab=testimonials" className="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs text-white/60 hover:bg-white/10 hover:text-white transition">
            + New testimonial
          </Link>
          <Link href="/" target="_blank" rel="noopener noreferrer" className="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs text-white/60 hover:bg-white/10 hover:text-white transition">
            View website ↗
          </Link>
        </div>
      </div>
    </div>
  );
}
