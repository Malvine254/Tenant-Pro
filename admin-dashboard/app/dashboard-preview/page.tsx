import dashboardPreview from '../../data/dashboard-preview.json';
import { BackToTopButton } from '../../components/company/back-to-top';
import { CompanyFooter } from '../../components/company/company-footer';
import { CompanyNavbar } from '../../components/company/company-navbar';
import {
  DashboardPreviewView,
  type Dashboard,
  type DashboardTone,
} from '../../components/company/dashboard-preview-view';

const allowedTones: DashboardTone[] = ['indigo', 'emerald', 'amber', 'violet'];

const previewDashboards = dashboardPreview.dashboards.map((dashboard) => ({
  ...dashboard,
  stats: dashboard.stats.map((stat) => ({
    ...stat,
    tone: (allowedTones.includes(stat.tone as DashboardTone) ? stat.tone : 'indigo') as DashboardTone,
  })),
})) as unknown as Dashboard[];

export default function DashboardPreviewPage() {
  return (
    <main className="min-h-screen bg-white text-zinc-900">
      <CompanyNavbar />
      <DashboardPreviewView dashboards={previewDashboards} />
      <CompanyFooter />
      <BackToTopButton />
    </main>
  );
}
