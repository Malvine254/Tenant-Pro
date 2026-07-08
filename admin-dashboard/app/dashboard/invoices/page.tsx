"use client";

import { FormEvent, useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';
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

type TenantOption = {
  id: string;
  firstName?: string | null;
  lastName?: string | null;
  phoneNumber: string;
};

export default function InvoicesPage() {
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';
  const [rows, setRows] = useState<InvoiceRow[]>([]);
  const [tenants, setTenants] = useState<TenantOption[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [creating, setCreating] = useState(false);
  const [form, setForm] = useState({
    tenantId: '',
    billingType: 'RENT',
    amount: '',
    dueDate: '',
    periodMonth: new Date().getMonth() + 1,
    periodYear: new Date().getFullYear(),
  });

  const loadData = async () => {
    try {
      if (isDemoMode) {
        const dataset = getDemoDataset();
        setRows(dataset.invoices as InvoiceRow[]);
        setTenants(dataset.users.filter((user: any) => user.role === 'TENANT').map((user: any) => ({ id: user.id, firstName: user.firstName, lastName: user.lastName, phoneNumber: user.phoneNumber })));
        return;
      }

      const session = getSession();
      if (!session) return;

      const [invoices, users] = await Promise.all([
        apiRequest<InvoiceRow[]>('/invoices', session.accessToken),
        apiRequest<any[]>('/users', session.accessToken),
      ]);

      setRows(invoices);
      setTenants(users.filter((user) => user.role === 'TENANT').map((user) => ({ id: user.id, firstName: user.firstName, lastName: user.lastName, phoneNumber: user.phoneNumber })));
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to load invoices');
    }
  };

  useEffect(() => {
    void loadData();
  }, [isDemoMode]);

  const totals = rows.reduce(
    (acc, invoice) => {
      acc.total += Number(invoice.totalAmount ?? 0);
      acc.paid += Number(invoice.paidAmount ?? 0);
      return acc;
    },
    { total: 0, paid: 0 },
  );

  const submitInvoice = async (event: FormEvent) => {
    event.preventDefault();
    try {
      setCreating(true);
      setError(null);
      const session = getSession();
      if (!session) return;

      await apiRequest('/invoices/bills', session.accessToken, {
        method: 'POST',
        body: JSON.stringify({
          tenantId: form.tenantId,
          billingType: form.billingType,
          amount: Number(form.amount),
          dueDate: form.dueDate,
          periodMonth: Number(form.periodMonth),
          periodYear: Number(form.periodYear),
        }),
      });

      setForm((current) => ({ ...current, amount: '', dueDate: '', tenantId: '' }));
      await loadData();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to create invoice');
    } finally {
      setCreating(false);
    }
  };

  const generateMonthlyRent = async () => {
    try {
      setCreating(true);
      setError(null);
      const session = getSession();
      if (!session) return;

      await apiRequest('/invoices/generate-monthly-rent', session.accessToken, {
        method: 'POST',
        body: JSON.stringify({
          month: Number(form.periodMonth),
          year: Number(form.periodYear),
          dueDay: 5,
        }),
      });

      await loadData();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to generate rent invoices');
    } finally {
      setCreating(false);
    }
  };

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

          <section className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h3 className="text-lg font-semibold text-gray-900">Create a real bill</h3>
                <p className="mt-1 text-sm text-gray-500">Add rent, water, garbage, electricity, or other charges directly for any tenant.</p>
              </div>
              <button type="button" onClick={() => void generateMonthlyRent()} className="rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Generate monthly rent
              </button>
            </div>

            <form onSubmit={submitInvoice} className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
              <select value={form.tenantId} onChange={(event) => setForm((current) => ({ ...current, tenantId: event.target.value }))} className="rounded-xl border border-gray-300 px-3 py-2 text-sm" required>
                <option value="">Select tenant</option>
                {tenants.map((tenant) => (
                  <option key={tenant.id} value={tenant.id}>{[tenant.firstName, tenant.lastName].filter(Boolean).join(' ') || tenant.phoneNumber}</option>
                ))}
              </select>

              <select value={form.billingType} onChange={(event) => setForm((current) => ({ ...current, billingType: event.target.value }))} className="rounded-xl border border-gray-300 px-3 py-2 text-sm">
                <option value="RENT">Rent</option>
                <option value="WATER">Water</option>
                <option value="GARBAGE">Garbage</option>
                <option value="ELECTRIC">Electric</option>
                <option value="OTHER">Other</option>
              </select>

              <input type="number" min="1" step="0.01" placeholder="Amount" value={form.amount} onChange={(event) => setForm((current) => ({ ...current, amount: event.target.value }))} className="rounded-xl border border-gray-300 px-3 py-2 text-sm" required />

              <input type="number" min="1" max="12" placeholder="Month" value={form.periodMonth} onChange={(event) => setForm((current) => ({ ...current, periodMonth: Number(event.target.value) }))} className="rounded-xl border border-gray-300 px-3 py-2 text-sm" required />

              <input type="number" min="2020" max="2100" placeholder="Year" value={form.periodYear} onChange={(event) => setForm((current) => ({ ...current, periodYear: Number(event.target.value) }))} className="rounded-xl border border-gray-300 px-3 py-2 text-sm" required />

              <input type="date" value={form.dueDate} onChange={(event) => setForm((current) => ({ ...current, dueDate: event.target.value }))} className="rounded-xl border border-gray-300 px-3 py-2 text-sm md:col-span-2 xl:col-span-1" required />

              <div className="md:col-span-2 xl:col-span-1">
                <button type="submit" disabled={creating} className="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60">
                  {creating ? 'Creating…' : 'Create bill'}
                </button>
              </div>
            </form>
          </section>

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
