import Link from 'next/link';

const quickLinks = [
  { href: '/', label: 'Home' },
  { href: '/about', label: 'About Us' },
  { href: '/portfolio', label: 'Portfolio' },
  { href: '/blog', label: 'Insights' },
  { href: '/dashboard?mode=demo', label: 'Dashboard Demo', newTab: true },
  { href: '/contact', label: 'Contact' },
];

const serviceLinks = [
  { href: '/services', label: 'Tenant Management' },
  { href: '/services', label: 'E-learning Platform' },
  { href: '/services', label: 'Web Development' },
  { href: '/services', label: 'IT Consulting' },
];

export function CompanyFooter() {
  return (
    <footer className="border-t border-zinc-200 bg-zinc-950 text-zinc-300">
      <div className="mx-auto grid max-w-6xl gap-8 px-6 py-12 md:grid-cols-2 xl:grid-cols-4">
        <div>
          <p className="text-lg font-semibold text-white">Starmax</p>
          <p className="mt-3 text-sm leading-6 text-zinc-400">
            Innovative Digital Solutions for tenant services, digital products, and custom business platforms.
          </p>
        </div>

        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-400">Quick Links</p>
          <ul className="mt-3 space-y-2 text-sm">
            {quickLinks.map((item) => (
              <li key={item.href + item.label}>
                <Link
                  href={item.href}
                  target={item.newTab ? '_blank' : undefined}
                  rel={item.newTab ? 'noopener noreferrer' : undefined}
                  className="transition hover:text-white"
                >
                  {item.label}
                </Link>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-400">Services</p>
          <ul className="mt-3 space-y-2 text-sm">
            {serviceLinks.map((item) => (
              <li key={item.label}>
                <Link href={item.href} className="transition hover:text-white">
                  {item.label}
                </Link>
              </li>
            ))}
          </ul>
        </div>

        <div>
          <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-400">Contact</p>
          <div className="mt-3 space-y-2 text-sm text-zinc-400">
            <p>info@starmaxltd.com</p>
            <p>+254 700 123 456</p>
            <p>Nairobi, Kenya</p>
          </div>
          <div className="mt-4 flex gap-2">
            {['Fb', 'In', 'X'].map((item) => (
              <span key={item} className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-zinc-700 text-xs font-semibold text-zinc-200">
                {item}
              </span>
            ))}
          </div>
        </div>
      </div>

      <div className="border-t border-zinc-800">
        <div className="mx-auto flex max-w-6xl flex-col gap-2 px-6 py-4 text-sm text-zinc-500 sm:flex-row sm:items-center sm:justify-between">
          <p>© {new Date().getFullYear()} Starmax. All rights reserved.</p>
          <p>Built with the Tenant Pro experience for modern digital operations.</p>
        </div>
      </div>
    </footer>
  );
}
