# Deploy Laravel TenantPro on GoDaddy Shared Hosting

## 1. Prepare Local Build
1. In `laravel-app`, copy `.env.godaddy.example` to `.env` and set database credentials.
2. Generate key:
   ```bash
   php artisan key:generate
   ```
3. Run migrations + seeders:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```
4. Optional optimization:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## 2. Upload to GoDaddy (cPanel)
1. Create folder `laravel` in your account root (one level above `public_html`).
2. Upload all files from `laravel-app` into that `laravel` folder.
3. Copy contents of `laravel/public` into `public_html`.
4. Edit `public_html/index.php` paths:
   - `../vendor/autoload.php` -> `../laravel/vendor/autoload.php`
   - `../bootstrap/app.php` -> `../laravel/bootstrap/app.php`
5. Copy `laravel/.env` to `laravel/.env` on server and set production values.
6. Ensure `laravel/storage` and `laravel/bootstrap/cache` are writable (755/775 depending on host policy).

## 3. Run Database Migrations on Host
Use cPanel Terminal (if available) or SSH:
```bash
cd ~/laravel
php artisan migrate --force
php artisan db:seed --force
```

## 4. Default Admin Login
- Email: `admin@starmaxltd.com`
- Password: `ChangeMe123!`

Change password immediately after first login.

## 5. Verify
- Public site: `https://starmaxltd.com/`
- Admin login: `https://starmaxltd.com/admin/login`
- API auth login: `POST https://starmaxltd.com/api/auth/login`

## 6. Important Notes
- Android app can keep using API endpoints under `/api/*`.
- If Sanctum migration is missing, ensure `personal_access_tokens` table exists.
- Keep `APP_DEBUG=false` in production.
