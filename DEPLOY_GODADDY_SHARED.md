# Starmax Tenant Services Backend On GoDaddy Shared Hosting

Target domain:

```text
https://app.starmaxltd.com
```

Backend health check after deployment:

```text
https://app.starmaxltd.com/api/health
```

## Important Limitation

GoDaddy shared hosting only works for this backend if your plan/cPanel includes **Setup Node.js App** or an equivalent Node.js Application Manager. If your cPanel only supports PHP/static websites, this NestJS backend cannot run there as a long-running API.

If Node.js App is not available, use one of these instead:

- GoDaddy VPS
- Render / Railway / Fly.io
- DigitalOcean / Hetzner / Azure / AWS

The files in `Dockerfile` and `docker-compose.prod.yml` are for VPS-style hosting, not ordinary shared hosting.

## 1. Create The Subdomain

In GoDaddy/cPanel:

1. Open **Domains** or **Subdomains**
2. Create `app.starmaxltd.com`
3. Point it to a folder outside your public website if cPanel allows it, for example:

```text
/home/YOUR_CPANEL_USER/tenant-pro-api
```

Enable SSL for `app.starmaxltd.com` in GoDaddy/cPanel.

## 2. Create MySQL Database

In cPanel:

1. Open **MySQL Databases**
2. Create a database, for example `CPANELUSER_tenantpro`
3. Create a database user
4. Assign the user to the database with all privileges

Your `DATABASE_URL` will look like:

```text
mysql://DB_USER:DB_PASSWORD@localhost:3306/DB_NAME
```

On GoDaddy shared hosting, the database host is often `localhost`, but use the host shown by cPanel if different.

## 3. Prepare Environment Variables

Create `.env.production` from `.env.example`, then update values:

```text
NODE_ENV=production
PORT=3000
FRONTEND_URL=https://app.starmaxltd.com
CORS_ORIGINS=https://app.starmaxltd.com
DATABASE_URL=mysql://DB_USER:DB_PASSWORD@localhost:3306/DB_NAME
JWT_SECRET=use-a-long-random-secret
MPESA_CALLBACK_URL=https://app.starmaxltd.com/api/payments/mpesa/callback
```

Also fill in:

```text
MAIL_*
MPESA_CONSUMER_KEY
MPESA_CONSUMER_SECRET
MPESA_SHORTCODE
MPESA_PASSKEY
```

Upload `firebase-service-account.json` to the application root.

## 4. Upload The App

From your local machine:

```bash
npm install
npm run build
```

Upload these to the GoDaddy application folder:

```text
dist/
prisma/
package.json
package-lock.json
server.js
.env.production
firebase-service-account.json
uploads/              # create if missing
```

Do not upload `node_modules`; install dependencies on the server.

## 5. Install Dependencies On GoDaddy

Using cPanel Terminal or SSH:

```bash
cd ~/tenant-pro-api
cp .env.production .env
npm ci --omit=dev
npm run prisma:generate
npm run prisma:deploy
```

Optional first-time seed:

```bash
npm run prisma:seed
```

Do not seed production with demo passwords unless you have changed them in `.env`.

## 6. Create The Node.js App In cPanel

In cPanel **Setup Node.js App**:

```text
Node.js version: 20 or 22
Application mode: production
Application root: tenant-pro-api
Application URL: app.starmaxltd.com
Application startup file: server.js
```

Add environment variables in the cPanel UI, or keep `.env` in the app root if your Node.js setup loads files from disk.

Start or restart the app.

## 7. Verify

Open:

```text
https://app.starmaxltd.com/api/health
```

Expected:

```json
{"status":"ok","service":"tenant-pro-api","timestamp":"..."}
```

## 8. Android App

The release Android app is already configured to use:

```text
https://app.starmaxltd.com/api/
```

After the backend is live, build the release APK/AAB.

## Troubleshooting

If cPanel asks for a startup file, use the included `server.js`; it loads the compiled Nest app from `dist/main.js`.

If migrations fail, confirm:

- `DATABASE_URL` is exact
- The database user has all privileges
- `npm run prisma:generate` completed
- The MySQL server allows Prisma's migration table creation

If the Node.js app feature is missing, this project cannot be deployed on that shared plan. Use VPS hosting or a Node-friendly app platform.
