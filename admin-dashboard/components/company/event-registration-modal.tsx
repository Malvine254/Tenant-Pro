'use client';

import { useEffect, useRef, useState } from 'react';

type Props = {
  eventTitle: string;
  eventDate: string;
  eventLocation: string;
  accentClass: string;
  onClose: () => void;
};

type FormState = 'idle' | 'submitting' | 'success' | 'error';

const inputCls =
  'w-full rounded-xl border border-zinc-700 bg-zinc-800 px-3 py-2.5 text-sm text-zinc-100 placeholder-zinc-500 transition duration-200 focus-visible:border-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/25';

const labelCls = 'mb-1.5 block text-xs font-semibold text-zinc-400';

export function EventRegistrationModal({ eventTitle, eventDate, eventLocation, accentClass, onClose }: Props) {
  const [form, setForm] = useState({ name: '', email: '', phone: '', organisation: '' });
  const [state, setState] = useState<FormState>('idle');
  const [errorMsg, setErrorMsg] = useState('');
  const backdropRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handler = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
    document.addEventListener('keydown', handler);
    return () => document.removeEventListener('keydown', handler);
  }, [onClose]);

  useEffect(() => {
    document.body.style.overflow = 'hidden';
    return () => { document.body.style.overflow = ''; };
  }, []);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setForm((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setState('submitting');
    setErrorMsg('');
    try {
      const res = await fetch('/api/events/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ...form,
          eventTitle,
          eventDate,
          eventLocation,
        }),
      });
      if (!res.ok) {
        const data = await res.json() as { message?: string };
        setErrorMsg(data.message ?? 'Something went wrong. Please try again.');
        setState('error');
      } else {
        setState('success');
      }
    } catch {
      setErrorMsg('Network error. Please check your connection and try again.');
      setState('error');
    }
  };

  const handleBackdropClick = (e: React.MouseEvent) => {
    if (e.target === backdropRef.current) onClose();
  };

  return (
    <div
      ref={backdropRef}
      onClick={handleBackdropClick}
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 px-4 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      aria-label={`Register for ${eventTitle}`}
    >
      <div className="relative w-full max-w-lg overflow-hidden rounded-3xl border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/60">
        {/* Coloured top bar */}
        <div className={`h-1 w-full bg-gradient-to-r ${accentClass}`} />

        <div className="px-7 pb-7 pt-6">
          {/* Header */}
          <div className="flex items-start justify-between gap-4">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.25em] text-zinc-500">Event Registration</p>
              <h2 className="mt-1 text-xl font-semibold text-white">{eventTitle}</h2>
              <p className="mt-0.5 text-sm text-zinc-400">{eventDate}</p>
            </div>
            <button
              type="button"
              onClick={onClose}
              aria-label="Close"
              className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-zinc-700 text-zinc-400 transition hover:border-zinc-600 hover:bg-zinc-800 hover:text-zinc-100"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="h-4 w-4">
                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          {state === 'success' ? (
            <div className="mt-8 flex flex-col items-center gap-4 py-6 text-center">
              <div className={`flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br ${accentClass} shadow-lg`}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5} className="h-8 w-8 text-white">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
              </div>
              <div>
                <h3 className="text-lg font-semibold text-white">You&apos;re registered!</h3>
                <p className="mt-1 text-sm text-zinc-400">
                  A confirmation email has been sent to{' '}
                  <span className="font-semibold text-zinc-200">{form.email}</span>.
                </p>
              </div>
              <button
                type="button"
                onClick={onClose}
                className={`mt-2 rounded-full bg-gradient-to-r ${accentClass} px-6 py-2.5 text-sm font-semibold text-white shadow transition hover:scale-[1.02]`}
              >
                Done
              </button>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="mt-6 space-y-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className={labelCls} htmlFor="reg-name">
                    Full name <span className="text-rose-400">*</span>
                  </label>
                  <input
                    id="reg-name"
                    name="name"
                    type="text"
                    required
                    value={form.name}
                    onChange={handleChange}
                    placeholder="Jane Doe"
                    className={inputCls}
                  />
                </div>
                <div>
                  <label className={labelCls} htmlFor="reg-email">
                    Email address <span className="text-rose-400">*</span>
                  </label>
                  <input
                    id="reg-email"
                    name="email"
                    type="email"
                    required
                    value={form.email}
                    onChange={handleChange}
                    placeholder="jane@example.com"
                    className={inputCls}
                  />
                </div>
              </div>
              <div className="grid gap-4 sm:grid-cols-2">
                <div>
                  <label className={labelCls} htmlFor="reg-phone">
                    Phone number
                  </label>
                  <input
                    id="reg-phone"
                    name="phone"
                    type="tel"
                    value={form.phone}
                    onChange={handleChange}
                    placeholder="+1 555 000 0000"
                    className={inputCls}
                  />
                </div>
                <div>
                  <label className={labelCls} htmlFor="reg-org">
                    Organisation
                  </label>
                  <input
                    id="reg-org"
                    name="organisation"
                    type="text"
                    value={form.organisation}
                    onChange={handleChange}
                    placeholder="Acme Corp"
                    className={inputCls}
                  />
                </div>
              </div>

              {state === 'error' && (
                <p className="rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-400">
                  {errorMsg || 'Something went wrong. Please try again.'}
                </p>
              )}

              <button
                type="submit"
                disabled={state === 'submitting'}
                className={`w-full rounded-full bg-gradient-to-r ${accentClass} py-3 text-sm font-semibold text-white shadow transition hover:scale-[1.01] disabled:opacity-70`}
              >
                {state === 'submitting' ? 'Submitting…' : 'Confirm Registration'}
              </button>

              <p className="text-center text-xs text-zinc-600 dark:text-zinc-500">
                Your details are only used for event communication.
              </p>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
