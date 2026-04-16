import blogPosts from '../../data/blog.json';
import { BackToTopButton } from '../../components/company/back-to-top';
import { BlogInsightsView } from '../../components/company/blog-insights-view';
import { CompanyFooter } from '../../components/company/company-footer';
import { CompanyNavbar } from '../../components/company/company-navbar';
import { PageHero } from '../../components/company/page-hero';

export const metadata = {
  title: 'Blog & Insights | Starmax',
  description: 'Practical articles on property technology, digital learning, and modern business systems from the Starmax team.',
};

export default function BlogPage() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <PageHero
        eyebrow="Blog & Insights"
        title="Ideas, updates & practical digital insights"
        description="Explore how Starmax approaches property technology, learning experiences, and custom platform design through concise, practical articles."
      />
      <BlogInsightsView posts={blogPosts} />
      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
