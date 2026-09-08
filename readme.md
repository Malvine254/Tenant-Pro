# Starmax Tenant Services

Starmax Tenant Services is a Laravel-based tenant payment and property-management platform for landlords, tenants, administrators, and caretakers in Kenya.

## Applications

- `laravel-app/` - Laravel backend, admin portal, public rental marketplace, database migrations, seeders, and mobile API.
- `tenant-app/` - Kotlin Android tenant application.
- `data/` - Public content data used by the Laravel application.

Laravel is the sole backend and source of truth. The Android app uses the Laravel API under `/api/*`.

## Laravel Setup

```bash
cd laravel-app
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --force
npm install
npm run build
```

For local development:

```bash
cd laravel-app
composer run dev
```

This starts the Laravel server, queue listener, logs, and Vite. The web application is available at `http://127.0.0.1:8000`.

For a focused server only:

```bash
cd laravel-app
php artisan serve
```

## Testing

```bash
cd laravel-app
composer test
```

## Android Setup

The Android application is under `tenant-app/`. Production builds use:

```text
https://app.starmaxltd.com/api/
```

For local Laravel development, configure `tenant-app/local.properties` with port `8000`, or run the helper script:

```powershell
cd tenant-app
.\update-backend-ip.ps1
```

See [tenant-app/INSTALLATION_GUIDE.md](tenant-app/INSTALLATION_GUIDE.md) for Android Studio and device setup.

## Deployment

Use [laravel-app/DEPLOY_GODADDY.md](laravel-app/DEPLOY_GODADDY.md) for shared-hosting deployment, migrations, scheduled tasks, and production configuration.

## Main Features

- Role-based landlord, tenant, admin, and caretaker access.
- Property, unit, tenancy, invitation, invoice, and payment management.
- Safaricom M-Pesa STK Push and callback settlement.
- Maintenance requests and support conversations.
- Notifications, email OTP authentication, and password reset.
- Public rental marketplace with neighbourhood discovery.
- Android offline support-message queue and cached tenant data.
