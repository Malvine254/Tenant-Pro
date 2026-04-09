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

export function saveSession(session: Session) {
  localStorage.setItem(SESSION_KEY, JSON.stringify(session));
}

export function getSession(): Session | null {
  const raw = localStorage.getItem(SESSION_KEY);
  if (!raw) return null;

  try {
    return JSON.parse(raw) as Session;
  } catch {
    return null;
  }
}

export function clearSession() {
  localStorage.removeItem(SESSION_KEY);
}
