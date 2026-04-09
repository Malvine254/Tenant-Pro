"use client";

import Link from 'next/link';
import { usePathname, useRouter, useSearchParams } from 'next/navigation';
import { clearDemoSession, clearSession, getDemoSession, getSession, withDashboardMode } from '../lib/auth';

const navSections = [
  {
    title: 'Overview',
    items: [
      { href: '/dashboard', label: 'Executive Dashboard', icon: '◫' },
    ],
  },
  {
    title: 'Operations',
    items: [
      { href: '/dashboard/properties', label: 'Property Management', icon: '⌂' },
      { href: '/dashboard/invoices', label: 'Invoices & Billing', icon: '◪' },
      { href: '/dashboard/maintenance', label: 'Maintenance Ops', icon: '⚒' },
      { href: '/dashboard/messages', label: 'One-on-One Chat', icon: '✉' },
    ],
  },
  {
    title: 'People & Access',
    items: [
      { href: '/dashboard/tenants', label: 'Tenant Directory', icon: '◉' },
      { href: '/dashboard/users', label: 'Users & Roles', icon: '☰' },
    ],
  },
];

type SidebarProps = {
  isOpen: boolean;
  onClose: () => void;
};

export function Sidebar({ isOpen, onClose }: SidebarProps) {
  const pathname = usePathname();
  const router = useRouter();
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';
  const session = typeof window !== 'undefined'
    ? (isDemoMode ? getDemoSession() : getSession())
    : null;

  const sections = navSections;

  const navContent = (
    <>
      <div className="mb-8 rounded-2xl bg-gradient-to-br from-slate-900 to-indigo-900 p-4 text-white shadow-lg">
        <h1 className="text-lg font-semibold">{isDemoMode ? 'Tenant Pro Demo Sandbox' : 'Tenant Pro Admin'}</h1>
        <p className="mt-2 text-sm text-slate-200">{session?.user.email ?? session?.user.phoneNumber ?? 'demo@starmax.preview'}</p>
        <p className="text-xs text-slate-300">Role: {session?.user.role ?? 'ADMIN'}</p>
        {isDemoMode ? <p className="mt-2 text-xs text-emerald-200">Full tenant-testing session enabled</p> : null}
      </div>

      <nav className="space-y-6">
        {sections.map((section) => (
          <div key={section.title} className="space-y-2">
            <p className="px-2 text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">{section.title}</p>
            <div className="space-y-1">
              {section.items.map((item) => {
                const href = withDashboardMode(item.href, isDemoMode);
                const active = pathname === item.href || (item.href === '/dashboard' && pathname === '/dashboard');
                return (
                  <Link
                    key={href}
                    href={href}
                    onClick={onClose}
                    className={`flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition ${
                      active
                        ? 'bg-slate-900 text-white shadow-sm'
                        : 'text-gray-700 hover:bg-gray-100'
                    }`}
                  >
                    <span className="text-xs opacity-80">{item.icon}</span>
                    <span>{item.label}</span>
                  </Link>
                );
              })}
            </div>
          </div>
        ))}
      </nav>

      <button
        type="button"
        onClick={() => {
          if (isDemoMode) {
            clearDemoSession();
            router.push('/');
          } else {
            clearSession();
            router.push('/login');
          }
          onClose();
        }}
        className="mt-8 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100"
      >
        {isDemoMode ? 'Exit Demo' : 'Logout'}
      </button>
    </>
  );

  return (
    <>
      <aside className="sticky top-0 hidden h-screen w-72 shrink-0 overflow-y-auto border-r border-gray-200 bg-white/95 p-5 backdrop-blur lg:block">
        {navContent}
      </aside>

      <div
        className={`fixed inset-0 z-40 bg-black/40 transition-opacity lg:hidden ${
          isOpen ? 'opacity-100' : 'pointer-events-none opacity-0'
        }`}
        onClick={onClose}
      />

      <aside
        className={`fixed inset-y-0 left-0 z-50 w-72 max-w-[86vw] overflow-y-auto border-r border-gray-200 bg-white/95 p-5 shadow-xl backdrop-blur transition-transform duration-200 lg:hidden ${
          isOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="mb-5 flex items-center justify-between">
          <h2 className="text-sm font-semibold text-gray-900">Navigation</h2>
          <button
            type="button"
            onClick={onClose}
            className="rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-100"
          >
            Close
          </button>
        </div>
        {navContent}
      </aside>
    </>
  );
}
