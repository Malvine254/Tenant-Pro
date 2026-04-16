import { createHmac } from 'crypto';
import { NextResponse } from 'next/server';

const ADMIN_EMAIL = process.env.STARMAX_ADMIN_EMAIL ?? '';
const ADMIN_PASSWORD = process.env.STARMAX_ADMIN_PASSWORD ?? '';
const SECRET = process.env.CONTENT_API_SECRET ?? '';

export async function POST(request: Request) {
  if (!ADMIN_EMAIL || !ADMIN_PASSWORD) {
    return NextResponse.json(
      { message: 'Admin credentials are not configured. Set STARMAX_ADMIN_EMAIL and STARMAX_ADMIN_PASSWORD in .env.local.' },
      { status: 503 },
    );
  }

  let body: { email?: string; password?: string };
  try {
    body = (await request.json()) as { email?: string; password?: string };
  } catch {
    return NextResponse.json({ message: 'Invalid request body.' }, { status: 400 });
  }

  if (
    body.email?.trim().toLowerCase() !== ADMIN_EMAIL.toLowerCase() ||
    body.password !== ADMIN_PASSWORD
  ) {
    return NextResponse.json({ message: 'Invalid credentials.' }, { status: 401 });
  }

  // Issue a locally-signed admin session token (8-hour expiry)
  const exp = Date.now() + 8 * 60 * 60 * 1000;
  const payload = Buffer.from(
    JSON.stringify({ id: 'starmax-admin', role: 'ADMIN', exp }),
  ).toString('base64url');
  const sig = createHmac('sha256', SECRET).update(payload).digest('base64url');
  const accessToken = `${payload}.${sig}`;

  return NextResponse.json({
    accessToken,
    user: {
      id: 'starmax-admin',
      email: ADMIN_EMAIL,
      firstName: 'Starmax',
      lastName: 'Admin',
      role: 'ADMIN',
      phoneNumber: '',
    },
  });
}
