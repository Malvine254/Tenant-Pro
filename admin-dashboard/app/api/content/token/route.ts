import { createHmac, timingSafeEqual } from 'crypto';
import { NextResponse } from 'next/server';
import { signContentToken } from '../../../../lib/content-auth';

const SECRET = process.env.CONTENT_API_SECRET ?? '';

/** Verifies a locally-signed admin session token (issued by /api/admin/auth). */
function verifyLocalAdminToken(
  token: string,
): { id: string; role: string; exp: number } | null {
  try {
    const [payload, sig] = token.split('.');
    if (!payload || !sig) return null;
    const expected = createHmac('sha256', SECRET).update(payload).digest('base64url');
    if (!timingSafeEqual(Buffer.from(sig), Buffer.from(expected))) return null;
    const data = JSON.parse(Buffer.from(payload, 'base64url').toString()) as {
      id: string;
      role: string;
      exp: number;
    };
    if (Date.now() > data.exp) return null;
    return data;
  } catch {
    return null;
  }
}

export async function POST(request: Request) {
  let body: { accessToken?: string };
  try {
    body = (await request.json()) as { accessToken?: string };
  } catch {
    return NextResponse.json({ message: 'Invalid request body.' }, { status: 400 });
  }

  if (!body.accessToken?.trim()) {
    return NextResponse.json({ message: 'accessToken required.' }, { status: 400 });
  }

  // 1. Try local admin token first (no backend needed)
  const local = verifyLocalAdminToken(body.accessToken);
  if (local) {
    if (!['ADMIN', 'LANDLORD'].includes(local.role)) {
      return NextResponse.json({ message: 'Insufficient permissions.' }, { status: 403 });
    }
    return NextResponse.json({ token: signContentToken(local.id, local.role) });
  }

  // 2. Fall back to NestJS backend validation (for Tenant Pro users)
  const API_BASE = process.env.NEXT_PUBLIC_API_BASE_URL ?? 'http://localhost:3000/api';
  try {
    const res = await fetch(`${API_BASE}/users/me`, {
      headers: { Authorization: `Bearer ${body.accessToken}` },
      cache: 'no-store',
    });

    if (!res.ok) {
      return NextResponse.json({ message: 'Invalid or expired session.' }, { status: 401 });
    }

    const profile = (await res.json()) as { id: string; role: string };
    if (!['ADMIN', 'LANDLORD'].includes(profile.role)) {
      return NextResponse.json({ message: 'Insufficient permissions.' }, { status: 403 });
    }
    return NextResponse.json({ token: signContentToken(profile.id, profile.role) });
  } catch {
    return NextResponse.json(
      { message: 'Unable to verify session. Ensure backend is running.' },
      { status: 503 },
    );
  }
}
