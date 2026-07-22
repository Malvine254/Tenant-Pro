# Tenant Pro Android App — Installation & Setup Guide

This guide covers installing and setting up the **Tenant Pro** Android app on your phone.

---

## Table of Contents

1. [For End Users (Pre-built APK)](#for-end-users-pre-built-apk)
2. [For Developers (Build from Source)](#for-developers-build-from-source)
3. [First-Time Setup](#first-time-setup)
4. [Troubleshooting](#troubleshooting)

---

## For End Users (Pre-built APK)

### System Requirements

- **Android version**: 8.0 (API 26) or higher
- **Storage**: ~60 MB free space
- **Network**: Active internet connection (Wi-Fi or mobile data)

### Step 1: Download the APK

1. **On your Android phone**, open a web browser or file manager
2. **Navigate to the download link** provided by your property manager or Starmax support
   - Typically: `http://your-domain.com/downloads/tenant-app.apk` or a similar link
   - Or scan the **QR code** provided by your landlord/management company
3. **Tap the download link** to begin downloading the APK file
4. Once complete, you'll see a notification at the top of your screen

### Step 2: Enable Installation from Unknown Sources

Before installing, Android requires permission to install apps from sources other than the Play Store.

**Steps:**
1. Open **Settings** on your phone
2. Go to **Apps & notifications** (or **Apps**)
3. Tap **Advanced** or scroll down to **Special app permissions**
4. Select **Install unknown apps**
5. Choose your **web browser or file manager** from the list
6. Toggle **Allow from this source** to **ON**

*Note: This is a one-time security configuration. You can disable it after installation.*

### Step 3: Install the App

1. Open your **Downloads** folder or file manager
2. Find **` tenant-app.apk`** or **`TenantPro.apk`**
3. **Tap the file** to start installation
4. Read the permissions list and tap **Install**
5. Wait for installation to complete (~30 seconds)
6. Tap **Open** or **Done**

### Step 4: Launch & Configure

1. **Open Tenant Pro** from your app drawer
2. You'll see the **Login** screen
3. Enter your **email and password** provided by your property manager
4. Tap **Sign In**
5. After login, the app will auto-connect to your property management backend

**That's it!** You're ready to view invoices, make payments, and track payment history.

---

## For Developers (Build from Source)

### Prerequisites

- **Android Studio** (latest version, 2024.1 or later)
- **Java 17+** (included with Android Studio)
- **Gradle** (included with Android Studio)
- **Git** (to clone the repository)
- **Backend server** running on `localhost:3000` or a known IP

### Step 1: Clone or Extract the Project

**Option A: Using Git**
```bash
git clone https://github.com/your-org/tenant-pro.git
cd tenant-pro/tenant-app
```

**Option B: Manual extraction**
- Extract the `tenant-app/` folder from the project archive
- Navigate to that folder in your terminal

### Step 2: Configure Backend IP

The app automatically detects your backend server. Run **one** of these scripts:

**Windows (PowerShell):**
```powershell
.\update-backend-ip.ps1
```

**macOS/Linux:**
```bash
chmod +x update-backend-ip.sh
./update-backend-ip.sh
```

**Manual Configuration (if scripts don't work):**

1. Open `local.properties` in the `tenant-app/` folder
2. Add or update these lines:
   ```properties
   backend.host=192.168.0.104
   backend.port=3000
   ```
   Replace `192.168.0.104` with your computer's actual IP address

3. **Find your IP:**
   - **Windows**: Open Command Prompt, type `ipconfig`, look for "IPv4 Address" (e.g., `192.168.0.x`)
   - **macOS/Linux**: Open Terminal, type `ifconfig`, look for "inet" address

**For Android Emulator:**
```properties
backend.host=10.0.2.2
backend.port=3000
```

### Step 3: Open in Android Studio

1. **Open Android Studio**
2. Click **File** → **Open**
3. Navigate to and select the `tenant-app/` folder
4. Click **Open**
5. Android Studio will load the project and sync Gradle (takes ~1–2 minutes)

### Step 4: Sync & Build

1. **File** → **Sync Project with Gradle Files** (or wait for auto-sync)
2. **Build** → **Clean Project**
3. **Build** → **Rebuild Project**
4. Wait until you see `BUILD SUCCESSFUL` in the console

### Step 5: Run on Device or Emulator

**Option A: Physical Device**
1. Connect your Android phone via USB cable
2. Enable **Developer Mode** on your phone:
   - Open **Settings** → **About**
   - Tap **Build Number** 7 times
   - Go back to **Settings** → **Developer Options**
   - Enable **USB Debugging**
3. In Android Studio, click **Run ▶** (or press Shift+F10)
4. Select your connected device
5. Click **OK**

**Option B: Android Emulator**
1. **Tools** → **Device Manager**
2. Click **Create Virtual Device**
3. Choose a device (e.g., Pixel 4) and click **Next**
4. Select **API 30+** (recommended: API 35) and click **Next**
5. Click **Finish**
6. In Android Studio, click **Run ▶**
7. Select your emulator from the list and click **OK**

### Step 6: Test

Once the app launches:
1. You'll see the **Login** screen
2. Enter test credentials (provided by your team)
3. Tap **Sign In**
4. Browse **My Invoices** to verify the backend connection works
5. Test **M-Pesa Payment** (sandbox mode)

---

## First-Time Setup

### Login & Authentication

1. **Email & Password**
   - Your property manager will provide these
   - Example: `tenant@example.com` / `SecurePass123`

2. **Forgot Password?**
   - Tap **Forgot Password** on the login screen
   - Enter your email
   - Check your email for a password reset link (valid for 1 hour)

### Viewing Invoices

1. After login, tap **My Invoices**
2. Scroll to see all outstanding invoices
3. **Pull down** to refresh the list (fetch latest invoices from the server)
4. Tap an invoice to see details:
   - Invoice number & date
   - Amount due
   - Due date
   - Payment history

### Making Payments via M-Pesa

1. Open an invoice and tap **Pay Now**
2. Enter your **M-Pesa phone number** (e.g., `254712345678`)
3. *Optional:* Enter a partial amount (default is full invoice amount)
4. Tap **Send M-Pesa**
5. Your phone will receive an **M-Pesa STK prompt** (a popup menu)
6. Enter your **M-Pesa PIN** and confirm
7. Payment is processed in real-time
8. You'll receive an SMS confirmation
9. Tap **Done** and the app will show the updated payment status

### Checking Payment History

1. Tap **Payments** in the main menu
2. View all past payments, including:
   - Payment date
   - Amount paid
   - M-Pesa receipt number (for m-mobiledev
verification)

---

## Troubleshooting

### App Won't Connect to Backend

**Problem**: Login button is grayed out or shows "Connection Error"

**Solution**:
1. Check your **Wi-Fi or mobile data** is active
2. Ask your IT/support contact for the correct backend URL:
   - Local network: `http://192.168.x.x:3000/api/`
   - Public domain: `https://api.yourdomain.com/api/`
3. If building from source, verify `local.properties` has the correct IP/hostname
4. Restart the app (close and reopen)

### "Invalid Credentials" Error

**Problem**: You can't log in even with correct email/password

**Solution**:
1. Verify your **email is spelled correctly** (case-insensitive)
2. Confirm your **password is correct** (case-sensitive)
3. Try **Forgot Password** to reset
4. Contact your property manager to verify your account is active

### M-Pesa Payment Fails

**Problem**: "Payment declined" or M-Pesa prompt doesn't appear

**Solution**:
1. Ensure your **M-Pesa account is active** and has sufficient balance
2. Verify your **phone number** is correct (include country code: `254...`)
3. Check you're not in **airplane mode**
4. Wait a few seconds and try again (STK push can be slow)
5. Contact your property manager or M-Pesa customer care

### App Crashes on Launch

**Problem**: App force-closes immediately after opening

**Solution**:
1. **Uninstall** the app completely
2. Clear app cache:
   - **Settings** → **Apps** → **Tenant Pro** → **Storage** → **Clear Cache**
3. **Reinstall** the app
4. If still crashing, update to the latest APK version

### Can't Install APK (Security Warning)

**Problem**: "Installation blocked" or "Unsafe file" message

**Solution**:
1. **Verify the APK source** — download only from official links provided by your property manager
2. **Enable installation from unknown sources** (see Step 2 under "For End Users")
3. This is normal for side-loaded apps (not from Google Play Store)
4. Your phone is still secure; this is a standard Android safety check

### Invoices Don't Update

**Problem**: You see outdated invoice data

**Solution**:
1. **Pull down** on the invoice list to manually refresh
2. Wait 10–15 seconds for the server to respond
3. Close and reopen the app
4. Check your internet connection
5. Contact your property manager if invoices are still missing

### Payment History Is Empty

**Problem**: You made payments but they don't show in the app

**Solution**:
1. **Pull down** to refresh the payment list
2. Wait for the latest data to load from the server
3. Check your **M-Pesa account** to confirm payment was successful
4. Wait ~1 hour for delayed payments to appear in the app
5. Contact support if missing after 24 hours

### App Performance Issues (Slow, Freezing)

**Problem**: App lags or freezes while scrolling invoices

**Solution**:
1. **Restart your phone**
2. **Close other apps** running in the background
3. **Clear app cache**:
   - **Settings** → **Apps** → **Tenant Pro** → **Storage** → **Clear Cache**
4. Ensure you have **at least 200 MB free storage** on your phone
5. **Uninstall and reinstall** the latest version of the app

---

## Getting Help

### Support Contacts

- **Property Manager**: Contact them for login credentials or account questions
- **IT Support**: For backend connectivity or server issues
- **M-Pesa Support**: For payment-related questions  
  - Call: **252** (from M-Pesa menu on your phone)
  - SMS: Send "HELP" to **15055**

### Reporting Bugs

If you find a bug in the app:
1. Note the **exact error message** (screenshot if possible)
2. Note **what you were trying to do** when the error occurred
3. Note your **Android phone model and OS version** (**Settings** → **About**)
4. Email your **property manager or IT support** with these details
5. Include the **app version** (**Settings** → **Apps** → **Tenant Pro** → **.** → **App info**)

---

## App Permissions

Tenant Pro requires these permissions:

| Permission | Purpose |
|-----------|---------|
| **Internet** | Connect to backend server and M-Pesa gateway |
| **Phone** | Detect your device's phone number for M-Pesa |

No other permissions are required. The app does **not** access your camera, location, contacts, or other sensitive data.

---

## Version History

| Version | Release Date | Changes |
|---------|-------------|---------|
| **1.0.0** | April 2026 | Initial release: Login, invoices, M-Pesa payments, payment history |

---

**Last Updated**: April 14, 2026  
**For Questions**: Contact your property manager or IT support

