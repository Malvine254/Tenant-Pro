# Starmax Tenant Services end-to-end product and security audit

Date: 29 August 2026

## Scope and production architecture

The Android release uses `https://app.starmaxltd.com/api/`, which is served by `laravel-app/`. The NestJS application under `src/` is a second backend and is not the production API consumed by the released Android configuration. Feature work must either be implemented in Laravel or the two APIs must be deliberately consolidated.

Reviewed areas: Android authentication, dashboard, invoices, payments/history, rental information, updates, chat/maintenance, account settings and deep links; Laravel mobile API, M-Pesa callback handling and role scoping; Laravel landlord/admin portal; source-control and release security.

## Product benchmark

The baseline was compared with official resident-portal material from:

- [Buildium Resident Center](https://www.buildium.com/features/resident-center/): payments and history, maintenance with photos/status, announcements and lease documents.
- [Buildium resident help](https://www.buildium.com/resident-site-help-center/resident-site-help-center/): payment history/auto-pay, documents, announcements and maintenance.
- [DoorLoop tenant portal help](https://support.doorloop.com/en/collections/3376068-tenant-portal): payments, maintenance, documents, announcements, invite tracking and multiple leases.
- [DoorLoop tenant portal](https://www.doorloop.com/roles/tenant): rent payments, lease information, maintenance and messaging.

## Fixed in this audit

### Critical security and data integrity

- Added explicit manager authorization to property, unit, tenant, invoice, manual-payment, invitation and maintenance mutations.
- Scoped landlord API reads and writes to the landlord's own properties.
- Made financial payment records immutable through the API.
- Restricted tenant invoice/payment/maintenance access to the authenticated tenant's active tenancy.
- Required invitation email or normalized phone ownership before a code can link a unit.
- Removed public testing/deployment routes that exposed privileged operational tools; retained the one-time deployment endpoint with token checks, reduced information disclosure and throttling.
- Changed deployment-tool role checking from fail-open to fail-closed.
- Added M-Pesa receipt uniqueness, callback amount verification and duplicate-receipt rejection.
- Persisted checkout request ID, merchant request ID, receipt/external reference, callback time and raw M-Pesa payload in structured transaction fields.
- Disabled Android backups and release cleartext traffic. Debug builds retain local HTTP support.

### Payments and dashboard

- Added production Laravel support for a single STK push covering multiple selected invoices, with persisted per-invoice allocations and idempotent callback settlement.
- Prevented one payment from mixing invoices managed by different landlords/paybills.
- Corrected Android mappings for Laravel's `payment_phone`, `mpesa_receipt`, `paid_at`, `SUCCESSFUL` and callback transaction fields.
- Included transaction details in payment-history responses.
- Increased the bounded invoice/payment history request to 100 records so six-month charts and older transactions are not silently limited to the first 15.
- Added a global, landlord-scoped Payments page to the admin portal for reconciliation and receipt searches.

### Rental linking and navigation

- Added `/api/invitations/claim` in the production backend.
- Matching existing tenant accounts now link automatically by verified email/phone; manual code entry remains only as a deep-link fallback, not a Rental Info form.
- Existing accounts selected or matched in the admin invitation flow are assigned immediately.
- Added HTTPS invitation deep-link handling alongside the custom app scheme.
- Renamed Notifications to Updates and clarified its role: receipts, billing notices, tenancy changes and important property events. Chat remains the two-way conversation surface.
- Notification preference now controls Firebase push delivery while important records remain visible in Updates.

### Source control

- Corrected the root `.gitignore`: the old `data/` rule excluded the Android app's entire `data` package (models, API and repositories). It now ignores only `/data/` at repository root.

## Role/capability decision

| Role | Intended boundary | Current result |
|---|---|---|
| Super admin | Platform-wide landlords, billing, deployment and all operations | Supported; deployment tools limited to platform admins |
| Admin | Platform operations except super-admin secrets | Supported |
| Landlord | Only owned properties, units, tenants, invoices, payments and chats | API scoping hardened in this audit |
| Tenant | Own active rentals, bills, payments, updates and chats | Supported and restricted to active tenancies |
| Caretaker | Assigned maintenance operations only | Not coherently implemented; currently blocked from the main admin route group and chat endpoints |

Do not expose `CARETAKER` as a selectable production role until assignments, permissions and a task/status page are implemented. If that workflow is not planned, remove the role to avoid misleading administrators.

## Important capabilities still missing

These require product/data-model work rather than a safe patch inside this audit:

1. **Lease and document centre** — upload/version lease agreements, house rules and notices; tenant read/download access; document visibility and audit trail.
2. **Landlord announcements** — Updates has the tenant inbox, but the admin portal still needs compose, property/unit audience selection, scheduling and delivery reporting.
3. **Structured maintenance workflow** — chat supports a Maintenance topic and attachments, matching the chosen simplified navigation, but there is no coherent landlord page for assignment, SLA, status timeline and resolution evidence.
4. **Move-in/out and inspections** — checklist, photos, meter readings, condition sign-off and deposit reconciliation.
5. **Deposit ledger** — the invitation captures a deposit amount, but it is not a financial ledger with receipts, deductions and refund state.
6. **Accounting exports and reconciliation** — CSV/PDF export, failed/pending STK follow-up and settlement reports.
7. **Audit log** — immutable record of admin/landlord changes to tenants, invoices, units and settings.
8. **Release distribution** — configure a private release keystore and distribute a signed release APK/AAB. The current portal workflow copies `app-debug.apk`; this must not be the production channel.
9. **Verified app links** — publish `/.well-known/assetlinks.json` with the real release certificate SHA-256 fingerprint before relying on automatic HTTPS app opening.
10. **Automated regression coverage** — role isolation tests, invitation-ownership tests, M-Pesa replay/amount mismatch tests, multi-invoice allocation tests and Android JSON contract tests.

## Deployment requirements for these fixes

- Commit the newly visible Android `data/` package after reviewing it for secrets.
- Run both 29 August Laravel migrations: payment allocation/receipt integrity and structured M-Pesa transaction identifiers.
- Confirm there are no historical duplicate non-null `mpesa_receipt` values before applying the unique index.
- Build/sign a release app and publish its certificate fingerprint in `assetlinks.json`.
- Run the server/API and Android test suites in the deployment environment.

No build, migration or application test suite was run during this audit at the owner's request. PHP syntax checks and Git whitespace checks were run.
