import { BackToTopButton } from '../../components/company/back-to-top';
import { CompanyFooter } from '../../components/company/company-footer';
import { CompanyNavbar } from '../../components/company/company-navbar';
import { ContactForm } from '../../components/company/contact-form';
import { PageHero } from '../../components/company/page-hero';

export default function ContactPage() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <PageHero
        eyebrow="Contact"
        title="Let’s talk about your next digital project"
        description="Reach out to Starmax for tenant-focused systems, digital products, custom development, and implementation support."
      />

      <section className="mx-auto grid max-w-6xl gap-8 px-6 py-16 lg:grid-cols-[1fr_1.05fr]">
        <div className="rounded-3xl border border-zinc-200 bg-zinc-50 p-6 shadow-sm">
          <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Contact details</p>
          <h2 className="mt-2 text-3xl font-semibold text-zinc-900">Start the conversation</h2>
          <div className="mt-6 space-y-4 text-sm text-zinc-700">
            <p><span className="font-semibold text-zinc-900">Email:</span> info@starmaxltd.com</p>
            <p><span className="font-semibold text-zinc-900">Phone:</span> +254 700 123 456</p>
            <p><span className="font-semibold text-zinc-900">Location:</span> Nairobi, Kenya</p>
            <p><span className="font-semibold text-zinc-900">Availability:</span> Monday – Friday, 8:00 AM – 6:00 PM</p>
          </div>
        </div>

        <ContactForm />
      </section>

      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
