import { mkdir, readFile, writeFile } from 'fs/promises';
import path from 'path';
import { randomUUID } from 'crypto';
import { NextResponse } from 'next/server';

type ContactSubmission = {
  id: string;
  name: string;
  email: string;
  message: string;
  createdAt: string;
};

const ROOT_DIR = path.basename(process.cwd()) === 'admin-dashboard'
  ? path.resolve(process.cwd(), '..')
  : process.cwd();
const DB_PATH = path.join(ROOT_DIR, 'data', 'contact-submissions.json');

async function readSubmissions(): Promise<ContactSubmission[]> {
  try {
    const raw = await readFile(DB_PATH, 'utf8');
    return JSON.parse(raw) as ContactSubmission[];
  } catch {
    return [];
  }
}

async function writeSubmissions(items: ContactSubmission[]) {
  await mkdir(path.dirname(DB_PATH), { recursive: true });
  await writeFile(DB_PATH, JSON.stringify(items, null, 2), 'utf8');
}

export async function GET() {
  const items = await readSubmissions();
  return NextResponse.json({ count: items.length, items });
}

export async function POST(request: Request) {
  const body = (await request.json()) as Partial<ContactSubmission>;

  if (!body.name?.trim() || !body.email?.trim() || !body.message?.trim()) {
    return NextResponse.json(
      { message: 'Name, email, and message are required.' },
      { status: 400 },
    );
  }

  const items = await readSubmissions();
  const submission: ContactSubmission = {
    id: randomUUID(),
    name: body.name.trim(),
    email: body.email.trim().toLowerCase(),
    message: body.message.trim(),
    createdAt: new Date().toISOString(),
  };

  items.unshift(submission);
  await writeSubmissions(items);

  return NextResponse.json({
    message: 'Thanks for reaching out to Starmax. Your message has been saved.',
    submission,
  });
}
