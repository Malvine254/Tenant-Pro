import { mkdir, readFile, writeFile } from 'fs/promises';
import path from 'path';
import { randomUUID } from 'crypto';
import { NextResponse } from 'next/server';

type CompanyInquiry = {
  id: string;
  name: string;
  companyName?: string;
  email: string;
  phone?: string;
  serviceInterest: string;
  propertyCount?: string;
  timeline?: string;
  projectGoals: string;
  createdAt: string;
};

type JsonDb = {
  users?: unknown[];
  companyInquiries?: CompanyInquiry[];
};

const ROOT_DIR = path.basename(process.cwd()) === 'admin-dashboard'
  ? path.resolve(process.cwd(), '..')
  : process.cwd();
const DB_PATH = path.join(ROOT_DIR, 'data', 'json-db.json');

async function readDb(): Promise<JsonDb> {
  try {
    const raw = await readFile(DB_PATH, 'utf8');
    return JSON.parse(raw) as JsonDb;
  } catch {
    return { users: [], companyInquiries: [] };
  }
}

async function writeDb(db: JsonDb) {
  await mkdir(path.dirname(DB_PATH), { recursive: true });
  await writeFile(DB_PATH, JSON.stringify(db, null, 2), 'utf8');
}

export async function GET() {
  const db = await readDb();
  return NextResponse.json({
    count: db.companyInquiries?.length ?? 0,
    items: db.companyInquiries ?? [],
  });
}

export async function POST(request: Request) {
  const body = (await request.json()) as Partial<CompanyInquiry>;

  if (!body.name?.trim() || !body.email?.trim() || !body.projectGoals?.trim()) {
    return NextResponse.json(
      { message: 'Name, email, and project goals are required.' },
      { status: 400 },
    );
  }

  const db = await readDb();
  const inquiry: CompanyInquiry = {
    id: randomUUID(),
    name: body.name.trim(),
    companyName: body.companyName?.trim() || '',
    email: body.email.trim().toLowerCase(),
    phone: body.phone?.trim() || '',
    serviceInterest: body.serviceInterest?.trim() || 'General inquiry',
    propertyCount: body.propertyCount?.trim() || '',
    timeline: body.timeline?.trim() || '',
    projectGoals: body.projectGoals.trim(),
    createdAt: new Date().toISOString(),
  };

  db.companyInquiries = [inquiry, ...(db.companyInquiries ?? [])];
  await writeDb(db);

  return NextResponse.json({
    message: 'Thanks — your Starmax requirements brief has been saved locally.',
    inquiry,
  });
}
