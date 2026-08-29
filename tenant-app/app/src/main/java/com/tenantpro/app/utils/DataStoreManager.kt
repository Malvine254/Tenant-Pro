package com.tenantpro.app.utils

import android.content.Context
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.booleanPreferencesKey
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map
import javax.inject.Inject
import javax.inject.Singleton

private val Context.dataStore by preferencesDataStore(name = "tenant_pro_prefs")

@Singleton
class DataStoreManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    companion object {
        private val KEY_ACCESS_TOKEN = stringPreferencesKey("access_token")
        private val KEY_PHONE_NUMBER = stringPreferencesKey("phone_number")
        private val KEY_USER_NAME    = stringPreferencesKey("user_name")
        private val KEY_USER_EMAIL   = stringPreferencesKey("user_email")
        private val KEY_USER_ID      = stringPreferencesKey("user_id")
        private val KEY_PROFILE_IMAGE_URI = stringPreferencesKey("profile_image_uri")
        private val KEY_EMERGENCY_CONTACT = stringPreferencesKey("emergency_contact")
        private val KEY_PROFILE_BIO = stringPreferencesKey("profile_bio")
        private val KEY_QUERY_CHAT_HISTORY = stringPreferencesKey("query_chat_history")
        private val KEY_PENDING_SUPPORT_QUEUE = stringPreferencesKey("pending_support_queue")
        private val KEY_LAST_NOTIFICATION_CHECKPOINT = stringPreferencesKey("last_notification_checkpoint")
        private val KEY_LAST_SUPPORT_REPLY_CHECKPOINT = stringPreferencesKey("last_support_reply_checkpoint")
        private val KEY_NOTIFICATIONS_ENABLED = booleanPreferencesKey("notifications_enabled")
        private val KEY_EMAIL_NOTIFICATIONS_ENABLED = booleanPreferencesKey("email_notifications_enabled")
        private val KEY_BIOMETRIC_LOCK_ENABLED = booleanPreferencesKey("biometric_lock_enabled")
        private val KEY_BIOMETRIC_SESSION_TOKEN = stringPreferencesKey("biometric_session_token")
        private val KEY_PENDING_FCM_TOKEN = stringPreferencesKey("pending_fcm_token")
        private val KEY_RENTAL_ACCESS_RESTRICTED = booleanPreferencesKey("rental_access_restricted")
    }

    val accessToken: Flow<String?> = context.dataStore.data
        .map { it[KEY_ACCESS_TOKEN] }

    val phoneNumber: Flow<String?> = context.dataStore.data
        .map { it[KEY_PHONE_NUMBER] }

    val userName: Flow<String?> = context.dataStore.data
        .map { it[KEY_USER_NAME] }

    val userEmail: Flow<String?> = context.dataStore.data
        .map { it[KEY_USER_EMAIL] }

    val userId: Flow<String?> = context.dataStore.data
        .map { it[KEY_USER_ID] }

    val profileImageUri: Flow<String?> = context.dataStore.data
        .map { it[KEY_PROFILE_IMAGE_URI] }

    val emergencyContact: Flow<String?> = context.dataStore.data
        .map { it[KEY_EMERGENCY_CONTACT] }

    val profileBio: Flow<String?> = context.dataStore.data
        .map { it[KEY_PROFILE_BIO] }

    val queryChatHistoryJson: Flow<String?> = context.dataStore.data
        .map { it[KEY_QUERY_CHAT_HISTORY] }

    val pendingSupportQueueJson: Flow<String?> = context.dataStore.data
        .map { it[KEY_PENDING_SUPPORT_QUEUE] }

    val lastNotificationCheckpoint: Flow<String?> = context.dataStore.data
        .map { it[KEY_LAST_NOTIFICATION_CHECKPOINT] }

    val lastSupportReplyCheckpoint: Flow<String?> = context.dataStore.data
        .map { it[KEY_LAST_SUPPORT_REPLY_CHECKPOINT] }

    val notificationsEnabled: Flow<Boolean> = context.dataStore.data
        .map { it[KEY_NOTIFICATIONS_ENABLED] ?: true }

    val emailNotificationsEnabled: Flow<Boolean> = context.dataStore.data
        .map { it[KEY_EMAIL_NOTIFICATIONS_ENABLED] ?: true }

    val biometricLockEnabled: Flow<Boolean> = context.dataStore.data
        .map { it[KEY_BIOMETRIC_LOCK_ENABLED] ?: false }

    val hasBiometricSession: Flow<Boolean> = context.dataStore.data
        .map { !it[KEY_BIOMETRIC_SESSION_TOKEN].isNullOrBlank() }

    val pendingFcmToken: Flow<String?> = context.dataStore.data
        .map { it[KEY_PENDING_FCM_TOKEN] }

    val rentalAccessRestricted: Flow<Boolean> = context.dataStore.data
        .map { it[KEY_RENTAL_ACCESS_RESTRICTED] ?: false }

    suspend fun saveAuthData(token: String, phone: String, name: String?, email: String? = null, userId: String? = null) {
        context.dataStore.edit { prefs ->
            prefs[KEY_ACCESS_TOKEN] = token
            prefs[KEY_PHONE_NUMBER] = phone
            if (name != null) prefs[KEY_USER_NAME] = name
            if (email != null) prefs[KEY_USER_EMAIL] = email
            if (userId != null) prefs[KEY_USER_ID] = userId
        }
    }

    suspend fun saveProfileData(
        name: String,
        phone: String,
        email: String,
        emergencyContact: String,
        bio: String
    ) {
        context.dataStore.edit { prefs ->
            prefs[KEY_USER_NAME] = name
            prefs[KEY_PHONE_NUMBER] = phone
            prefs[KEY_USER_EMAIL] = email
            prefs[KEY_EMERGENCY_CONTACT] = emergencyContact
            prefs[KEY_PROFILE_BIO] = bio
        }
    }

    suspend fun saveProfileImageUri(uri: String) {
        context.dataStore.edit { prefs ->
            prefs[KEY_PROFILE_IMAGE_URI] = uri
        }
    }

    suspend fun saveQueryChatHistory(json: String) {
        context.dataStore.edit { prefs ->
            prefs[KEY_QUERY_CHAT_HISTORY] = json
        }
    }

    suspend fun savePendingSupportQueue(json: String) {
        context.dataStore.edit { prefs ->
            prefs[KEY_PENDING_SUPPORT_QUEUE] = json
        }
    }

    suspend fun saveLastNotificationCheckpoint(value: Long) {
        context.dataStore.edit { prefs ->
            prefs[KEY_LAST_NOTIFICATION_CHECKPOINT] = value.toString()
        }
    }

    suspend fun saveLastSupportReplyCheckpoint(value: Long) {
        context.dataStore.edit { prefs ->
            prefs[KEY_LAST_SUPPORT_REPLY_CHECKPOINT] = value.toString()
        }
    }

    suspend fun saveNotificationsEnabled(enabled: Boolean) {
        context.dataStore.edit { prefs ->
            prefs[KEY_NOTIFICATIONS_ENABLED] = enabled
        }
    }

    suspend fun saveEmailNotificationsEnabled(enabled: Boolean) {
        context.dataStore.edit { prefs ->
            prefs[KEY_EMAIL_NOTIFICATIONS_ENABLED] = enabled
        }
    }

    suspend fun saveBiometricLockEnabled(enabled: Boolean) {
        context.dataStore.edit { prefs ->
            prefs[KEY_BIOMETRIC_LOCK_ENABLED] = enabled
            if (!enabled) {
                prefs.remove(KEY_BIOMETRIC_SESSION_TOKEN)
            }
        }
    }

    suspend fun saveSettingsPreferences(
        notificationsEnabled: Boolean,
        emailNotificationsEnabled: Boolean,
        biometricLockEnabled: Boolean
    ) {
        context.dataStore.edit { prefs ->
            prefs[KEY_NOTIFICATIONS_ENABLED] = notificationsEnabled
            prefs[KEY_EMAIL_NOTIFICATIONS_ENABLED] = emailNotificationsEnabled
            prefs[KEY_BIOMETRIC_LOCK_ENABLED] = biometricLockEnabled
            if (!biometricLockEnabled) {
                prefs.remove(KEY_BIOMETRIC_SESSION_TOKEN)
            }
        }
    }

    suspend fun savePendingFcmToken(token: String) {
        context.dataStore.edit { prefs ->
            prefs[KEY_PENDING_FCM_TOKEN] = token
        }
    }

    suspend fun saveRentalAccessRestricted(restricted: Boolean) {
        context.dataStore.edit { prefs ->
            prefs[KEY_RENTAL_ACCESS_RESTRICTED] = restricted
        }
    }

    suspend fun clearPendingFcmToken() {
        context.dataStore.edit { prefs ->
            prefs.remove(KEY_PENDING_FCM_TOKEN)
        }
    }

    suspend fun saveCurrentSessionForBiometric(): Boolean {
        val token = context.dataStore.data.first()[KEY_ACCESS_TOKEN].orEmpty()
        if (token.isBlank()) return false

        context.dataStore.edit { prefs ->
            prefs[KEY_BIOMETRIC_SESSION_TOKEN] = token
        }
        return true
    }

    suspend fun restoreBiometricSession(): Boolean {
        val token = context.dataStore.data.first()[KEY_BIOMETRIC_SESSION_TOKEN].orEmpty()
        if (token.isBlank()) return false

        context.dataStore.edit { prefs ->
            prefs[KEY_ACCESS_TOKEN] = token
        }
        return true
    }

    suspend fun clearSession() {
        context.dataStore.edit { prefs ->
            prefs.remove(KEY_ACCESS_TOKEN)
            prefs.remove(KEY_USER_ID)
            prefs.remove(KEY_PHONE_NUMBER)
            prefs.remove(KEY_USER_NAME)
            prefs.remove(KEY_USER_EMAIL)
            prefs.remove(KEY_PROFILE_IMAGE_URI)
            prefs.remove(KEY_EMERGENCY_CONTACT)
            prefs.remove(KEY_PROFILE_BIO)
            prefs.remove(KEY_QUERY_CHAT_HISTORY)
            prefs.remove(KEY_PENDING_SUPPORT_QUEUE)
            prefs.remove(KEY_LAST_NOTIFICATION_CHECKPOINT)
            prefs.remove(KEY_LAST_SUPPORT_REPLY_CHECKPOINT)
            prefs.remove(KEY_BIOMETRIC_SESSION_TOKEN)
            prefs.remove(KEY_RENTAL_ACCESS_RESTRICTED)
        }
    }

    suspend fun clearRestrictedRentalData() {
        context.dataStore.edit { prefs ->
            prefs[KEY_RENTAL_ACCESS_RESTRICTED] = true
            prefs.remove(KEY_QUERY_CHAT_HISTORY)
            prefs.remove(KEY_PENDING_SUPPORT_QUEUE)
            prefs.remove(KEY_LAST_SUPPORT_REPLY_CHECKPOINT)
        }
    }

    suspend fun clearAll() {
        context.dataStore.edit { it.clear() }
    }
}
