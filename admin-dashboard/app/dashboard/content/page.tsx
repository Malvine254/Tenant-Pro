"use client";

import { useCallback, useEffect, useRef, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { getSession, getDemoSession } from '../../../lib/auth';

// ── Types ──────────────────────────────────────────────────────────────────
type ContentType = 'blog' | 'projects' | 'services' | 'testimonials';

interface Field {
  key: string;
  label: string;
  type: 'text' | 'textarea' | 'number' | 'url' | 'select';
  options?: string[];
  placeholder?: string;
}

const SCHEMAS: Record<ContentType, { label: string; icon: string; fields: Field[]; rowLabel: string }> = {
  blog: {
    label: 'Blog Posts',
    icon: '✦',
    rowLabel: 'title',
    fields: [
      { key: 'title', label: 'Title', type: 'text', placeholder: 'Post title' },
      { key: 'slug', label: 'Slug', type: 'text', placeholder: 'url-friendly-slug' },
      { key: 'excerpt', label: 'Excerpt', type: 'textarea', placeholder: 'Short summary...' },
      { key: 'category', label: 'Category', type: 'select', options: ['Property Tech', 'E-learning', 'Digital Strategy', 'Web', 'Mobile', 'Business Systems'] },
      { key: 'author', label: 'Author', type: 'text', placeholder: 'Author name' },
      { key: 'publishedAt', label: 'Published Date', type: 'text', placeholder: 'YYYY-MM-DD' },
      { key: 'image', label: 'Image URL', type: 'url', placeholder: '/assets/...' },
    ],
  },
  projects: {
    label: 'Portfolio Projects',
    icon: '⬡',
    rowLabel: 'title',
    fields: [
      { key: 'title', label: 'Title', type: 'text', placeholder: 'Project name' },
      { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Short description...' },
      { key: 'category', label: 'Category', type: 'select', options: ['Property Tech', 'E-learning', 'Digital Strategy', 'Web', 'Mobile', 'Business Systems', 'EdTech'] },
      { key: 'image', label: 'Image URL', type: 'url', placeholder: '/assets/...' },
    ],
  },
  services: {
    label: 'Services',
    icon: '◈',
    rowLabel: 'title',
    fields: [
      { key: 'title', label: 'Title', type: 'text', placeholder: 'Service name' },
      { key: 'description', label: 'Description', type: 'textarea', placeholder: 'What this service covers...' },
      { key: 'icon', label: 'Icon Key', type: 'select', options: ['building', 'learning', 'code', 'android', 'grid', 'consulting'] },
      { key: 'link', label: 'Link', type: 'text', placeholder: '/services' },
    ],
  },
  testimonials: {
    label: 'Testimonials',
    icon: '❝',
    rowLabel: 'name',
    fields: [
      { key: 'name', label: 'Name', type: 'text', placeholder: 'Client name' },
      { key: 'role', label: 'Role', type: 'text', placeholder: 'Job title' },
      { key: 'company', label: 'Company', type: 'text', placeholder: 'Company name' },
      { key: 'quote', label: 'Quote', type: 'textarea', placeholder: 'What they said...' },
      { key: 'rating', label: 'Rating (1–5)', type: 'number', placeholder: '5' },
      { key: 'avatar', label: 'Avatar URL', type: 'url', placeholder: '/assets/avatars/...' },
    ],
  },
};

const TABS: ContentType[] = ['blog', 'projects', 'services', 'testimonials'];

// ── Helpers ────────────────────────────────────────────────────────────────
function inputClass(type: Field['type']) {
  const base =
    'w-full rounded-xl border border-zinc-300 bg-zinc-50 px-3.5 py-2.5 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-indigo-500 transition';
  return type === 'textarea' ? `${base} min-h-24 resize-none` : base;
}

// ── Main Component ─────────────────────────────────────────────────────────
export default function ContentManagerPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';

  const [cmsToken, setCmsToken] = useState<string | null>(null);
  const [tokenError, setTokenError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<ContentType>('blog');
  const [items, setItems] = useState<Record<string, unknown>[]>([]);
  const [loading, setLoading] = useState(false);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<Record<string, unknown> | null>(null);
  const [formData, setFormData] = useState<Record<string, string>>({});
  const [saving, setSaving] = useState(false);
  const [deleteId, setDeleteId] = useState<string | null>(null);
  const [deleting, setDeleting] = useState(false);
  const [feedback, setFeedback] = useState<{ type: 'ok' | 'err'; msg: string } | null>(null);
  const feedbackTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // ── Auth ───────────────────────────────────────────────────────────────
  useEffect(() => {
    if (isDemoMode) {
      setTokenError('Content Manager is not available in demo mode.');
      return;
    }

    const session = getSession() ?? getDemoSession();
    if (!session) {
      router.replace('/login');
      return;
    }

    // Exchange backend access token for CMS token
    fetch('/api/content/token/', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ accessToken: session.accessToken }),
    })
      .then(async (r) => {
        const d = (await r.json()) as { token?: string; message?: string };
        if (!r.ok) throw new Error(d.message ?? 'Unable to get content token.');
        setCmsToken(d.token ?? null);
      })
      .catch((e: unknown) => {
        const msg = e instanceof Error ? e.message : 'Authentication failed.';
        // If backend is offline, fall back to direct secret approach
        if (msg.includes('backend') || msg.includes('Unable to verify')) {
          // Use a client-side fallback: sign with known session
          setTokenError('Backend offline — content editing unavailable.');
        } else {
          setTokenError(msg);
        }
      });
  }, [isDemoMode, router]);

  // ── Load items ─────────────────────────────────────────────────────────
  const loadItems = useCallback(async (type: ContentType) => {
    setLoading(true);
    try {
      const r = await fetch(`/api/content/${type}/`);
      const d = (await r.json()) as unknown;
      setItems(Array.isArray(d) ? (d as Record<string, unknown>[]) : []);
    } catch {
      setItems([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadItems(activeTab);
  }, [activeTab, loadItems]);

  // ── Feedback helper ────────────────────────────────────────────────────
  const showFeedback = (type: 'ok' | 'err', msg: string) => {
    setFeedback({ type, msg });
    if (feedbackTimer.current) clearTimeout(feedbackTimer.current);
    feedbackTimer.current = setTimeout(() => setFeedback(null), 4000);
  };

  // ── Open form ──────────────────────────────────────────────────────────
  const openNew = () => {
    const blank: Record<string, string> = {};
    SCHEMAS[activeTab].fields.forEach((f) => { blank[f.key] = ''; });
    setEditing(null);
    setFormData(blank);
    setFormOpen(true);
  };

  const openEdit = (item: Record<string, unknown>) => {
    const data: Record<string, string> = {};
    SCHEMAS[activeTab].fields.forEach((f) => {
      data[f.key] = item[f.key] != null ? String(item[f.key]) : '';
    });
    setEditing(item);
    setFormData(data);
    setFormOpen(true);
  };

  // ── Save ───────────────────────────────────────────────────────────────
  const handleSave = async () => {
    if (!cmsToken) { showFeedback('err', 'Not authenticated.'); return; }
    setSaving(true);
    try {
      const isEdit = Boolean(editing?.['id'] ?? editing?.['slug']);
      const payload = { ...formData, ...(isEdit ? { id: editing!['id'] ?? editing!['slug'] } : {}) };
      const r = await fetch(`/api/content/${activeTab}/`, {
        method: isEdit ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${cmsToken}` },
        body: JSON.stringify(payload),
      });
      const d = (await r.json()) as { message?: string };
      if (!r.ok) throw new Error(d.message ?? 'Failed to save.');
      showFeedback('ok', isEdit ? 'Updated successfully.' : 'Created successfully.');
      setFormOpen(false);
      await loadItems(activeTab);
    } catch (e) {
      showFeedback('err', e instanceof Error ? e.message : 'Save failed.');
    } finally {
      setSaving(false);
    }
  };

  // ── Delete ─────────────────────────────────────────────────────────────
  const handleDelete = async (id: string) => {
    if (!cmsToken) { showFeedback('err', 'Not authenticated.'); return; }
    setDeleting(true);
    try {
      const r = await fetch(`/api/content/${activeTab}/?id=${encodeURIComponent(id)}`, {
        method: 'DELETE',
        headers: { Authorization: `Bearer ${cmsToken}` },
      });
      const d = (await r.json()) as { message?: string };
      if (!r.ok) throw new Error(d.message ?? 'Delete failed.');
      showFeedback('ok', 'Deleted.');
      setDeleteId(null);
      await loadItems(activeTab);
    } catch (e) {
      showFeedback('err', e instanceof Error ? e.message : 'Delete failed.');
    } finally {
      setDeleting(false);
    }
  };

  const schema = SCHEMAS[activeTab];

  // ── Render ─────────────────────────────────────────────────────────────
  if (tokenError) {
    return (
      <div className="flex min-h-[50vh] items-center justify-center p-8">
        <div className="max-w-md rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center">
          <p className="text-2xl">⚠️</p>
          <p className="mt-2 font-semibold text-amber-800">Content Manager Unavailable</p>
          <p className="mt-1 text-sm text-amber-700">{tokenError}</p>
          <p className="mt-3 text-xs text-amber-600">Content can still be edited directly in the JSON data files in the <code className="rounded bg-amber-100 px-1">data/</code> folder.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="border-b border-gray-200 bg-white px-6 py-5">
        <h1 className="text-xl font-bold text-zinc-900">Website Content Manager</h1>
        <p className="mt-0.5 text-sm text-zinc-500">
          Create, edit, and delete content that appears across public-facing pages.
          All changes are saved immediately.
        </p>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200 bg-white px-6">
        <div className="flex gap-1">
          {TABS.map((tab) => (
            <button
              key={tab}
              type="button"
              onClick={() => setActiveTab(tab)}
              className={`flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition ${
                activeTab === tab
                  ? 'border-indigo-600 text-indigo-700'
                  : 'border-transparent text-zinc-500 hover:text-zinc-700'
              }`}
            >
              <span>{SCHEMAS[tab].icon}</span>
              {SCHEMAS[tab].label}
            </button>
          ))}
        </div>
      </div>

      <div className="p-6">
        {/* Feedback banner */}
        {feedback && (
          <div
            className={`mb-4 flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium ${
              feedback.type === 'ok'
                ? 'border border-emerald-200 bg-emerald-50 text-emerald-700'
                : 'border border-red-200 bg-red-50 text-red-600'
            }`}
          >
            {feedback.type === 'ok' ? '✓' : '✗'} {feedback.msg}
          </div>
        )}

        {/* Action bar */}
        <div className="mb-4 flex items-center justify-between">
          <p className="text-sm text-zinc-500">
            {loading ? 'Loading…' : `${items.length} item${items.length !== 1 ? 's' : ''}`}
          </p>
          <button
            type="button"
            onClick={openNew}
            disabled={!cmsToken}
            className="flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-700 hover:to-violet-700 disabled:opacity-50"
          >
            <span className="text-base leading-none">+</span> Add {schema.label.replace(/s$/, '')}
          </button>
        </div>

        {/* Table */}
        <div className="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
          {loading ? (
            <div className="space-y-3 p-4">
              {[1, 2, 3].map((i) => (
                <div key={i} className="h-12 animate-pulse rounded-xl bg-zinc-100" />
              ))}
            </div>
          ) : items.length === 0 ? (
            <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
              <span className="text-4xl opacity-30">{schema.icon}</span>
              <p className="font-semibold text-zinc-400">No {schema.label.toLowerCase()} yet</p>
              <button
                type="button"
                onClick={openNew}
                disabled={!cmsToken}
                className="text-sm text-indigo-600 underline underline-offset-2 hover:text-indigo-700"
              >
                Add the first one →
              </button>
            </div>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-zinc-100 bg-zinc-50 text-left">
                  <th className="px-4 py-3 font-semibold text-zinc-500">{schema.fields[0].label}</th>
                  {activeTab === 'blog' && <th className="px-4 py-3 font-semibold text-zinc-500">Category</th>}
                  {activeTab === 'projects' && <th className="px-4 py-3 font-semibold text-zinc-500">Category</th>}
                  {activeTab === 'testimonials' && <th className="px-4 py-3 font-semibold text-zinc-500">Company</th>}
                  {activeTab === 'services' && <th className="px-4 py-3 font-semibold text-zinc-500">Icon</th>}
                  <th className="px-4 py-3 font-semibold text-zinc-500">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-100">
                {items.map((item, idx) => {
                  const primaryKey = item['id'] as string | undefined ?? item['slug'] as string | undefined ?? String(idx);
                  const primaryLabel = item[schema.rowLabel] as string ?? '—';
                  const secondaryLabel = (
                    activeTab === 'blog' ? item['category'] :
                    activeTab === 'projects' ? item['category'] :
                    activeTab === 'testimonials' ? item['company'] :
                    item['icon']
                  ) as string ?? '—';

                  return (
                    <tr key={primaryKey} className="group hover:bg-zinc-50">
                      <td className="px-4 py-3">
                        <p className="font-medium text-zinc-900 line-clamp-1">{primaryLabel}</p>
                        {!!item['slug'] && (
                          <p className="text-xs text-zinc-400">{item['slug'] as string}</p>
                        )}
                      </td>
                      <td className="px-4 py-3 text-zinc-600">{secondaryLabel}</td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2 opacity-0 transition group-hover:opacity-100">
                          <button
                            type="button"
                            onClick={() => openEdit(item)}
                            disabled={!cmsToken}
                            className="rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-700 hover:border-indigo-200 hover:text-indigo-700 transition"
                          >
                            Edit
                          </button>
                          <button
                            type="button"
                            onClick={() => setDeleteId(primaryKey)}
                            disabled={!cmsToken}
                            className="rounded-lg border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-700 hover:border-red-200 hover:text-red-600 transition"
                          >
                            Delete
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          )}
        </div>

        {!cmsToken && (
          <p className="mt-3 text-center text-xs text-amber-600">
            ⚠ Connecting to content API… Make sure the backend is running on localhost:3000 for write access.
          </p>
        )}
      </div>

      {/* ── Add/Edit Modal ─────────────────────────────────────────────── */}
      {formOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
          <div className="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div className="flex items-center justify-between border-b border-zinc-200 px-6 py-4">
              <h2 className="font-bold text-zinc-900">
                {editing ? 'Edit' : 'New'} {schema.label.replace(/s$/, '')}
              </h2>
              <button
                type="button"
                onClick={() => setFormOpen(false)}
                className="rounded-xl p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700"
              >
                ✕
              </button>
            </div>
            <div className="max-h-[70vh] overflow-y-auto p-6">
              <div className="space-y-4">
                {schema.fields.map((field) => (
                  <div key={field.key}>
                    <label className="mb-1.5 block text-sm font-medium text-zinc-700">{field.label}</label>
                    {field.type === 'textarea' ? (
                      <textarea
                        className={inputClass('textarea')}
                        placeholder={field.placeholder}
                        value={formData[field.key] ?? ''}
                        onChange={(e) => setFormData((prev) => ({ ...prev, [field.key]: e.target.value }))}
                      />
                    ) : field.type === 'select' && field.options ? (
                      <select
                        className={inputClass('text')}
                        value={formData[field.key] ?? ''}
                        onChange={(e) => setFormData((prev) => ({ ...prev, [field.key]: e.target.value }))}
                      >
                        <option value="">Select…</option>
                        {field.options.map((opt) => (
                          <option key={opt} value={opt}>{opt}</option>
                        ))}
                      </select>
                    ) : (
                      <input
                        type={field.type}
                        className={inputClass('text')}
                        placeholder={field.placeholder}
                        value={formData[field.key] ?? ''}
                        onChange={(e) => setFormData((prev) => ({ ...prev, [field.key]: e.target.value }))}
                      />
                    )}
                  </div>
                ))}
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 border-t border-zinc-200 px-6 py-4">
              <button
                type="button"
                onClick={() => setFormOpen(false)}
                className="rounded-xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={() => void handleSave()}
                disabled={saving}
                className="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-700 hover:to-violet-700 disabled:opacity-60"
              >
                {saving ? 'Saving…' : editing ? 'Save changes' : 'Create'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ── Delete Confirm ─────────────────────────────────────────────── */}
      {deleteId && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
          <div className="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
            <h2 className="font-bold text-zinc-900">Delete this item?</h2>
            <p className="mt-2 text-sm text-zinc-500">This action cannot be undone.</p>
            <div className="mt-5 flex gap-3">
              <button
                type="button"
                onClick={() => setDeleteId(null)}
                className="flex-1 rounded-xl border border-zinc-200 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={() => void handleDelete(deleteId)}
                disabled={deleting}
                className="flex-1 rounded-xl bg-red-600 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60"
              >
                {deleting ? 'Deleting…' : 'Delete'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
