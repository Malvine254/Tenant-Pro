import { BackToTopButton } from '../../components/company/back-to-top';
import { CompanyFooter } from '../../components/company/company-footer';
import { CompanyNavbar } from '../../components/company/company-navbar';
import { PageHero } from '../../components/company/page-hero';
import { SolutionsShowcase } from '../../components/company/solutions-showcase';
import { TestimonialsSlider } from '../../components/company/testimonials-slider';

export default function ProductsPage() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <PageHero
        eyebrow="Products / Solutions"
        title="Scalable digital products that fit modern teams"
        description="Starmax solutions focus on rental operations, learning experiences, and custom systems that help businesses work smarter and serve users better."
      />

      <SolutionsShowcase />

      <section className="mx-auto max-w-6xl px-6 pb-16">
        <TestimonialsSlider />
      </section>

      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
