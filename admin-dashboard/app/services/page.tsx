import { BackToTopButton } from '../../components/company/back-to-top';
import { CompanyFooter } from '../../components/company/company-footer';
import { CompanyNavbar } from '../../components/company/company-navbar';
import { DynamicServicesGrid } from '../../components/company/dynamic-services-grid';
import { PageHero } from '../../components/company/page-hero';
import { TestimonialsSlider } from '../../components/company/testimonials-slider';

export default function ServicesPage() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <PageHero
        eyebrow="Services"
        title="Digital services designed for operations, growth, and better user experiences"
        description="Explore Starmax offerings across tenant platforms, e-learning products, web experiences, mobile apps, and IT consulting."
      />

      <section className="mx-auto max-w-6xl px-6 py-16">
        <DynamicServicesGrid />
      </section>

      <section className="mx-auto max-w-6xl px-6 pb-16">
        <TestimonialsSlider />
      </section>

      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
