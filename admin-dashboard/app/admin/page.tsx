"use client";

import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { getStarmaxAdminSession, saveStarmaxAdminSession } from '../../lib/auth';

type AdminAuthResponse = {
  accessToken: string;
  user: {
    id: string;
    phoneNumber: string;
    email?: string | null;
    firstName?: string | null;
    lastName?: string | null;
    role: 'LANDLORD' | 'TENANT' | 'ADMIN' | 'CARETAKER';
  };
};

export default function StarmaxAdminLoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (getStarmaxAdminSession()) {
      router.replace('/admin/dashboard');
    }
  }, [router]);

  const handleLogin = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const res = await fetch('/api/admin/auth/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: email.trim().toLowerCase(),
          password,
        }),
      });

      const data = await res.json() as AdminAuthResponse & { message?: string };

      if (!res.ok) {
        setError(data.message ?? 'Invalid credentials.');
        return;
      }

      saveStarmaxAdminSession({ accessToken: data.accessToken, user: data.user });
      router.push('/admin/dashboard');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Login failed. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex flex-col" style={{ background: 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%)' }}>
      {/* Top bar */}
      <div className="border-b border-white/10 px-6 py-4 flex items-center justify-between">
        <Link href="/" className="flex items-center gap-2.5 text-white">
          <span className="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 via-indigo-500 to-violet-500 text-sm font-bold shadow-lg shadow-blue-500/20">
            S
          </span>
          <span>
            <span className="block text-sm font-semibold leading-none">Starmax</span>
            <span className="block text-xs text-white/50 leading-none mt-0.5">Website Administration</span>
          </span>
        </Link>
        <Link href="/" className="text-xs text-white/40 hover:text-white/70 transition">
          ← Back to website
        </Link>
      </div>

      <main className="flex flex-1 items-center justify-center px-6 py-12">
        <div className="w-full max-w-sm">
          <div className="mb-8 text-center">
            <div className="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-500 to-violet-500 shadow-2xl shadow-indigo-500/30 text-3xl font-black text-white select-none">
              S
            </div>
            <h1 className="text-2xl font-bold text-white">Starmax Website Admin</h1>
            <p className="mt-1.5 text-sm text-white/50">
              Manage blog posts, portfolio, services & testimonials
            </p>
          </div>

          <div className="rounded-2xl border border-white/10 bg-white/5 p-6 shadow-2xl backdrop-blur-sm">
            <form onSubmit={(e) => void handleLogin(e)} className="space-y-4">
              <div>
                <label className="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">
                  Email address
                </label>
                <input
                  type="email"
                  required
                  autoComplete="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="admin@example.com"
                  className="w-full rounded-xl border border-white/10 bg-white/10 px-3.5 py-2.5 text-sm text-white placeholder:text-white/30 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400 transition"
                />
              </div>
              <div>
                <label className="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">
                  Password
                </label>
                <input
                  type="password"
                  required
                  autoComplete="current-password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full rounded-xl border border-white/10 bg-white/10 px-3.5 py-2.5 text-sm text-white placeholder:text-white/30 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-400 transition"
                />
              </div>

              {error && (
                <div className="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-xs text-red-300">
                  {error}
                </div>
              )}

              <button
                type="submit"
                disabled={loading}
                className="w-full rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/25 transition hover:from-blue-700 hover:to-violet-700 disabled:opacity-50"
              >
                {loading ? 'Signing in…' : 'Sign in to Website Admin'}
              </button>
            </form>

            <div className="mt-5 border-t border-white/10 pt-4">
              <p className="text-center text-xs text-white/30">
                Looking for Tenant Pro operations?{' '}
                <Link href="/login" className="text-indigo-400 hover:text-indigo-300 transition">
                  Sign in here →
                </Link>
              </p>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
}
