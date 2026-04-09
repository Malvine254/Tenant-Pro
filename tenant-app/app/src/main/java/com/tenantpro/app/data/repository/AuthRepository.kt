package com.tenantpro.app.data.repository

import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.model.AcceptInvitationRequest
import com.tenantpro.app.data.model.AuthResponse
import com.tenantpro.app.data.model.EmailLoginRequest
import com.tenantpro.app.data.model.ForgotPasswordRequest
import com.tenantpro.app.data.model.MessageResponse
import com.tenantpro.app.data.model.RegisterRequest
import com.tenantpro.app.data.model.RequestEmailOtpRequest
import com.tenantpro.app.data.model.RequestOtpRequest
import com.tenantpro.app.data.model.ResetPasswordRequest
import com.tenantpro.app.data.model.UpdateProfileRequest
import com.tenantpro.app.data.model.UserProfile
import com.tenantpro.app.data.model.VerifyEmailOtpRequest
import com.tenantpro.app.data.model.VerifyOtpRequest
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.Resource
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.map
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    private val api: ApiService,
    private val dataStore: DataStoreManager
) {
    private fun parseErrorMessage(response: retrofit2.Response<*>): String {
        return try {
            val raw = response.errorBody()?.string().orEmpty()
            if (raw.isBlank()) return "Request failed (${response.code()})"

            val obj = org.json.JSONObject(raw)
            when (val msg = obj.opt("message")) {
                is org.json.JSONArray -> (0 until msg.length()).joinToString(", ") { index -> msg.optString(index) }
                is String -> msg
                else -> obj.optString("error", "Request failed (${response.code()})")
            }
        } catch (_: Exception) {
            "Request failed (${response.code()})"
        }
    }
    suspend fun loginWithEmailPassword(email: String, password: String): Resource<AuthResponse> = try {
        val response = api.loginWithEmail(EmailLoginRequest(email, password))
        if (response.isSuccessful) {
            val body = response.body()!!
            val displayName = listOfNotNull(body.user?.firstName, body.user?.lastName)
                .joinToString(" ")
                .ifBlank { null }
            dataStore.saveAuthData(
                token = body.accessToken,
                phone = body.user?.phoneNumber ?: "",
                name = displayName,
                email = body.user?.email
            )
            Resource.Success(body)
        } else {
            Resource.Error("Invalid email or password (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    /** Normalises a phone number to E.164-ish format accepted by the backend regex \^\\+?[1-9]\\d{7,14}$.
     *  Strips leading 0 and prepends +254 for Kenyan numbers. */
    private fun normalisePhone(raw: String): String {
        val digits = raw.trim()
        return when {
            digits.startsWith("+") -> digits                          // already has country code
            digits.startsWith("254") -> "+$digits"                   // 254XXXXXXXXX → +254...
            digits.startsWith("0") && digits.length == 10 -> "+254${digits.substring(1)}" // 07XX → +2547XX
            else -> "+254$digits"                                      // bare number
        }
    }

    suspend fun registerUser(
        email: String,
        password: String,
        fullName: String,
        phoneNumber: String
    ): Resource<AuthResponse> = try {
        val names = fullName.trim().split(" ", limit = 2)
        val firstName = names.getOrNull(0) ?: fullName
        val lastName = names.getOrNull(1) ?: ""
        val normalisedPhone = normalisePhone(phoneNumber)

        val response = api.registerUser(
            RegisterRequest(
                email = email,
                password = password,
                firstName = firstName,
                lastName = lastName,
                phoneNumber = normalisedPhone,
                role = "TENANT"
            )
        )

        if (response.isSuccessful) {
            val body = response.body()!!
            val displayName = listOfNotNull(body.user?.firstName, body.user?.lastName)
                .joinToString(" ")
                .ifBlank { null }
            dataStore.saveAuthData(
                token = body.accessToken,
                phone = body.user?.phoneNumber ?: "",
                name = displayName,
                email = body.user?.email
            )
            Resource.Success(body)
        } else {
            // Try to parse NestJS validation error body for a readable message
            val errBody = try {
                val raw = response.errorBody()?.string() ?: ""
                val obj = org.json.JSONObject(raw)
                when {
                    obj.has("message") -> {
                        val msg = obj.get("message")
                        if (msg is org.json.JSONArray) msg.join(", ").replace("\"","") else msg.toString()
                    }
                    else -> "Registration failed (${response.code()})"
                }
            } catch (_: Exception) { "Registration failed (${response.code()})" }
            Resource.Error(errBody)
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error during registration")
    }

    /** Requests an OTP for the given phone number. */
    suspend fun requestOtp(phoneNumber: String): Resource<String> = try {
        val response = api.requestOtp(RequestOtpRequest(phoneNumber))
        if (response.isSuccessful) {
            Resource.Success(response.body()?.message ?: "OTP sent")
        } else {
            Resource.Error("Failed to send OTP (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    /** Verifies the OTP and persists the returned JWT. */
    suspend fun verifyOtp(phoneNumber: String, code: String): Resource<AuthResponse> = try {
        val response = api.verifyOtp(VerifyOtpRequest(phoneNumber, code))
        if (response.isSuccessful) {
            val body = response.body()!!
            // Persist token + user info
            dataStore.saveAuthData(
                token = body.accessToken,
                phone = phoneNumber,
                name  = listOfNotNull(body.user?.firstName, body.user?.lastName)
                    .joinToString(" ")
                    .ifBlank { null },
                email = body.user?.email
            )
            Resource.Success(body)
        } else {
            Resource.Error("Invalid OTP (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    /** Returns a Flow of whether the user has a stored JWT. */
    val isLoggedIn: Flow<Boolean> = dataStore.accessToken.map { !it.isNullOrBlank() }

    suspend fun logout() = dataStore.clearAll()

    suspend fun getSavedPhone(): String? = dataStore.phoneNumber.firstOrNull()

    private suspend fun syncUserProfileToStore(user: UserProfile) {
        val displayName = user.fullName?.takeIf { it.isNotBlank() }
            ?: listOfNotNull(user.firstName, user.lastName).joinToString(" ").ifBlank { null }

        dataStore.saveAuthData(
            token = dataStore.accessToken.firstOrNull() ?: "",
            phone = user.phoneNumber,
            name = displayName,
            email = user.email
        )

        dataStore.saveProfileData(
            name = displayName.orEmpty(),
            phone = user.phoneNumber,
            email = user.email.orEmpty(),
            emergencyContact = user.emergencyContactPhone.orEmpty(),
            bio = user.bio.orEmpty()
        )

        user.profileImageUrl?.let { dataStore.saveProfileImageUri(it) }
    }

    /** Fetches the current user's basic profile from the backend. */
    suspend fun getCurrentUser(): Resource<UserProfile> = try {
        val response = api.getMe()
        if (response.isSuccessful) {
            val user = response.body()!!
            syncUserProfileToStore(user)
            Resource.Success(user)
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    /** Fetches the richer tenant profile including tenancy details. */
    suspend fun getMyProfile(): Resource<UserProfile> = try {
        val response = api.getMyProfile()
        if (response.isSuccessful) {
            val user = response.body()!!
            syncUserProfileToStore(user)
            Resource.Success(user)
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    suspend fun updateMyProfile(
        fullName: String,
        phone: String,
        email: String,
        emergencyContact: String,
        bio: String,
        profileImageUrl: String? = null
    ): Resource<UserProfile> = try {
        val names = fullName.trim().split(" ", limit = 2)
        val response = api.updateMyProfile(
            UpdateProfileRequest(
                phoneNumber = phone.trim().ifBlank { null },
                email = email.trim().ifBlank { null },
                firstName = names.getOrNull(0)?.ifBlank { null },
                lastName = names.getOrNull(1)?.ifBlank { null },
                emergencyContactPhone = emergencyContact.trim().ifBlank { null },
                bio = bio.trim().ifBlank { null },
                profileImageUrl = profileImageUrl
            )
        )

        if (response.isSuccessful) {
            val user = response.body()!!
            syncUserProfileToStore(user)
            Resource.Success(user)
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    suspend fun acceptInvitation(code: String): Resource<String> = try {
        val response = api.acceptInvitation(AcceptInvitationRequest(code.trim()))
        if (response.isSuccessful) {
            Resource.Success(response.body()?.message ?: "Invitation accepted")
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Email OTP Login
    // ──────────────────────────────────────────────────────────────────────────

    /** Requests an OTP to be sent to the user's email. */
    suspend fun requestEmailOtp(email: String): Resource<MessageResponse> = try {
        val response = api.requestEmailOtp(RequestEmailOtpRequest(email))
        if (response.isSuccessful) {
            Resource.Success(response.body()!!)
        } else {
            Resource.Error("Failed to send OTP (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    /** Verifies the email OTP and logs the user in. */
    suspend fun verifyEmailOtp(email: String, code: String): Resource<AuthResponse> = try {
        val response = api.verifyEmailOtp(VerifyEmailOtpRequest(email, code))
        if (response.isSuccessful) {
            val body = response.body()!!
            val displayName = listOfNotNull(body.user?.firstName, body.user?.lastName)
                .joinToString(" ")
                .ifBlank { null }
            dataStore.saveAuthData(
                token = body.accessToken,
                phone = body.user?.phoneNumber ?: "",
                name = displayName,
                email = body.user?.email
            )
            Resource.Success(body)
        } else {
            Resource.Error("Invalid OTP (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Password Reset
    // ──────────────────────────────────────────────────────────────────────────

    /** Sends a password reset OTP to the user's email. */
    suspend fun forgotPassword(email: String): Resource<String> = try {
        val normalizedEmail = email.trim().lowercase()
        val response = api.forgotPassword(ForgotPasswordRequest(normalizedEmail))
        if (response.isSuccessful) {
            Resource.Success(response.body()?.message ?: "Reset code sent to your email")
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    /** Resets the user's password using the OTP. */
    suspend fun resetPassword(email: String, code: String, newPassword: String): Resource<String> = try {
        val normalizedEmail = email.trim().lowercase()
        val response = api.resetPassword(
            ResetPasswordRequest(normalizedEmail, code.trim(), newPassword)
        )
        if (response.isSuccessful) {
            Resource.Success(response.body()?.message ?: "Password reset successfully")
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }
}
