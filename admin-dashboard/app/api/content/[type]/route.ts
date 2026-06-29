import { mkdir, readFile, writeFile } from 'fs/promises';
import { randomUUID } from 'crypto';
import path from 'path';
import { NextResponse } from 'next/server';
import { requireContentAuth } from '../../../../lib/content-auth';

export const dynamic = 'force-static';

const ALLOWED_TYPES = ['blog', 'projects', 'services', 'testimonials'] as const;
type ContentType = (typeof ALLOWED_TYPES)[number];

export function generateStaticParams() {
  return ALLOWED_TYPES.map((type) => ({ type }));
}

const ROOT_DIR =
  path.basename(process.cwd()) === 'admin-dashboard'
    ? path.resolve(process.cwd(), '..')
    : process.cwd();

function filePath(type: ContentType) {
  return path.join(ROOT_DIR, 'data', `${type}.json`);
}

async function readItems(type: ContentType): Promise<Record<string, unknown>[]> {
  try {
    const raw = await readFile(filePath(type), 'utf8');
    const parsed = JSON.parse(raw) as unknown;
    return Array.isArray(parsed) ? (parsed as Record<string, unknown>[]) : [];
  } catch {
    return [];
  }
}

async function writeItems(type: ContentType, items: Record<string, unknown>[]) {
  const fp = filePath(type);
  await mkdir(path.dirname(fp), { recursive: true });
  await writeFile(fp, JSON.stringify(items, null, 2), 'utf8');
}

function isAllowedType(t: string): t is ContentType {
  return (ALLOWED_TYPES as readonly string[]).includes(t);
}

// ── GET (public read) ──────────────────────────────────────────────────────
export async function GET(
  _request: Request,
  context: { params: Promise<{ type: string }> },
) {
  const { type } = await context.params;
  if (!isAllowedType(type)) {
    return NextResponse.json({ message: 'Unknown content type.' }, { status: 404 });
  }
  const items = await readItems(type);
  return NextResponse.json(items);
}

// ── POST (create) ──────────────────────────────────────────────────────────
export async function POST(
  request: Request,
  context: { params: Promise<{ type: string }> },
) {
  const auth = requireContentAuth(request);
  if (!auth) return NextResponse.json({ message: 'Unauthorized.' }, { status: 401 });

  const { type } = await context.params;
  if (!isAllowedType(type)) {
    return NextResponse.json({ message: 'Unknown content type.' }, { status: 404 });
  }

  const body = (await request.json()) as Record<string, unknown>;
  const item = { id: randomUUID(), ...body, createdAt: new Date().toISOString() };

  const items = await readItems(type);
  items.unshift(item);
  await writeItems(type, items);

  return NextResponse.json({ message: 'Created.', item }, { status: 201 });
}

// ── PUT (update by id) ─────────────────────────────────────────────────────
export async function PUT(
  request: Request,
  context: { params: Promise<{ type: string }> },
) {
  const auth = requireContentAuth(request);
  if (!auth) return NextResponse.json({ message: 'Unauthorized.' }, { status: 401 });

  const { type } = await context.params;
  if (!isAllowedType(type)) {
    return NextResponse.json({ message: 'Unknown content type.' }, { status: 404 });
  }

  const body = (await request.json()) as Record<string, unknown>;
  const { id, ...updates } = body;
  if (!id) return NextResponse.json({ message: 'id required.' }, { status: 400 });

  const items = await readItems(type);
  const idx = items.findIndex((i) => i['id'] === id || i['slug'] === id);
  if (idx === -1) return NextResponse.json({ message: 'Item not found.' }, { status: 404 });

  items[idx] = { ...items[idx], ...updates, updatedAt: new Date().toISOString() };
  await writeItems(type, items);

  return NextResponse.json({ message: 'Updated.', item: items[idx] });
}

// ── DELETE (by id) ─────────────────────────────────────────────────────────
export async function DELETE(
  request: Request,
  context: { params: Promise<{ type: string }> },
) {
  const auth = requireContentAuth(request);
  if (!auth) return NextResponse.json({ message: 'Unauthorized.' }, { status: 401 });

  const { type } = await context.params;
  if (!isAllowedType(type)) {
    return NextResponse.json({ message: 'Unknown content type.' }, { status: 404 });
  }

  const { searchParams } = new URL(request.url);
  const id = searchParams.get('id');
  if (!id) return NextResponse.json({ message: 'id query param required.' }, { status: 400 });

  const items = await readItems(type);
  const filtered = items.filter((i) => i['id'] !== id && i['slug'] !== id);
  if (filtered.length === items.length) {
    return NextResponse.json({ message: 'Item not found.' }, { status: 404 });
  }

  await writeItems(type, filtered);
  return NextResponse.json({ message: 'Deleted.' });
}
