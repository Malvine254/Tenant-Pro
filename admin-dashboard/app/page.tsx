import Link from 'next/link';
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

      <Reveal as="section" className="border-b border-zinc-200 bg-white" delay={105}>
        <div className="mx-auto grid max-w-6xl gap-8 px-6 py-16 lg:grid-cols-[1.05fr_0.95fr]">
          <div className="rounded-3xl border border-zinc-200 bg-zinc-50 p-8 shadow-sm">
            <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Property Experience</p>
            <h2 className="mt-3 text-3xl font-semibold tracking-tight text-zinc-950">The property showcase is now part of the public experience.</h2>
            <p className="mt-4 max-w-2xl text-sm leading-7 text-zinc-600">
              The modern property page, sample listings, market blocks, and property-focused blog content are live on the public site. This makes the property work visible from the company home page instead of hiding it on a separate route.
            </p>
            <div className="mt-6 flex flex-wrap gap-3">
              <Link href="/properties" className="rounded-full bg-zinc-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800">
                Open property page
              </Link>
              <Link href="/blog" className="rounded-full border border-zinc-300 px-5 py-3 text-sm font-semibold text-zinc-700 transition hover:border-zinc-950 hover:text-zinc-950">
                Read property insights
              </Link>
            </div>
          </div>

          <div className="rounded-3xl border border-zinc-200 bg-gradient-to-br from-zinc-950 via-zinc-900 to-blue-950 p-8 text-white shadow-sm">
            <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-300">Android App Rollout</p>
            <h2 className="mt-3 text-3xl font-semibold tracking-tight">How users install the tenant app.</h2>
            <ol className="mt-6 space-y-4 text-sm leading-6 text-zinc-200">
              <li><span className="font-semibold text-white">1.</span> Download the APK from the company delivery link or the deployment package shared with the client.</li>
              <li><span className="font-semibold text-white">2.</span> Enable installation from unknown sources once on the Android device.</li>
              <li><span className="font-semibold text-white">3.</span> Install the app, sign in with the issued tenant credentials, then confirm invoices and payment history load correctly.</li>
              <li><span className="font-semibold text-white">4.</span> For developer setups, point the app to the correct backend host and use <span className="font-semibold text-white">10.0.2.2</span> for the Android emulator.</li>
            </ol>
            <div className="mt-6 flex flex-wrap gap-3">
              <Link href="/contact" className="rounded-full bg-white px-5 py-3 text-sm font-semibold text-zinc-950 transition hover:bg-zinc-200">
                Request app rollout
              </Link>
              <a href="#requirements" className="rounded-full border border-white/20 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                Send requirements
              </a>
            </div>
          </div>
        </div>
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
