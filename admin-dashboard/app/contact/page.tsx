import { BackToTopButton } from '../../components/company/back-to-top';
import { CompanyFooter } from '../../components/company/company-footer';
import { CompanyNavbar } from '../../components/company/company-navbar';
import { ContactForm } from '../../components/company/contact-form';
import { PageHero } from '../../components/company/page-hero';

const contactDetails = [
  {
    label: 'Email us',
    value: 'info@starmaxltd.com',
    sub: 'We reply within one business day',
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth={1.5} className="h-5 w-5">
        <path strokeLinecap="round" strokeLinejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
      </svg>
    ),
    gradient: 'from-blue-500 to-indigo-600',
  },
  {
    label: 'Call us',
    value: '+254 700 123 456',
    sub: 'Mon-Fri, 8 AM - 6 PM EAT',
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth={1.5} className="h-5 w-5">
        <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
      </svg>
    ),
    gradient: 'from-violet-500 to-purple-600',
  },
  {
    label: 'Our office',
    value: 'Nairobi, Kenya',
    sub: 'Serving clients across East Africa',
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth={1.5} className="h-5 w-5">
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
        <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
      </svg>
    ),
    gradient: 'from-emerald-500 to-teal-600',
  },
  {
    label: 'Availability',
    value: 'Monday - Friday',
    sub: '8:00 AM - 6:00 PM EAT',
    icon: (
      <svg viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth={1.5} className="h-5 w-5">
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
    gradient: 'from-amber-500 to-orange-500',
  },
];

export default function ContactPage() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <PageHero
        eyebrow="Contact"
        title="Let's talk about your next digital project"
        description="Reach out to Starmax for tenant-focused systems, digital products, custom development, and implementation support."
      />

      <section className="mx-auto grid max-w-6xl gap-8 px-6 py-16 lg:grid-cols-[1fr_1.05fr]">
        <div className="flex flex-col gap-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
          <div>
            <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-400">Get in touch</p>
            <h2 className="mt-2 text-2xl font-bold text-zinc-900">Start the conversation</h2>
            <p className="mt-2 text-sm leading-6 text-zinc-500">
              Have a project in mind? Fill out the form or reach us directly through any of the channels below.
            </p>
          </div>

          <div className="grid gap-3 sm:grid-cols-2">
            {contactDetails.map((item) => (
              <div
                key={item.label}
                className="flex items-start gap-3 rounded-xl border border-zinc-100 bg-zinc-50 p-4"
              >
                <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br ${item.gradient} shadow-sm`}>
                  {item.icon}
                </div>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wide text-zinc-400">{item.label}</p>
                  <p className="text-sm font-semibold text-zinc-900">{item.value}</p>
                  <p className="text-xs text-zinc-500">{item.sub}</p>
                </div>
              </div>
            ))}
          </div>

          <div className="mt-auto h-1.5 w-full rounded-full bg-gradient-to-r from-blue-500 via-violet-500 to-emerald-400" />
        </div>

        <ContactForm />
      </section>

      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}