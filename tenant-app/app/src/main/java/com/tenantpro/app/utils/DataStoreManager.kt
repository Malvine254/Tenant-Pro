package com.tenantpro.app.utils

import android.content.Context
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.Flow
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
        private val KEY_PROFILE_IMAGE_URI = stringPreferencesKey("profile_image_uri")
        private val KEY_EMERGENCY_CONTACT = stringPreferencesKey("emergency_contact")
        private val KEY_PROFILE_BIO = stringPreferencesKey("profile_bio")
        private val KEY_QUERY_CHAT_HISTORY = stringPreferencesKey("query_chat_history")
        private val KEY_LAST_NOTIFICATION_CHECKPOINT = stringPreferencesKey("last_notification_checkpoint")
        private val KEY_LAST_SUPPORT_REPLY_CHECKPOINT = stringPreferencesKey("last_support_reply_checkpoint")
    }

    val accessToken: Flow<String?> = context.dataStore.data
        .map { it[KEY_ACCESS_TOKEN] }

    val phoneNumber: Flow<String?> = context.dataStore.data
        .map { it[KEY_PHONE_NUMBER] }

    val userName: Flow<String?> = context.dataStore.data
        .map { it[KEY_USER_NAME] }

    val userEmail: Flow<String?> = context.dataStore.data
        .map { it[KEY_USER_EMAIL] }

    val profileImageUri: Flow<String?> = context.dataStore.data
        .map { it[KEY_PROFILE_IMAGE_URI] }

    val emergencyContact: Flow<String?> = context.dataStore.data
        .map { it[KEY_EMERGENCY_CONTACT] }

    val profileBio: Flow<String?> = context.dataStore.data
        .map { it[KEY_PROFILE_BIO] }

    val queryChatHistoryJson: Flow<String?> = context.dataStore.data
        .map { it[KEY_QUERY_CHAT_HISTORY] }

    val lastNotificationCheckpoint: Flow<String?> = context.dataStore.data
        .map { it[KEY_LAST_NOTIFICATION_CHECKPOINT] }

    val lastSupportReplyCheckpoint: Flow<String?> = context.dataStore.data
        .map { it[KEY_LAST_SUPPORT_REPLY_CHECKPOINT] }

    suspend fun saveAuthData(token: String, phone: String, name: String?, email: String? = null) {
        context.dataStore.edit { prefs ->
            prefs[KEY_ACCESS_TOKEN] = token
            prefs[KEY_PHONE_NUMBER] = phone
            if (name != null) prefs[KEY_USER_NAME] = name
            if (email != null) prefs[KEY_USER_EMAIL] = email
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

    suspend fun clearAll() {
        context.dataStore.edit { it.clear() }
    }
}
