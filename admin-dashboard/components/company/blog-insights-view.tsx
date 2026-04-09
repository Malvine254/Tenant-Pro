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

export function BlogInsightsView({ posts }: { posts: BlogPost[] }) {
  const [activeCategory, setActiveCategory] = useState('All');

  const categories = useMemo(
    () => ['All', ...Array.from(new Set(posts.map((post) => post.category)))],
    [posts],
  );

  const filteredPosts = useMemo(() => {
    const sorted = [...posts].sort(
      (a, b) => new Date(b.publishedAt).getTime() - new Date(a.publishedAt).getTime(),
    );

    if (activeCategory === 'All') {
      return sorted;
    }

    return sorted.filter((post) => post.category === activeCategory);
  }, [activeCategory, posts]);

  return (
    <section className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(99,102,241,0.12),_transparent_0,_transparent_55%),linear-gradient(to_bottom,_#ffffff,_#f8fafc)] text-zinc-900">
      <div className="mx-auto max-w-6xl px-6 py-16">
        <div className="max-w-3xl">
          <p className="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Blog / Insights</p>
          <h1 className="mt-3 text-4xl font-semibold tracking-tight text-zinc-950 sm:text-5xl">
            Ideas, updates, and practical digital insights.
          </h1>
          <p className="mt-4 text-base leading-7 text-zinc-600">
            Explore how Starmax approaches property technology, learning experiences, and custom platform design through concise, practical articles.
          </p>
        </div>

        <div className="mt-8 flex flex-wrap gap-3">
          {categories.map((category) => {
            const isActive = category === activeCategory;
            return (
              <button
                key={category}
                type="button"
                onClick={() => setActiveCategory(category)}
                className={`rounded-full px-4 py-2 text-sm font-medium transition ${
                  isActive
                    ? 'bg-zinc-950 text-white shadow-lg shadow-zinc-950/15'
                    : 'border border-zinc-200 bg-white text-zinc-700 hover:border-indigo-200 hover:text-indigo-700'
                }`}
              >
                {category}
              </button>
            );
          })}
        </div>

        <div className="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
          {filteredPosts.map((post) => (
            <article
              key={post.slug}
              className="group overflow-hidden rounded-[28px] border border-zinc-200 bg-white shadow-[0_20px_60px_-24px_rgba(15,23,42,0.18)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_26px_80px_-24px_rgba(79,70,229,0.25)]"
            >
              <div className="relative h-48 w-full overflow-hidden bg-zinc-100">
                <Image
                  src={post.image}
                  alt={post.title}
                  fill
                  className="object-cover transition duration-500 group-hover:scale-105"
                  unoptimized
                />
              </div>

              <div className="p-5">
                <div className="flex items-center justify-between gap-3 text-xs text-zinc-500">
                  <span className="rounded-full bg-indigo-50 px-3 py-1 font-medium text-indigo-700">
                    {post.category}
                  </span>
                  <span>
                    {new Date(post.publishedAt).toLocaleDateString('en-US', {
                      month: 'short',
                      day: 'numeric',
                      year: 'numeric',
                    })}
                  </span>
                </div>

                <h2 className="mt-4 text-xl font-semibold text-zinc-950">{post.title}</h2>
                <p className="mt-3 text-sm leading-6 text-zinc-600">{post.excerpt}</p>

                <div className="mt-5 flex items-center justify-between gap-3">
                  <span className="text-sm text-zinc-500">By {post.author}</span>
                  <Link
                    href={`/blog/${post.slug}`}
                    className="inline-flex items-center rounded-full bg-zinc-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-600"
                  >
                    Read article
                  </Link>
                </div>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
