import { BackToTopButton } from '../../components/company/back-to-top';
import { CompanyFooter } from '../../components/company/company-footer';
import { CompanyHighlights } from '../../components/company/company-highlights';
import { CompanyNavbar } from '../../components/company/company-navbar';
import { FaqSection } from '../../components/company/faq-section';
import { PageHero } from '../../components/company/page-hero';

const values = [
  {
    title: 'Clarity first',
    text: 'We simplify workflows and interfaces so teams can move faster without confusion.',
  },
  {
    title: 'Practical innovation',
    text: 'Our solutions focus on real business outcomes, not unnecessary complexity.',
  },
  {
    title: 'Long-term maintainability',
    text: 'We structure projects with separation of concerns so future growth stays manageable.',
  },
];

export default function AboutPage() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <PageHero
        eyebrow="About Starmax"
        title="A technology partner focused on usable digital systems"
        description="Starmax builds modern digital solutions for property businesses, training platforms, and organizations that need structured operations and responsive user experiences."
      />

      <section className="mx-auto grid max-w-6xl gap-8 px-6 py-16 lg:grid-cols-[1.1fr_0.9fr]">
        <div>
          <h2 className="text-3xl font-semibold text-zinc-900">Who we are</h2>
          <p className="mt-4 text-sm leading-7 text-zinc-600">
            Starmax is a modern tech company delivering web platforms, custom systems, tenant-focused services, and operational tools designed for real teams. We combine clean design, modular engineering, and practical product thinking to help clients move from manual work to reliable digital workflows.
          </p>
          <p className="mt-4 text-sm leading-7 text-zinc-600">
            Our approach emphasizes maintainable code structure, accessible interfaces, and solution design that supports both immediate needs and future growth.
          </p>
        </div>

        <div className="rounded-3xl border border-zinc-200 bg-zinc-50 p-6 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Mission</p>
          <p className="mt-3 text-base leading-7 text-zinc-700">
            To deliver innovative digital solutions that simplify operations, improve service delivery, and help organizations build more dependable experiences for the people they serve.
          </p>
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-6 pb-16">
        <div className="grid gap-5 md:grid-cols-3">
          {values.map((value) => (
            <article key={value.title} className="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
              <h3 className="text-lg font-semibold text-zinc-900">{value.title}</h3>
              <p className="mt-2 text-sm leading-6 text-zinc-600">{value.text}</p>
            </article>
          ))}
        </div>
      </section>

      <CompanyHighlights />
      <FaqSection />
      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
