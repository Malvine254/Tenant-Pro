import Image from 'next/image';
import Link from 'next/link';

import blogPosts from '../../data/blog.json';
import { BackToTopButton } from '../../components/company/back-to-top';
import { CompanyFooter } from '../../components/company/company-footer';
import { CompanyNavbar } from '../../components/company/company-navbar';
import { PageHero } from '../../components/company/page-hero';
import {
  marketClusters,
  propertyExperiencePillars,
  propertyFaqs,
  propertyStats,
  showcaseProperties,
} from '../../lib/property-data';

type BlogPost = {
  slug: string;
  title: string;
  excerpt: string;
  image: string;
  category: string;
  publishedAt: string;
  author: string;
};

const propertyCategories = new Set(['Property Tech', 'Property Marketing']);
const propertyArticles = (blogPosts as BlogPost[])
  .filter((post) => propertyCategories.has(post.category))
  .sort((a, b) => new Date(b.publishedAt).getTime() - new Date(a.publishedAt).getTime())
  .slice(0, 3);

const cardAccents = [
  'from-cyan-500 via-sky-500 to-indigo-500',
  'from-emerald-500 via-teal-500 to-cyan-500',
  'from-amber-500 via-orange-500 to-rose-500',
];

export default function PropertiesPage() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <PageHero
        eyebrow="Property Showcase"
        title="Modern property presentation built for leasing, trust, and portfolio growth"
        description="A sample public-facing property experience for residential and mixed-use portfolios, combining listings, neighborhood context, operational signals, and supporting insight content."
      />

      <section className="border-b border-zinc-200 bg-zinc-50">
        <div className="mx-auto grid max-w-6xl gap-4 px-6 py-10 sm:grid-cols-2 xl:grid-cols-4">
          {propertyStats.map((item) => (
            <article key={item.label} className="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm shadow-zinc-950/5">
              <p className="text-3xl font-semibold tracking-tight text-zinc-950">{item.value}</p>
              <p className="mt-2 text-sm leading-6 text-zinc-600">{item.label}</p>
            </article>
          ))}
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-6 py-16">
        <div className="max-w-3xl">
          <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Featured Stock</p>
          <h2 className="mt-2 text-3xl font-semibold text-zinc-950 sm:text-4xl">Sample properties presented with stronger detail and better story</h2>
          <p className="mt-4 text-sm leading-7 text-zinc-600">
            Each card below is seeded sample data that shows how Starmax can position properties with clearer pricing, amenity framing, and operational confidence signals.
          </p>
        </div>

        <div className="mt-10 grid gap-6 xl:grid-cols-3">
          {showcaseProperties.map((property, index) => (
            <article key={property.name} className="overflow-hidden rounded-[32px] border border-zinc-200 bg-white shadow-[0_20px_60px_-24px_rgba(15,23,42,0.2)]">
              <div className={`bg-gradient-to-br ${cardAccents[index % cardAccents.length]} p-6 text-white`}>
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-white/70">{property.type}</p>
                    <h3 className="mt-3 text-2xl font-semibold tracking-tight">{property.name}</h3>
                    <p className="mt-2 text-sm text-white/80">{property.location}</p>
                  </div>
                  <span className="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-medium">
                    {property.occupancy}
                  </span>
                </div>

                <div className="mt-8 grid grid-cols-2 gap-3 text-sm">
                  <div className="rounded-2xl border border-white/15 bg-white/10 p-3">
                    <p className="text-white/70">Price range</p>
                    <p className="mt-1 font-semibold">{property.priceRange}</p>
                  </div>
                  <div className="rounded-2xl border border-white/15 bg-white/10 p-3">
                    <p className="text-white/70">Yield signal</p>
                    <p className="mt-1 font-semibold">{property.yield}</p>
                  </div>
                </div>
              </div>

              <div className="p-6">
                <p className="text-sm leading-7 text-zinc-600">{property.description}</p>
                <div className="mt-4 rounded-2xl bg-zinc-50 p-4">
                  <p className="text-xs font-semibold uppercase tracking-[0.25em] text-zinc-500">Highlight</p>
                  <p className="mt-2 text-sm font-medium text-zinc-800">{property.highlight}</p>
                </div>

                <div className="mt-5 flex flex-wrap gap-2">
                  {property.amenities.map((amenity) => (
                    <span key={amenity} className="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium text-zinc-700">
                      {amenity}
                    </span>
                  ))}
                </div>
              </div>
            </article>
          ))}
        </div>
      </section>

      <section className="border-y border-zinc-200 bg-[linear-gradient(180deg,#fafafa_0%,#ffffff_100%)]">
        <div className="mx-auto grid max-w-6xl gap-6 px-6 py-16 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
          <div>
            <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Property Experience</p>
            <h2 className="mt-2 text-3xl font-semibold text-zinc-950">What a more modern property page should communicate</h2>
            <p className="mt-4 text-sm leading-7 text-zinc-600">
              Good property pages sell confidence, not just square footage. They explain who the property suits, how it is run, and why the experience is different.
            </p>
          </div>

          <div className="grid gap-4 md:grid-cols-3">
            {propertyExperiencePillars.map((pillar) => (
              <article key={pillar.title} className="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm">
                <h3 className="text-lg font-semibold text-zinc-950">{pillar.title}</h3>
                <p className="mt-3 text-sm leading-6 text-zinc-600">{pillar.text}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-6 py-16">
        <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div className="max-w-3xl">
            <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Market Snapshot</p>
            <h2 className="mt-2 text-3xl font-semibold text-zinc-950">Sample neighborhood signals for positioning and investor conversations</h2>
          </div>
          <Link
            href="/contact"
            className="inline-flex items-center rounded-full bg-zinc-950 px-5 py-3 text-sm font-medium text-white transition hover:bg-indigo-600"
          >
            Request a custom property brief
          </Link>
        </div>

        <div className="mt-8 grid gap-5 lg:grid-cols-3">
          {marketClusters.map((cluster) => (
            <article key={cluster.name} className="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
              <div className="flex items-center justify-between gap-3">
                <h3 className="text-xl font-semibold text-zinc-950">{cluster.name}</h3>
                <span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                  {cluster.demand}
                </span>
              </div>
              <p className="mt-4 text-sm leading-7 text-zinc-600">{cluster.profile}</p>
              <div className="mt-6 grid grid-cols-2 gap-3 text-sm">
                <div className="rounded-2xl bg-zinc-50 p-4">
                  <p className="text-zinc-500">Average rent</p>
                  <p className="mt-1 font-semibold text-zinc-900">{cluster.avgRent}</p>
                </div>
                <div className="rounded-2xl bg-zinc-50 p-4">
                  <p className="text-zinc-500">Vacancy</p>
                  <p className="mt-1 font-semibold text-zinc-900">{cluster.vacancy}</p>
                </div>
              </div>
            </article>
          ))}
        </div>
      </section>

      <section className="border-y border-zinc-200 bg-zinc-50">
        <div className="mx-auto max-w-6xl px-6 py-16">
          <div className="max-w-3xl">
            <p className="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-500">Property Insights</p>
            <h2 className="mt-2 text-3xl font-semibold text-zinc-950">Blog articles that support the property story</h2>
            <p className="mt-4 text-sm leading-7 text-zinc-600">
              These seeded articles give the property section more depth and make the public site feel like a real operating brand rather than a static brochure.
            </p>
          </div>

          <div className="mt-10 grid gap-6 lg:grid-cols-3">
            {propertyArticles.map((post) => (
              <article key={post.slug} className="overflow-hidden rounded-[28px] border border-zinc-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                <div className="relative h-48 bg-zinc-100">
                  <Image src={post.image} alt={post.title} fill className="object-cover" unoptimized />
                </div>
                <div className="p-5">
                  <div className="flex items-center justify-between gap-3 text-xs text-zinc-500">
                    <span className="rounded-full bg-indigo-50 px-3 py-1 font-medium text-indigo-700">{post.category}</span>
                    <span>
                      {new Date(post.publishedAt).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                      })}
                    </span>
                  </div>
                  <h3 className="mt-4 text-xl font-semibold text-zinc-950">{post.title}</h3>
                  <p className="mt-3 text-sm leading-6 text-zinc-600">{post.excerpt}</p>
                  <div className="mt-5 flex items-center justify-between gap-3">
                    <span className="text-sm text-zinc-500">By {post.author}</span>
                    <Link href={`/blog/${post.slug}`} className="inline-flex items-center rounded-full bg-zinc-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-600">
                      Read article
                    </Link>
                  </div>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-6 py-16">
        <div className="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
          <div className="rounded-[32px] bg-zinc-950 p-8 text-white shadow-[0_24px_80px_-30px_rgba(15,23,42,0.55)]">
            <p className="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-200">Launch Ready</p>
            <h2 className="mt-3 max-w-2xl text-3xl font-semibold tracking-tight sm:text-4xl">
              Use this layout as the base for a live leasing or property-brand page.
            </h2>
            <p className="mt-4 max-w-2xl text-sm leading-7 text-zinc-300">
              The current version ships with sample listings and insight content. It can be extended into a fully dynamic property catalog, landing page, or investor-ready showcase.
            </p>
            <div className="mt-6 flex flex-wrap gap-3">
              <Link href="/contact" className="inline-flex items-center rounded-full bg-white px-5 py-3 text-sm font-medium text-zinc-950 transition hover:bg-cyan-100">
                Talk to Starmax
              </Link>
              <Link href="/blog" className="inline-flex items-center rounded-full border border-white/15 px-5 py-3 text-sm font-medium text-white transition hover:bg-white/10">
                Explore all insights
              </Link>
            </div>
          </div>

          <div className="space-y-4">
            {propertyFaqs.map((item) => (
              <article key={item.question} className="rounded-[28px] border border-zinc-200 bg-white p-6 shadow-sm">
                <h3 className="text-lg font-semibold text-zinc-950">{item.question}</h3>
                <p className="mt-3 text-sm leading-7 text-zinc-600">{item.answer}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}