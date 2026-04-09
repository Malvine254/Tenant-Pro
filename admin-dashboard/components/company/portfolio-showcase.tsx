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

export function PortfolioShowcase({ preview = false }: PortfolioShowcaseProps) {
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeCategory, setActiveCategory] = useState('All');

  useEffect(() => {
    let active = true;

    fetch('/api/company-data/projects')
      .then((response) => response.json())
      .then((data: Project[]) => {
        if (active) {
          setProjects(data);
          setLoading(false);
        }
      })
      .catch(() => {
        if (active) {
          setLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, []);

  const categories = useMemo(
    () => ['All', ...Array.from(new Set(projects.map((project) => project.category)))],
    [projects],
  );

  const filteredProjects = projects.filter(
    (project) => activeCategory === 'All' || project.category === activeCategory,
  );

  const displayProjects = preview ? filteredProjects.slice(0, 3) : filteredProjects;

  return (
    <div>
      {!preview ? (
        <div className="mb-6 flex flex-wrap gap-2">
          {categories.map((category) => (
            <button
              key={category}
              type="button"
              onClick={() => setActiveCategory(category)}
              className={`rounded-full px-4 py-2 text-sm font-medium transition ${
                activeCategory === category
                  ? 'bg-zinc-900 text-white'
                  : 'border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50'
              }`}
            >
              {category}
            </button>
          ))}
        </div>
      ) : null}

      <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        {loading
          ? Array.from({ length: preview ? 3 : 4 }).map((_, index) => (
              <div key={index} className="tp-skeleton h-72 rounded-2xl border border-zinc-200" />
            ))
          : displayProjects.map((project) => (
              <article key={`${project.title}-${project.category}`} className="tp-card-lift overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                <div className="relative h-48 w-full overflow-hidden bg-zinc-100">
                  <Image src={project.image} alt={project.title} fill className="object-cover" unoptimized />
                </div>
                <div className="p-5">
                  <span className="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                    {project.category}
                  </span>
                  <h3 className="mt-3 text-lg font-semibold text-zinc-900">{project.title}</h3>
                  <p className="mt-2 text-sm leading-6 text-zinc-600">{project.description}</p>
                </div>
              </article>
            ))}
      </div>

      {preview ? (
        <div className="mt-6">
          <Link href="/portfolio" className="inline-flex rounded-full border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50">
            View full portfolio
          </Link>
        </div>
      ) : null}
    </div>
  );
}
