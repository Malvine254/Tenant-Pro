"use client";

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { getStarmaxAdminSession } from '../../../../lib/auth';

interface Submission {
  id: string;
  name: string;
  email: string;
  message: string;
  createdAt: string;
}

export default function ContactSubmissionsPage() {
  const router = useRouter();
  const [items, setItems] = useState<Submission[]>([]);
  const [loading, setLoading] = useState(true);
  const [expanded, setExpanded] = useState<string | null>(null);

  useEffect(() => {
    if (!getStarmaxAdminSession()) { router.replace('/admin'); return; }

    fetch('/api/contact/')
      .then((r) => r.json())
      .then((d: unknown) => {
        // route returns { count, items } or a plain array
        if (Array.isArray(d)) setItems(d as Submission[]);
        else if (d && typeof d === 'object' && Array.isArray((d as { items?: unknown }).items)) {
          setItems((d as { items: Submission[] }).items);
        }
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, [router]);

  const sorted = [...items].sort(
    (a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
  );

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-white">Contact Submissions</h1>
        <p className="mt-1 text-sm text-white/50">Messages sent via the public contact form.</p>
      </div>

      {loading ? (
        <div className="space-y-3">
          {[1, 2, 3].map((i) => <div key={i} className="h-16 animate-pulse rounded-2xl bg-white/5" />)}
        </div>
      ) : sorted.length === 0 ? (
        <div className="rounded-2xl border border-white/10 bg-white/5 py-16 text-center">
          <p className="text-3xl opacity-20">✉</p>
          <p className="mt-3 text-sm text-white/30">No contact submissions yet.</p>
        </div>
      ) : (
        <div className="space-y-2">
          {sorted.map((item) => (
            <div
              key={item.id}
              className="overflow-hidden rounded-2xl border border-white/10 bg-white/5 transition hover:bg-white/[0.07]"
            >
              <button
                type="button"
                className="flex w-full items-center justify-between px-5 py-4 text-left"
                onClick={() => setExpanded((v) => (v === item.id ? null : item.id))}
              >
                <div className="flex items-center gap-4 min-w-0">
                  <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-violet-600 text-sm font-bold text-white select-none">
                    {item.name.charAt(0).toUpperCase()}
                  </div>
                  <div className="min-w-0">
                    <p className="font-semibold text-white truncate">{item.name}</p>
                    <p className="text-xs text-white/40 truncate">{item.email}</p>
                  </div>
                </div>
                <div className="flex items-center gap-4 shrink-0 ml-4">
                  <p className="hidden text-xs text-white/30 sm:block">
                    {new Date(item.createdAt).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                  </p>
                  <span className={`text-white/40 transition ${expanded === item.id ? 'rotate-180' : ''}`}>▾</span>
                </div>
              </button>

              {expanded === item.id && (
                <div className="border-t border-white/10 px-5 py-4">
                  <p className="text-xs text-white/30 mb-2">
                    {new Date(item.createdAt).toLocaleString('en-GB', { dateStyle: 'full', timeStyle: 'short' })}
                  </p>
                  <p className="text-sm text-white/80 leading-7 whitespace-pre-wrap">{item.message}</p>
                  <a
                    href={`mailto:${item.email}?subject=Re: Your enquiry`}
                    className="mt-4 inline-flex items-center gap-1.5 rounded-xl border border-indigo-500/30 bg-indigo-500/10 px-4 py-2 text-xs font-semibold text-indigo-300 hover:bg-indigo-500/20 transition"
                  >
                    Reply via email →
                  </a>
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
