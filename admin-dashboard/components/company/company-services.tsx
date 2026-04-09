import Link from 'next/link';
import { DynamicServicesGrid } from './dynamic-services-grid';

export function CompanyServices() {
  return (
    <section id="services" className="mx-auto max-w-6xl px-6 py-16">
      <div className="max-w-2xl">
        <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Services</p>
        <h2 className="mt-2 text-3xl font-semibold text-zinc-900">What Starmax delivers</h2>
        <p className="mt-3 text-zinc-600">
          Flexible service offerings tailored for modern property, learning, and business operations.
        </p>
      </div>

      <div className="mt-8">
        <DynamicServicesGrid preview />
      </div>

      <div className="mt-6">
        <Link href="/services" className="inline-flex rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50">
          View all services
        </Link>
      </div>
    </section>
  );
}
