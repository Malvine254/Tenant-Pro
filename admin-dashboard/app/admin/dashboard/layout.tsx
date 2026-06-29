"use client";

import { useEffect, useState } from 'react';
import { usePathname, useRouter } from 'next/navigation';
import Link from 'next/link';
import { clearStarmaxAdminSession, getStarmaxAdminSession } from '../../../lib/auth';

const navItems = [
  { href: '/admin/dashboard', label: 'Overview', icon: '◫' },
  { href: '/admin/dashboard/content', label: 'Content Manager', icon: '✎' },
  { href: '/admin/dashboard/contacts', label: 'Contact Submissions', icon: '✉' },
];

export default function StarmaxAdminDashboardLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [session, setSession] = useState<ReturnType<typeof getStarmaxAdminSession>>(null);
  const [sidebarOpen, setSidebarOpen] = useState(false);

  useEffect(() => {
    const s = getStarmaxAdminSession();
    if (!s) {
      router.replace('/admin');
      return;
    }
    setSession(s);
  }, [router]);

  const handleSignOut = () => {
    clearStarmaxAdminSession();
    router.push('/admin');
  };

  if (!session) {
    return <div className="flex min-h-screen items-center justify-center bg-slate-950 text-white text-sm">Loading…</div>;
  }

  const Sidebar = (
    <aside className="flex h-full w-64 flex-col border-r border-white/10 bg-slate-950 p-4">
      {/* Brand */}
      <Link href="/" className="mb-8 flex items-center gap-2.5 text-white">
        <span className="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 via-indigo-500 to-violet-500 text-sm font-bold shadow-lg shadow-blue-500/20">
          S
        </span>
        <div>
          <p className="text-sm font-semibold leading-none">Starmax</p>
          <p className="mt-0.5 text-xs text-white/40 leading-none">Website Admin</p>
        </div>
      </Link>

      {/* User pill */}
      <div className="mb-6 rounded-xl border border-white/10 bg-white/5 px-3 py-2.5">
        <p className="text-xs font-semibold text-white truncate">{session.user.email ?? session.user.phoneNumber}</p>
        <p className="text-xs text-white/40">{session.user.role}</p>
      </div>

      {/* Nav */}
      <nav className="space-y-1 flex-1">
        <p className="mb-2 px-2 text-[10px] font-semibold uppercase tracking-widest text-white/30">Navigation</p>
        {navItems.map((item) => {
          const active = pathname === item.href;
          return (
            <Link
              key={item.href}
              href={item.href}
              onClick={() => setSidebarOpen(false)}
              className={`flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm transition ${
                active
                  ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20'
                  : 'text-white/60 hover:bg-white/5 hover:text-white'
              }`}
            >
              <span className="text-xs opacity-70">{item.icon}</span>
              {item.label}
            </Link>
          );
        })}
      </nav>

      {/* Divider links */}
      <div className="mt-6 space-y-1 border-t border-white/10 pt-4">
        <Link
          href="/"
          className="flex items-center gap-2.5 rounded-xl px-3 py-2 text-xs text-white/40 hover:text-white/70 transition"
        >
          ← View company website
        </Link>
        <button
          type="button"
          onClick={handleSignOut}
          className="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs text-red-400/70 hover:text-red-400 transition"
        >
          Sign out
        </button>
      </div>
    </aside>
  );

  return (
    <div className="min-h-screen bg-slate-900 text-white flex flex-col">
      {/* Mobile header */}
      <header className="sticky top-0 z-30 flex items-center justify-between border-b border-white/10 bg-slate-950/90 px-4 py-3 lg:hidden backdrop-blur">
        <div className="flex items-center gap-2">
          <span className="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-violet-500 text-xs font-bold">S</span>
          <span className="text-sm font-semibold">Starmax Website Admin</span>
        </div>
        <button
          type="button"
          onClick={() => setSidebarOpen((v) => !v)}
          className="rounded-lg border border-white/10 px-3 py-1.5 text-xs text-white/60"
        >
          Menu
        </button>
      </header>

      <div className="flex flex-1">
        {/* Desktop sidebar */}
        <div className="hidden lg:flex lg:flex-col lg:w-64 lg:shrink-0 lg:min-h-screen">
          {Sidebar}
        </div>

        {/* Mobile sidebar overlay */}
        {sidebarOpen && (
          <div className="fixed inset-0 z-40 lg:hidden">
            <div className="absolute inset-0 bg-black/60" onClick={() => setSidebarOpen(false)} />
            <div className="absolute left-0 top-0 bottom-0 w-64">
              {Sidebar}
            </div>
          </div>
        )}

        {/* Main content */}
        <main className="min-w-0 flex-1 p-6 lg:p-8">
          {children}
        </main>
      </div>
    </div>
  );
}
