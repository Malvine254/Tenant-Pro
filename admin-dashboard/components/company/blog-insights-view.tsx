"use client";

import Image from 'next/image';
import Link from 'next/link';
import { useMemo, useState } from 'react';

export type BlogPost = {
  slug: string;
  title: string;
  excerpt: string;
  image: string;
  category: string;
  publishedAt: string;
  author: string;
};

const categoryAccent: Record<string, string> = {
  'Property Tech':     'from-blue-500 via-indigo-500 to-violet-500',
  'Property Marketing':'from-violet-500 via-purple-500 to-pink-500',
  'E-learning':        'from-cyan-400 via-blue-500 to-indigo-500',
  'Digital Strategy':  'from-emerald-400 via-teal-500 to-cyan-500',
  'Default':           'from-indigo-500 via-purple-500 to-violet-500',
};

const categoryBadge: Record<string, string> = {
  'Property Tech':      'bg-indigo-50 text-indigo-700 ring-indigo-200',
  'Property Marketing': 'bg-violet-50 text-violet-700 ring-violet-200',
  'E-learning':         'bg-cyan-50 text-cyan-700 ring-cyan-200',
  'Digital Strategy':   'bg-emerald-50 text-emerald-700 ring-emerald-200',
};

function getAccent(category: string) {
  return categoryAccent[category] ?? categoryAccent['Default'];
}

function getBadge(category: string) {
  return categoryBadge[category] ?? 'bg-zinc-100 text-zinc-700 ring-zinc-200';
}

function AuthorAvatar({ name }: { name: string }) {
  const initials = name.split(' ').map((w) => w[0]).join('').slice(0, 2).toUpperCase();
  return (
    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 text-[10px] font-bold text-white">
      {initials}
    </span>
  );
}

function FeaturedCard({ post }: { post: BlogPost }) {
  const accent = getAccent(post.category);
  const badge = getBadge(post.category);
  return (
    <Link
      href={`/blog/${post.slug}`}
      className="group relative mb-10 flex flex-col overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-2xl sm:flex-row"
    >
      {/* Left: gradient + image */}
      <div className={`relative overflow-hidden sm:w-72 md:w-80 shrink-0`}>
        <div className={`absolute inset-0 bg-gradient-to-br ${accent}`} />
        <div
          className="absolute inset-0 opacity-10"
          style={{ backgroundImage: 'radial-gradient(circle, white 1px, transparent 1px)', backgroundSize: '20px 20px' }}
        />
        <Image
          src={post.image}
          alt={post.title}
          fill
          className="relative z-10 object-contain p-10 opacity-80 mix-blend-luminosity transition duration-500 group-hover:scale-105"
          unoptimized
        />
        <div className="relative z-20 p-6">
          <span className="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-white ring-1 ring-white/30">
            {post.category}
          </span>
        </div>
      </div>

      {/* Right: content */}
      <div className="flex flex-1 flex-col justify-between p-7 sm:p-8">
        <div>
          <span className="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-500">Featured</span>
          <h2 className="mt-2 text-2xl font-bold leading-tight text-zinc-900 group-hover:text-indigo-700 transition-colors sm:text-3xl">
            {post.title}
          </h2>
          <p className="mt-3 text-sm leading-7 text-zinc-600">{post.excerpt}</p>
        </div>
        <div className="mt-6 flex items-center justify-between gap-4">
          <div className="flex items-center gap-2">
            <AuthorAvatar name={post.author} />
            <div>
              <p className="text-xs font-semibold text-zinc-800">{post.author}</p>
              <p className="text-[11px] text-zinc-400">
                {new Date(post.publishedAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
              </p>
            </div>
          </div>
          <span className={`inline-flex items-center gap-2 rounded-full bg-gradient-to-r ${accent} px-4 py-2 text-xs font-semibold text-white shadow transition group-hover:scale-[1.03]`}>
            Read article
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5} className="h-3.5 w-3.5">
              <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
          </span>
        </div>
      </div>
    </Link>
  );
}

function BlogCard({ post }: { post: BlogPost }) {
  const accent = getAccent(post.category);
  const badge = getBadge(post.category);
  return (
    <Link
      href={`/blog/${post.slug}`}
      className="group flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-indigo-100 hover:shadow-xl"
    >
      {/* Gradient image header */}
      <div className={`relative h-44 overflow-hidden bg-gradient-to-br ${accent}`}>
        <div
          className="absolute inset-0 opacity-10"
          style={{ backgroundImage: 'radial-gradient(circle, white 1px, transparent 1px)', backgroundSize: '18px 18px' }}
        />
        <Image
          src={post.image}
          alt={post.title}
          fill
          className="relative z-10 object-contain p-8 opacity-75 mix-blend-luminosity transition duration-500 group-hover:scale-105"
          unoptimized
        />
        {/* gradient top accent line */}
        <div className={`absolute bottom-0 inset-x-0 h-px bg-gradient-to-r ${accent} opacity-60`} />
      </div>

      <div className="flex flex-1 flex-col p-5">
        {/* Category + date row */}
        <div className="flex items-center justify-between gap-2">
          <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ${badge}`}>
            {post.category}
          </span>
          <span className="text-[11px] text-zinc-400">
            {new Date(post.publishedAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
          </span>
        </div>

        <h2 className="mt-3 text-base font-bold leading-snug text-zinc-900 group-hover:text-indigo-700 transition-colors line-clamp-2">
          {post.title}
        </h2>
        <p className="mt-2 line-clamp-2 text-sm leading-6 text-zinc-500">{post.excerpt}</p>

        {/* Footer */}
        <div className="mt-auto flex items-center justify-between pt-4">
          <div className="flex items-center gap-2">
            <AuthorAvatar name={post.author} />
            <span className="text-xs font-medium text-zinc-600">{post.author}</span>
          </div>
          <span className="flex items-center gap-1 text-xs font-semibold text-indigo-600 transition group-hover:gap-2">
            Read
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5} className="h-3 w-3">
              <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
          </span>
        </div>
      </div>
    </Link>
  );
}

export function BlogInsightsView({ posts }: { posts: BlogPost[] }) {
  const [activeCategory, setActiveCategory] = useState('All');

  const categories = useMemo(
    () => ['All', ...Array.from(new Set(posts.map((post) => post.category)))],
    [posts],
  );

  const sorted = useMemo(
    () => [...posts].sort((a, b) => new Date(b.publishedAt).getTime() - new Date(a.publishedAt).getTime()),
    [posts],
  );

  const filteredPosts = useMemo(
    () => activeCategory === 'All' ? sorted : sorted.filter((p) => p.category === activeCategory),
    [activeCategory, sorted],
  );

  const [featured, ...rest] = filteredPosts;

  return (
    <section className="bg-white">
      <div className="mx-auto max-w-6xl px-6 py-14">

        {/* Filter bar */}
        <div className="flex flex-wrap items-center gap-2">
          {categories.map((cat) => {
            const active = cat === activeCategory;
            return (
              <button
                key={cat}
                type="button"
                onClick={() => setActiveCategory(cat)}
                className={`rounded-full px-4 py-1.5 text-sm font-medium transition duration-200 ${
                  active
                    ? 'bg-zinc-950 text-white shadow'
                    : 'border border-zinc-200 bg-white text-zinc-600 hover:border-indigo-200 hover:text-indigo-700'
                }`}
              >
                {cat}
              </button>
            );
          })}
          <span className="ml-auto text-xs text-zinc-400">{filteredPosts.length} article{filteredPosts.length !== 1 ? 's' : ''}</span>
        </div>

        {/* Featured post */}
        {featured && (
          <div className="mt-8">
            <FeaturedCard post={featured} />
          </div>
        )}

        {/* Grid */}
        {rest.length > 0 && (
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {rest.map((post) => (
              <BlogCard key={post.slug} post={post} />
            ))}
          </div>
        )}

        {filteredPosts.length === 0 && (
          <div className="mt-16 text-center">
            <p className="text-zinc-500">No articles in this category yet.</p>
          </div>
        )}
      </div>
    </section>
  );
}
