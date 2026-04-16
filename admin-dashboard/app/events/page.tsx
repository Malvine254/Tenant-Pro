import { BackToTopButton } from '../../components/company/back-to-top';
import { CompanyFooter } from '../../components/company/company-footer';
import { CompanyNavbar } from '../../components/company/company-navbar';
import { EventsClientSection } from '../../components/company/events-client-section';
import { PageHero } from '../../components/company/page-hero';

export const metadata = {
  title: 'Events | Starmax',
  description: 'Upcoming Starmax webinars, workshops, and product showcases. Register to secure your spot.',
};

export default function EventsPage() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <PageHero
        eyebrow="Events"
        title="Webinars, workshops & product showcases"
        description="Join Starmax live events to explore digital solutions, ask questions, and see real product demos in action."
      />
      <EventsClientSection />
      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
