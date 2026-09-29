package com.tenantpro.app.data.repository

import android.content.Context
import android.net.Uri
import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.model.AcceptInvitationRequest
import com.tenantpro.app.data.model.AuthResponse
import com.tenantpro.app.data.model.ChangePasswordRequest
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
import com.tenantpro.app.data.local.OfflineActionQueue
import com.tenantpro.app.data.local.OfflineActionTypes
import com.tenantpro.app.data.local.OfflineFileStore
import com.tenantpro.app.data.local.SafeResponseCache
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.NetworkConnectivityObserver
import com.tenantpro.app.utils.NotificationWorkScheduler
import com.tenantpro.app.utils.OfflineCredential
import com.tenantpro.app.utils.OfflineCredentialStore
import com.tenantpro.app.utils.OfflineSyncScheduler
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.UploadPayloadResolver
import com.google.firebase.messaging.FirebaseMessaging
import com.google.gson.Gson
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.tasks.await
import kotlinx.coroutines.withTimeoutOrNull
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import dagger.hilt.android.qualifiers.ApplicationContext
import java.io.File
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepository @Inject constructor(
    @ApplicationContext private val applicationContext: Context,
    private val api: ApiService,
    private val dataStore: DataStoreManager,
    private val notificationWorkScheduler: NotificationWorkScheduler,
    private val cache: SafeResponseCache,
    private val offlineActions: OfflineActionQueue,
    private val offlineCredentials: OfflineCredentialStore,
    private val offlineSyncScheduler: OfflineSyncScheduler,
    private val connectivity: NetworkConnectivityObserver,
    private val offlineFiles: OfflineFileStore,
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
                        offlineCredentials.save(
                            email = body.user?.email ?: email,
                            userId = body.user?.userId.orEmpty(),
                            token = body.accessToken,
                            name = displayName,
                            phone = body.user?.phoneNumber ?: "",
                            password = password
                        )
                        syncFcmToken()
                        notificationWorkScheduler.schedule()
                        // Flush changes queued while offline or before a previous sign-out.
                        offlineSyncScheduler.schedule()
                    }
                    Resource.Success(body)
                }
            }
        } else if (response.code() == 408 || response.code() in 502..504) {
            // The host answered but the Laravel backend behind it is unavailable.
            offlineLogin(email, password) ?: Resource.Error(parseErrorMessage(response))
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        // Only fall back offline when the backend was never reached, so a rejected
        // password can never be satisfied by a stale local verifier.
        if (ApiErrorMapper.isConnectivityError(e)) {
            offlineLogin(email, password) ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        } else {
            Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    /** Restores the last verified session for this device so cached data stays reachable offline. */
    private suspend fun offlineLogin(email: String, password: String): Resource<AuthResponse>? {
        val credential = offlineCredentials.verify(email, password) ?: return null
        restoreSession(credential)

        return Resource.Success(
            AuthResponse(accessToken = credential.token, user = null, requiresPasswordChange = false),
            fromCache = true
        )
    }

    /** Restores token and identity together; the Room cache is scoped by user ID, so both are required. */
    private suspend fun restoreSession(credential: OfflineCredential) {
        dataStore.saveAuthData(
            token = credential.token,
            phone = credential.phone,
            name = credential.name,
            email = credential.email,
            userId = credential.userId
        )
        notificationWorkScheduler.schedule()
        // FCM and data sync run through WorkManager once a connection is available, never blocking sign-in.
        offlineSyncScheduler.schedule()
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
    val hasBiometricSession: Flow<Boolean> =
        combine(dataStore.hasBiometricSession, offlineCredentials.hasCredential) { token, credential -> token || credential }

    /** A revoked token may only be restored offline; online it would be rejected immediately. */
    suspend fun canUseBiometricSession(): Boolean {
        val credential = offlineCredentials.current()
        if (credential != null) {
            return !credential.tokenRevoked || connectivity.isConnected.firstOrNull() != true
        }
        return dataStore.hasBiometricSession.firstOrNull() == true
    }

    suspend fun logout() {
        notificationWorkScheduler.cancel()
        offlineSyncScheduler.cancel()
        withTimeoutOrNull(LOGOUT_NETWORK_TIMEOUT_MS) {
            val revoked = runCatching { api.logout().isSuccessful }.getOrDefault(false)
            if (revoked) offlineCredentials.markTokenRevoked()
            runCatching { FirebaseMessaging.getInstance().deleteToken().await() }
        }
        // The encrypted cache, queued actions and offline credential are scoped to this user
        // and kept so the tenant can sign back in offline; they sync after the next online sign-in.
        dataStore.clearSession()
    }

    suspend fun restoreBiometricSession(): Boolean {
        if (!canUseBiometricSession()) return false

        val credential = offlineCredentials.current()
        if (credential != null) {
            restoreSession(credential)
            return true
        }

        val restored = dataStore.restoreBiometricSession()
        if (restored) {
            notificationWorkScheduler.schedule()
            offlineSyncScheduler.schedule()
        }
        return restored
    }

    suspend fun getSavedPhone(): String? = dataStore.phoneNumber.firstOrNull()

    suspend fun claimMatchingInvitations(): Boolean = runCatching {
        val response = api.claimMatchingInvitations()
        val connected = response.isSuccessful && (response.body()?.connectedCount ?: 0) > 0
        if (connected) {
            cache.remove(CacheKeys.PROFILE)
            cache.remove(CacheKeys.INVOICES)
        }
        connected
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
        dataStore.saveRentalAccessRestricted(!user.subscriptionAllowed)

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
                cachedProfile(CacheKeys.PROFILE_BASIC, CachePolicy.OFFLINE_MAX_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(parseErrorMessage(response))
            }
        } catch (e: Exception) {
            cachedProfile(CacheKeys.PROFILE_BASIC, CachePolicy.OFFLINE_MAX_AGE_MS)?.let {
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
                cachedProfile(CacheKeys.PROFILE, CachePolicy.OFFLINE_MAX_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(parseErrorMessage(response))
            }
        } catch (e: Exception) {
            cachedProfile(CacheKeys.PROFILE, CachePolicy.OFFLINE_MAX_AGE_MS)?.let {
                Resource.Success(it, fromCache = true)
            } ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    private suspend fun cachedProfile(key: String, maxAgeMillis: Long?): UserProfile? =
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
    ): Resource<UserProfile> {
        val names = fullName.trim().split(" ", limit = 2)
        val request = UpdateProfileRequest(
            phoneNumber = phone.trim().ifBlank { null },
            email = email.trim().ifBlank { null },
            firstName = names.getOrNull(0)?.ifBlank { null },
            lastName = names.getOrNull(1)?.ifBlank { null },
            emergencyContactPhone = emergencyContact.trim().ifBlank { null },
            bio = bio.trim().ifBlank { null },
            // A photo saved offline is a device path; the server copy is set by the queued upload.
            profileImageUrl = profileImageUrl?.takeUnless { it.startsWith("file://") || it.startsWith("content://") }
        )
        return try {
            val response = api.updateMyProfile(request)
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
            val cached = cachedProfile(CacheKeys.PROFILE, CachePolicy.OFFLINE_MAX_AGE_MS)
                ?: return Resource.Error(ApiErrorMapper.fromThrowable(e))
            val updated = cached.copy(
                phoneNumber = request.phoneNumber ?: cached.phoneNumber,
                email = request.email ?: cached.email,
                firstName = request.firstName ?: cached.firstName,
                lastName = request.lastName ?: cached.lastName,
                fullName = fullName,
                emergencyContactPhone = request.emergencyContactPhone,
                bio = request.bio,
                profileImageUrl = request.profileImageUrl ?: cached.profileImageUrl
            )
            offlineActions.enqueue(
                OfflineActionTypes.UPDATE_PROFILE,
                gson.toJson(request),
                dedupeKey = "profile"
            ) ?: return Resource.Error(ApiErrorMapper.fromThrowable(e))
            syncUserProfileToStore(updated)
            cacheUpdatedProfile(updated)
            Resource.Success(updated, fromCache = true)
        }
    }

    suspend fun updateAppSettings(
        notificationsEnabled: Boolean? = null,
        emailNotificationsEnabled: Boolean? = null,
        biometricLockEnabled: Boolean? = null
    ): Resource<UserProfile> {
        val settingsUpdate = com.tenantpro.app.data.model.AppSettingsUpdate(
            notificationsEnabled = notificationsEnabled,
            emailNotificationsEnabled = emailNotificationsEnabled,
            biometricLockEnabled = biometricLockEnabled
        )
        val request = UpdateProfileRequest(appSettings = settingsUpdate)
        return try {
            val response = api.updateMyProfile(request)
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
            val cached = cachedProfile(CacheKeys.PROFILE, CachePolicy.OFFLINE_MAX_AGE_MS)
                ?: return Resource.Error(ApiErrorMapper.fromThrowable(e))
            val currentSettings = cached.appSettings ?: com.tenantpro.app.data.model.AppSettings()
            val updated = cached.copy(
                appSettings = currentSettings.copy(
                    notificationsEnabled = notificationsEnabled ?: currentSettings.notificationsEnabled,
                    emailNotificationsEnabled = emailNotificationsEnabled ?: currentSettings.emailNotificationsEnabled,
                    biometricLockEnabled = biometricLockEnabled ?: currentSettings.biometricLockEnabled
                )
            )
            val consolidatedSettings = updated.appSettings ?: currentSettings
            val consolidatedRequest = UpdateProfileRequest(
                appSettings = com.tenantpro.app.data.model.AppSettingsUpdate(
                    notificationsEnabled = consolidatedSettings.notificationsEnabled,
                    emailNotificationsEnabled = consolidatedSettings.emailNotificationsEnabled,
                    biometricLockEnabled = consolidatedSettings.biometricLockEnabled
                )
            )
            offlineActions.enqueue(
                OfflineActionTypes.UPDATE_SETTINGS,
                gson.toJson(consolidatedRequest),
                dedupeKey = "settings"
            ) ?: return Resource.Error(ApiErrorMapper.fromThrowable(e))
            syncUserProfileToStore(updated)
            cacheUpdatedProfile(updated)
            Resource.Success(updated, fromCache = true)
        }
    }

    suspend fun changePassword(
        currentPassword: String,
        newPassword: String,
        confirmation: String
    ): Resource<String> = try {
        val response = api.changePassword(
            ChangePasswordRequest(currentPassword, newPassword, confirmation)
        )
        if (response.isSuccessful) {
            offlineCredentials.updatePassword(newPassword)
            Resource.Success(response.body()?.message ?: "Password changed successfully.")
        } else {
            Resource.Error(parseErrorMessage(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    suspend fun uploadProfileImage(uri: Uri, context: Context): Resource<UserProfile> {
        val mimeType = context.contentResolver.getType(uri).orEmpty()
        if (mimeType !in setOf("image/jpeg", "image/png", "image/webp")) {
            return Resource.Error("Use a JPG, PNG, or WebP image.")
        }

        val payload = runCatching {
            UploadPayloadResolver.fromUri(
                context = context,
                uri = uri,
                fallbackName = "profile_${System.currentTimeMillis()}.jpg"
            )
        }.getOrNull() ?: return Resource.Error("Cannot open selected image.")

        if (payload.bytes.size > 5 * 1024 * 1024) {
            return Resource.Error("Profile image must be 5 MB or smaller.")
        }

        return try {
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
            if (ApiErrorMapper.isConnectivityError(e)) {
                queueProfileImage(payload.bytes, payload.fileName, payload.mimeType)
            } else {
                Resource.Error(ApiErrorMapper.fromThrowable(e))
            }
        }
    }

    private suspend fun queueProfileImage(bytes: ByteArray, fileName: String, mimeType: String): Resource<UserProfile> {
        // Only the newest photo matters; drop any photo still waiting to upload.
        offlineFiles.clear(OfflineFileStore.PROFILE_IMAGE_DIR)
        val queued = offlineFiles.save(OfflineFileStore.PROFILE_IMAGE_DIR, bytes, fileName, mimeType)
        if (offlineActions.enqueue(OfflineActionTypes.UPLOAD_PROFILE_IMAGE, gson.toJson(queued), dedupeKey = "profile-image") == null) {
            offlineFiles.delete(queued.path)
            return Resource.Error("The photo could not be saved for upload. Please try again.")
        }

        val localUrl = android.net.Uri.fromFile(File(queued.path)).toString()
        val updated = profileForOfflineEdit().copy(profileImageUrl = localUrl)
        cacheUpdatedProfile(updated)
        dataStore.saveProfileImageUri(localUrl)
        return Resource.Success(updated, fromCache = true)
    }

    suspend fun clearProfileImage(): Resource<UserProfile> {
        return try {
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
            if (!ApiErrorMapper.isConnectivityError(e)) return Resource.Error(ApiErrorMapper.fromThrowable(e))

            // Removing the file also cancels a photo still waiting to upload.
            offlineFiles.clear(OfflineFileStore.PROFILE_IMAGE_DIR)
            offlineActions.enqueue(
                OfflineActionTypes.UPDATE_PROFILE,
                gson.toJson(UpdateProfileRequest(profileImageUrl = "")),
                dedupeKey = "profile-image-clear"
            ) ?: return Resource.Error(ApiErrorMapper.fromThrowable(e))

            val updated = profileForOfflineEdit().copy(profileImageUrl = null)
            cacheUpdatedProfile(updated)
            dataStore.saveProfileImageUri("")
            Resource.Success(updated, fromCache = true)
        }
    }

    private suspend fun profileForOfflineEdit(): UserProfile =
        cachedProfile(CacheKeys.PROFILE, CachePolicy.OFFLINE_MAX_AGE_MS)
            ?: UserProfile(userId = dataStore.userId.firstOrNull().orEmpty())

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
            notificationWorkScheduler.cancel()
            cache.clearCurrentUser()
            dataStore.clearSession()
            // Reset invalidates the old password and existing tokens.
            offlineCredentials.updatePassword(newPassword, forEmail = normalizedEmail, revokeToken = true)
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

    private companion object {
        const val LOGOUT_NETWORK_TIMEOUT_MS = 5_000L
    }
}
