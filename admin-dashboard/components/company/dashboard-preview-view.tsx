"use client";

import { useMemo, useState } from 'react';

export type DashboardTone = 'indigo' | 'emerald' | 'amber' | 'violet';

type StatCard = {
  label: string;
  value: string;
  trend: string;
  hint: string;
  tone: DashboardTone;
};

type ChartPoint = {
  label: string;
  value: number;
};

type KpiItem = {
  label: string;
  value: string;
};

type ActivityItem = {
  title: string;
  meta: string;
  status: string;
};

export type Dashboard = {
  id: string;
  label: string;
  subtitle: string;
  sidebar: string[];
  stats: StatCard[];
  chartTitle: string;
  chartSeries: ChartPoint[];
  breakdownTitle: string;
  kpis: KpiItem[];
  activity: ActivityItem[];
};

const toneClasses: Record<DashboardTone, string> = {
  indigo: 'from-indigo-500/15 to-blue-500/5 text-indigo-700',
  emerald: 'from-emerald-500/15 to-teal-500/5 text-emerald-700',
  amber: 'from-amber-500/15 to-orange-500/5 text-amber-700',
  violet: 'from-violet-500/15 to-fuchsia-500/5 text-violet-700',
};

export function DashboardPreviewView({ dashboards }: { dashboards: Dashboard[] }) {
  const [activeId, setActiveId] = useState(dashboards[0]?.id ?? '');

  const activeDashboard = useMemo(
    () => dashboards.find((dashboard) => dashboard.id === activeId) ?? dashboards[0],
    [activeId, dashboards],
  );

  if (!activeDashboard) {
    return null;
  }

  return (
    <section className="bg-[radial-gradient(circle_at_top,_rgba(99,102,241,0.15),_transparent_0,_transparent_55%),linear-gradient(to_bottom,_#ffffff,_#f8fafc)] text-zinc-900">
      <div className="mx-auto max-w-7xl px-6 py-16">
        <div className="max-w-3xl">
          <p className="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Dashboard Preview</p>
          <h1 className="mt-3 text-4xl font-semibold tracking-tight text-zinc-950 sm:text-5xl">
            Explore a modern SaaS admin experience.
          </h1>
          <p className="mt-4 text-base leading-7 text-zinc-600">
            Switch between Starmax dashboard concepts for tenant operations and e-learning delivery in a polished guided showcase.
          </p>
        </div>

        <div className="mt-8 flex flex-wrap gap-3">
          {dashboards.map((dashboard) => {
            const isActive = dashboard.id === activeDashboard.id;
            return (
              <button
                key={dashboard.id}
                type="button"
                onClick={() => setActiveId(dashboard.id)}
                className={`rounded-full px-4 py-2 text-sm font-medium transition ${
                  isActive
                    ? 'bg-zinc-950 text-white shadow-lg shadow-zinc-950/15'
                    : 'border border-zinc-200 bg-white text-zinc-700 hover:border-indigo-200 hover:text-indigo-700'
                }`}
              >
                {dashboard.label}
              </button>
            );
          })}
        </div>

        <div className="mt-8 overflow-hidden rounded-[32px] border border-zinc-200 bg-white shadow-[0_30px_90px_-30px_rgba(15,23,42,0.28)]">
          <div className="grid lg:grid-cols-[260px_1fr]">
            <aside className="border-b border-zinc-800 bg-zinc-950 p-5 text-zinc-100 lg:border-b-0 lg:border-r">
              <div className="flex items-center gap-3">
                <span className="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-500 to-violet-500 font-bold text-white">
                  S
                </span>
                <div>
                  <p className="font-semibold text-white">Starmax Suite</p>
                  <p className="text-xs text-zinc-400">Preview workspace</p>
                </div>
              </div>

              <nav className="mt-6 space-y-2">
                {activeDashboard.sidebar.map((item, index) => (
                  <button
                    key={item}
                    type="button"
                    className={`flex w-full items-center justify-between rounded-2xl px-3 py-2 text-sm transition ${
                      index === 0
                        ? 'bg-white/10 text-white'
                        : 'text-zinc-300 hover:bg-white/5 hover:text-white'
                    }`}
                  >
                    <span>{item}</span>
                    <span className="text-xs text-zinc-500">›</span>
                  </button>
                ))}
              </nav>

              <div className="mt-8 rounded-2xl border border-white/10 bg-white/5 p-4">
                <p className="text-xs uppercase tracking-[0.2em] text-zinc-400">Workspace status</p>
                <p className="mt-2 text-sm text-white">Live preview mode enabled</p>
                <p className="mt-1 text-xs text-zinc-400">Preview metrics reflect a guided showcase environment.</p>
              </div>
            </aside>

            <div className="bg-zinc-50">
              <div className="border-b border-zinc-200 bg-white/80 px-4 py-4 backdrop-blur md:px-6">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                  <div>
                    <p className="text-sm font-medium text-zinc-500">Top header bar</p>
                    <h2 className="text-xl font-semibold text-zinc-950">{activeDashboard.label}</h2>
                  </div>

                  <div className="flex flex-wrap items-center gap-3">
                    <div className="rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm text-zinc-500 shadow-sm">
                      Search dashboard...
                    </div>
                    <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                      Live demo
                    </span>
                    <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-zinc-950 text-sm font-semibold text-white">
                      SM
                    </span>
                  </div>
                </div>
              </div>

              <div className="space-y-6 p-4 md:p-6">
                <div className="rounded-[28px] bg-gradient-to-r from-zinc-950 via-slate-900 to-indigo-950 p-6 text-white shadow-lg">
                  <p className="text-sm font-medium text-indigo-200">Preview summary</p>
                  <h3 className="mt-2 text-2xl font-semibold">{activeDashboard.label}</h3>
                  <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-200">{activeDashboard.subtitle}</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                  {activeDashboard.stats.map((stat) => (
                    <div
                      key={stat.label}
                      className={`rounded-[24px] border border-zinc-200 bg-gradient-to-br p-4 shadow-sm ${toneClasses[stat.tone]}`}
                    >
                      <p className="text-sm font-medium text-zinc-600">{stat.label}</p>
                      <p className="mt-3 text-2xl font-semibold text-zinc-950">{stat.value}</p>
                      <div className="mt-3 flex items-center justify-between text-xs">
                        <span className="font-semibold">{stat.trend}</span>
                        <span className="text-zinc-500">{stat.hint}</span>
                      </div>
                    </div>
                  ))}
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.35fr_0.9fr]">
                  <div className="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-sm font-semibold text-zinc-900">{activeDashboard.chartTitle}</p>
                        <p className="text-xs text-zinc-500">Mock chart data</p>
                      </div>
                      <span className="rounded-full bg-zinc-100 px-3 py-1 text-xs text-zinc-600">Last 6 months</span>
                    </div>

                    <div className="mt-6 flex h-60 items-end gap-3">
                      {activeDashboard.chartSeries.map((point) => (
                        <div key={point.label} className="flex flex-1 flex-col items-center gap-2">
                          <div className="flex h-full w-full items-end rounded-2xl bg-zinc-100 p-1">
                            <div
                              className="w-full rounded-xl bg-gradient-to-t from-indigo-600 via-blue-500 to-cyan-400"
                              style={{ height: `${point.value}%` }}
                              aria-label={`${point.label} value ${point.value}`}
                            />
                          </div>
                          <span className="text-xs text-zinc-500">{point.label}</span>
                        </div>
                      ))}
                    </div>
                  </div>

                  <div className="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
                    <p className="text-sm font-semibold text-zinc-900">{activeDashboard.breakdownTitle}</p>
                    <div className="mt-5 space-y-4">
                      {activeDashboard.kpis.map((item, index) => {
                        const progress = 68 + index * 11;
                        return (
                          <div key={item.label}>
                            <div className="mb-2 flex items-center justify-between gap-3 text-sm">
                              <span className="text-zinc-600">{item.label}</span>
                              <span className="font-semibold text-zinc-900">{item.value}</span>
                            </div>
                            <div className="h-2 rounded-full bg-zinc-100">
                              <div
                                className="h-2 rounded-full bg-gradient-to-r from-indigo-600 to-cyan-500"
                                style={{ width: `${Math.min(progress, 96)}%` }}
                              />
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                </div>

                <div className="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
                  <div className="flex items-center justify-between gap-3">
                    <div>
                      <p className="text-sm font-semibold text-zinc-900">Recent activity</p>
                      <p className="text-xs text-zinc-500">Latest sample actions inside the workspace</p>
                    </div>
                    <span className="rounded-full bg-zinc-100 px-3 py-1 text-xs text-zinc-600">Real-time style preview</span>
                  </div>

                  <div className="mt-4 grid gap-3">
                    {activeDashboard.activity.map((item) => (
                      <div key={item.title} className="flex flex-col gap-2 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                          <p className="font-medium text-zinc-900">{item.title}</p>
                          <p className="text-sm text-zinc-500">{item.meta}</p>
                        </div>
                        <span className="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                          {item.status}
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
