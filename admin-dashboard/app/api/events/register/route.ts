import { NextResponse } from 'next/server';
import nodemailer from 'nodemailer';

type RegistrationBody = {
  name: string;
  email: string;
  phone?: string;
  organisation?: string;
  eventTitle: string;
  eventDate: string;
  eventLocation: string;
};

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

function getParticipantHtml(data: RegistrationBody): string {
  return `
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Event Registration Confirmed</title>
</head>
<body style="margin:0;padding:0;background:#09090b;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#09090b;padding:40px 16px;">
    <tr>
      <td align="center">
        <table width="100%" style="max-width:560px;background:#18181b;border-radius:20px;overflow:hidden;border:1px solid #27272a;">

          <!-- Header gradient bar -->
          <tr>
            <td style="height:4px;background:linear-gradient(90deg,#3b82f6,#6366f1,#8b5cf6);display:block;"></td>
          </tr>

          <!-- Logo row -->
          <tr>
            <td style="padding:32px 36px 0;">
              <table cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background:linear-gradient(135deg,#3b82f6,#6366f1,#8b5cf6);width:44px;height:44px;border-radius:12px;text-align:center;vertical-align:middle;">
                    <span style="color:#fff;font-weight:700;font-size:20px;">S</span>
                  </td>
                  <td style="padding-left:12px;">
                    <p style="margin:0;color:#fff;font-size:16px;font-weight:700;">Starmax</p>
                    <p style="margin:0;color:#71717a;font-size:11px;">Innovative Digital Solutions</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Check icon -->
          <tr>
            <td style="padding:28px 36px 0;text-align:center;">
              <div style="display:inline-block;width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1,#8b5cf6);text-align:center;line-height:64px;">
                <span style="color:#fff;font-size:28px;">✓</span>
              </div>
            </td>
          </tr>

          <!-- Title -->
          <tr>
            <td style="padding:16px 36px 0;text-align:center;">
              <h1 style="margin:0;color:#fff;font-size:24px;font-weight:700;">You're registered!</h1>
              <p style="margin:8px 0 0;color:#a1a1aa;font-size:14px;line-height:1.6;">
                Hi ${data.name}, your spot is confirmed for the event below.
              </p>
            </td>
          </tr>

          <!-- Event card -->
          <tr>
            <td style="padding:24px 36px 0;">
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#09090b;border-radius:16px;border:1px solid #27272a;padding:20px 24px;">
                <tr>
                  <td>
                    <p style="margin:0 0 4px;color:#6366f1;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">Event details</p>
                    <h2 style="margin:4px 0 16px;color:#fff;font-size:18px;font-weight:700;">${data.eventTitle}</h2>
                    <table cellpadding="0" cellspacing="0" width="100%">
                      <tr>
                        <td style="padding:6px 0;color:#a1a1aa;font-size:13px;">📅&nbsp; ${data.eventDate}</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0;color:#a1a1aa;font-size:13px;">📍&nbsp; ${data.eventLocation}</td>
                      </tr>
                      ${data.organisation ? `<tr><td style="padding:6px 0;color:#a1a1aa;font-size:13px;">🏢&nbsp; ${data.organisation}</td></tr>` : ''}
                      ${data.phone ? `<tr><td style="padding:6px 0;color:#a1a1aa;font-size:13px;">📞&nbsp; ${data.phone}</td></tr>` : ''}
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Info note -->
          <tr>
            <td style="padding:20px 36px 0;">
              <p style="margin:0;color:#71717a;font-size:13px;line-height:1.7;">
                We'll send you further details including the access link or venue information closer to the event date.
                If you have any questions, reply to this email or contact us at
                <a href="https://starmax.com/contact" style="color:#6366f1;text-decoration:none;">starmax.com/contact</a>.
              </p>
            </td>
          </tr>

          <!-- Divider -->
          <tr>
            <td style="padding:28px 36px 0;">
              <div style="height:1px;background:#27272a;"></div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 36px 28px;text-align:center;">
              <p style="margin:0;color:#52525b;font-size:12px;">
                © ${new Date().getFullYear()} Starmax · Innovative Digital Solutions<br />
                You received this because you registered for a Starmax event.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>`.trim();
}

function getAdminHtml(data: RegistrationBody): string {
  return `
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8" /><title>New Event Registration</title></head>
<body style="margin:0;padding:0;background:#09090b;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#09090b;padding:40px 16px;">
    <tr>
      <td align="center">
        <table width="100%" style="max-width:520px;background:#18181b;border-radius:20px;overflow:hidden;border:1px solid #27272a;">
          <tr><td style="height:4px;background:linear-gradient(90deg,#3b82f6,#6366f1,#8b5cf6);"></td></tr>
          <tr>
            <td style="padding:28px 32px;">
              <h1 style="margin:0 0 4px;color:#fff;font-size:20px;font-weight:700;">New Event Registration</h1>
              <p style="margin:0 0 20px;color:#71717a;font-size:13px;">Someone just registered for a Starmax event.</p>
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#09090b;border-radius:12px;border:1px solid #27272a;padding:16px 20px;">
                <tr><td style="padding:5px 0;color:#a1a1aa;font-size:13px;"><b style="color:#fff;">Name:</b>&nbsp; ${data.name}</td></tr>
                <tr><td style="padding:5px 0;color:#a1a1aa;font-size:13px;"><b style="color:#fff;">Email:</b>&nbsp; ${data.email}</td></tr>
                ${data.phone ? `<tr><td style="padding:5px 0;color:#a1a1aa;font-size:13px;"><b style="color:#fff;">Phone:</b>&nbsp; ${data.phone}</td></tr>` : ''}
                ${data.organisation ? `<tr><td style="padding:5px 0;color:#a1a1aa;font-size:13px;"><b style="color:#fff;">Organisation:</b>&nbsp; ${data.organisation}</td></tr>` : ''}
                <tr><td style="padding:14px 0 5px;color:#6366f1;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;">Event</td></tr>
                <tr><td style="padding:5px 0;color:#a1a1aa;font-size:13px;"><b style="color:#fff;">Title:</b>&nbsp; ${data.eventTitle}</td></tr>
                <tr><td style="padding:5px 0;color:#a1a1aa;font-size:13px;"><b style="color:#fff;">Date:</b>&nbsp; ${data.eventDate}</td></tr>
                <tr><td style="padding:5px 0;color:#a1a1aa;font-size:13px;"><b style="color:#fff;">Location:</b>&nbsp; ${data.eventLocation}</td></tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:0 32px 24px;text-align:center;">
              <p style="margin:0;color:#52525b;font-size:12px;">© ${new Date().getFullYear()} Starmax internal notification</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`.trim();
}

export async function POST(request: Request) {
  let body: Partial<RegistrationBody>;

  try {
    body = await request.json() as Partial<RegistrationBody>;
  } catch {
    return NextResponse.json({ message: 'Invalid request body.' }, { status: 400 });
  }

  const { name, email, eventTitle, eventDate, eventLocation } = body;

  if (!name?.trim() || !email?.trim() || !eventTitle?.trim()) {
    return NextResponse.json({ message: 'Name, email and event are required.' }, { status: 400 });
  }

  // Validate email format
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    return NextResponse.json({ message: 'Invalid email address.' }, { status: 400 });
  }

  const data: RegistrationBody = {
    name: name.trim(),
    email: email.trim(),
    phone: body.phone?.trim(),
    organisation: body.organisation?.trim(),
    eventTitle: eventTitle.trim(),
    eventDate: (eventDate ?? '').trim(),
    eventLocation: (eventLocation ?? '').trim(),
  };

  const fromName = process.env.MAIL_FROM_NAME ?? 'Starmax';
  const fromEmail = process.env.MAIL_FROM_EMAIL ?? process.env.MAIL_USER ?? '';
  const bcc = process.env.EVENT_BCC_EMAIL ?? 'owuormalvine75@gmail.com';

  try {
    const transporter = getTransporter();

    // Confirmation to participant
    await transporter.sendMail({
      from: `"${fromName}" <${fromEmail}>`,
      to: data.email,
      bcc,
      subject: `Registration confirmed: ${data.eventTitle}`,
      html: getParticipantHtml(data),
    });

    // Admin notification (BCC already covers admin, this is the direct admin copy)
    await transporter.sendMail({
      from: `"${fromName}" <${fromEmail}>`,
      to: bcc,
      subject: `[New Registration] ${data.eventTitle} — ${data.name}`,
      html: getAdminHtml(data),
    });

    return NextResponse.json({ success: true, message: 'Registration confirmed.' });
  } catch (error) {
    console.error('Event registration email failed:', error);
    return NextResponse.json(
      { message: 'Registration saved but email delivery failed. We will follow up shortly.' },
      { status: 500 },
    );
  }
}
