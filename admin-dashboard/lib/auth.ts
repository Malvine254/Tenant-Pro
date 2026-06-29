import { resetDemoDataset } from './demo-tenant-ops';

export type SessionUser = {
  id: string;
  phoneNumber: string;
  email?: string | null;
  firstName?: string | null;
  lastName?: string | null;
  role: 'LANDLORD' | 'TENANT' | 'ADMIN' | 'CARETAKER';
};

export type Session = {
  accessToken: string;
  user: SessionUser;
};

const SESSION_KEY = 'tenant_pro_admin_session';
const DEMO_SESSION_KEY = 'tenant_pro_demo_session';

export function saveSession(session: Session) {
  if (typeof window === 'undefined') return;
  localStorage.setItem(SESSION_KEY, JSON.stringify(session));
}

export function getSession(): Session | null {
  if (typeof window === 'undefined') return null;

  const raw = localStorage.getItem(SESSION_KEY) ?? sessionStorage.getItem(DEMO_SESSION_KEY);
  if (!raw) return null;

  try {
    return JSON.parse(raw) as Session;
  } catch {
    return null;
  }
}

export function withDashboardMode(path: string, isDemoMode = false) {
  if (!isDemoMode) return path;
  return `${path}${path.includes('?') ? '&' : '?'}mode=demo`;
}

export function clearSession() {
  if (typeof window === 'undefined') return;
  localStorage.removeItem(SESSION_KEY);
}

export function createDemoSession(): Session {
  return {
    accessToken: 'demo-mode-token',
    user: {
      id: 'demo-account',
      phoneNumber: 'demo@starmax.preview',
      email: 'demo@starmax.preview',
      firstName: 'Demo',
      lastName: 'User',
      role: 'ADMIN',
    },
  };
}

export function saveDemoSession(session: Session = createDemoSession()) {
  if (typeof window === 'undefined') return;
  sessionStorage.setItem(DEMO_SESSION_KEY, JSON.stringify(session));
}

export function getDemoSession(): Session | null {
  if (typeof window === 'undefined') return null;

  const raw = sessionStorage.getItem(DEMO_SESSION_KEY);
  if (!raw) return null;

  try {
    return JSON.parse(raw) as Session;
  } catch {
    return null;
  }
}

export function clearDemoSession() {
  if (typeof window === 'undefined') return;
  sessionStorage.removeItem(DEMO_SESSION_KEY);
  resetDemoDataset();
}

// ── Starmax Website Admin session (separate from Tenant Pro) ──────────────
const STARMAX_ADMIN_SESSION_KEY = 'starmax_website_admin_session';

export function saveStarmaxAdminSession(session: Session) {
  if (typeof window === 'undefined') return;
  localStorage.setItem(STARMAX_ADMIN_SESSION_KEY, JSON.stringify(session));
}

export function getStarmaxAdminSession(): Session | null {
  if (typeof window === 'undefined') return null;
  const raw = localStorage.getItem(STARMAX_ADMIN_SESSION_KEY);
  if (!raw) return null;
  try {
    return JSON.parse(raw) as Session;
  } catch {
    return null;
  }
}

export function clearStarmaxAdminSession() {
  if (typeof window === 'undefined') return;
  localStorage.removeItem(STARMAX_ADMIN_SESSION_KEY);
}
