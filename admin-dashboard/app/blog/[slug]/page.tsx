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

  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />

      <article className="mx-auto max-w-4xl px-6 py-16">
        <Link
          href="/blog"
          className="inline-flex items-center rounded-full border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-indigo-200 hover:text-indigo-700"
        >
          ← Back to Insights
        </Link>

        <div className="mt-6">
          <span className="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">
            {post.category}
          </span>
          <h1 className="mt-4 text-4xl font-semibold tracking-tight text-zinc-950 sm:text-5xl">
            {post.title}
          </h1>
          <p className="mt-4 text-base leading-7 text-zinc-600">{post.excerpt}</p>
          <div className="mt-4 flex flex-wrap items-center gap-3 text-sm text-zinc-500">
            <span>By {post.author}</span>
            <span>•</span>
            <span>
              {new Date(post.publishedAt).toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
              })}
            </span>
          </div>
        </div>

        <div className="relative mt-8 h-64 overflow-hidden rounded-[28px] border border-zinc-200 bg-zinc-100 shadow-sm sm:h-80">
          <Image src={post.image} alt={post.title} fill className="object-cover" unoptimized />
        </div>

        <div className="prose prose-zinc mt-8 max-w-none">
          {post.content.map((paragraph) => (
            <p key={paragraph} className="mb-4 text-base leading-8 text-zinc-700">
              {paragraph}
            </p>
          ))}
        </div>
      </article>

      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
