# Email Authentication & Reminder System Documentation

## Overview

This system provides email-based OTP authentication, password reset, and automated reminder emails using your cPanel SMTP credentials (`noreply@starmaxltd.com`).

---

## Configuration

### Environment Variables (.env)

```env
# Email SMTP Configuration
MAIL_HOST="mail.starmaxltd.com"
MAIL_PORT="465"
MAIL_SECURE="true"
MAIL_USER="noreply@starmaxltd.com"
MAIL_PASSWORD="H{Y}E8pO]=ci"
MAIL_FROM_NAME="Starmax Ltd"
MAIL_FROM_EMAIL="noreply@starmaxltd.com"

# OTP Configuration
OTP_LENGTH="6"
OTP_EXPIRY_MINUTES="10"
OTP_RESEND_DELAY_SECONDS="60"
```

---

## Features Implemented

### 1. **Email-Based OTP Authentication**

Users can log in using email and OTP instead of password.

#### Endpoints:

**Request OTP**
```http
POST /api/auth/email-otp/request
Content-Type: application/json

{
  "email": "user@example.com"
}
```

**Response:**
```json
{
  "message": "OTP sent to your email",
  "email": "user@example.com",
  "expiresAt": "2026-04-03T10:00:00.000Z"
}
```

**Verify OTP & Login**
```http
POST /api/auth/email-otp/verify
Content-Type: application/json

{
  "email": "user@example.com",
  "code": "123456"
}
```

**Response:**
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIs...",
  "tokenType": "Bearer",
  "user": {
    "id": "uuid",
    "phoneNumber": "+254700000000",
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "role": "TENANT"
  }
}
```

---

### 2. **Password Reset Flow**

Users can reset their password via email OTP.

#### Endpoints:

**Request Password Reset**
```http
POST /api/auth/forgot-password
Content-Type: application/json

{
  "email": "user@example.com"
}
```

**Response:**
```json
{
  "message": "Password reset code sent to your email"
}
```

**Reset Password with OTP**
```http
POST /api/auth/reset-password
Content-Type: application/json

{
  "email": "user@example.com",
  "code": "123456",
  "newPassword": "NewSecurePass@123"
}
```

**Response:**
```json
{
  "message": "Password reset successfully"
}
```

---

### 3. **Automated Reminder Emails**

#### Scheduled Jobs (Cron):

**Rent Reminders**
- Runs daily at 9:00 AM
- Sends reminders 3 days before rent due date
- To tenants with pending invoices

**Overdue Payment Notices**
- Runs daily at 10:00 AM
- Sends notices for all overdue invoices
- Includes number of days overdue

---

### 4. **Manual Reminder Endpoints**

These require authentication and proper roles.

**Send Manual Rent Reminder**
```http
POST /api/reminders/rent/:invoiceId
Authorization: Bearer <token>
```

*Required Roles:* LANDLORD, ADMIN

**Send Maintenance Update**
```http
POST /api/reminders/maintenance/:requestId
Authorization: Bearer <token>
Content-Type: application/json

{
  "status": "IN_PROGRESS",
  "message": "Technician has been assigned and will visit tomorrow."
}
```

*Required Roles:* LANDLORD, ADMIN, CARETAKER

**Send Welcome Email**
```http
POST /api/reminders/welcome/:userId
Authorization: Bearer <token>
```

*Required Roles:* ADMIN

---

## Email Templates

### 1. **OTP Email**
- Clean, professional design
- Large, centered OTP code
- Expiration time displayed
- Security notice

### 2. **Password Reset Email**
- Reset link button
- Expiration notice (1 hour)
- Security warning

### 3. **Welcome Email**
- Branded header
- Feature highlights
- Call-to-action

### 4. **Rent Reminder**
- Property name
- Amount due
- Due date
- Payment instructions

### 5. **Overdue Notice**
- Urgent styling
- Days overdue
- Amount owed
- Contact information

### 6. **Maintenance Update**
- Request ID
- Current status
- Update message

---

## OTP Security Features

### Rate Limiting
- Configurable resend delay (default: 60 seconds)
- Prevents OTP spam

### Expiration
- Configurable expiry time (default: 10 minutes)
- Automatic cleanup on verification

### Configurable Length
- Default: 6 digits
- Can be changed via `OTP_LENGTH` env variable

### Error Messages
- Generic messages to prevent user enumeration
- Clear expiration errors
- Invalid code detection

---

## Testing the Implementation

### 1. Test Email OTP Login

```bash
# Request OTP
curl -X POST http://localhost:3000/api/auth/email-otp/request \
  -H "Content-Type: application/json" \
  -d '{"email":"tenant@example.com"}'

# Check email for OTP code

# Verify OTP
curl -X POST http://localhost:3000/api/auth/email-otp/verify \
  -H "Content-Type: application/json" \
  -d '{"email":"tenant@example.com","code":"123456"}'
```

### 2. Test Password Reset

```bash
# Request reset
curl -X POST http://localhost:3000/api/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email":"tenant@example.com"}'

# Check email for OTP

# Reset password
curl -X POST http://localhost:3000/api/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email":"tenant@example.com",
    "code":"123456",
    "newPassword":"NewPassword@123"
  }'
```

### 3. Test Manual Reminders

```bash
# Get auth token first
TOKEN="your-jwt-token"

# Send rent reminder
curl -X POST http://localhost:3000/api/reminders/rent/invoice-uuid \
  -H "Authorization: Bearer $TOKEN"

# Send maintenance update
curl -X POST http://localhost:3000/api/reminders/maintenance/request-uuid \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status":"IN_PROGRESS",
    "message":"We are working on your request"
  }'
```

---

## Email Service Architecture

### Components:

1. **EmailService** (`src/modules/email/email.service.ts`)
   - Handles SMTP connection
   - Email template rendering
   - Sending all email types

2. **OtpService** (`src/modules/auth/otp.service.ts`)
   - OTP generation
   - OTP verification
   - Expiration management
   - Rate limiting

3. **RemindersService** (`src/modules/reminders/reminders.service.ts`)
   - Scheduled jobs
   - Manual reminder triggers
   - Database queries for due/overdue invoices

4. **AuthService** (`src/modules/auth/auth.service.ts`)
   - Email OTP login flow
   - Password reset flow
   - JWT token generation

---

## Troubleshooting

### Email Not Sending

1. **Check SMTP credentials:**
   ```bash
   # Verify .env file has correct settings
   cat .env | grep MAIL_
   ```

2. **Check logs:**
   ```bash
   # Server logs will show email errors
   npm run start:dev
   ```

3. **Test SMTP connection:**
   - Use a tool like Postman or curl to trigger an OTP request
   - Check server console for nodemailer errors

### OTP Not Working

1. **Check expiration:**
   - Default is 10 minutes
   - Verify OTP was generated recently

2. **Check resend delay:**
   - Wait 60 seconds between requests
   - Error message indicates wait time

3. **Verify OTP code:**
   - Code is case-sensitive
   - Must be exact match

### Cron Jobs Not Running

1. **Verify ScheduleModule is imported:**
   ```typescript
   // src/app.module.ts
   imports: [ScheduleModule.forRoot(), ...]
   ```

2. **Check server is running:**
   - Cron jobs only run when server is active
   - Check logs for "Starting rent reminder job..."

---

## Future Enhancements

### Potential Improvements:

1. **SMS Integration**
   - Add SMS OTP via Twilio/Africa's Talking
   - Fallback to SMS if email fails

2. **Email Queue**
   - Use Bull/BullMQ for email queuing
   - Retry failed emails

3. **Email Tracking**
   - Track email opens/clicks
   - Delivery status tracking

4. **Template Customization**
   - Admin UI to edit email templates
   - Multi-language support

5. **Rate Limiting**
   - Global rate limits per IP
   - Per-user rate limits

6. **Email Preferences**
   - User can opt-out of certain reminders
   - Preferred notification times

---

## Security Best Practices

1. **Never log OTP codes** in production
2. **Use environment variables** for all sensitive data
3. **Implement rate limiting** on OTP endpoints
4. **Monitor for abuse** - track failed OTP attempts
5. **Use HTTPS** in production for reset links
6. **Validate email addresses** before sending
7. **Implement CAPTCHA** for public OTP endpoints
8. **Set SPF, DKIM, DMARC** records on your domain

---

## Production Checklist

Before deploying to production:

- [ ] Update `FRONTEND_URL` in .env for password reset links
- [ ] Set strong JWT_SECRET
- [ ] Configure domain DNS (SPF, DKIM, DMARC)
- [ ] Test all email types
- [ ] Set up email monitoring/alerting
- [ ] Configure firewall rules for SMTP
- [ ] Set up log aggregation
- [ ] Test cron jobs in production environment
- [ ] Set up backup email service (fallback)
- [ ] Configure rate limiting
- [ ] Add CAPTCHA to public endpoints

---

## Support

For issues or questions:
- Check server logs: `npm run start:dev`
- Verify .env configuration
- Test SMTP connection manually
- Check email spam/junk folders

---

**Last Updated:** April 3, 2026  
**Version:** 1.0.0
