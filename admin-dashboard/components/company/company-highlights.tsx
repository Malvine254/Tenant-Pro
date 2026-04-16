import { companyHighlights } from '../../lib/company-data';

const icons = [
  // Building/property icon
  <svg key="prop" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-6 w-6">
    <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
  </svg>,
  // Rocket/launch icon
  <svg key="launch" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-6 w-6">
    <path strokeLinecap="round" strokeLinejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.82m5.84-2.56a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.82m2.56-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7a18.056 18.056 0 01-2.079 4.26M12 9a3 3 0 11-6 0 3 3 0 016 0z" />
  </svg>,
  // Shield/trust icon
  <svg key="shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-6 w-6">
    <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
  </svg>,
];

export function CompanyHighlights() {
  return (
    <section className="border-y border-zinc-200 bg-gradient-to-b from-zinc-50 to-white">
      <div className="mx-auto grid max-w-6xl gap-5 px-6 py-16 md:grid-cols-3">
        {companyHighlights.map((item, i) => (
          <article
            key={item.title}
            className="group relative rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-indigo-200"
          >
            {/* Gradient top accent */}
            <div className="absolute inset-x-0 top-0 h-px rounded-t-2xl bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500 opacity-0 transition duration-300 group-hover:opacity-100" />
            {/* Icon badge */}
            <div className="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-50 to-blue-50 text-indigo-600 ring-1 ring-indigo-100">
              {icons[i % icons.length]}
            </div>
            <h3 className="text-lg font-semibold text-zinc-900">{item.title}</h3>
            <p className="mt-2 text-sm leading-6 text-zinc-600">{item.text}</p>
          </article>
        ))}
      </div>
    </section>
  );
}
