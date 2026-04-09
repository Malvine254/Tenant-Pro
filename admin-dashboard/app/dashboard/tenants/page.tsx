"use client";

import Link from 'next/link';
import { useEffect, useMemo, useState } from 'react';
import { getSession } from '../../../lib/auth';
import { apiRequest } from '../../../lib/api';

type UserRow = {
  id: string;
  phoneNumber: string;
  email?: string | null;
  firstName?: string | null;
  lastName?: string | null;
  role: 'LANDLORD' | 'TENANT' | 'ADMIN' | 'CARETAKER';
  isActive: boolean;
};

export default function TenantsPage() {
  const [rows, setRows] = useState<UserRow[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const run = async () => {
      const session = getSession();
      if (!session) return;

      try {
        const users = await apiRequest<UserRow[]>('/users', session.accessToken);
        setRows(users);
      } catch (requestError) {
        setError(requestError instanceof Error ? requestError.message : 'Failed to load tenants');
      }
    };

    void run();
  }, []);

  const tenants = useMemo(() => rows.filter((row) => row.role === 'TENANT'), [rows]);

  const stats = useMemo(
    () => ({
      total: tenants.length,
      active: tenants.filter((tenant) => tenant.isActive).length,
      inactive: tenants.filter((tenant) => !tenant.isActive).length,
    }),
    [tenants],
  );

  return (
    <main className="mx-auto flex h-[calc(100dvh-6.5rem)] w-full max-w-7xl flex-col text-gray-900 lg:h-[calc(100dvh-4rem)]">
      <section className="shrink-0 rounded-2xl border border-gray-200 bg-gradient-to-r from-cyan-600 via-sky-600 to-indigo-700 px-6 py-8 text-white shadow-xl">
        <h2 className="text-3xl font-bold tracking-tight">Tenant Directory</h2>
        <p className="mt-2 max-w-2xl text-sm text-cyan-50">View registered tenants across the platform and monitor who is active in the system.</p>
        <div className="mt-4 flex flex-wrap gap-3 text-xs">
          <span className="rounded-full bg-white/15 px-3 py-1.5">{stats.total} tenants</span>
          <span className="rounded-full bg-emerald-400/20 px-3 py-1.5 text-emerald-100">{stats.active} active</span>
          <span className="rounded-full bg-white/15 px-3 py-1.5">{stats.inactive} inactive</span>
        </div>
      </section>

      <section className="mt-6 min-h-0 flex-1 overflow-y-auto pb-8 pr-1">
        <div className="space-y-8">
          {error ? <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{error}</div> : null}

          <div className="overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-sm">
            <table className="min-w-full text-sm">
              <thead className="bg-gray-50 text-left text-gray-700">
                <tr>
                  <th className="px-4 py-3">Name</th>
                  <th className="px-4 py-3">Phone</th>
                  <th className="px-4 py-3">Email</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Action</th>
                </tr>
              </thead>
              <tbody>
                {tenants.map((tenant) => (
                  <tr key={tenant.id} className="border-t border-gray-100">
                    <td className="px-4 py-3">
                      <div className="font-medium text-gray-900">{[tenant.firstName, tenant.lastName].filter(Boolean).join(' ') || '-'}</div>
                    </td>
                    <td className="px-4 py-3">{tenant.phoneNumber}</td>
                    <td className="px-4 py-3">{tenant.email ?? '-'}</td>
                    <td className="px-4 py-3">
                      <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${tenant.isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
                        {tenant.isActive ? 'Active' : 'Inactive'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <Link
                        href={`/dashboard/messages?tenantId=${tenant.id}&tenantName=${encodeURIComponent([
                          tenant.firstName,
                          tenant.lastName,
                        ].filter(Boolean).join(' ') || tenant.phoneNumber)}`}
                        className="inline-flex rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800"
                      >
                        Message tenant
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </main>
  );
}
