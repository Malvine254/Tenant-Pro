"use client";

import { useSearchParams } from 'next/navigation';
import { useEffect, useState } from 'react';
import { apiRequest } from '../../../lib/api';
import { getSession } from '../../../lib/auth';
import { getDemoDataset } from '../../../lib/demo-tenant-ops';

type InvoiceRow = {
  id: string;
  billingType: string;
  periodMonth: number;
  periodYear: number;
  dueDate: string;
  totalAmount: number | string;
  paidAmount: number | string;
  status: string;
  tenant?: {
    user?: {
      firstName?: string | null;
      lastName?: string | null;
      phoneNumber?: string;
    };
  };
  unit?: {
    unitNumber?: string;
    property?: {
      name?: string;
    };
  };
};

export default function InvoicesPage() {
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';
  const [rows, setRows] = useState<InvoiceRow[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const run = async () => {
      try {
        if (isDemoMode) {
          setRows(getDemoDataset().invoices as InvoiceRow[]);
          return;
        }

        const session = getSession();
        if (!session) return;

        const data = await apiRequest<InvoiceRow[]>('/invoices', session.accessToken);
        setRows(data);
      } catch (requestError) {
        setError(requestError instanceof Error ? requestError.message : 'Failed to load invoices');
      }
    };

    void run();
  }, [isDemoMode]);

  const totals = rows.reduce(
    (acc, invoice) => {
      acc.total += Number(invoice.totalAmount ?? 0);
      acc.paid += Number(invoice.paidAmount ?? 0);
      return acc;
    },
    { total: 0, paid: 0 },
  );

  return (
    <main className="mx-auto flex h-[calc(100dvh-6.5rem)] w-full max-w-7xl flex-col text-gray-900 lg:h-[calc(100dvh-4rem)]">
      <section className="shrink-0 rounded-2xl border border-gray-200 bg-gradient-to-r from-violet-700 via-indigo-700 to-slate-900 px-6 py-8 text-white shadow-xl">
        <h2 className="text-3xl font-bold tracking-tight">Invoices & Billing</h2>
        <p className="mt-2 max-w-2xl text-sm text-indigo-100">Track billed amounts, payments received, balances outstanding, and invoice status across all tenants and units.</p>
        <div className="mt-4 flex flex-wrap gap-3 text-xs">
          <span className="rounded-full bg-white/15 px-3 py-1.5">{rows.length} invoices</span>
          <span className="rounded-full bg-white/15 px-3 py-1.5">KES {totals.total.toLocaleString()} billed</span>
          <span className="rounded-full bg-emerald-400/20 px-3 py-1.5 text-emerald-100">KES {totals.paid.toLocaleString()} paid</span>
        </div>
      </section>

      <section className="mt-6 min-h-0 flex-1 overflow-y-auto pb-8 pr-1">
        <div className="space-y-4">
          {error ? <p className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{error}</p> : null}

          <div className="overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-sm">
            <table className="min-w-full text-sm">
              <thead className="bg-gray-50 text-left text-gray-700">
                <tr>
                  <th className="px-4 py-3">Tenant</th>
                  <th className="px-4 py-3">Property / Unit</th>
                  <th className="px-4 py-3">Type</th>
                  <th className="px-4 py-3">Period</th>
                  <th className="px-4 py-3">Total</th>
                  <th className="px-4 py-3">Paid</th>
                  <th className="px-4 py-3">Balance</th>
                  <th className="px-4 py-3">Status</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((invoice) => {
                  const total = Number(invoice.totalAmount ?? 0);
                  const paid = Number(invoice.paidAmount ?? 0);
                  const balance = Math.max(total - paid, 0);

                  return (
                    <tr key={invoice.id} className="border-t border-gray-100">
                      <td className="px-4 py-3">
                        {[
                          invoice.tenant?.user?.firstName,
                          invoice.tenant?.user?.lastName,
                        ]
                          .filter(Boolean)
                          .join(' ') || invoice.tenant?.user?.phoneNumber || '-'}
                      </td>
                      <td className="px-4 py-3">
                        {invoice.unit?.property?.name ?? '-'} / {invoice.unit?.unitNumber ?? '-'}
                      </td>
                      <td className="px-4 py-3">{invoice.billingType}</td>
                      <td className="px-4 py-3">{invoice.periodMonth}/{invoice.periodYear}</td>
                      <td className="px-4 py-3">KES {total.toLocaleString()}</td>
                      <td className="px-4 py-3">KES {paid.toLocaleString()}</td>
                      <td className="px-4 py-3">KES {balance.toLocaleString()}</td>
                      <td className="px-4 py-3">{invoice.status}</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </main>
  );
}
