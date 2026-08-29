package com.tenantpro.app.data.repository

import android.content.Context
import android.net.Uri
import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.model.AcceptInvitationRequest
import com.tenantpro.app.data.model.AuthResponse
import com.tenantpro.app.data.model.RegisterResponse
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
import com.tenantpro.app.data.api.ApiErrorMapper
import com.tenantpro.app.data.local.CacheKeys
import com.tenantpro.app.data.local.CachePolicy
import com.tenantpro.app.data.local.SafeResponseCache
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.NotificationWorkScheduler
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.UploadPayloadResolver
import com.google.firebase.messaging.FirebaseMessaging
import com.google.gson.Gson
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.tasks.await
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    private val api: ApiService,
    private val dataStore: DataStoreManager,
    private val notificationWorkScheduler: NotificationWorkScheduler,
    private val cache: SafeResponseCache,
    private val gson: Gson
) {
    private fun parseErrorMessage(response: retrofit2.Response<*>): String =
        ApiErrorMapper.fromResponse(response)

    suspend fun loginWithEmailPassword(email: String, password: String): Resource<AuthResponse> = try {
        val response = api.loginWithEmail(EmailLoginRequest(email, password))
        if (response.isSuccessful) {
            val body = response.body()
            when {
                body == null -> Resource.Error("Login response was empty. Please try again.")
                body.accessToken.isBlank() -> Resource.Error("Login response was missing a session token.")
                body.user?.userId.isNullOrBlank() -> Resource.Error("Login response was missing the account identity.")
                else -> {
                    val displayName = listOfNotNull(body.user?.firstName, body.user?.lastName)
                        .joinToString(" ")
                        .ifBlank { null }
                    if (!body.requiresPasswordChange) {
                        dataStore.saveAuthData(
                            token = body.accessToken,
                            phone = body.user?.phoneNumber ?: "",
                            name = displayName,
                            email = body.user?.email,
                            userId = body.user?.userId
                        )
                        body.user?.let { syncUserProfileToStore(it) }
                        saveBiometricSessionIfEnabled()
                        syncFcmToken()
                        notificationWorkScheduler.schedule()
                    }
                    Resource.Success(body)
                }
            }
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
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
    ): Resource<RegisterResponse> = try {
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
            Resource.Success(response.body() ?: RegisterResponse("Registration successful", email))
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    /** Requests an OTP for the given phone number. */
    suspend fun requestOtp(phoneNumber: String): Resource<String> = try {
        val response = api.requestOtp(RequestOtpRequest(phoneNumber))
        if (response.isSuccessful) {
            Resource.Success(response.body()?.message ?: "OTP sent")
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    /** Verifies the OTP and persists the returned JWT. */
    suspend fun verifyOtp(phoneNumber: String, code: String): Resource<AuthResponse> = try {
        val response = api.verifyOtp(VerifyOtpRequest(phoneNumber, code))
        if (response.isSuccessful) {
            val body = response.body()
            when {
                body == null -> Resource.Error("OTP response was empty. Please try again.")
                body.accessToken.isBlank() -> Resource.Error("OTP response was missing a session token.")
                body.user?.userId.isNullOrBlank() -> Resource.Error("OTP response was missing the account identity.")
                else -> {
                    dataStore.saveAuthData(
                        token = body.accessToken,
                        phone = phoneNumber,
                        name = listOfNotNull(body.user?.firstName, body.user?.lastName)
                            .joinToString(" ")
                            .ifBlank { null },
                        email = body.user?.email,
                        userId = body.user?.userId
                    )
                    body.user?.let { syncUserProfileToStore(it) }
                    saveBiometricSessionIfEnabled()
                    syncFcmToken()
                    notificationWorkScheduler.schedule()
                    Resource.Success(body)
                }
            }
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    suspend fun syncFcmToken() {
        runCatching {
            val pendingToken = dataStore.pendingFcmToken.firstOrNull()
            val token = pendingToken ?: FirebaseMessaging.getInstance().token.await()
            if (token.isBlank()) return@runCatching

            val response = api.saveDeviceToken(mapOf("token" to token))
            if (response.isSuccessful) {
                dataStore.clearPendingFcmToken()
            }
        }
    }

    private suspend fun saveBiometricSessionIfEnabled() {
        if (dataStore.biometricLockEnabled.firstOrNull() == true) {
            dataStore.saveCurrentSessionForBiometric()
        }
    }

    /** Returns a Flow of whether the user has a stored JWT. */
    val isLoggedIn: Flow<Boolean> = dataStore.accessToken.map { !it.isNullOrBlank() }
    val hasBiometricSession: Flow<Boolean> = dataStore.hasBiometricSession

    suspend fun logout() {
        notificationWorkScheduler.cancel()
        cache.clearCurrentUser()
        dataStore.clearSession()
    }

    suspend fun restoreBiometricSession(): Boolean {
        val restored = dataStore.restoreBiometricSession()
        if (restored) {
            syncFcmToken()
            notificationWorkScheduler.schedule()
        }
        return restored
    }

    suspend fun getSavedPhone(): String? = dataStore.phoneNumber.firstOrNull()

    suspend fun claimMatchingInvitations(): Boolean = runCatching {
        val response = api.claimMatchingInvitations()
        if (response.isSuccessful && (response.body()?.connectedCount ?: 0) > 0) {
            cache.remove(CacheKeys.PROFILE)
            cache.remove(CacheKeys.INVOICES)
        }
        response.isSuccessful
    }.getOrDefault(false)

    private suspend fun syncUserProfileToStore(user: UserProfile) {
        val displayName = user.fullName?.takeIf { it.isNotBlank() }
            ?: listOfNotNull(user.firstName, user.lastName).joinToString(" ").ifBlank { null }

        dataStore.saveAuthData(
            token = dataStore.accessToken.firstOrNull() ?: "",
            phone = user.phoneNumber,
            name = displayName,
            email = user.email,
            userId = user.userId
        )

        dataStore.saveProfileData(
            name = displayName.orEmpty(),
            phone = user.phoneNumber,
            email = user.email.orEmpty(),
            emergencyContact = user.emergencyContactPhone.orEmpty(),
            bio = user.bio.orEmpty()
        )

        user.profileImageUrl?.let { dataStore.saveProfileImageUri(it) }
        user.appSettings?.let {
            dataStore.saveSettingsPreferences(
                notificationsEnabled = it.notificationsEnabled,
                emailNotificationsEnabled = it.emailNotificationsEnabled,
                biometricLockEnabled = it.biometricLockEnabled
            )
        }
    }

    /** Fetches the current user's basic profile from the backend. */
    suspend fun getCurrentUser(forceRefresh: Boolean = false): Resource<UserProfile> {
        if (!forceRefresh) cachedProfile(CacheKeys.PROFILE_BASIC, CachePolicy.PROFILE_MS)?.let {
            return Resource.Success(it, fromCache = true)
        }
        return try {
            val response = api.getMe()
            if (response.isSuccessful) {
                val user = response.body()
                if (user == null) Resource.Error("Profile response was empty. Please try again.")
                else {
                    syncUserProfileToStore(user)
                    cache.write(CacheKeys.PROFILE_BASIC, gson.toJson(user))
                    Resource.Success(user)
                }
            } else {
                cachedProfile(CacheKeys.PROFILE_BASIC, CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(parseErrorMessage(response))
            }
        } catch (e: Exception) {
            cachedProfile(CacheKeys.PROFILE_BASIC, CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                Resource.Success(it, fromCache = true)
            } ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    /** Fetches the richer tenant profile including tenancy details. */
    suspend fun getMyProfile(forceRefresh: Boolean = false): Resource<UserProfile> {
        if (!forceRefresh) cachedProfile(CacheKeys.PROFILE, CachePolicy.PROFILE_MS)?.let {
            return Resource.Success(it, fromCache = true)
        }
        return try {
            val response = api.getMyProfile()
            if (response.isSuccessful) {
                val user = response.body()
                if (user == null) Resource.Error("Profile response was empty. Please try again.")
                else {
                    syncUserProfileToStore(user)
                    cache.write(CacheKeys.PROFILE, gson.toJson(user))
                    Resource.Success(user)
                }
            } else {
                cachedProfile(CacheKeys.PROFILE, CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(parseErrorMessage(response))
            }
        } catch (e: Exception) {
            cachedProfile(CacheKeys.PROFILE, CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                Resource.Success(it, fromCache = true)
            } ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    private suspend fun cachedProfile(key: String, maxAgeMillis: Long): UserProfile? =
        cache.read(key, maxAgeMillis)?.let { payload ->
            runCatching { gson.fromJson(payload, UserProfile::class.java) }.getOrNull()
        }

    private suspend fun cacheUpdatedProfile(user: UserProfile) {
        val payload = gson.toJson(user)
        cache.write(CacheKeys.PROFILE, payload)
        cache.write(CacheKeys.PROFILE_BASIC, payload)
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
            val user = response.body()
            if (user == null) {
                Resource.Error("Profile update response was empty. Please try again.")
            } else {
                syncUserProfileToStore(user)
                cacheUpdatedProfile(user)
                Resource.Success(user)
            }
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    suspend fun updateAppSettings(
        notificationsEnabled: Boolean,
        emailNotificationsEnabled: Boolean,
        biometricLockEnabled: Boolean
    ): Resource<UserProfile> = try {
        val response = api.updateMyProfile(
            UpdateProfileRequest(
                appSettings = com.tenantpro.app.data.model.AppSettings(
                    notificationsEnabled = notificationsEnabled,
                    emailNotificationsEnabled = emailNotificationsEnabled,
                    biometricLockEnabled = biometricLockEnabled
                )
            )
        )

        if (response.isSuccessful) {
            val user = response.body()
            if (user == null) {
                Resource.Error("Settings update response was empty. Please try again.")
            } else {
                syncUserProfileToStore(user)
                cacheUpdatedProfile(user)
                Resource.Success(user)
            }
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    suspend fun uploadProfileImage(uri: Uri, context: Context): Resource<UserProfile> {
        return try {
            val mimeType = context.contentResolver.getType(uri).orEmpty()
            if (mimeType !in setOf("image/jpeg", "image/png", "image/webp")) {
                return Resource.Error("Use a JPG, PNG, or WebP image.")
            }

            val payload = UploadPayloadResolver.fromUri(
                context = context,
                uri = uri,
                fallbackName = "profile_${System.currentTimeMillis()}.jpg"
            ) ?: return Resource.Error("Cannot open selected image.")

            if (payload.bytes.size > 5 * 1024 * 1024) {
                return Resource.Error("Profile image must be 5 MB or smaller.")
            }

            val part = MultipartBody.Part.createFormData(
                "file",
                payload.fileName,
                payload.bytes.toRequestBody(payload.mimeType.toMediaTypeOrNull())
            )
            val response = api.uploadMyProfileImage(part)

            if (response.isSuccessful) {
                val user = response.body()
                if (user == null) {
                    Resource.Error("Profile image response was empty. Please try again.")
                } else {
                    syncUserProfileToStore(user)
                    cacheUpdatedProfile(user)
                    Resource.Success(user)
                }
            } else {
                Resource.Error(parseErrorMessage(response))
            }
        } catch (e: Exception) {
            Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    suspend fun clearProfileImage(): Resource<UserProfile> = try {
        val response = api.updateMyProfile(UpdateProfileRequest(profileImageUrl = ""))
        if (response.isSuccessful) {
            val user = response.body()
            if (user == null) {
                Resource.Error("Profile update response was empty. Please try again.")
            } else {
                syncUserProfileToStore(user)
                cacheUpdatedProfile(user)
                dataStore.saveProfileImageUri("")
                Resource.Success(user)
            }
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    suspend fun acceptInvitation(code: String): Resource<String> = try {
        val response = api.acceptInvitation(AcceptInvitationRequest(code.trim()))
        if (response.isSuccessful) {
            cache.remove(CacheKeys.PROFILE)
            cache.remove(CacheKeys.INVOICES)
            Resource.Success(response.body()?.message ?: "Invitation accepted")
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Email OTP Login
    // ──────────────────────────────────────────────────────────────────────────

    /** Requests an OTP to be sent to the user's email. */
    suspend fun requestEmailOtp(email: String): Resource<MessageResponse> = try {
        val response = api.requestEmailOtp(RequestEmailOtpRequest(email))
        if (response.isSuccessful) {
            Resource.Success(response.body() ?: MessageResponse("OTP sent", email))
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    /** Verifies the email OTP and logs the user in. */
    suspend fun verifyEmailOtp(email: String, code: String): Resource<AuthResponse> = try {
        val response = api.verifyEmailOtp(VerifyEmailOtpRequest(email, code))
        if (response.isSuccessful) {
            val body = response.body()
            when {
                body == null -> Resource.Error("Verification response was empty. Please try again.")
                body.accessToken.isBlank() -> Resource.Error("Verification response was missing a session token.")
                body.user?.userId.isNullOrBlank() -> Resource.Error("Verification response was missing the account identity.")
                else -> {
                    val displayName = listOfNotNull(body.user?.firstName, body.user?.lastName)
                        .joinToString(" ")
                        .ifBlank { null }
                    dataStore.saveAuthData(
                        token = body.accessToken,
                        phone = body.user?.phoneNumber ?: "",
                        name = displayName,
                        email = body.user?.email,
                        userId = body.user?.userId
                    )
                    body.user?.let { syncUserProfileToStore(it) }
                    saveBiometricSessionIfEnabled()
                    syncFcmToken()
                    notificationWorkScheduler.schedule()
                    Resource.Success(body)
                }
            }
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
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
        Resource.Error(ApiErrorMapper.fromThrowable(e))
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
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FCM Token Management
    // ──────────────────────────────────────────────────────────────────────────

    /** Backward-compatible no-op wrapper retained for existing call sites. */
    @Suppress("unused")
    private suspend fun uploadFcmToken() {
        syncFcmToken()
    }
}
