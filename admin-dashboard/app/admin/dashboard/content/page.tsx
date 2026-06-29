"use client";

import { useCallback, useEffect, useRef, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { getStarmaxAdminSession } from '../../../../lib/auth';

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
    label: 'Blog Posts', icon: '✦', rowLabel: 'title',
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
    label: 'Portfolio Projects', icon: '⬡', rowLabel: 'title',
    fields: [
      { key: 'title', label: 'Title', type: 'text', placeholder: 'Project name' },
      { key: 'description', label: 'Description', type: 'textarea', placeholder: 'Short description...' },
      { key: 'category', label: 'Category', type: 'select', options: ['Property Tech', 'E-learning', 'Digital Strategy', 'Web', 'Mobile', 'Business Systems', 'EdTech'] },
      { key: 'image', label: 'Image URL', type: 'url', placeholder: '/assets/...' },
    ],
  },
  services: {
    label: 'Services', icon: '◈', rowLabel: 'title',
    fields: [
      { key: 'title', label: 'Title', type: 'text', placeholder: 'Service name' },
      { key: 'description', label: 'Description', type: 'textarea', placeholder: 'What this service covers...' },
      { key: 'icon', label: 'Icon Key', type: 'select', options: ['building', 'learning', 'code', 'android', 'grid', 'consulting'] },
      { key: 'link', label: 'Link', type: 'text', placeholder: '/services' },
    ],
  },
  testimonials: {
    label: 'Testimonials', icon: '❝', rowLabel: 'name',
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

const inputCls = (type: Field['type']) => {
  const base = 'w-full rounded-xl border border-white/10 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder:text-white/30 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400 transition';
  return type === 'textarea' ? `${base} min-h-24 resize-none` : base;
};

export default function StarmaxContentPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const initialTab = (searchParams.get('tab') as ContentType) ?? 'blog';

  const [cmsToken, setCmsToken] = useState<string | null>(null);
  const [tokenError, setTokenError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<ContentType>(
    TABS.includes(initialTab) ? initialTab : 'blog'
  );
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

  // Auth — exchange Starmax admin accessToken for CMS token
  useEffect(() => {
    const session = getStarmaxAdminSession();
    if (!session) { router.replace('/admin'); return; }

    fetch('/api/content/token/', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ accessToken: session.accessToken }),
    })
      .then(async (r) => {
        const d = await r.json() as { token?: string; message?: string };
        if (!r.ok) throw new Error(d.message ?? 'Unable to get content token.');
        setCmsToken(d.token ?? null);
      })
      .catch((e: unknown) => {
        setTokenError(e instanceof Error ? e.message : 'Authentication failed.');
      });
  }, [router]);

  const loadItems = useCallback(async (type: ContentType) => {
    setLoading(true);
    try {
      const r = await fetch(`/api/content/${type}/`);
      const d = await r.json() as unknown;
      setItems(Array.isArray(d) ? (d as Record<string, unknown>[]) : []);
    } catch { setItems([]); }
    finally { setLoading(false); }
  }, []);

  useEffect(() => { void loadItems(activeTab); }, [activeTab, loadItems]);

  const showFeedback = (type: 'ok' | 'err', msg: string) => {
    setFeedback({ type, msg });
    if (feedbackTimer.current) clearTimeout(feedbackTimer.current);
    feedbackTimer.current = setTimeout(() => setFeedback(null), 4000);
  };

  const openNew = () => {
    const blank: Record<string, string> = {};
    SCHEMAS[activeTab].fields.forEach((f) => { blank[f.key] = ''; });
    setEditing(null); setFormData(blank); setFormOpen(true);
  };

  const openEdit = (item: Record<string, unknown>) => {
    const data: Record<string, string> = {};
    SCHEMAS[activeTab].fields.forEach((f) => { data[f.key] = item[f.key] != null ? String(item[f.key]) : ''; });
    setEditing(item); setFormData(data); setFormOpen(true);
  };

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
      const d = await r.json() as { message?: string };
      if (!r.ok) throw new Error(d.message ?? 'Failed to save.');
      showFeedback('ok', isEdit ? 'Updated.' : 'Created.');
      setFormOpen(false);
      await loadItems(activeTab);
    } catch (e) { showFeedback('err', e instanceof Error ? e.message : 'Save failed.'); }
    finally { setSaving(false); }
  };

  const handleDelete = async (id: string) => {
    if (!cmsToken) { showFeedback('err', 'Not authenticated.'); return; }
    setDeleting(true);
    try {
      const r = await fetch(`/api/content/${activeTab}/?id=${encodeURIComponent(id)}`, {
        method: 'DELETE',
        headers: { Authorization: `Bearer ${cmsToken}` },
      });
      const d = await r.json() as { message?: string };
      if (!r.ok) throw new Error(d.message ?? 'Delete failed.');
      showFeedback('ok', 'Deleted.');
      setDeleteId(null);
      await loadItems(activeTab);
    } catch (e) { showFeedback('err', e instanceof Error ? e.message : 'Delete failed.'); }
    finally { setDeleting(false); }
  };

  const schema = SCHEMAS[activeTab];

  if (tokenError) {
    return (
      <div className="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-6 text-center">
        <p className="text-lg">⚠️</p>
        <p className="mt-2 font-semibold text-amber-300">Content Manager Unavailable</p>
        <p className="mt-1 text-sm text-amber-400/80">{tokenError}</p>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-white">Content Manager</h1>
        <p className="mt-1 text-sm text-white/50">Edit blog posts, portfolio, services, and testimonials shown on the public website.</p>
      </div>

      {/* Tabs */}
      <div className="mb-5 flex gap-1 border-b border-white/10 pb-0">
        {TABS.map((tab) => (
          <button
            key={tab}
            type="button"
            onClick={() => setActiveTab(tab)}
            className={`flex items-center gap-1.5 border-b-2 px-4 py-2.5 text-sm font-medium transition ${
              activeTab === tab
                ? 'border-indigo-400 text-indigo-300'
                : 'border-transparent text-white/40 hover:text-white/70'
            }`}
          >
            <span>{SCHEMAS[tab].icon}</span> {SCHEMAS[tab].label}
          </button>
        ))}
      </div>

      {/* Feedback */}
      {feedback && (
        <div className={`mb-4 flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium ${
          feedback.type === 'ok'
            ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-300'
            : 'border border-red-500/20 bg-red-500/10 text-red-300'
        }`}>
          {feedback.type === 'ok' ? '✓' : '✗'} {feedback.msg}
        </div>
      )}

      {/* Action bar */}
      <div className="mb-4 flex items-center justify-between">
        <p className="text-sm text-white/40">{loading ? 'Loading…' : `${items.length} item${items.length !== 1 ? 's' : ''}`}</p>
        <button
          type="button"
          onClick={openNew}
          disabled={!cmsToken}
          className="flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-blue-700 hover:to-violet-700 disabled:opacity-50"
        >
          + Add {schema.label.replace(/s$/, '')}
        </button>
      </div>

      {/* Table */}
      <div className="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
        {loading ? (
          <div className="space-y-3 p-4">
            {[1, 2, 3].map((i) => <div key={i} className="h-12 animate-pulse rounded-xl bg-white/5" />)}
          </div>
        ) : items.length === 0 ? (
          <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
            <span className="text-4xl opacity-20">{schema.icon}</span>
            <p className="font-semibold text-white/30">No {schema.label.toLowerCase()} yet</p>
            <button type="button" onClick={openNew} disabled={!cmsToken} className="text-sm text-indigo-400 underline underline-offset-2 hover:text-indigo-300">
              Add the first one →
            </button>
          </div>
        ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-white/10 text-left">
                <th className="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-white/30">{schema.fields[0].label}</th>
                <th className="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-white/30">
                  {activeTab === 'testimonials' ? 'Company' : activeTab === 'services' ? 'Icon' : 'Category'}
                </th>
                <th className="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-white/30">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {items.map((item, idx) => {
                const primaryKey = item['id'] as string | undefined ?? item['slug'] as string | undefined ?? String(idx);
                const primaryLabel = item[schema.rowLabel] as string ?? '—';
                const secondaryLabel = (
                  activeTab === 'testimonials' ? item['company'] :
                  activeTab === 'services' ? item['icon'] :
                  item['category']
                ) as string ?? '—';

                return (
                  <tr key={primaryKey} className="group hover:bg-white/5">
                    <td className="px-4 py-3">
                      <p className="font-medium text-white line-clamp-1">{primaryLabel}</p>
                      {!!item['slug'] && <p className="text-xs text-white/30">{item['slug'] as string}</p>}
                    </td>
                    <td className="px-4 py-3 text-white/50 text-xs">{secondaryLabel}</td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2 opacity-0 transition group-hover:opacity-100">
                        <button
                          type="button"
                          onClick={() => openEdit(item)}
                          disabled={!cmsToken}
                          className="rounded-lg border border-white/10 px-3 py-1 text-xs text-white/60 hover:border-indigo-400/40 hover:text-indigo-300 transition"
                        >
                          Edit
                        </button>
                        <button
                          type="button"
                          onClick={() => setDeleteId(primaryKey)}
                          disabled={!cmsToken}
                          className="rounded-lg border border-white/10 px-3 py-1 text-xs text-white/60 hover:border-red-400/40 hover:text-red-400 transition"
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

      {/* Add/Edit Modal */}
      {formOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
          <div className="w-full max-w-lg overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-2xl">
            <div className="flex items-center justify-between border-b border-white/10 px-6 py-4">
              <h2 className="font-bold text-white">{editing ? 'Edit' : 'New'} {schema.label.replace(/s$/, '')}</h2>
              <button type="button" onClick={() => setFormOpen(false)} className="rounded-xl p-1.5 text-white/40 hover:bg-white/10 hover:text-white">✕</button>
            </div>
            <div className="max-h-[70vh] overflow-y-auto p-6">
              <div className="space-y-4">
                {schema.fields.map((field) => (
                  <div key={field.key}>
                    <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-white/40">{field.label}</label>
                    {field.type === 'textarea' ? (
                      <textarea
                        className={inputCls('textarea')}
                        placeholder={field.placeholder}
                        value={formData[field.key] ?? ''}
                        onChange={(e) => setFormData((p) => ({ ...p, [field.key]: e.target.value }))}
                      />
                    ) : field.type === 'select' && field.options ? (
                      <select
                        className={inputCls('text')}
                        value={formData[field.key] ?? ''}
                        onChange={(e) => setFormData((p) => ({ ...p, [field.key]: e.target.value }))}
                      >
                        <option value="">Select…</option>
                        {field.options.map((opt) => <option key={opt} value={opt}>{opt}</option>)}
                      </select>
                    ) : (
                      <input
                        type={field.type}
                        className={inputCls('text')}
                        placeholder={field.placeholder}
                        value={formData[field.key] ?? ''}
                        onChange={(e) => setFormData((p) => ({ ...p, [field.key]: e.target.value }))}
                      />
                    )}
                  </div>
                ))}
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 border-t border-white/10 px-6 py-4">
              <button type="button" onClick={() => setFormOpen(false)} className="rounded-xl border border-white/10 px-4 py-2 text-sm text-white/60 hover:bg-white/5">Cancel</button>
              <button
                type="button"
                onClick={() => void handleSave()}
                disabled={saving}
                className="rounded-xl bg-gradient-to-r from-blue-600 to-violet-600 px-5 py-2 text-sm font-semibold text-white hover:from-blue-700 hover:to-violet-700 disabled:opacity-60"
              >
                {saving ? 'Saving…' : editing ? 'Save changes' : 'Create'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Delete Confirm */}
      {deleteId && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm">
          <div className="w-full max-w-sm rounded-2xl border border-white/10 bg-slate-900 p-6 shadow-2xl">
            <h2 className="font-bold text-white">Delete this item?</h2>
            <p className="mt-2 text-sm text-white/50">This action cannot be undone.</p>
            <div className="mt-5 flex gap-3">
              <button type="button" onClick={() => setDeleteId(null)} className="flex-1 rounded-xl border border-white/10 py-2 text-sm text-white/60 hover:bg-white/5">Cancel</button>
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
