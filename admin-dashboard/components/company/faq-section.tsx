const faqs = [
  {
    question: 'What kind of companies does Starmax work with?',
    answer:
      'We primarily support property-focused businesses, training platforms, and operations teams that need practical digital systems and clear service workflows.',
  },
  {
    question: 'Can Starmax build custom portals or dashboards?',
    answer:
      'Yes. We design custom business systems, tenant platforms, and internal tools around your specific reporting and workflow needs.',
  },
  {
    question: 'How are requirements captured during early conversations?',
    answer:
      'The portfolio includes a lightweight project brief form and contact flow so initial requirements can be collected and organized immediately.',
  },
  {
    question: 'Can the platform run while the main database is unavailable?',
    answer:
      'Yes. Starmax supports continuity during maintenance windows and staged rollouts so public experiences remain available.',
  },
];

export function FaqSection() {
  return (
    <section className="mx-auto max-w-6xl px-6 py-16">
      <div className="max-w-2xl">
        <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">FAQ</p>
        <h2 className="mt-2 text-3xl font-semibold text-zinc-900">Common questions</h2>
      </div>

      <div className="mt-8 space-y-3">
        {faqs.map((item) => (
          <details
            key={item.question}
            className="group rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-200 hover:border-indigo-200 open:border-indigo-200 open:shadow-md"
          >
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
              <span className="text-sm font-semibold text-zinc-900">{item.question}</span>
              <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-zinc-200 text-zinc-400 transition duration-200 group-open:border-indigo-200 group-open:bg-indigo-50 group-open:text-indigo-600">
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth={2}
                  className="h-4 w-4 transition duration-200 group-open:rotate-180"
                >
                  <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
              </span>
            </summary>
            <div className="border-t border-zinc-100 px-5 pb-4 pt-3">
              <p className="text-sm leading-6 text-zinc-600">{item.answer}</p>
            </div>
          </details>
        ))}
      </div>
    </section>
  );
}
