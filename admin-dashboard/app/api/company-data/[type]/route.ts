import { readFile } from 'fs/promises';
import path from 'path';
import { NextResponse } from 'next/server';

type DataType = 'services' | 'projects' | 'testimonials';

export const dynamic = 'force-static';
export const dynamicParams = false;

export function generateStaticParams() {
  return [
    { type: 'services' },
    { type: 'projects' },
    { type: 'testimonials' },
  ];
}

const ROOT_DIR = path.basename(process.cwd()) === 'admin-dashboard'
  ? path.resolve(process.cwd(), '..')
  : process.cwd();

export async function GET(
  _request: Request,
  context: { params: Promise<{ type: string }> },
) {
  const { type } = await context.params;

  if (!['services', 'projects', 'testimonials'].includes(type)) {
    return NextResponse.json({ message: 'Unknown data type requested.' }, { status: 404 });
  }

  const filePath = path.join(ROOT_DIR, 'data', `${type as DataType}.json`);

  try {
    const raw = await readFile(filePath, 'utf8');
    return new NextResponse(raw, {
      status: 200,
      headers: { 'Content-Type': 'application/json' },
    });
  } catch {
    return NextResponse.json({ message: `Unable to read ${type} data.` }, { status: 500 });
  }
}
