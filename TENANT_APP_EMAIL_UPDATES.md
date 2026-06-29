# Tenant App Email Authentication Updates

## Summary

The Android tenant app has been updated to include email-based authentication features that match the backend API:

---

## ✅ Features Added

### 1. **Email OTP Login**
- Users can request an OTP sent to their email address
- Login using email + OTP code instead of password
- Backend API endpoints: `/auth/email-otp/request` and `/auth/email-otp/verify`

### 2. **Password Reset Flow**
- **Forgot Password** screen accessible from login page
- Users enter their email to receive a reset code
- Enter OTP code + new password to reset
- Backend API endpoints: `/auth/forgot-password` and `/auth/reset-password`

### 3. **Welcome Email on Registration**
- When a tenant registers, they automatically receive a welcome email
- Email includes their account details and app features

---

## 📱 New UI Components

### ForgotPasswordFragment
- **Location**: `app/src/main/java/com/tenantpro/app/ui/auth/ForgotPasswordFragment.kt`
- **Layout**: `app/src/main/res/layout/fragment_forgot_password.xml`
- **ViewModel**: `ForgotPasswordViewModel.kt`

**Features:**
- Two-step process: Request OTP → Reset Password
- Email validation
- Password strength validation
- Confirmation password matching
- Loading states with progress indicators
- Error handling with toast messages

### Updated LoginFragment
- Added "Forgot Password" link that navigates to `ForgotPasswordFragment`
- Existing email/password login still works

---

## 🔧 Backend Integration

### Updated Data Models (`AuthModels.kt`)

```kotlin
// Email OTP Login
data class RequestEmailOtpRequest(email: String)
data class VerifyEmailOtpRequest(email: String, code: String)

// Password Reset
data class ForgotPasswordRequest(email: String)
data class ResetPasswordRequest(email: String, code: String, newPassword: String)

// Updated MessageResponse to include expiry info
data class MessageResponse(
    message: String,
    email: String? = null,
    expiresAt: String? = null
)
```

### Updated API Service (`ApiService.kt`)

Added endpoints:
- `POST /auth/email-otp/request`
- `POST /auth/email-otp/verify`
- `POST /auth/forgot-password`
- `POST /auth/reset-password`

### Updated Repository (`AuthRepository.kt`)

New methods:
- `requestEmailOtp(email: String): Resource<MessageResponse>`
- `verifyEmailOtp(email: String, code: String): Resource<AuthResponse>`
- `forgotPassword(email: String): Resource<String>`
- `resetPassword(email: String, code: String, newPassword: String): Resource<String>`

---

## 🗺️ Navigation Updates

### nav_graph.xml

Added:
- `forgotPasswordFragment` destination
- `action_loginFragment_to_forgotPasswordFragment` action
- `action_forgotPasswordFragment_to_loginFragment` action (back navigation)

---

## 🎯 User Flow

### Password Reset Flow

1. User taps "Forgot Password?" on login screen
2. User enters their email address
3. User taps "Send Verification Code"
4. System sends OTP to user's email via `noreply@starmaxltd.com`
5. User receives email with 6-digit OTP (valid for 10 minutes)
6. User enters OTP code
7. User enters new password (min 8 characters)
8. User confirms new password
9. User taps "Reset Password"
10. Success! User is redirected to login screen
11. User logs in with new password

### Email Notifications

When a tenant **registers**:
- Backend automatically sends welcome email
- Email sent from `noreply@starmaxltd.com`
- Email contains account details and app features

When a tenant has **rent due** (automated):
- Backend cron job runs daily at 9 AM
- Sends reminder 3 days before due date
- Email includes property name, amount, and due date

When a tenant is **overdue** (automated):
- Backend cron job runs daily at 10 AM
- Sends overdue notice
- Email includes days overdue and amount

---

## 🔐 Security Features

### OTP Configuration
- **Code length**: 6 digits
- **Expiry time**: 10 minutes
- **Resend delay**: 60 seconds (rate limiting)
- **Storage**: In-memory only (cleared after verification)

### Password Requirements
- Minimum 8 characters
- Must contain uppercase letter
- Must contain lowercase letter
- Must contain digit
- Must match confirmation password

---

## 🧪 Testing the App

### Test Password Reset

1. Build and run the tenant app:
   ```bash
   cd tenant-app
   ./gradlew assembleDebug
   # or open in Android Studio
   ```

2. On login screen, tap "Forgot Password?"
3. Enter registered email (e.g., `tenant@example.com`)
4. Check email inbox for OTP code
5. Enter OTP and new password
6. Verify redirect to login
7. Login with new password

### Test Welcome Email

1. On login screen, tap "Register Here"
2. Fill in registration form with valid email
3. Complete registration
4. Check email inbox for welcome email from Starmax Ltd

---

## 📧 Email Templates

All emails use professional HTML templates with:
- Starmax Ltd branding
- Mobile-responsive design
- Clear CTA buttons
- Security notices
- Footer with copyright

### Email Types:
1. **OTP Email** - Verification code in large centered box
2. **Password Reset** - Reset link with instructions
3. **Welcome Email** - Branded welcome with feature list
4. **Rent Reminder** - Payment due notification
5. **Overdue Notice** - Urgent payment request
6. **Maintenance Update** - Request status changes

---

## 🚀 Deployment Notes

### Backend Requirements
- Ensure `.env` has correct SMTP settings:
  ```
  MAIL_HOST="mail.starmaxltd.com"
  MAIL_PORT="465"
  MAIL_SECURE="true"
  MAIL_USER="noreply@starmaxltd.com"
  MAIL_PASSWORD="H{Y}E8pO]=ci"
  ```

### Android App Config
- Update base URL in `NetworkModule.kt` to production API
- Test on real device (not emulator) for email verification
- Ensure internet permission in AndroidManifest.xml

---

## 📝 Implementation Checklist

Backend:
- [x] Email service with nodemailer
- [x] OTP service for email verification
- [x] Email OTP endpoints
- [x] Password reset endpoints
- [x] Email templates (6 types)
- [x] Reminder service with cron jobs
- [x] Welcome email on registration

Android App:
- [x] Updated data models
- [x] Updated API service
- [x] Updated auth repository
- [x] ForgotPasswordFragment UI
- [x] ForgotPasswordViewModel
- [x] Navigation graph updates
- [x] LoginFragment forgot password link

---

## 🔍 Files Modified/Created

### Backend (NestJS)
- `src/modules/email/email.service.ts` *(new)*
- `src/modules/email/email.module.ts` *(new)*
- `src/modules/auth/otp.service.ts` *(updated)*
- `src/modules/auth/auth.service.ts` *(updated)*
- `src/modules/auth/auth.controller.ts` *(updated)*
- `src/modules/auth/auth.module.ts` *(updated)*
- `src/modules/auth/dto/request-email-otp.dto.ts` *(new)*
- `src/modules/auth/dto/verify-email-otp.dto.ts` *(new)*
- `src/modules/auth/dto/forgot-password.dto.ts` *(new)*
- `src/modules/auth/dto/reset-password.dto.ts` *(new)*
- `src/modules/reminders/reminders.service.ts` *(new)*
- `src/modules/reminders/reminders.module.ts` *(new)*
- `src/modules/reminders/reminders.controller.ts` *(new)*
- `src/modules/users/users.service.ts` *(updated)*
- `src/app.module.ts` *(updated)*
- `.env` *(updated)*

### Android App (Kotlin)
- `data/model/AuthModels.kt` *(updated)*
- `data/api/ApiService.kt` *(updated)*
- `data/repository/AuthRepository.kt` *(updated)*
- `ui/auth/ForgotPasswordViewModel.kt` *(new)*
- `ui/auth/ForgotPasswordFragment.kt` *(new)*
- `ui/auth/LoginFragment.kt` *(updated)*
- `res/layout/fragment_forgot_password.xml` *(new)*
- `res/navigation/nav_graph.xml` *(updated)*

---

## 🎉 What's Next?

### Future Enhancements

1. **Email Verification on Registration**
   - Require email verification before first login
   - Send verification link after registration

2. **SMS OTP Fallback**
   - Send OTP via SMS if email fails
   - Use Africa's Talking or Twilio

3. **Email Preferences**
   - Let users choose notification preferences
   - Opt-out of certain reminder types

4. **Email Queue**
   - Implement Bull queue for email reliability
   - Retry failed emails automatically

5. **Push Notifications**
   - Firebase Cloud Messaging integration
   - Push notifications for reminders

---

**Last Updated:** April 3, 2026  
**Version:** 1.0.0  
**Tested On:** Android 8.0+ (API 26+)
