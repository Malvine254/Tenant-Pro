"use client";

import { useRouter, useSearchParams } from 'next/navigation';
import { useEffect } from 'react';

export default function MaintenancePage() {
  const router = useRouter();
  const searchParams = useSearchParams();

  useEffect(() => {
    const query = searchParams.toString();
    router.replace(`/dashboard/messages${query ? `?${query}` : ''}`);
  }, [router, searchParams]);

  return (
    <main className="mx-auto flex h-[calc(100dvh-6.5rem)] w-full max-w-7xl items-center justify-center text-gray-700 lg:h-[calc(100dvh-4rem)]">
      <div className="rounded-2xl border border-gray-200 bg-white px-6 py-5 text-sm shadow-sm">
        Redirecting you to the unified operations hub…
      </div>
    </main>
  );
}
