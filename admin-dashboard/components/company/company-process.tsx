import { companyProcess } from '../../lib/company-data';

const stepAccents = [
  'from-blue-500 to-indigo-500',
  'from-indigo-500 to-violet-500',
  'from-violet-500 to-purple-600',
];

export function CompanyProcess() {
  return (
    <section id="process" className="mx-auto max-w-6xl px-6 py-16">
      <div className="max-w-2xl">
        <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Process</p>
        <h2 className="mt-2 text-3xl font-semibold text-zinc-900">How projects move with Starmax</h2>
      </div>

      <div className="relative mt-10">
        {/* Horizontal connector line (desktop) */}
        <div className="absolute left-0 right-0 top-[22px] hidden h-px bg-gradient-to-r from-blue-200 via-indigo-200 to-violet-200 md:block" />

        <div className="grid gap-8 md:grid-cols-3">
          {companyProcess.map((item, i) => (
            <article key={item.step} className="group relative rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
              {/* Step number badge */}
              <div className={`relative z-10 inline-flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br ${stepAccents[i % stepAccents.length]} text-sm font-bold text-white shadow-md`}>
                {String(item.step).padStart(2, '0')}
              </div>
              {/* Gradient top bar */}
              <div className={`absolute inset-x-0 top-0 h-0.5 rounded-t-2xl bg-gradient-to-r ${stepAccents[i % stepAccents.length]} opacity-0 transition duration-300 group-hover:opacity-100`} />

              <h3 className="mt-4 text-lg font-semibold text-zinc-900">{item.title}</h3>
              <p className="mt-2 text-sm leading-6 text-zinc-600">{item.text}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
