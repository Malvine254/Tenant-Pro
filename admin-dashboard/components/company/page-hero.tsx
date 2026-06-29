type PageHeroProps = {
  eyebrow: string;
  title: string;
  description: string;
};

export function PageHero({ eyebrow, title, description }: PageHeroProps) {
  return (
    <section className="border-b border-zinc-200 bg-gradient-to-br from-zinc-950 via-zinc-900 to-indigo-950 text-white">
      <div className="mx-auto max-w-6xl px-6 py-18">
        <span className="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-blue-100">
          {eyebrow}
        </span>
        <h1 className="mt-4 max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl">{title}</h1>
        <p className="mt-4 max-w-2xl text-base leading-7 text-zinc-200">{description}</p>
      </div>
    </section>
  );
}
