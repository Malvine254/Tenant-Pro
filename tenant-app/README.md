# Tenant Pro — Android App

Kotlin + MVVM tenant-facing mobile app for managing invoices and M-Pesa payments.

> 📱 **For End Users**: See [INSTALLATION_GUIDE.md](./INSTALLATION_GUIDE.md) for step-by-step download and setup instructions.

## Features

| Screen | Description |
|--------|-------------|
| **Login** | Sign in with email and password |
| **My Invoices** | List of all invoices for the authenticated tenant (pull-to-refresh) |
| **Pay via M-Pesa** | Triggers STK Push; optionally enter a partial amount |
| **Payment History** | Per-invoice payment records with M-Pesa receipt numbers |

## Architecture

```
data/
  api/          — Retrofit ApiService + AuthInterceptor
  model/        — Kotlin data classes matching NestJS response shapes
  repository/   — AuthRepository, InvoiceRepository, PaymentRepository
di/             — Hilt NetworkModule
ui/
  auth/         — LoginFragment + ViewModels
  invoices/     — InvoicesFragment, InvoiceAdapter + ViewModel
  payment/      — PaymentFragment + ViewModel
  history/      — PaymentHistoryFragment, PaymentHistoryAdapter + ViewModel
utils/          — DataStoreManager, Resource<T>, Extension functions
```

**Stack**: Kotlin · MVVM · Hilt · Retrofit · OkHttp · Coroutines/Flow · Navigation Component · DataStore · Material 3

## Setup

### 1. Configure Backend IP Address (Automatic)

The app automatically reads the backend server IP from `local.properties`. When your IP address changes, simply run:

**Windows (PowerShell):**
```powershell
.\update-backend-ip.ps1
```

**macOS/Linux:**
```bash
chmod +x update-backend-ip.sh
./update-backend-ip.sh
```

This will:
- ✅ Automatically detect your computer's network IP
- ✅ Update `local.properties` with the correct IP
- ✅ Configure the app to connect to `http://YOUR_IP:3000/api/`

**Manual Configuration (Alternative):**

Edit `local.properties` and set:
```properties
backend.host=192.168.0.104  # Your computer's IP
backend.port=3000
```

**For Android Emulator:**
```properties
backend.host=10.0.2.2  # Special emulator localhost alias
backend.port=3000
```

### 2. Open in Android Studio

```
File → Open → select the tenant-app/ folder
```

Android Studio will sync Gradle automatically.

### 3. Sync & Build

After updating the IP:
1. **File** → **Sync Project with Gradle Files**
2. **Build** → **Clean Project**
3. **Build** → **Rebuild Project**

### 4. Run

Click **Run ▶** or use:

```bash
./gradlew :app:installDebug
```

### 4. M-Pesa Sandbox

The backend uses Daraja STK Push. For local testing make sure:
- Your `.env` has valid `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_PASSKEY`, and `MPESA_SHORTCODE`  
- `MPESA_ENV=sandbox` for sandbox
- The backend is reachable from the device / emulator on port 3000

## Sample login credentials

Use these seeded credentials for testing:

- Tenant: `tenant@example.com` / `Tenant@1234`
- Admin: `admin@example.com` / `Admin@1234`
- Landlord: `landlord@example.com` / `Pass@1234`

## Build variants

| Variant | BASE_URL |
|---------|----------|
| `debug` | `http://192.168.0.104:3000/api/` |
| `release` | Update in `build.gradle.kts` to your production URL |
