import Image from 'next/image';
import Link from 'next/link';
import { notFound } from 'next/navigation';

import blogPosts from '../../../data/blog.json';
import { BackToTopButton } from '../../../components/company/back-to-top';
import { CompanyFooter } from '../../../components/company/company-footer';
import { CompanyNavbar } from '../../../components/company/company-navbar';

type BlogPost = {
  slug: string;
  title: string;
  excerpt: string;
  image: string;
  category: string;
  publishedAt: string;
  author: string;
  content: string[];
};

const categoryAccent: Record<string, string> = {
  'Property Tech':      'from-blue-500 via-indigo-500 to-violet-500',
  'Property Marketing': 'from-violet-500 via-purple-500 to-pink-500',
  'E-learning':         'from-cyan-400 via-blue-500 to-indigo-500',
  'Digital Strategy':   'from-emerald-400 via-teal-500 to-cyan-500',
};

const categoryBadge: Record<string, string> = {
  'Property Tech':      'bg-indigo-50 text-indigo-700 ring-indigo-200',
  'Property Marketing': 'bg-violet-50 text-violet-700 ring-violet-200',
  'E-learning':         'bg-cyan-50 text-cyan-700 ring-cyan-200',
  'Digital Strategy':   'bg-emerald-50 text-emerald-700 ring-emerald-200',
};

export function generateStaticParams() {
  return (blogPosts as BlogPost[]).map((post) => ({ slug: post.slug }));
}

export default async function BlogPostPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const post = (blogPosts as BlogPost[]).find((item) => item.slug === slug);

  if (!post) {
    notFound();
  }

  const accent = categoryAccent[post.category] ?? 'from-indigo-500 via-purple-500 to-violet-500';
  const badge = categoryBadge[post.category] ?? 'bg-zinc-100 text-zinc-700 ring-zinc-200';
  const initials = post.author.split(' ').map((w) => w[0]).join('').slice(0, 2).toUpperCase();

  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />

      {/* Hero banner */}
      <div className={`border-b border-zinc-200 bg-gradient-to-br ${accent}`}>
        <div className="mx-auto max-w-4xl px-6 py-14">
          <Link
            href="/blog"
            className="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/10 px-4 py-1.5 text-sm font-medium text-white backdrop-blur-sm transition hover:bg-white/20"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="h-3.5 w-3.5">
              <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Back to Insights
          </Link>
          <div className="mt-5">
            <span className={`inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-xs font-bold uppercase tracking-wider text-white ring-1 ring-white/30`}>
              {post.category}
            </span>
          </div>
          <h1 className="mt-4 text-3xl font-bold leading-tight text-white sm:text-4xl lg:text-5xl">
            {post.title}
          </h1>
          <p className="mt-4 max-w-2xl text-base leading-7 text-white/80">{post.excerpt}</p>
          <div className="mt-5 flex items-center gap-3">
            <span className="flex h-8 w-8 items-center justify-center rounded-full bg-white/20 text-xs font-bold text-white ring-1 ring-white/30">
              {initials}
            </span>
            <div>
              <p className="text-sm font-semibold text-white">{post.author}</p>
              <p className="text-xs text-white/60">
                {new Date(post.publishedAt).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Article */}
      <article className="mx-auto max-w-4xl px-6 py-14">
        {/* Feature image */}
        <div className={`relative mb-10 h-64 overflow-hidden rounded-3xl bg-gradient-to-br ${accent} shadow-lg sm:h-80`}>
          <div
            className="absolute inset-0 opacity-10"
            style={{ backgroundImage: 'radial-gradient(circle, white 1px, transparent 1px)', backgroundSize: '22px 22px' }}
          />
          <Image src={post.image} alt={post.title} fill className="relative z-10 object-contain p-12 opacity-80 mix-blend-luminosity" unoptimized />
        </div>

        {/* Content */}
        <div className="space-y-5">
          {post.content.map((paragraph, i) => (
            <p key={i} className="text-base leading-8 text-zinc-700">
              {paragraph}
            </p>
          ))}
        </div>

        {/* Footer nav */}
        <div className="mt-14 flex items-center justify-between border-t border-zinc-200 pt-8">
          <Link
            href="/blog"
            className="inline-flex items-center gap-2 rounded-full border border-zinc-200 px-5 py-2.5 text-sm font-medium text-zinc-700 transition hover:border-indigo-200 hover:text-indigo-700"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} className="h-3.5 w-3.5">
              <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            All articles
          </Link>
          <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ${badge}`}>
            {post.category}
          </span>
        </div>
      </article>

      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
