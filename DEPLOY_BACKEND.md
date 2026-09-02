# Starmax Tenant Services Backend Deployment

Target domain: `app.starmaxltd.com`

The backend serves API routes under `/api`, so the deployed health check is:

```text
https://app.starmaxltd.com/api/health
```

## 1. Point DNS

Create an `A` record:

```text
app.starmaxltd.com -> YOUR_SERVER_PUBLIC_IP
```

Wait for DNS propagation before issuing SSL.

## 2. Prepare The Server

Install Docker, Docker Compose, Nginx, and Certbot on your VPS.

Clone the project onto the server:

```bash
git clone YOUR_REPO_URL tenant-pro
cd tenant-pro
```

Create the production env file:

```bash
cp .env.example .env.production
nano .env.production
```

At minimum, set strong values for:

```text
MYSQL_PASSWORD
MYSQL_ROOT_PASSWORD
JWT_SECRET
MAIL_*
MPESA_*
```

For M-Pesa callbacks, use:

```text
MPESA_CALLBACK_URL=https://app.starmaxltd.com/api/payments/mpesa/callback
```

Place your Firebase Admin service account at:

```text
firebase-service-account.json
```

## 3. Start Backend And Run Migrations

The container runs Prisma migrations before starting the API.

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

Check logs:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f api
```

Optional first-time seed:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec api npm run prisma:seed
```

## 4. Configure Nginx

Copy the included config:

```bash
sudo cp deploy/nginx/app.starmaxltd.com.conf /etc/nginx/sites-available/app.starmaxltd.com
sudo ln -s /etc/nginx/sites-available/app.starmaxltd.com /etc/nginx/sites-enabled/app.starmaxltd.com
sudo nginx -t
sudo systemctl reload nginx
```

Issue SSL:

```bash
sudo certbot --nginx -d app.starmaxltd.com
```

## 5. Verify

```bash
curl https://app.starmaxltd.com/api/health
```

Expected response:

```json
{"status":"ok","service":"tenant-pro-api","timestamp":"..."}
```

## 6. Android App Production URL

For production APKs, set the release `BASE_URL` in `tenant-app/app/build.gradle.kts` to:

```text
https://app.starmaxltd.com/api/
```

Then build the release APK/AAB.

## Notes

- Port `3000` is bound only to `127.0.0.1` in Docker Compose. Public traffic should go through Nginx and HTTPS.
- Uploaded files are persisted in `./uploads`.
- MySQL data is persisted in the Docker volume `tenant-pro_mysql_data`.
- Do not commit `.env.production` or `firebase-service-account.json`.
