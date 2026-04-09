import { BackToTopButton } from '../../components/company/back-to-top';
import { CompanyFooter } from '../../components/company/company-footer';
import { CompanyNavbar } from '../../components/company/company-navbar';
import { PageHero } from '../../components/company/page-hero';
import { PortfolioShowcase } from '../../components/company/portfolio-showcase';

export default function PortfolioPage() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <PageHero
        eyebrow="Portfolio"
        title="Selected project directions across property tech, edtech, and custom systems"
        description="Explore sample Starmax portfolio work and filter projects by category to see how solutions are shaped around different business needs."
      />

      <section className="mx-auto max-w-6xl px-6 py-16">
        <PortfolioShowcase />
      </section>

      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
