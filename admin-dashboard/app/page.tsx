import { BackToTopButton } from '../components/company/back-to-top';
import { CompanyFooter } from '../components/company/company-footer';
import { CompanyHighlights } from '../components/company/company-highlights';
import { HeroSlider } from '../components/company/hero-slider';
import { CompanyNavbar } from '../components/company/company-navbar';
import { CompanyProcess } from '../components/company/company-process';
import { CompanyRequirementsForm } from '../components/company/company-requirements-form';
import { CompanyServices } from '../components/company/company-services';
import { FaqSection } from '../components/company/faq-section';
import { PortfolioShowcase } from '../components/company/portfolio-showcase';
import { Reveal } from '../components/company/reveal';
import { SolutionsShowcase } from '../components/company/solutions-showcase';
import { TestimonialsSlider } from '../components/company/testimonials-slider';

export default function Home() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <HeroSlider />
      <Reveal>
        <CompanyServices />
      </Reveal>
      <Reveal delay={60}>
        <CompanyHighlights />
      </Reveal>
      <Reveal delay={90}>
        <SolutionsShowcase />
      </Reveal>

      <Reveal as="section" className="border-y border-zinc-200 bg-zinc-50" delay={120}>
        <div className="mx-auto max-w-6xl px-6 py-16">
          <div className="mb-8 max-w-2xl">
            <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Portfolio</p>
            <h2 className="mt-2 text-3xl font-semibold text-zinc-900">Recent solution directions</h2>
            <p className="mt-3 text-sm leading-6 text-zinc-600">
              A snapshot of how Starmax structures property technology, learning products, and custom systems into reusable digital experiences.
            </p>
          </div>
          <PortfolioShowcase preview />
        </div>
      </Reveal>

      <Reveal className="mx-auto max-w-6xl px-6 py-16" delay={150}>
        <TestimonialsSlider />
      </Reveal>

      <Reveal delay={180}>
        <CompanyProcess />
      </Reveal>
      <Reveal delay={210}>
        <FaqSection />
      </Reveal>

      <Reveal as="section" id="requirements" className="border-t border-zinc-200 bg-zinc-50" delay={240}>
        <div className="mx-auto grid max-w-6xl gap-8 px-6 py-16 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
          <div>
            <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Requirements</p>
            <h2 className="mt-2 text-3xl font-semibold text-zinc-900">Tell Starmax what you need</h2>
            <p className="mt-3 text-sm leading-6 text-zinc-600">
              Use this brief to capture your tenant-service or product requirements and send them directly to the Starmax team for follow-up.
            </p>
          </div>

          <CompanyRequirementsForm />
        </div>
      </Reveal>

      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
