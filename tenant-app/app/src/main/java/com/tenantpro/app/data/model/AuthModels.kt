package com.tenantpro.app.data.model

import com.google.gson.annotations.SerializedName

// ─── Auth ────────────────────────────────────────────────────────────────────

data class RequestOtpRequest(
    @SerializedName("phoneNumber") val phoneNumber: String
)

data class VerifyOtpRequest(
    @SerializedName("phoneNumber") val phoneNumber: String,
    @SerializedName("code") val code: String
)

// Email OTP
data class RequestEmailOtpRequest(
    @SerializedName("email") val email: String
)

data class VerifyEmailOtpRequest(
    @SerializedName("email") val email: String,
    @SerializedName("code") val code: String
)

// Password Reset
data class ForgotPasswordRequest(
    @SerializedName("email") val email: String
)

data class ResetPasswordRequest(
    @SerializedName("email") val email: String,
    @SerializedName("code") val code: String,
    @SerializedName("newPassword") val newPassword: String
)

data class EmailLoginRequest(
    @SerializedName("email") val email: String,
    @SerializedName("password") val password: String
)

data class RegisterRequest(
    @SerializedName("email") val email: String,
    @SerializedName("password") val password: String,
    @SerializedName("firstName") val firstName: String,
    @SerializedName("lastName") val lastName: String = "",
    @SerializedName("phoneNumber") val phoneNumber: String,
    @SerializedName("role") val role: String = "TENANT"
)

data class AuthResponse(
    @SerializedName(value = "accessToken", alternate = ["access_token", "token"]) val accessToken: String = "",
    @SerializedName("user") val user: UserProfile? = null,
    @SerializedName("requiresPasswordChange") val requiresPasswordChange: Boolean = false
)

data class ChangePasswordRequest(
    @SerializedName("currentPassword") val currentPassword: String,
    @SerializedName("password") val password: String,
    @SerializedName("passwordConfirmation") val passwordConfirmation: String
)

data class RegisterResponse(
    @SerializedName("message") val message: String = "Registration successful",
    @SerializedName("email") val email: String = "",
    @SerializedName("user") val user: UserProfile? = null
)

data class UserProfile(
    @SerializedName(value = "id", alternate = ["userId"]) val userId: String = "",
    @SerializedName("phoneNumber") val phoneNumber: String = "",
    @SerializedName("email") val email: String? = null,
    @SerializedName("firstName") val firstName: String? = null,
    @SerializedName("lastName") val lastName: String? = null,
    @SerializedName("fullName") val fullName: String? = null,
    @SerializedName("profileImageUrl") val profileImageUrl: String? = null,
    @SerializedName("emergencyContactName") val emergencyContactName: String? = null,
    @SerializedName("emergencyContactPhone") val emergencyContactPhone: String? = null,
    @SerializedName("bio") val bio: String? = null,
    @SerializedName("tenantProfile") val tenantProfile: TenantTenancyProfile? = null,
    @SerializedName("tenantProfiles") val tenantProfiles: List<TenantTenancyProfile> = emptyList(),
    @SerializedName("appSettings") val appSettings: AppSettings? = null,
    @SerializedName("role") val role: String = "TENANT",
    @SerializedName("requiresSubscription") val requiresSubscription: Boolean = false,
    @SerializedName("billingStatus") val billingStatus: String? = null,
    @SerializedName("trialStartedAt") val trialStartedAt: String? = null,
    @SerializedName("trialEndsAt") val trialEndsAt: String? = null,
    @SerializedName("servicePaidUntil") val servicePaidUntil: String? = null,
    @SerializedName("subscriptionStatus") val subscriptionStatus: String? = null,
    @SerializedName("subscriptionAllowed") val subscriptionAllowed: Boolean = true,
    @SerializedName("subscriptionMessage") val subscriptionMessage: String? = null
)

data class AppSettings(
    @SerializedName("notificationsEnabled") val notificationsEnabled: Boolean = true,
    @SerializedName("emailNotificationsEnabled") val emailNotificationsEnabled: Boolean = true,
    @SerializedName("biometricLockEnabled") val biometricLockEnabled: Boolean = false
)

/** Partial settings payload so changing one switch cannot overwrite another. */
data class AppSettingsUpdate(
    @SerializedName("notificationsEnabled") val notificationsEnabled: Boolean? = null,
    @SerializedName("emailNotificationsEnabled") val emailNotificationsEnabled: Boolean? = null,
    @SerializedName("biometricLockEnabled") val biometricLockEnabled: Boolean? = null
)

data class MessageResponse(
    @SerializedName("message") val message: String = "Operation successful",
    @SerializedName("email") val email: String? = null,
    @SerializedName("expiresAt") val expiresAt: String? = null,
    @SerializedName("connectedCount") val connectedCount: Int = 0
)
