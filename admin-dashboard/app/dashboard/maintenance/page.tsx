"use client";

import { useSearchParams } from 'next/navigation';
import { useEffect, useMemo, useState } from 'react';
import { apiRequest } from '../../../lib/api';
import { getSession } from '../../../lib/auth';
import { getDemoDataset, saveDemoDataset } from '../../../lib/demo-tenant-ops';

type MaintenanceStatus = 'OPEN' | 'IN_PROGRESS' | 'RESOLVED' | 'CLOSED';
type MaintenancePriority = 'LOW' | 'MEDIUM' | 'HIGH' | 'URGENT';

type RequestRow = {
  id: string;
  title: string;
  description: string;
  priority: MaintenancePriority;
  status: MaintenanceStatus;
  createdAt: string;
  unit?: {
    unitNumber?: string;
    property?: { name?: string };
  };
  tenant?: {
    user?: { firstName?: string | null; lastName?: string | null; phoneNumber?: string };
  };
  assignedTo?: {
    firstName?: string | null;
    lastName?: string | null;
    phoneNumber?: string;
  } | null;
};

const statusOptions: MaintenanceStatus[] = ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'];

export default function MaintenancePage() {
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';
  const [rows, setRows] = useState<RequestRow[]>([]);
  const [error, setError] = useState<string | null>(null);

  const loadRequests = async () => {
    try {
      if (isDemoMode) {
        setRows(getDemoDataset().maintenance as RequestRow[]);
        return;
      }

      const session = getSession();
      if (!session) return;

      const data = await apiRequest<RequestRow[]>('/maintenance', session.accessToken);
      setRows(data);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to load maintenance requests');
    }
  };

  useEffect(() => {
    void loadRequests();
  }, [isDemoMode]);

  const updateStatus = async (requestId: string, status: MaintenanceStatus) => {
    try {
      if (isDemoMode) {
        const dataset = getDemoDataset();
        dataset.maintenance = dataset.maintenance.map((item) =>
          item.id === requestId ? { ...item, status } : item,
        );
        saveDemoDataset(dataset);
        setRows(dataset.maintenance as RequestRow[]);
        return;
      }

      const session = getSession();
      if (!session) return;

      await apiRequest(`/maintenance/${requestId}/status`, session.accessToken, {
        method: 'PATCH',
        body: JSON.stringify({ status }),
      });
      await loadRequests();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to update request status');
    }
  };

  const summary = useMemo(() => ({
    total: rows.length,
    urgent: rows.filter((row) => row.priority === 'URGENT').length,
    open: rows.filter((row) => row.status === 'OPEN').length,
    inProgress: rows.filter((row) => row.status === 'IN_PROGRESS').length,
  }), [rows]);

  return (
    <main className="mx-auto flex h-[calc(100dvh-6.5rem)] w-full max-w-7xl flex-col text-gray-900 lg:h-[calc(100dvh-4rem)]">
      <section className="shrink-0 rounded-2xl border border-gray-200 bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 px-6 py-8 text-white shadow-xl">
        <h2 className="text-3xl font-bold tracking-tight">Maintenance Operations</h2>
        <p className="mt-2 max-w-2xl text-sm text-orange-50">Monitor service issues across all properties and push requests through the workflow from one admin view.</p>
        <div className="mt-4 flex flex-wrap gap-3 text-xs">
          <span className="rounded-full bg-white/15 px-3 py-1.5">{summary.total} requests</span>
          <span className="rounded-full bg-white/15 px-3 py-1.5">{summary.open} open</span>
          <span className="rounded-full bg-white/15 px-3 py-1.5">{summary.inProgress} in progress</span>
          <span className="rounded-full bg-rose-400/30 px-3 py-1.5">{summary.urgent} urgent</span>
        </div>
      </section>

      <section className="mt-6 min-h-0 flex-1 overflow-y-auto pb-8 pr-1">
        <div className="space-y-8">
          {error ? <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}

          <section className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              {rows.map((request) => (
                <article key={request.id} className="rounded-xl border border-gray-200 p-4 shadow-sm">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <h3 className="font-semibold text-gray-900">{request.title}</h3>
                      <p className="mt-1 text-sm text-gray-600">{request.unit?.property?.name ?? '-'} / Unit {request.unit?.unitNumber ?? '-'}</p>
                    </div>
                    <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${request.priority === 'URGENT' ? 'bg-rose-100 text-rose-700' : request.priority === 'HIGH' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'}`}>
                      {request.priority}
                    </span>
                  </div>

                  <p className="mt-3 text-sm text-gray-700">{request.description}</p>
                  <p className="mt-3 text-xs text-gray-500">Tenant: {[request.tenant?.user?.firstName, request.tenant?.user?.lastName].filter(Boolean).join(' ') || request.tenant?.user?.phoneNumber || 'N/A'}</p>
                  <p className="mt-1 text-xs text-gray-500">Assigned: {[request.assignedTo?.firstName, request.assignedTo?.lastName].filter(Boolean).join(' ') || request.assignedTo?.phoneNumber || 'Unassigned'}</p>

                  <div className="mt-4 flex items-center justify-between gap-3">
                    <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${request.status === 'OPEN' ? 'bg-blue-100 text-blue-700' : request.status === 'IN_PROGRESS' ? 'bg-amber-100 text-amber-700' : request.status === 'RESOLVED' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'}`}>
                      {request.status}
                    </span>
                    <select
                      value={request.status}
                      onChange={(e) => void updateStatus(request.id, e.target.value as MaintenanceStatus)}
                      className="tp-select !w-auto !px-2 !py-1.5"
                    >
                      {statusOptions.map((status) => (
                        <option key={status} value={status}>{status}</option>
                      ))}
                    </select>
                  </div>
                </article>
              ))}
            </div>
          </section>
        </div>
      </section>
    </main>
  );
}
