"use client";

import { useEffect, useState } from 'react';
import { usePathname, useRouter, useSearchParams } from 'next/navigation';
import { Sidebar } from '../../components/sidebar';
import { clearDemoSession, getDemoSession, getSession } from '../../lib/auth';

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';
  const [ready, setReady] = useState(false);
  const [sidebarOpen, setSidebarOpen] = useState(false);

  useEffect(() => {
    if (isDemoMode) {
      const demoSession = getDemoSession();
      if (!demoSession) {
        router.replace('/login?mode=demo');
        return;
      }

      setReady(true);
      return;
    }

    clearDemoSession();

    const session = getSession();
    if (!session) {
      router.replace('/login');
      return;
    }

    setReady(true);
  }, [isDemoMode, pathname, router]);

  useEffect(() => {
    setSidebarOpen(false);
  }, [pathname, searchParams]);

  if (!ready) {
    return <div className="p-6 text-sm text-gray-600">Loading dashboard...</div>;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur lg:hidden">
        <div className="flex items-center justify-between px-4 py-3">
          <button
            type="button"
            onClick={() => setSidebarOpen(true)}
            className="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100"
          >
            Menu
          </button>
          <h1 className="text-sm font-semibold text-gray-900">{isDemoMode ? 'Tenant Pro Demo' : 'Tenant Pro Admin'}</h1>
          <div className="w-14" />
        </div>
      </header>

      <div className="mx-auto flex w-full max-w-[1800px]">
        <Sidebar isOpen={sidebarOpen} onClose={() => setSidebarOpen(false)} />
        <section className="min-w-0 flex-1 p-4 sm:p-6 lg:p-8">{children}</section>
      </div>
    </div>
  );
}
