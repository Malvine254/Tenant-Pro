import { createHmac, timingSafeEqual } from 'crypto';

const SECRET = process.env.CONTENT_API_SECRET ?? '';

/** Creates a signed CMS token: base64(payload).signature */
export function signContentToken(userId: string, role: string): string {
  const exp = Date.now() + 4 * 60 * 60 * 1000; // 4-hour expiry
  const payload = Buffer.from(JSON.stringify({ userId, role, exp })).toString('base64url');
  const sig = createHmac('sha256', SECRET).update(payload).digest('base64url');
  return `${payload}.${sig}`;
}

/** Verifies a CMS token. Returns the payload or null if invalid/expired. */
export function verifyContentToken(
  token: string,
): { userId: string; role: string; exp: number } | null {
  try {
    const [payload, sig] = token.split('.');
    if (!payload || !sig) return null;

    const expected = createHmac('sha256', SECRET).update(payload).digest('base64url');
    const match = timingSafeEqual(Buffer.from(sig), Buffer.from(expected));
    if (!match) return null;

    const data = JSON.parse(Buffer.from(payload, 'base64url').toString()) as {
      userId: string;
      role: string;
      exp: number;
    };

    if (Date.now() > data.exp) return null;
    return data;
  } catch {
    return null;
  }
}

/** Extracts and verifies the Bearer token from an Authorization header. */
export function requireContentAuth(
  request: Request,
  allowedRoles: string[] = ['ADMIN', 'LANDLORD'],
): { userId: string; role: string } | null {
  const header = request.headers.get('authorization') ?? '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : null;
  if (!token) return null;

  const data = verifyContentToken(token);
  if (!data) return null;
  if (!allowedRoles.includes(data.role)) return null;
  return { userId: data.userId, role: data.role };
}
