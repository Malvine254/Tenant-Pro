"use client";

import { FormEvent, useState } from 'react';
import { serviceOptions } from '../../lib/company-data';

type RequirementFormState = {
  name: string;
  companyName: string;
  email: string;
  phone: string;
  serviceInterest: string;
  propertyCount: string;
  timeline: string;
  projectGoals: string;
};

const initialState: RequirementFormState = {
  name: '',
  companyName: '',
  email: '',
  phone: '',
  serviceInterest: serviceOptions[0],
  propertyCount: '',
  timeline: '',
  projectGoals: '',
};

export function CompanyRequirementsForm() {
  const [form, setForm] = useState<RequirementFormState>(initialState);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const updateField = (field: keyof RequirementFormState, value: string) => {
    setForm((current) => ({ ...current, [field]: value }));
  };

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setLoading(true);
    setError(null);
    setSuccess(null);

    try {
      const response = await fetch('/api/requirements', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });

      const result = (await response.json()) as { message?: string };

      if (!response.ok) {
        throw new Error(result.message || 'Unable to submit your project brief right now.');
      }

      setSuccess(result.message || 'Your requirements were captured successfully.');
      setForm(initialState);
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Submission failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form
      onSubmit={handleSubmit}
      className="tp-form-panel"
      autoComplete="off"
      suppressHydrationWarning
    >
      <div>
        <h3 className="tp-form-title">Project requirements form</h3>
        <p className="mt-1 text-sm text-zinc-600">
          Share your current need and Starmax will capture it for prompt follow-up.
        </p>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <div>
          <label className="mb-1 block text-sm font-medium text-zinc-800">Your name</label>
          <input className="tp-input" value={form.name} onChange={(e) => updateField('name', e.target.value)} required />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-zinc-800">Company / property brand</label>
          <input className="tp-input" value={form.companyName} onChange={(e) => updateField('companyName', e.target.value)} />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-zinc-800">Email</label>
          <input type="email" className="tp-input" value={form.email} onChange={(e) => updateField('email', e.target.value)} required />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-zinc-800">Phone</label>
          <input className="tp-input" value={form.phone} onChange={(e) => updateField('phone', e.target.value)} />
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-zinc-800">Primary service</label>
          <select className="tp-select" value={form.serviceInterest} onChange={(e) => updateField('serviceInterest', e.target.value)}>
            {serviceOptions.map((option) => (
              <option key={option} value={option}>{option}</option>
            ))}
          </select>
        </div>
        <div>
          <label className="mb-1 block text-sm font-medium text-zinc-800">Approx. unit count</label>
          <input className="tp-input" value={form.propertyCount} onChange={(e) => updateField('propertyCount', e.target.value)} placeholder="e.g. 48 units" />
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <div>
          <label className="mb-1 block text-sm font-medium text-zinc-800">Preferred timeline</label>
          <input className="tp-input" value={form.timeline} onChange={(e) => updateField('timeline', e.target.value)} placeholder="e.g. This month" />
        </div>
      </div>

      <div>
        <label className="mb-1 block text-sm font-medium text-zinc-800">Project goals / requirements</label>
        <textarea
          className="tp-textarea min-h-32"
          value={form.projectGoals}
          onChange={(e) => updateField('projectGoals', e.target.value)}
          placeholder="Tell Starmax what you need: tenant support, maintenance, billing, reporting, or a full property operations setup."
          required
        />
      </div>

      <button
        type="submit"
        disabled={loading}
        className="tp-primary-btn disabled:opacity-60"
        suppressHydrationWarning
      >
        {loading ? 'Saving brief...' : 'Submit requirements'}
      </button>

      {success ? <p className="text-sm text-emerald-700">{success}</p> : null}
      {error ? <p className="text-sm text-red-600">{error}</p> : null}
    </form>
  );
}
