🚀 PROJECT INSTRUCTIONS FOR COPILOT

## LOCAL DEVELOPMENT (AUTO-SYNC)

Use these commands from the project root:

- `npm install`
- `npm run dev`

What `npm run dev` does:

- Starts backend in watch mode (Nest + ts-node-dev) on `http://localhost:3000`
- Starts admin dashboard in Next.js dev mode on `http://localhost:3001`
- Auto-reloads when you save code changes (no manual restart needed)

Production-mode commands (`npm start`, `npm --prefix admin-dashboard run start`) do **not** auto-sync changes.

You are building a full-stack Tenant Payment & Property Management System with:

📱 Mobile App (Android)
Tech: Kotlin (Android Jetpack)
Architecture: MVVM
API: REST
🌐 Web Admin Panel
Tech: React (Next.js preferred)
UI: Tailwind CSS
Auth: JWT
⚙️ Backend API
Tech: Node.js with Express OR NestJS (preferred)
Database: PostgreSQL
ORM: Prisma
💳 Payment Integration
Integrate M-Pesa Daraja API:
STK Push
Payment callback handling
Transaction validation
🧩 CORE FEATURES TO IMPLEMENT
1. Authentication
Phone number login (OTP)
JWT-based sessions
Roles:
Landlord
Tenant
Admin
2. Property Management
Landlord can:
Create properties
Add units
Set rent amount
Add billing types:
Rent
Water
Garbage
3. Tenant Invitation System
Generate invite link or SMS
Assign tenant to unit
Tenant registers and joins unit
4. Billing System
Monthly auto-generated invoices
Water billing (manual input)
Late penalties
Invoice statuses:
Paid
Pending
Overdue
5. Payment System
Trigger M-Pesa STK Push
Handle callback endpoint
Update invoice status
Store transactions
6. Notifications
SMS + push notifications:
Payment reminders
Payment confirmations
7. Dashboards
Admin Web Panel:
Total revenue
Occupancy rate
Outstanding balances
Charts (monthly trends)
Tenant App:
View invoices
Pay bills
View payment history
8. Maintenance Module
Tenant submits issues
Landlord tracks status
🗄️ DATABASE SCHEMA (REQUIRED TABLES)

Generate Prisma models for:

Users
Roles
Properties
Units
Tenants
Invitations
Invoices
Payments
Transactions
MaintenanceRequests
🔐 SECURITY REQUIREMENTS
Hash passwords (bcrypt)
Use HTTPS
Validate M-Pesa callbacks
Role-based access control
📦 PROJECT STRUCTURE
Backend
/backend
  /src
    /modules
      /auth
      /users
      /properties
      /billing
      /payments
Web Admin
/admin-web
  /pages
  /components
  /services
Android App
/android-app
  /ui
  /viewmodel
  /repository
  /api
🧪 EXTRA FEATURES (OPTIONAL BUT RECOMMENDED)
PDF receipts
Export reports (CSV)
Multi-language (English/Swahili)
Role: caretaker
Offline sync for Android
Dark mode
🧱 TASK BREAKDOWN (IMPORTANT)

Build in this order:

Backend API (auth + users)
Database schema (Prisma)
Property & tenant modules
Billing system
M-Pesa integration
Web admin dashboard
Android app
Notifications
⚡ CODING RULES
Use clean architecture
Write reusable services
Add comments
Use environment variables
Handle errors properly
🛠️ 3. Suggested Tech Stack (Final Decision)
Backend
Node.js + NestJS ✅ (best structure)
PostgreSQL
Prisma ORM
Admin Web
Next.js (React)
Tailwind CSS
Chart.js (analytics)
Mobile App
Kotlin + Jetpack Compose
DevOps
Docker
Nginx
CI/CD (GitHub Actions)