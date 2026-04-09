import Link from 'next/link';
import { companyStats } from '../../lib/company-data';

export function CompanyHero() {
  return (
    <section className="border-b border-zinc-200 bg-gradient-to-br from-zinc-950 via-zinc-900 to-indigo-950 text-white">
      <div className="mx-auto max-w-6xl px-6 py-16">
        <div className="grid gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
          <div>
            <span className="inline-flex rounded-full border border-white/10 bg-white/10 px-3 py-1 text-xs font-medium text-blue-100">
              Innovative Digital Solutions for property, learning, and business operations
            </span>
            <h1 className="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">
              Starmax builds modern digital experiences that keep teams moving.
            </h1>
            <p className="mt-4 max-w-2xl text-base leading-7 text-zinc-200">
              From tenant management systems and e-learning platforms to custom business software,
              Starmax delivers clean, scalable solutions with a polished SaaS-inspired experience.
            </p>
            <div className="mt-6 flex flex-wrap gap-3">
              <a href="#requirements" className="rounded-full bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:scale-[1.02]">
                Start your brief
              </a>
              <Link href="/portfolio" className="rounded-full border border-white/20 px-4 py-2 text-sm font-medium text-white transition hover:bg-white/10">
                View portfolio
              </Link>
            </div>
          </div>

          <div className="tp-card-lift rounded-3xl border border-white/10 bg-white/5 p-5 shadow-2xl backdrop-blur">
            <p className="text-sm font-semibold text-white">Core focus areas</p>
            <ul className="mt-4 space-y-3 text-sm text-zinc-200">
              <li>• Tenant communication and rental operations</li>
              <li>• Product design for learning platforms</li>
              <li>• Custom dashboards and business systems</li>
              <li>• Fast requirement capture and digital rollout</li>
            </ul>
          </div>
        </div>

        <div className="mt-10 grid gap-4 sm:grid-cols-3">
          {companyStats.map((item) => (
            <div key={item.label} className="tp-card-lift rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
              <div className="text-2xl font-semibold text-white">{item.value}</div>
              <div className="mt-1 text-sm text-zinc-200">{item.label}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
