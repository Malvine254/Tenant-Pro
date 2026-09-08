# Starmax Tenant Services

This file records the current project architecture and conventions for future maintenance.

## Architecture

```text
Tenant Pro/
├── laravel-app/   Laravel backend, admin portal, marketplace, API, migrations, and seeders
├── tenant-app/    Kotlin Android tenant application
├── data/          Public content data used by Laravel
└── uploads/       Local development upload storage
```

Laravel is the only backend and owns the MySQL schema used by the admin portal, public marketplace, and Android app. The deleted NestJS and Prisma stack is no longer part of this project.

## Development

```bash
cd laravel-app
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed --force
npm install
npm run build
composer run dev
```

The Laravel development server runs on `http://127.0.0.1:8000`. Android production uses `https://app.starmaxltd.com/api/`; local Android development should use the Laravel server on port `8000`.

## Main Components

| Area | Location |
|---|---|
| Web and mobile routes | `laravel-app/routes/web.php`, `laravel-app/routes/api.php` |
| Controllers | `laravel-app/app/Http/Controllers/` |
| Models | `laravel-app/app/Models/` |
| Services | `laravel-app/app/Services/` |
| Schema | `laravel-app/database/migrations/` |
| Seed data | `laravel-app/database/seeders/` |
| Admin views | `laravel-app/resources/views/admin/` |
| Marketplace views | `laravel-app/resources/views/tenant-marketplace/` |
| Android app | `tenant-app/` |
| Laravel deployment guide | `laravel-app/DEPLOY_GODADDY.md` |

## Roles

- `SUPER_ADMIN` and `ADMIN`: platform operations.
- `LANDLORD`: owned properties, units, tenants, invoices, payments, chats, and maintenance.
- `TENANT`: assigned rentals, invoices, payments, updates, support, and maintenance requests.
- `CARETAKER`: defined in the data model; maintenance assignment permissions should remain aligned with the implemented workflow.

## Core Features

- Property and unit management.
- Tenant invitations, multi-unit assignments, and tenancy closure.
- Rent and utility invoices with penalties and payment history.
- M-Pesa STK Push and callback settlement.
- Maintenance request tracking and admin assignment/status controls.
- Support conversations, attachments, notifications, email OTP, and password reset.
- Public rental marketplace with saved homes and neighbourhood discovery.
- Android offline support-message queue, cached data, FCM token sync, profile image upload, biometric login, and app settings sync.

## Test Accounts

Use only in local or testing environments. Change passwords before any shared deployment.

| Role | Email | Password |
|---|---|---|
| Landlord | `landlord@example.com` | `Pass@1234` |
| Tenant | `tenant@example.com` | `Tenant@1234` |
| Admin | `admin@example.com` | `Admin@1234` |
| Caretaker | `caretaker@example.com` | `Caretaker@1234` |

Seed with:

```bash
cd laravel-app
php artisan db:seed --force
```

## Validation Commands

```bash
cd laravel-app
php artisan route:list
php artisan view:cache
composer test
```

Android builds require Java 17 and a configured Android SDK. Use `tenant-app/update-backend-ip.ps1` or `tenant-app/update-backend-ip.sh` to configure a local Laravel API address.

## Maintenance Rules

- Keep Laravel as the single backend source of truth.
- Add schema changes through Laravel migrations and seeders.
- Preserve landlord/property authorization on every manager-facing operation.
- Keep marketplace pages isolated under `resources/views/tenant-marketplace/`.
- Do not reintroduce root-level NestJS, Prisma, Docker API, or port-3000 setup files.
