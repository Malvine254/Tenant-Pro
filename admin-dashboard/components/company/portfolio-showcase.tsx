"use client";

import Image from 'next/image';
import Link from 'next/link';
import { useEffect, useMemo, useState } from 'react';

type Project = {
  title: string;
  description: string;
  image: string;
  category: string;
};

type PortfolioShowcaseProps = {
  preview?: boolean;
};

const categoryAccent: Record<string, string> = {
  'Property Tech':      'from-blue-500 via-indigo-500 to-violet-500',
  'Property Marketing': 'from-violet-500 via-purple-500 to-pink-500',
  'E-learning':         'from-cyan-400 via-blue-500 to-indigo-500',
  'Digital Strategy':   'from-emerald-400 via-teal-500 to-cyan-500',
  'Web':                'from-orange-400 via-rose-400 to-pink-500',
  'Mobile':             'from-green-400 via-emerald-400 to-teal-500',
};

const categoryBadge: Record<string, string> = {
  'Property Tech':      'bg-indigo-50 text-indigo-700 ring-indigo-200',
  'Property Marketing': 'bg-violet-50 text-violet-700 ring-violet-200',
  'E-learning':         'bg-cyan-50 text-cyan-700 ring-cyan-200',
  'Digital Strategy':   'bg-emerald-50 text-emerald-700 ring-emerald-200',
  'Web':                'bg-orange-50 text-orange-700 ring-orange-200',
  'Mobile':             'bg-green-50 text-green-700 ring-green-200',
};

function getAccent(cat: string) { return categoryAccent[cat] ?? 'from-indigo-500 via-purple-500 to-violet-500'; }
function getBadge(cat: string) { return categoryBadge[cat] ?? 'bg-zinc-100 text-zinc-700 ring-zinc-200'; }

function ProjectCard({ project, index }: { project: Project; index: number }) {
  const accent = getAccent(project.category);
  const badge = getBadge(project.category);
  return (
    <article className="group flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-indigo-100 hover:shadow-xl">
      {/* Gradient image header */}
      <div className={`relative h-44 overflow-hidden bg-gradient-to-br ${accent}`}>
        <div
          className="absolute inset-0 opacity-10"
          style={{ backgroundImage: 'radial-gradient(circle, white 1px, transparent 1px)', backgroundSize: '18px 18px' }}
        />
        <Image
          src={project.image}
          alt={project.title}
          fill
          className="relative z-10 object-contain p-8 opacity-75 mix-blend-luminosity transition duration-500 group-hover:scale-105"
          unoptimized
        />
        {/* Index number */}
        <span className="absolute bottom-3 right-4 text-4xl font-black text-white/20 select-none">
          {String(index + 1).padStart(2, '0')}
        </span>
      </div>

      <div className="flex flex-1 flex-col p-5">
        <div className="flex items-center justify-between gap-2">
          <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 ${badge}`}>
            {project.category}
          </span>
        </div>
        <h3 className="mt-3 text-base font-bold leading-snug text-zinc-900 group-hover:text-indigo-700 transition-colors">
          {project.title}
        </h3>
        <p className="mt-2 line-clamp-2 text-sm leading-6 text-zinc-500">{project.description}</p>
        <div className="mt-auto pt-4">
          <span className="flex items-center gap-1 text-xs font-semibold text-indigo-600 transition group-hover:gap-2">
            View project
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5} className="h-3 w-3">
              <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
          </span>
        </div>
      </div>
    </article>
  );
}

export function PortfolioShowcase({ preview = false }: PortfolioShowcaseProps) {
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeCategory, setActiveCategory] = useState('All');

  useEffect(() => {
    let active = true;
    fetch('/api/company-data/projects')
      .then((r) => r.json())
      .then((data: Project[]) => { if (active) { setProjects(data); setLoading(false); } })
      .catch(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);

  const categories = useMemo(
    () => ['All', ...Array.from(new Set(projects.map((p) => p.category)))],
    [projects],
  );

  const filtered = projects.filter((p) => activeCategory === 'All' || p.category === activeCategory);
  const displayed = preview ? filtered.slice(0, 3) : filtered;

  return (
    <div>
      {!preview && (
        <div className="mb-8 flex flex-wrap items-center gap-2">
          {categories.map((cat) => (
            <button
              key={cat}
              type="button"
              onClick={() => setActiveCategory(cat)}
              className={`rounded-full px-4 py-1.5 text-sm font-medium transition duration-200 ${
                activeCategory === cat
                  ? 'bg-zinc-950 text-white shadow'
                  : 'border border-zinc-200 bg-white text-zinc-600 hover:border-indigo-200 hover:text-indigo-700'
              }`}
            >
              {cat}
            </button>
          ))}
          <span className="ml-auto text-xs text-zinc-400">{filtered.length} project{filtered.length !== 1 ? 's' : ''}</span>
        </div>
      )}

      <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {loading
          ? Array.from({ length: preview ? 3 : 6 }).map((_, i) => (
              <div key={i} className="h-72 animate-pulse rounded-2xl bg-zinc-100 border border-zinc-200" />
            ))
          : displayed.map((p, i) => <ProjectCard key={`${p.title}-${p.category}`} project={p} index={i} />)}
      </div>

      {preview && (
        <div className="mt-8">
          <Link
            href="/portfolio"
            className="inline-flex items-center gap-2 rounded-full border border-zinc-200 px-5 py-2.5 text-sm font-semibold text-zinc-800 transition hover:border-indigo-200 hover:text-indigo-700"
          >
            View full portfolio
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5} className="h-3.5 w-3.5">
              <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
          </Link>
        </div>
      )}
    </div>
  );
}
