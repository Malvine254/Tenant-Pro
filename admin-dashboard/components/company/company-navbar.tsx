"use client";

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useState } from 'react';

const serviceLinks = [
  { href: '/services', label: 'All Services' },
  { href: '/services#tenant-management-system', label: 'Tenant Management' },
  { href: '/services#e-learning-platform', label: 'E-learning Platform' },
  { href: '/services#it-consulting', label: 'IT Consulting' },
];

const productLinks = [
  { href: '/products', label: 'Solutions Overview' },
  { href: '/products#rental-system', label: 'Rental / Tenant System' },
  { href: '/products#elearning-resources', label: 'E-learning Resources' },
  { href: '/products#custom-business-systems', label: 'Custom Business Systems' },
  { href: '/dashboard?mode=demo', label: 'Dashboard Demo', newTab: true },
];

const topLinks = [
  { href: '/', label: 'Home' },
  { href: '/about', label: 'About Us' },
  { href: '/portfolio', label: 'Portfolio' },
  { href: '/blog', label: 'Insights' },
  { href: '/contact', label: 'Contact' },
];

function NavLink({ href, label, pathname }: { href: string; label: string; pathname: string }) {
  const active = pathname === href;
  return (
    <Link
      href={href}
      className={`rounded-full px-3 py-2 text-sm font-medium transition ${
        active
          ? 'bg-white text-zinc-950 shadow-sm'
          : 'text-zinc-100 hover:bg-white/10 hover:text-white'
      }`}
    >
      {label}
    </Link>
  );
}

export function CompanyNavbar() {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);

  return (
    <header className="sticky top-0 z-50 border-b border-white/10 bg-zinc-950/80 backdrop-blur-xl">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4 text-white">
        <Link href="/" className="flex items-center gap-3" onClick={() => setOpen(false)}>
          <span className="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-500 to-violet-500 font-bold text-white shadow-lg shadow-blue-500/20">
            S
          </span>
          <span>
            <span className="block text-base font-semibold">Starmax</span>
            <span className="block text-xs text-zinc-300">Innovative Digital Solutions</span>
          </span>
        </Link>

        <nav className="hidden items-center gap-1 lg:flex">
          {topLinks.slice(0, 2).map((item) => (
            <NavLink key={item.href} {...item} pathname={pathname} />
          ))}

          <div className="group relative">
            <button className="rounded-full px-3 py-2 text-sm font-medium text-zinc-100 transition hover:bg-white/10 hover:text-white">
              Services
            </button>
            <div className="invisible absolute left-0 top-full mt-2 w-64 translate-y-2 rounded-2xl border border-zinc-800 bg-zinc-950 p-2 opacity-0 shadow-2xl transition duration-200 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
              {serviceLinks.map((item) => (
                <Link key={item.href} href={item.href} className="block rounded-xl px-3 py-2 text-sm text-zinc-100 transition hover:bg-zinc-900 hover:text-white">
                  {item.label}
                </Link>
              ))}
            </div>
          </div>

          <div className="group relative">
            <button className="rounded-full px-3 py-2 text-sm font-medium text-zinc-100 transition hover:bg-white/10 hover:text-white">
              Products
            </button>
            <div className="invisible absolute left-0 top-full mt-2 w-64 translate-y-2 rounded-2xl border border-zinc-800 bg-zinc-950 p-2 opacity-0 shadow-2xl transition duration-200 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
              {productLinks.map((item) => (
                <Link
                  key={item.href}
                  href={item.href}
                  target={item.newTab ? '_blank' : undefined}
                  rel={item.newTab ? 'noopener noreferrer' : undefined}
                  className="block rounded-xl px-3 py-2 text-sm text-zinc-100 transition hover:bg-zinc-900 hover:text-white"
                >
                  {item.label}
                </Link>
              ))}
            </div>
          </div>

          {topLinks.slice(2).map((item) => (
            <NavLink key={item.href} {...item} pathname={pathname} />
          ))}
        </nav>

        <button
          type="button"
          className="rounded-xl border border-zinc-700 p-2 text-zinc-100 transition hover:bg-zinc-900 lg:hidden"
          onClick={() => setOpen((value) => !value)}
          aria-label="Toggle navigation"
          aria-expanded={open}
        >
          <span className="block h-0.5 w-5 bg-current" />
          <span className="mt-1.5 block h-0.5 w-5 bg-current" />
          <span className="mt-1.5 block h-0.5 w-5 bg-current" />
        </button>
      </div>

      <div className={`lg:hidden ${open ? 'pointer-events-auto' : 'pointer-events-none'}`}>
        <div className={`border-t border-zinc-800 bg-zinc-950 px-6 pb-6 transition-all duration-300 ${open ? 'max-h-[80vh] opacity-100' : 'max-h-0 overflow-hidden opacity-0'}`}>
          <div className="mt-4 space-y-2">
            {[...topLinks, ...serviceLinks, ...productLinks].map((item) => (
              <Link
                key={`${item.href}-${item.label}`}
                href={item.href}
                target={'newTab' in item && item.newTab ? '_blank' : undefined}
                rel={'newTab' in item && item.newTab ? 'noopener noreferrer' : undefined}
                onClick={() => setOpen(false)}
                className="block rounded-xl px-3 py-2 text-sm text-zinc-100 transition hover:bg-zinc-900 hover:text-white"
              >
                {item.label}
              </Link>
            ))}
          </div>
        </div>
      </div>
    </header>
  );
}
