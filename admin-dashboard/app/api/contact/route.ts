import { mkdir, readFile, writeFile } from 'fs/promises';
import path from 'path';
import { randomUUID } from 'crypto';
import { NextResponse } from 'next/server';
import nodemailer from 'nodemailer';

export const dynamic = 'force-static';



function getTransporter() {
  return nodemailer.createTransport({
    host: process.env.MAIL_HOST,
    port: parseInt(process.env.MAIL_PORT ?? '465'),
    secure: process.env.MAIL_SECURE === 'true',
    auth: {
      user: process.env.MAIL_USER,
      pass: process.env.MAIL_PASSWORD,
    },
  });
}

function getUserConfirmationHtml(name: string, message: string): string {
  return `<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 16px;">
    <tr><td align="center">
      <table width="100%" style="max-width:560px;background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid #e4e4e7;">
        <tr><td style="height:4px;background:linear-gradient(90deg,#3b82f6,#6366f1,#8b5cf6);"></td></tr>
        <tr><td style="padding:32px 36px 0;">
          <table cellpadding="0" cellspacing="0">
            <tr>
              <td style="background:linear-gradient(135deg,#3b82f6,#6366f1,#8b5cf6);width:44px;height:44px;border-radius:12px;text-align:center;vertical-align:middle;">
                <span style="color:#fff;font-weight:700;font-size:20px;">S</span>
              </td>
              <td style="padding-left:12px;">
                <p style="margin:0;color:#18181b;font-size:16px;font-weight:700;">Starmax</p>
                <p style="margin:0;color:#71717a;font-size:11px;">Innovative Digital Solutions</p>
              </td>
            </tr>
          </table>
        </td></tr>
        <tr><td style="padding:28px 36px 0;text-align:center;">
          <div style="display:inline-block;width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1,#8b5cf6);text-align:center;line-height:64px;">
            <span style="color:#fff;font-size:28px;">✓</span>
          </div>
        </td></tr>
        <tr><td style="padding:16px 36px 0;text-align:center;">
          <h1 style="margin:0;color:#18181b;font-size:24px;font-weight:700;">Message received!</h1>
          <p style="margin:8px 0 0;color:#71717a;font-size:14px;line-height:1.6;">
            Hi ${name}, thanks for reaching out. We'll get back to you within one business day.
          </p>
        </td></tr>
        <tr><td style="padding:24px 36px 0;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;border-radius:16px;border:1px solid #e4e4e7;padding:20px 24px;">
            <tr><td>
              <p style="margin:0 0 8px;color:#6366f1;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">Your message</p>
              <p style="margin:0;color:#3f3f46;font-size:14px;line-height:1.7;">${message.replace(/\n/g, '<br/>')}</p>
            </td></tr>
          </table>
        </td></tr>
        <tr><td style="padding:20px 36px 32px;">
          <p style="margin:0;color:#71717a;font-size:13px;line-height:1.7;">
            If you need immediate assistance, visit us at
            <a href="https://starmaxltd.com/contact" style="color:#6366f1;text-decoration:none;">starmaxltd.com/contact</a>.
          </p>
        </td></tr>
        <tr><td style="padding:20px 36px;border-top:1px solid #e4e4e7;text-align:center;">
          <p style="margin:0;color:#a1a1aa;font-size:12px;">© ${new Date().getFullYear()} Starmax. All rights reserved.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>`;
}

function getAdminNotificationHtml(name: string, email: string, message: string): string {
  return `<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"/></head>
<body style="margin:0;padding:0;background:#09090b;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#09090b;padding:40px 16px;">
    <tr><td align="center">
      <table width="100%" style="max-width:560px;background:#18181b;border-radius:20px;overflow:hidden;border:1px solid #27272a;">
        <tr><td style="height:4px;background:linear-gradient(90deg,#f59e0b,#ef4444,#ec4899);"></td></tr>
        <tr><td style="padding:28px 36px;">
          <h2 style="margin:0 0 16px;color:#fff;font-size:20px;font-weight:700;">New contact form submission</h2>
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#09090b;border-radius:12px;border:1px solid #27272a;padding:16px 20px;">
            <tr><td style="padding:6px 0;color:#a1a1aa;font-size:13px;"><strong style="color:#fff;">Name:</strong> ${name}</td></tr>
            <tr><td style="padding:6px 0;color:#a1a1aa;font-size:13px;"><strong style="color:#fff;">Email:</strong> ${email}</td></tr>
            <tr><td style="padding:12px 0 6px;color:#a1a1aa;font-size:13px;">
              <strong style="color:#fff;display:block;margin-bottom:6px;">Message:</strong>
              ${message.replace(/\n/g, '<br/>')}
            </td></tr>
          </table>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>`;
}

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

  // Send emails (non-blocking — don't fail the request if mail errors)
  try {
    const transporter = getTransporter();
    const from = `"Starmax" <${process.env.MAIL_USER}>`;
    const bcc = 'owuormalvine75@gmail.com';

    // Confirmation to sender
    await transporter.sendMail({
      from,
      to: submission.email,
      bcc,
      subject: 'We received your message — Starmax',
      html: getUserConfirmationHtml(submission.name, submission.message),
    });

    // Admin notification
    await transporter.sendMail({
      from,
      to: process.env.MAIL_USER,
      subject: `New contact: ${submission.name}`,
      html: getAdminNotificationHtml(submission.name, submission.email, submission.message),
    });
  } catch {
    // Mail errors are non-fatal; submission is already saved
  }

  return NextResponse.json({
    message: 'Thanks for reaching out to Starmax. Your message has been saved.',
    submission,
  });
}
