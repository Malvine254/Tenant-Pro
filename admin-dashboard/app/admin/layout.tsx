import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Starmax Website Admin',
  description: 'Starmax company website administration.',
  robots: 'noindex, nofollow',
};

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
