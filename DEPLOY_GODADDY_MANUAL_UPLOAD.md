# Manual Upload To GoDaddy Shared Hosting

Use this when you have no GoDaddy Terminal/SSH access.

Target backend URL:

```text
https://app.starmaxltd.com/api/
```

Health check:

```text
https://app.starmaxltd.com/api/health
```

## Read This First

This backend can run on GoDaddy shared hosting only if cPanel has **Setup Node.js App**.

Without Terminal/SSH, you also need at least one of these:

- A **Run NPM Install** button in cPanel's Node.js App screen
- Or the ability to upload a complete `node_modules` folder by ZIP/File Manager

If your hosting has neither Node.js App nor a way to install/upload Node dependencies, this backend cannot run on that plan.

## 1. Create The Database

In GoDaddy/cPanel:

1. Open **MySQL Databases**
2. Create a database, for example `CPANELUSER_tenantpro`
3. Create a database user
4. Add the user to the database with **All Privileges**

Write down:

```text
DB_NAME
DB_USER
DB_PASSWORD
DB_HOST
```

Usually `DB_HOST` is `localhost` on shared hosting.

## 2. Import The Schema With phpMyAdmin

In cPanel:

1. Open **phpMyAdmin**
2. Select the new database
3. Open **Import**
4. Upload this file:

```text
deploy/godaddy/manual-schema.sql
```

This replaces `prisma migrate deploy` for a fresh database.

## 3. Prepare `.env`

Create a file named `.env` locally with production values.

Use this shape:

```text
NODE_ENV=production
PORT=3000
FRONTEND_URL=https://app.starmaxltd.com
CORS_ORIGINS=https://app.starmaxltd.com
DATABASE_URL=mysql://DB_USER:DB_PASSWORD@localhost:3306/DB_NAME
JWT_SECRET=replace-with-a-long-random-secret
JWT_EXPIRES_IN=7d
USE_JSON_DB=false

OTP_LENGTH=6
OTP_EXPIRY_MINUTES=10
OTP_RESEND_DELAY_SECONDS=60

MAIL_HOST=smtp.example.com
MAIL_PORT=465
MAIL_SECURE=true
MAIL_USER=no-reply@example.com
MAIL_PASSWORD=replace-me
MAIL_FROM_NAME=Starmax
MAIL_FROM_EMAIL=no-reply@starmaxltd.com

MPESA_ENV=sandbox
MPESA_CONSUMER_KEY=replace-me
MPESA_CONSUMER_SECRET=replace-me
MPESA_SHORTCODE=174379
MPESA_PASSKEY=replace-me
MPESA_CALLBACK_URL=https://app.starmaxltd.com/api/payments/mpesa/callback
```

## 4. Build Locally

On your computer, stop the local dev server first so Prisma files are not locked.

Then prepare the backend:

```powershell
npm install
npx prisma generate
npm run build
```

Because the app is uploaded from Windows to GoDaddy Linux, `prisma/schema.prisma` includes Linux `binaryTargets`. That lets the uploaded Prisma client find a compatible query engine on the server.

## 5. Upload Files In cPanel File Manager

Create the app folder, for example:

```text
/home/CPANELUSER/tenant-pro-api
```

Upload these:

```text
dist/
node_modules/
prisma/
uploads/
package.json
package-lock.json
server.js
.env
firebase-service-account.json
```

If File Manager is slow, zip the folders locally, upload the ZIP, then use **Extract** in cPanel.

## 6. Create The Node.js App

In cPanel **Setup Node.js App**:

```text
Node.js version: 20 or 22
Application mode: production
Application root: tenant-pro-api
Application URL: app.starmaxltd.com
Application startup file: server.js
```

If there is a **Run NPM Install** button and you prefer not to upload `node_modules`, upload everything except `node_modules`, click **Run NPM Install**, then restart the app.

## 7. Restart And Test

Restart the Node.js app in cPanel.

Open:

```text
https://app.starmaxltd.com/api/health
```

Expected:

```json
{"status":"ok","service":"tenant-pro-api","timestamp":"..."}
```

## If It Fails

If you see a Prisma engine error, the uploaded `node_modules` does not contain the right Linux Prisma engine. The best fix is to use cPanel's **Run NPM Install** button. If that button is unavailable, this hosting plan is a poor fit for Prisma/NestJS.

If you see a database error, confirm:

- The phpMyAdmin schema import succeeded
- `DATABASE_URL` uses the exact cPanel database name and user
- The database user has all privileges

If cPanel does not show **Setup Node.js App**, GoDaddy shared hosting cannot run this backend. You need GoDaddy VPS or another Node hosting service.
