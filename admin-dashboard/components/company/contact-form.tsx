"use client";

import { FormEvent, useState } from 'react';

type ContactState = {
  name: string;
  email: string;
  message: string;
};

const initialState: ContactState = {
  name: '',
  email: '',
  message: '',
};

export function ContactForm() {
  const [form, setForm] = useState<ContactState>(initialState);
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setResult(null);
    setError(null);

    try {
      const response = await fetch('/api/contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });

      const text = await response.text();
      let data: { message?: string } = {};
      try { data = JSON.parse(text) as { message?: string }; } catch { /* non-JSON response */ }

      if (!response.ok) {
        throw new Error(data.message || 'Unable to submit your message. Please try again.');
      }

      setForm(initialState);
      setResult(data.message || 'Message sent successfully.');
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Submission failed.');
    } finally {
      setLoading(false);
    }
  };

  const inputClass =
    'w-full rounded-xl border border-zinc-300 bg-zinc-50 px-3.5 py-2.5 text-sm text-zinc-900 placeholder:text-zinc-400 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500';

  return (
    <form
      onSubmit={submit}
      className="flex flex-col gap-5 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm"
      autoComplete="off"
      suppressHydrationWarning
    >
      <div>
        <h3 className="text-xl font-bold text-zinc-900">Send a message</h3>
        <p className="mt-1 text-sm text-zinc-500">The Starmax team will follow up shortly.</p>
      </div>

      <div>
        <label className="mb-1.5 block text-sm font-medium text-zinc-700">Full name</label>
        <input
          className={inputClass}
          placeholder="Jane Otieno"
          value={form.name}
          onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))}
          required
        />
      </div>

      <div>
        <label className="mb-1.5 block text-sm font-medium text-zinc-700">Email address</label>
        <input
          type="email"
          className={inputClass}
          placeholder="jane@company.com"
          value={form.email}
          onChange={(event) => setForm((current) => ({ ...current, email: event.target.value }))}
          required
        />
      </div>

      <div>
        <label className="mb-1.5 block text-sm font-medium text-zinc-700">Message</label>
        <textarea
          className={`${inputClass} min-h-32 resize-none`}
          placeholder="Tell us about your project..."
          value={form.message}
          onChange={(event) => setForm((current) => ({ ...current, message: event.target.value }))}
          required
        />
      </div>

      <button
        type="submit"
        disabled={loading}
        className="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-700 hover:to-violet-700 disabled:opacity-60"
        suppressHydrationWarning
      >
        {loading ? 'Sending…' : 'Send message'}
      </button>

      {result ? (
        <p className="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
          <svg viewBox="0 0 20 20" fill="currentColor" className="h-4 w-4 shrink-0"><path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clipRule="evenodd" /></svg>
          {result}
        </p>
      ) : null}
      {error ? (
        <p className="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
          <svg viewBox="0 0 20 20" fill="currentColor" className="h-4 w-4 shrink-0"><path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clipRule="evenodd" /></svg>
          {error}
        </p>
      ) : null}
    </form>
  );
}
