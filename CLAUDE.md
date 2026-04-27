# Tenant Pro — Project Summary for Claude

> This file is maintained by Claude. Update it whenever new features, modules, endpoints, or significant architectural changes are made.

---

## Project Overview

**Tenant Pro** is a full-stack, multi-platform **Tenant Payment & Property Management System** targeting landlords, tenants, admins, and caretakers in Kenya (M-Pesa payments). It consists of three main applications sharing a single MySQL database via Prisma ORM.

---

## Architecture

```
Tenant Pro/
├── src/                  → NestJS Backend API          [PORT 3000]
├── admin-dashboard/      → Next.js Admin Dashboard     [PORT 3001]
├── tenant-app/           → Kotlin/Android Mobile App
├── laravel-app/          → Legacy Laravel instance
└── prisma/               → Database schema & migrations
```

**Dev start:** `npm run dev` (runs backend + dashboard concurrently)

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | NestJS 11 (TypeScript), Node.js |
| ORM | Prisma 6.5 |
| Database | MySQL |
| Auth | JWT (Passport), bcryptjs |
| Email | Nodemailer (SMTP via cPanel/Starmaxltd) |
| Scheduler | @nestjs/schedule (cron) |
| Frontend | Next.js 16 / React 19, Tailwind CSS 4, Recharts |
| Mobile | Kotlin, Jetpack Compose, Hilt, Retrofit, Material 3 |
| Payments | Safaricom M-Pesa Daraja STK Push |

---

## Database Models

| Model | Purpose |
|-------|---------|
| User | All users (landlords, tenants, admins, caretakers) |
| Role | Enum: LANDLORD, TENANT, ADMIN, CARETAKER |
| Property | Buildings owned by landlords |
| Unit | Individual apartments within properties |
| Tenant | Links user to unit (move-in/out dates) |
| Invitation | Invite codes for tenants to join properties |
| Invoice | Bills (Rent, Water, Garbage, Other) |
| Payment | M-Pesa payment attempts + status |
| Transaction | Raw M-Pesa callback records |
| MaintenanceRequest | Issue tickets with priority/status |
| Notification | In-app notifications per user |
| SupportConversation | Support ticket threads |
| SupportMessage | Messages within support threads |

**Schema file:** [prisma/schema.prisma](prisma/schema.prisma)

---

## Backend Modules & API Endpoints

All routes prefixed with `/api`. JWT required unless noted.

### Auth — `/api/auth`
| Method | Path | Description |
|--------|------|-------------|
| POST | /register | Register with email (sends verification) |
| POST | /login | Email + password login |
| POST | /email-otp/request | Request email OTP |
| POST | /email-otp/verify | Verify OTP → JWT |
| POST | /forgot-password | Request password-reset OTP |
| POST | /reset-password | Reset password using OTP |
| POST | /demo/request | Request demo access |
| GET | /me | Current user profile (JWT) |
| GET | /admin-only | Admin guard test |

### Users — `/api/users`
| Method | Path | Access |
|--------|------|--------|
| GET | /me/profile | Own profile |
| PATCH | /me/profile | Update own profile |
| POST | / | Create user (ADMIN) |
| GET | / | List all users (ADMIN) |
| GET | /:id | Get user (ADMIN) |
| PATCH | /:id | Update user (ADMIN) |
| PATCH | /:id/role | Assign role (ADMIN) |
| DELETE | /:id | Delete user (ADMIN) |

### Properties — `/api/properties`
| Method | Path | Access |
|--------|------|--------|
| POST | / | Create property (LANDLORD/ADMIN) |
| GET | / | List properties (LANDLORD/ADMIN) |
| PATCH | /:propertyId | Update property (LANDLORD/ADMIN) |
| POST | /:propertyId/units | Add unit (LANDLORD/ADMIN) |
| PATCH | /units/:unitId | Update unit (LANDLORD/ADMIN) |

### Invitations — `/api/invitations`
| Method | Path | Access |
|--------|------|--------|
| POST | / | Create invite (LANDLORD/ADMIN) |
| POST | /accept | Accept invite (TENANT) |
| POST | /expire | Expire pending invites (LANDLORD/ADMIN) |

### Invoices — `/api/invoices`
| Method | Path | Access |
|--------|------|--------|
| GET | / | List invoices (role-aware) |
| GET | /tenant/:tenantId | Tenant's invoices |
| POST | /generate-monthly-rent | Auto-generate rent (LANDLORD/ADMIN) |
| POST | /bills | Create utility bill (LANDLORD/ADMIN) |
| POST | /apply-penalties | Apply overdue penalties (LANDLORD/ADMIN) |

### Payments — `/api/payments`
| Method | Path | Access |
|--------|------|--------|
| POST | /pay | Initiate M-Pesa STK Push |
| POST | /mpesa/callback | M-Pesa callback (PUBLIC — no JWT) |
| GET | /invoice/:invoiceId | Payments for invoice |

### Maintenance — `/api/maintenance`
| Method | Path | Access |
|--------|------|--------|
| POST | / | Create request (TENANT) |
| GET | / | List requests (role-aware) |
| GET | /:id | Get single request |
| PATCH | /:id/assign | Assign caretaker (LANDLORD) |
| PATCH | /:id/status | Update status (LANDLORD/CARETAKER/ADMIN) |

### Notifications — `/api/notifications`
| Method | Path | Description |
|--------|------|-------------|
| GET | / | List own notifications |
| PATCH | /:id/read | Mark single as read |
| POST | /mark-all-read | Mark all as read |

### Support — `/api/support`
| Method | Path | Access |
|--------|------|--------|
| POST | /upload | Upload attachment (multipart) |
| GET | /messages | Tenant's messages |
| POST | /messages | Send message |
| GET | /conversations | List conversations (OPS) |
| POST | /conversations | Start conversation (OPS) |
| GET | /conversations/:id/messages | Get messages |
| POST | /conversations/:id/messages | Reply |

### Analytics — `/api/admin/analytics`
| Method | Path | Description |
|--------|------|-------------|
| GET | /total-revenue | Total revenue |
| GET | /outstanding-balances | Outstanding balances |
| GET | /occupancy-rate | Property occupancy rate |
| GET | /monthly-payment-trends | Monthly trends (?months=N) |

---

## Frontend Structure (Admin Dashboard)

```
admin-dashboard/app/
├── admin/dashboard/         → Admin panel (stats, users, contacts)
├── dashboard/               → Landlord/Tenant dashboard
│   ├── content/             → Overview, stats
│   ├── invoices/            → Invoice list & detail
│   ├── properties/          → Property list
│   ├── tenants/             → Tenant management
│   ├── maintenance/         → Maintenance requests
│   ├── messages/            → Support messages
│   └── users/               → User profiles
├── login/                   → Login page
├── about/, blog/, contact/, services/, products/, portfolio/
└── api/                     → Next.js API routes
```

**Key components:** [admin-dashboard/components/sidebar.tsx](admin-dashboard/components/sidebar.tsx), [admin-dashboard/components/stat-card.tsx](admin-dashboard/components/stat-card.tsx)

---

## Mobile App Structure (Android/Kotlin)

Architecture: MVVM + Hilt DI

```
tenant-app/.../tenantpro/
├── data/api/            → Retrofit service + AuthInterceptor
├── data/model/          → Kotlin data classes
├── data/repository/     → Auth, Invoice, Payment repositories
├── di/                  → Hilt NetworkModule
├── ui/auth/             → Login fragments & ViewModels
├── ui/invoices/         → Invoice list + adapter
├── ui/payment/          → M-Pesa STK payment screen
└── utils/               → DataStoreManager, Resource<T>
```

---

## Authentication & Security

- **Methods:** Email+Password, Email OTP, Phone OTP (legacy)
- **JWT:** Bearer token, secret from `JWT_SECRET`, expiry from `JWT_EXPIRES_IN`
- **Guards:** `JwtAuthGuard`, `RolesGuard`
- **Decorator:** `@Roles(RoleName.ADMIN)` etc.
- **Roles:** LANDLORD, TENANT, ADMIN, CARETAKER
- **Passwords:** bcryptjs hashed

---

## M-Pesa Payment Flow

1. `POST /api/payments/pay` → backend gets M-Pesa access token
2. STK Push sent to tenant's phone
3. Tenant enters M-Pesa PIN
4. Safaricom posts to `POST /api/payments/mpesa/callback` (PUBLIC)
5. Backend records Transaction, updates Payment & Invoice status

**Env vars:** `MPESA_ENV`, `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_SHORTCODE`, `MPESA_PASSKEY`, `MPESA_CALLBACK_URL`

---

## Email Configuration

Provider: cPanel SMTP (Starmax Ltd)

**Env vars:** `MAIL_HOST`, `MAIL_PORT`, `MAIL_SECURE`, `MAIL_USER`, `MAIL_PASSWORD`, `MAIL_FROM_NAME`, `MAIL_FROM_EMAIL`

**Email triggers:** Registration, OTP login, password reset, payment reminders, invoice notifications, support acknowledgment

---

## Key Enums

| Enum | Values |
|------|--------|
| RoleName | LANDLORD, TENANT, ADMIN, CARETAKER |
| UnitStatus | VACANT, OCCUPIED, MAINTENANCE |
| InvoiceStatus | PENDING, PAID, OVERDUE, CANCELLED |
| PaymentStatus | INITIATED, PENDING, SUCCESS, FAILED, REVERSED |
| BillingType | RENT, WATER, GARBAGE, OTHER |
| MaintenanceStatus | OPEN, IN_PROGRESS, RESOLVED, CLOSED |
| NotificationType | GENERAL, INVOICE, PAYMENT, MAINTENANCE, MESSAGE, PROFILE |

---

## Seed Test Credentials

| Role | Email | Password |
|------|-------|----------|
| Landlord | landlord@example.com | Pass@1234 |
| Tenant | tenant@example.com | Tenant@1234 |
| Admin | admin@example.com | Admin@1234 |
| Caretaker | caretaker@example.com | Caretaker@1234 |

Seed command: `npx prisma db seed`

---

## Key File Locations

| File | Purpose |
|------|---------|
| [src/app.module.ts](src/app.module.ts) | Root NestJS module |
| [src/main.ts](src/main.ts) | Bootstrap (CORS, pipes, static files) |
| [src/modules/](src/modules/) | Feature modules (auth, users, properties, etc.) |
| [src/common/guards/](src/common/guards/) | JwtAuthGuard, RolesGuard |
| [src/common/decorators/](src/common/decorators/) | @Roles() decorator |
| [prisma/schema.prisma](prisma/schema.prisma) | Database schema |
| [prisma/seed.js](prisma/seed.js) | Seed data |
| [admin-dashboard/app/](admin-dashboard/app/) | Next.js pages |
| [admin-dashboard/next.config.ts](admin-dashboard/next.config.ts) | Next.js config |
| [tenant-app/](tenant-app/) | Android source |
| [.env](.env) | All environment variables |

---

## Existing Documentation

- [readme.md](readme.md) — Project instructions & core features
- [EMAIL_AUTHENTICATION_DOCS.md](EMAIL_AUTHENTICATION_DOCS.md) — Email OTP & password reset
- [TENANT_APP_EMAIL_UPDATES.md](TENANT_APP_EMAIL_UPDATES.md) — Android email auth changes
- [tenant-app/INSTALLATION_GUIDE.md](tenant-app/INSTALLATION_GUIDE.md) — Android install guide
- [laravel-app/DEPLOY_GODADDY.md](laravel-app/DEPLOY_GODADDY.md) — Legacy deploy guide

---

## Implemented Features

- Multi-role system (Landlord, Tenant, Admin, Caretaker)
- Property & unit management
- Tenant invitation flow (invite code → unit assignment)
- Invoice generation (monthly rent auto-gen, manual utility bills)
- Overdue penalty application
- M-Pesa STK Push payments with callback handling
- Payment history per invoice
- Maintenance request lifecycle (submit → assign → resolve)
- Support conversation system with file attachments
- In-app notifications with read/unread state
- Analytics: revenue, occupancy, balances, monthly trends
- Email OTP authentication
- Password reset via OTP
- JWT + RBAC security
- Scheduled payment reminders (cron)
- Admin user management
- Modern Material 3 UI (Android) — gradient auth screens, semantic stat cards, slide animations

---

## Android UI Design System

The tenant-app uses a consistent Material 3 design language. Key patterns:

| Pattern | Details |
|---------|---------|
| Auth screens | Split: gradient top (`bg_gradient_primary`) + `bg_card_white_rounded` form bottom |
| Stat cards | Semantic surface colors: `warning_surface`, `error_surface`, `info_surface`, `success_surface` |
| List items | `MaterialCardView` with 4dp left accent strip (billing type color) |
| Loading | `LinearProgressIndicator` (replaces raw `ProgressBar` throughout) |
| Drawer | `bg_gradient_primary` header + `ShapeableImageView` avatar |
| Transitions | `slide_in_right` / `slide_out_left` forward; reversed on back-stack pop |

Key drawable resources: `bg_gradient_primary`, `bg_auth_top`, `bg_card_white_rounded`, `bg_quick_action`, `bg_unread_dot`, `bg_badge_green/red/yellow/gray`

---

## Pending Integrations (Not Yet Implemented)

These integrations are identified but not yet built. Prioritize in future sessions.

### 1. Firebase Cloud Messaging (FCM) — Push Notifications
- **What:** Real-time push notifications when invoices are created, payments succeed/fail, maintenance status changes.
- **Android side:** Add `google-services.json`, Firebase Messaging dependency, `FirebaseMessagingService` subclass, update device token to backend on login.
- **Backend side:** Add `POST /api/users/device-token` endpoint (saves FCM token per user), integrate `firebase-admin` SDK to send pushes from payment callback, maintenance update, invoice creation hooks.

### 2. Profile Image Upload
- **What:** Tenant can upload a profile photo from the Account Settings screen.
- **Android side:** `btnChangePhoto` already exists in `fragment_account_settings.xml`; wire up image picker → Retrofit multipart upload.
- **Backend side:** Add `POST /api/users/me/avatar` endpoint (accepts multipart, stores file via `multer`, returns URL); update `User` model with `avatarUrl` field.

### 3. Invoice PDF Generation & Download
- **What:** Tenants can download/share a PDF receipt for each invoice.
- **Android side:** `btnSave` in `item_invoice.xml` calls `onDownloadClick` in `InvoiceAdapter` — the click handler is wired but currently unimplemented in `InvoicesFragment`.
- **Backend side:** Add `GET /api/invoices/:id/pdf` endpoint using `pdfkit` or `puppeteer` to render invoice as PDF, served as `application/pdf`.

### 4. Biometric Authentication
- **What:** Fingerprint / Face ID login after the first successful email login (token stored in `DataStore`).
- **Android side:** Use `BiometricPrompt` API; check `BiometricManager.canAuthenticate()`; store/retrieve JWT via `DataStoreManager`; show biometric prompt on app resume if token exists.
- **No backend changes required** — uses existing JWT.

### 5. Dark Mode Toggle
- **What:** System-aware dark/light theme switching with a manual override in Account Settings.
- **Android side:** Add `Theme.TenantPro.Dark` in `themes.xml` (replace `@color/background` → dark equivalents); use `AppCompatDelegate.setDefaultNightMode()` to persist preference in `DataStore`; add toggle switch in `fragment_account_settings.xml`.
- **Backend:** No changes required.

---

*Last updated: 2026-04-27 — Added UI design system docs, pending integration notes, Material 3 modernisation.*
