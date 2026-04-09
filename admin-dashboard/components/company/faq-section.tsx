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
          <details key={item.question} className="group rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <summary className="cursor-pointer list-none text-base font-semibold text-zinc-900">
              <div className="flex items-center justify-between gap-4">
                <span>{item.question}</span>
                <span className="text-zinc-500 transition group-open:rotate-45">+</span>
              </div>
            </summary>
            <p className="mt-3 text-sm leading-6 text-zinc-600">{item.answer}</p>
          </details>
        ))}
      </div>
    </section>
  );
}
