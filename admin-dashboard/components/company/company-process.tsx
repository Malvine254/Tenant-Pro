import { companyProcess } from '../../lib/company-data';

export function CompanyProcess() {
  return (
    <section id="process" className="mx-auto max-w-6xl px-6 py-16">
      <div className="max-w-2xl">
        <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Process</p>
        <h2 className="mt-2 text-3xl font-semibold text-zinc-900">How projects move with Starmax</h2>
      </div>

      <div className="mt-8 grid gap-5 md:grid-cols-3">
        {companyProcess.map((item) => (
          <article key={item.step} className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <span className="text-xs font-semibold uppercase tracking-[0.3em] text-zinc-500">Step {item.step}</span>
            <h3 className="mt-2 text-lg font-semibold text-zinc-900">{item.title}</h3>
            <p className="mt-2 text-sm leading-6 text-zinc-600">{item.text}</p>
          </article>
        ))}
      </div>
    </section>
  );
}
