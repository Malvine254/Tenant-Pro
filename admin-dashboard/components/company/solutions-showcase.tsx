const solutions = [
  {
    id: 'rental-system',
    title: 'Rental / Tenant System',
    description:
      'A feature-rich property operations dashboard with rent tracking, maintenance requests, tenant messaging, and occupancy visibility.',
    bullets: ['Role-based dashboards', 'Billing visibility', 'Maintenance workflows'],
  },
  {
    id: 'elearning-resources',
    title: 'E-learning Resources Platform',
    description:
      'A flexible learning environment for institutions and businesses delivering digital training, content libraries, and learner analytics.',
    bullets: ['Course delivery', 'Progress tracking', 'Resource management'],
  },
  {
    id: 'custom-business-systems',
    title: 'Custom Business Systems',
    description:
      'Tailored portals and operational systems that turn fragmented manual work into streamlined digital processes.',
    bullets: ['Custom workflows', 'Reporting tools', 'Scalable modules'],
  },
];

export function SolutionsShowcase() {
  return (
    <section className="mx-auto max-w-6xl px-6 py-16">
      <div className="max-w-2xl">
        <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Products / Solutions</p>
        <h2 className="mt-2 text-3xl font-semibold text-zinc-900">Designed around real operational needs</h2>
        <p className="mt-3 text-zinc-600">
          Starmax blends practical UX with maintainable architecture to deliver digital tools teams can use confidently.
        </p>
      </div>

      <div className="mt-8 grid gap-5 md:grid-cols-3">
        {solutions.map((solution) => (
          <article id={solution.id} key={solution.id} className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
            <h3 className="text-lg font-semibold text-zinc-900">{solution.title}</h3>
            <p className="mt-2 text-sm leading-6 text-zinc-600">{solution.description}</p>
            <ul className="mt-4 space-y-2 text-sm text-zinc-700">
              {solution.bullets.map((bullet) => (
                <li key={bullet}>• {bullet}</li>
              ))}
            </ul>
          </article>
        ))}
      </div>
    </section>
  );
}
