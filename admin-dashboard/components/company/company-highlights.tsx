import { companyHighlights } from '../../lib/company-data';

export function CompanyHighlights() {
  return (
    <section className="border-y border-zinc-200 bg-zinc-50">
      <div className="mx-auto grid max-w-6xl gap-5 px-6 py-16 md:grid-cols-3">
        {companyHighlights.map((item) => (
          <article key={item.title} className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h3 className="text-lg font-semibold text-zinc-900">{item.title}</h3>
            <p className="mt-2 text-sm leading-6 text-zinc-600">{item.text}</p>
          </article>
        ))}
      </div>
    </section>
  );
}
