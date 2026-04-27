"use client";

import Link from 'next/link';
import { useRouter, useSearchParams } from 'next/navigation';
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

import dashboardPreview from '../../data/dashboard-preview.json';
import { StatCard } from '../../components/stat-card';
import { clearSession, getSession, withDashboardMode } from '../../lib/auth';
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

type DemoDashboard = {
  id: string;
  label: string;
  subtitle: string;
  stats: Array<{ label: string; value: string; trend: string; hint: string }>;
  chartTitle: string;
  chartSeries: Array<{ label: string; value: number }>;
  breakdownTitle: string;
  kpis: Array<{ label: string; value: string }>;
  activity: Array<{ title: string; meta: string; status: string }>;
};

const demoDashboards = dashboardPreview.dashboards as DemoDashboard[];

export default function DashboardPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';

  const [activeId, setActiveId] = useState(demoDashboards[0]?.id ?? '');
  const [revenue, setRevenue] = useState<Revenue | null>(null);
  const [outstanding, setOutstanding] = useState<Outstanding | null>(null);
  const [occupancy, setOccupancy] = useState<Occupancy | null>(null);
  const [trend, setTrend] = useState<Trend | null>(null);
  const [users, setUsers] = useState<UserRow[]>([]);
  const [properties, setProperties] = useState<PropertyRow[]>([]);
  const [maintenance, setMaintenance] = useState<MaintenanceRow[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (isDemoMode) {
      setError(null);
      return;
    }

    const run = async () => {
      const session = getSession();
      if (!session) {
        setError('Your admin session is missing. Please sign in again.');
        router.push('/login');
        return;
      }

      if (session.user.role === 'TENANT') {
        router.replace('/dashboard/invoices');
        return;
      }

      if (session.user.role === 'LANDLORD' || session.user.role === 'CARETAKER') {
        router.replace('/dashboard/properties');
        return;
      }

      if (session.user.role !== 'ADMIN') {
        clearSession();
        setError('Access denied.');
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
        setError(message);
      }
    };

    void run();
  }, [isDemoMode, router]);

  const activeDashboard = useMemo(
    () => demoDashboards.find((dashboard) => dashboard.id === activeId) ?? demoDashboards[0],
    [activeId],
  );

  const chartData = useMemo<Array<{ label: string; totalAmount: number; value: number }>>(
    () => isDemoMode
      ? activeDashboard?.chartSeries?.map((item) => ({ label: item.label, totalAmount: item.value, value: item.value })) ?? []
      : trend?.trends?.map((item) => ({ label: item.month, totalAmount: item.totalAmount, value: item.totalAmount })) ?? [],
    [activeDashboard, isDemoMode, trend],
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

  if (!activeDashboard && isDemoMode) {
    return null;
  }

  return (
    <main className="mx-auto flex h-[calc(100dvh-6.5rem)] w-full max-w-7xl flex-col text-gray-900 lg:h-[calc(100dvh-4rem)]">
      <section className="shrink-0 rounded-2xl border border-gray-200 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-900 px-6 py-8 text-white shadow-xl">
        <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
          <div>
            <div className="flex flex-wrap items-center gap-2">
              {isDemoMode ? (
                <>
                  <span className="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-200">
                    Demo Mode
                  </span>
                  <span className="rounded-full bg-white/10 px-3 py-1 text-xs text-slate-200">
                    New-tab sandbox session
                  </span>
                </>
              ) : (
                <span className="rounded-full bg-white/10 px-3 py-1 text-xs text-slate-200">
                  Admin workspace
                </span>
              )}
            </div>
            <h2 className="mt-3 text-3xl font-bold tracking-tight">Executive Control Center</h2>
            <p className="mt-2 max-w-2xl text-sm text-slate-200">
              {isDemoMode
                ? 'This sandbox gives you a safe demo workspace for testing tenant-related operations without affecting real accounts.'
                : 'Monitor finance, occupancy, user roles, maintenance, and property operations from one robust admin dashboard.'}
            </p>
          </div>

          <div className="flex flex-wrap gap-2">
            {isDemoMode ? (
              <>
                {demoDashboards.map((dashboard) => {
                  const isActive = dashboard.id === activeDashboard.id;
                  return (
                    <button
                      key={dashboard.id}
                      type="button"
                      onClick={() => setActiveId(dashboard.id)}
                      className={`rounded-xl px-4 py-2 text-sm font-medium transition ${
                        isActive
                          ? 'bg-white text-slate-900'
                          : 'border border-white/20 text-white hover:bg-white/10'
                      }`}
                    >
                      {dashboard.label}
                    </button>
                  );
                })}
                <Link href={withDashboardMode('/dashboard/tenants', true)} className="rounded-xl bg-white px-4 py-2 text-sm font-medium text-slate-900 transition hover:bg-slate-100">Open Tenant Directory</Link>
                <Link href={withDashboardMode('/dashboard/properties', true)} className="rounded-xl border border-white/20 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10">Test Properties</Link>
              </>
            ) : (
              <>
                <Link href="/dashboard/users" className="rounded-xl bg-white px-4 py-2 text-sm font-medium text-slate-900 transition hover:bg-slate-100">Manage Users & Roles</Link>
                <Link href="/dashboard/properties" className="rounded-xl border border-white/20 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10">Open Properties</Link>
                <Link href="/dashboard/maintenance" className="rounded-xl border border-white/20 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10">Review Maintenance</Link>
              </>
            )}
          </div>
        </div>
      </section>

      <section className="mt-6 min-h-0 flex-1 overflow-y-auto pb-8 pr-1">
        <div className="space-y-8">
          {error ? <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{error}</div> : null}

          {isDemoMode ? (
            <>
              <div className="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-700">
                <span className="font-semibold">Demo workspace active:</span> {activeDashboard.subtitle}
              </div>

              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {activeDashboard.stats.map((stat) => (
                  <StatCard
                    key={stat.label}
                    label={stat.label}
                    value={stat.value}
                    hint={`${stat.trend} • ${stat.hint}`}
                  />
                ))}
              </div>

              <div className="grid gap-6 xl:grid-cols-[1.5fr_0.9fr]">
                <div className="min-w-0 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                  <h3 className="mb-1 text-base font-semibold">{activeDashboard.chartTitle}</h3>
                  <p className="mb-4 text-sm text-gray-500">Performance trends rendered in the existing dashboard shell.</p>
                  <div className="h-80 w-full">
                    <ResponsiveContainer width="100%" height="100%">
                      <BarChart data={chartData}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis dataKey="label" />
                        <YAxis />
                        <Tooltip />
                        <Bar dataKey="totalAmount" fill="#111827" name="Value" radius={[8, 8, 0, 0]} />
                      </BarChart>
                    </ResponsiveContainer>
                  </div>
                </div>

                <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                  <h3 className="text-base font-semibold">{activeDashboard.breakdownTitle}</h3>
                  <div className="mt-4 space-y-3 text-sm">
                    {activeDashboard.kpis.map((item) => (
                      <div key={item.label} className="rounded-xl bg-gray-50 px-4 py-3">
                        <div className="text-gray-500">{item.label}</div>
                        <div className="mt-1 text-lg font-semibold text-gray-900">{item.value}</div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              <div className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div className="flex items-center justify-between gap-3">
                  <div>
                    <h3 className="text-base font-semibold">Recent activity</h3>
                    <p className="text-sm text-gray-500">Recent operational updates for the selected dashboard.</p>
                  </div>
                  <span className="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600">Demo workspace</span>
                </div>

                <div className="mt-4 grid gap-3">
                  {activeDashboard.activity.map((item) => (
                    <div key={item.title} className="flex flex-col gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                      <div>
                        <p className="font-medium text-gray-900">{item.title}</p>
                        <p className="text-sm text-gray-500">{item.meta}</p>
                      </div>
                      <span className="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                        {item.status}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            </>
          ) : (
            <>
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
            </>
          )}
        </div>
      </section>
    </main>
  );
}
