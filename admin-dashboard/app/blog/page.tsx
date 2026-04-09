import blogPosts from '../../data/blog.json';
import { BackToTopButton } from '../../components/company/back-to-top';
import { BlogInsightsView } from '../../components/company/blog-insights-view';
import { CompanyFooter } from '../../components/company/company-footer';
import { CompanyNavbar } from '../../components/company/company-navbar';

export default function BlogPage() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <BlogInsightsView posts={blogPosts} />
      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
