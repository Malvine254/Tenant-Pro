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
    @SerializedName(value = "accessToken", alternate = ["access_token"]) val accessToken: String,
    @SerializedName("user") val user: UserProfile?
)

data class UserProfile(
    @SerializedName(value = "id", alternate = ["userId"]) val userId: String,
    @SerializedName("phoneNumber") val phoneNumber: String,
    @SerializedName("email") val email: String?,
    @SerializedName("firstName") val firstName: String?,
    @SerializedName("lastName") val lastName: String?,
    @SerializedName("fullName") val fullName: String? = null,
    @SerializedName("profileImageUrl") val profileImageUrl: String? = null,
    @SerializedName("emergencyContactName") val emergencyContactName: String? = null,
    @SerializedName("emergencyContactPhone") val emergencyContactPhone: String? = null,
    @SerializedName("bio") val bio: String? = null,
    @SerializedName("tenantProfile") val tenantProfile: TenantTenancyProfile? = null,
    @SerializedName("role") val role: String
)

data class MessageResponse(
    @SerializedName("message") val message: String,
    @SerializedName("email") val email: String? = null,
    @SerializedName("expiresAt") val expiresAt: String? = null
)
