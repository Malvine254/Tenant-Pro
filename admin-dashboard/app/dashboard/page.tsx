"use client";

import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { useEffect, useMemo, useState } from 'react';
import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';
import { StatCard } from '../../components/stat-card';
import { clearSession, getSession } from '../../lib/auth';
import { apiRequest } from '../../lib/api';

type Revenue = { totalRevenue: number; successfulPayments: number; currency: string };
type Outstanding = {
  outstandingInvoices: number;
  outstandingBalance: number;
  currency: string;
};
type Occupancy = {
  totalUnits: number;
  occupiedUnits: number;
  vacantUnits: number;
  occupancyRate: number;
  unit: string;
};
type Trend = {
  months: number;
  currency: string;
  trends: Array<{ month: string; paymentsCount: number; totalAmount: number }>;
};

type UserRow = {
  id: string;
  role: 'LANDLORD' | 'TENANT' | 'ADMIN' | 'CARETAKER';
  isActive: boolean;
};

type PropertyRow = {
  id: string;
  units: Array<{ id: string; status: string }>;
};

type MaintenanceRow = {
  id: string;
  status: 'OPEN' | 'IN_PROGRESS' | 'RESOLVED' | 'CLOSED';
  priority: 'LOW' | 'MEDIUM' | 'HIGH' | 'URGENT';
};

export default function DashboardPage() {
  const router = useRouter();
  const [revenue, setRevenue] = useState<Revenue | null>(null);
  const [outstanding, setOutstanding] = useState<Outstanding | null>(null);
  const [occupancy, setOccupancy] = useState<Occupancy | null>(null);
  const [trend, setTrend] = useState<Trend | null>(null);
  const [users, setUsers] = useState<UserRow[]>([]);
  const [properties, setProperties] = useState<PropertyRow[]>([]);
  const [maintenance, setMaintenance] = useState<MaintenanceRow[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const run = async () => {
      const session = getSession();
      if (!session) {
        setError('Your admin session is missing. Please sign in again.');
        router.push('/login');
        return;
      }

      if (session.user.role !== 'ADMIN') {
        clearSession();
        setError('Admin access is required for this dashboard. Please sign in with +254700000099.');
        router.push('/login');
        return;
      }

      try {
        const [revenueRes, outstandingRes, occupancyRes, trendRes, usersRes, propertiesRes, maintenanceRes] = await Promise.all([
          apiRequest<Revenue>('/admin/analytics/total-revenue', session.accessToken),
          apiRequest<Outstanding>('/admin/analytics/outstanding-balances', session.accessToken),
          apiRequest<Occupancy>('/admin/analytics/occupancy-rate', session.accessToken),
          apiRequest<Trend>('/admin/analytics/monthly-payment-trends?months=12', session.accessToken),
          apiRequest<UserRow[]>('/users', session.accessToken),
          apiRequest<PropertyRow[]>('/properties', session.accessToken),
          apiRequest<MaintenanceRow[]>('/maintenance', session.accessToken),
        ]);

        setRevenue(revenueRes);
        setOutstanding(outstandingRes);
        setOccupancy(occupancyRes);
        setTrend(trendRes);
        setUsers(usersRes);
        setProperties(propertiesRes);
        setMaintenance(maintenanceRes);
      } catch (requestError) {
        const message = requestError instanceof Error
          ? requestError.message
          : 'Failed to load dashboard metrics';

        if (/Unauthorized|Forbidden/i.test(message)) {
          clearSession();
          setError('Your admin session expired or does not have access. Please sign in again with +254700000099.');
          router.push('/login');
          return;
        }

        setError(message);
      }
    };

    void run();
  }, [router]);

  const chartData = useMemo(
    () => trend?.trends?.map((item) => ({ ...item, label: item.month })) ?? [],
    [trend],
  );

  const ops = useMemo(() => {
    const totalUnits = properties.reduce((sum, property) => sum + property.units.length, 0);
    return {
      totalUsers: users.length,
      activeUsers: users.filter((user) => user.isActive).length,
      landlords: users.filter((user) => user.role === 'LANDLORD').length,
      caretakers: users.filter((user) => user.role === 'CARETAKER').length,
      totalProperties: properties.length,
      totalUnits,
      openMaintenance: maintenance.filter((item) => item.status === 'OPEN').length,
      urgentMaintenance: maintenance.filter((item) => item.priority === 'URGENT').length,
    };
  }, [maintenance, properties, users]);

  return (
    <main className="mx-auto flex h-[calc(100dvh-6.5rem)] w-full max-w-7xl flex-col text-gray-900 lg:h-[calc(100dvh-4rem)]">
      <section className="shrink-0 rounded-2xl border border-gray-200 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-900 px-6 py-8 text-white shadow-xl">
        <h2 className="text-3xl font-bold tracking-tight">Executive Control Center</h2>
        <p className="mt-2 max-w-2xl text-sm text-slate-200">Monitor finance, occupancy, user roles, maintenance, and property operations from one robust admin dashboard.</p>
        <div className="mt-5 flex flex-wrap gap-3">
          <Link href="/dashboard/users" className="rounded-xl bg-white px-4 py-2 text-sm font-medium text-slate-900 transition hover:bg-slate-100">Manage Users & Roles</Link>
          <Link href="/dashboard/properties" className="rounded-xl border border-white/20 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10">Open Properties</Link>
          <Link href="/dashboard/maintenance" className="rounded-xl border border-white/20 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10">Review Maintenance</Link>
        </div>
      </section>

      <section className="mt-6 min-h-0 flex-1 overflow-y-auto pb-8 pr-1">
        <div className="space-y-8">
          {error ? <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{error}</div> : null}

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard
              label="Total Revenue"
              value={revenue ? `${revenue.currency} ${revenue.totalRevenue.toLocaleString()}` : '...'}
              hint="Successful payments"
            />
            <StatCard
              label="Outstanding Balance"
              value={
                outstanding
                  ? `${outstanding.currency} ${outstanding.outstandingBalance.toLocaleString()}`
                  : '...'
              }
              hint={
                outstanding ? `${outstanding.outstandingInvoices} invoices pending` : 'Pending invoices'
              }
            />
            <StatCard
              label="Occupancy Rate"
              value={occupancy ? `${occupancy.occupancyRate}%` : '...'}
              hint={occupancy ? `${occupancy.occupiedUnits}/${occupancy.totalUnits} occupied` : ''}
            />
            <StatCard
              label="Successful Payments"
              value={revenue ? revenue.successfulPayments : '...'}
              hint="All-time count"
            />
          </div>

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total Users" value={ops.totalUsers} hint={`${ops.activeUsers} active accounts`} />
            <StatCard label="Properties" value={ops.totalProperties} hint={`${ops.totalUnits} units under management`} />
            <StatCard label="Open Maintenance" value={ops.openMaintenance} hint={`${ops.urgentMaintenance} urgent requests`} />
            <StatCard label="Caretakers" value={ops.caretakers} hint={`${ops.landlords} landlords in system`} />
          </div>

          <div className="grid gap-6 xl:grid-cols-[1.5fr_0.9fr]">
            <div className="min-w-0 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
              <h3 className="mb-4 text-base font-semibold">Monthly Payment Trends</h3>
              <div className="h-80 w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={chartData}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="label" />
                    <YAxis />
                    <Tooltip />
                    <Bar dataKey="totalAmount" fill="#111827" name="Total Amount" radius={[8, 8, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>

            <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
              <h3 className="text-base font-semibold">Quick System Snapshot</h3>
              <div className="mt-4 space-y-3 text-sm">
                <div className="rounded-xl bg-gray-50 px-4 py-3">
                  <div className="text-gray-500">Occupancy</div>
                  <div className="mt-1 text-lg font-semibold text-gray-900">{occupancy ? `${occupancy.occupancyRate}%` : '...'}</div>
                </div>
                <div className="rounded-xl bg-gray-50 px-4 py-3">
                  <div className="text-gray-500">Outstanding Invoices</div>
                  <div className="mt-1 text-lg font-semibold text-gray-900">{outstanding?.outstandingInvoices ?? '...'}</div>
                </div>
                <div className="rounded-xl bg-gray-50 px-4 py-3">
                  <div className="text-gray-500">Role Coverage</div>
                  <div className="mt-1 text-lg font-semibold text-gray-900">{ops.landlords} landlords / {ops.caretakers} caretakers</div>
                </div>
                <div className="rounded-xl bg-gray-50 px-4 py-3">
                  <div className="text-gray-500">Urgent Maintenance</div>
                  <div className="mt-1 text-lg font-semibold text-gray-900">{ops.urgentMaintenance}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
