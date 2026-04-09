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

      const data = (await response.json()) as { message?: string };

      if (!response.ok) {
        throw new Error(data.message || 'Unable to submit your message.');
      }

      setForm(initialState);
      setResult(data.message || 'Message sent successfully.');
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Submission failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form
      onSubmit={submit}
      className="tp-form-panel"
      autoComplete="off"
      suppressHydrationWarning
    >
      <div>
        <h3 className="tp-form-title">Send a message</h3>
        <p className="mt-1 text-sm text-zinc-600">Send us a message and the Starmax team will follow up shortly.</p>
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-zinc-800">Name</label>
        <input
          className="tp-input"
          value={form.name}
          onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))}
          required
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-zinc-800">Email</label>
        <input
          type="email"
          className="tp-input"
          value={form.email}
          onChange={(event) => setForm((current) => ({ ...current, email: event.target.value }))}
          required
        />
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-zinc-800">Message</label>
        <textarea
          className="tp-textarea min-h-32"
          value={form.message}
          onChange={(event) => setForm((current) => ({ ...current, message: event.target.value }))}
          required
        />
      </div>

      <button
        type="submit"
        disabled={loading}
        className="tp-primary-btn disabled:opacity-60"
        suppressHydrationWarning
      >
        {loading ? 'Sending...' : 'Send message'}
      </button>

      {result ? <p className="text-sm text-emerald-700">{result}</p> : null}
      {error ? <p className="text-sm text-red-600">{error}</p> : null}
    </form>
  );
}
