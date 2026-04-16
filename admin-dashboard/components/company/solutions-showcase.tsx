const solutions = [
  {
    id: 'rental-system',
    title: 'Rental / Tenant System',
    description:
      'A feature-rich property operations dashboard with rent tracking, maintenance requests, tenant messaging, and occupancy visibility.',
    bullets: ['Role-based dashboards', 'Billing visibility', 'Maintenance workflows'],
    accentClass: 'from-blue-500 via-indigo-500 to-violet-500',
    iconBg: 'from-blue-50 to-indigo-50',
    iconColor: 'text-indigo-600',
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-6 w-6">
        <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
      </svg>
    ),
  },
  {
    id: 'elearning-resources',
    title: 'E-learning Resources Platform',
    description:
      'A flexible learning environment for institutions and businesses delivering digital training, content libraries, and learner analytics.',
    bullets: ['Course delivery', 'Progress tracking', 'Resource management'],
    accentClass: 'from-violet-500 via-purple-500 to-indigo-500',
    iconBg: 'from-violet-50 to-purple-50',
    iconColor: 'text-violet-600',
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-6 w-6">
        <path strokeLinecap="round" strokeLinejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
      </svg>
    ),
  },
  {
    id: 'custom-business-systems',
    title: 'Custom Business Systems',
    description:
      'Tailored portals and operational systems that turn fragmented manual work into streamlined digital processes.',
    bullets: ['Custom workflows', 'Reporting tools', 'Scalable modules'],
    accentClass: 'from-cyan-400 via-blue-500 to-indigo-500',
    iconBg: 'from-cyan-50 to-blue-50',
    iconColor: 'text-blue-600',
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-6 w-6">
        <path strokeLinecap="round" strokeLinejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z" />
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    ),
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
          <article
            id={solution.id}
            key={solution.id}
            className="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-zinc-300 hover:shadow-xl"
          >
            {/* Gradient top bar on hover */}
            <div className={`absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r ${solution.accentClass} opacity-0 transition duration-300 group-hover:opacity-100`} />

            {/* Icon */}
            <div className={`mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br ${solution.iconBg} ${solution.iconColor} ring-1 ring-zinc-100`}>
              {solution.icon}
            </div>

            <h3 className="text-lg font-semibold text-zinc-900">{solution.title}</h3>
            <p className="mt-2 text-sm leading-6 text-zinc-600">{solution.description}</p>

            <ul className="mt-4 space-y-2">
              {solution.bullets.map((bullet) => (
                <li key={bullet} className="flex items-center gap-2 text-sm text-zinc-700">
                  <span className={`h-1.5 w-1.5 shrink-0 rounded-full bg-gradient-to-r ${solution.accentClass}`} />
                  {bullet}
                </li>
              ))}
            </ul>
          </article>
        ))}
      </div>
    </section>
  );
}
