package com.tenantpro.app.utils

import com.google.gson.Gson
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import java.security.MessageDigest
import java.security.SecureRandom
import javax.crypto.SecretKeyFactory
import javax.crypto.spec.PBEKeySpec
import javax.inject.Inject
import javax.inject.Singleton
import android.util.Base64

private const val PBKDF2_ALGORITHM = "PBKDF2WithHmacSHA256"
private const val PBKDF2_SALT_BYTES = 16
private const val PBKDF2_KEY_LENGTH_BITS = 256
private const val PBKDF2_ITERATIONS = 120_000

/**
 * Lets a tenant who has already authenticated on this device sign in again while the
 * backend is unreachable. The password is never stored - only a PBKDF2 verifier - and the
 * whole record is additionally encrypted at rest by [DataStoreManager].
 */
data class OfflineCredential(
    val email: String = "",
    val userId: String = "",
    val token: String = "",
    val name: String? = null,
    val phone: String = "",
    val salt: String = "",
    val iterations: Int = PBKDF2_ITERATIONS,
    val verifier: String = "",
    // The backend rejected this token; it may only be used while the device is offline.
    val tokenRevoked: Boolean = false
)

@Singleton
class OfflineCredentialStore @Inject constructor(
    private val dataStore: DataStoreManager,
    private val gson: Gson
) {
    suspend fun save(
        email: String,
        userId: String,
        token: String,
        name: String?,
        phone: String,
        password: String
    ) {
        if (email.isBlank() || userId.isBlank() || token.isBlank() || password.isBlank()) return

        val salt = ByteArray(PBKDF2_SALT_BYTES).also { SecureRandom().nextBytes(it) }
        val verifier = withContext(Dispatchers.Default) { derive(password, salt, PBKDF2_ITERATIONS) }

        val credential = OfflineCredential(
            email = email.trim().lowercase(),
            userId = userId,
            token = token,
            name = name,
            phone = phone,
            salt = Base64.encodeToString(salt, Base64.NO_WRAP),
            iterations = PBKDF2_ITERATIONS,
            verifier = Base64.encodeToString(verifier, Base64.NO_WRAP)
        )

        dataStore.saveOfflineCredential(gson.toJson(credential))
    }

    /** Returns the stored session only when the supplied password matches the saved verifier. */
    suspend fun verify(email: String, password: String): OfflineCredential? {
        val stored = read() ?: return null
        if (!stored.email.equals(email.trim(), ignoreCase = true)) return null
        if (stored.token.isBlank() || stored.userId.isBlank()) return null

        val salt = runCatching { Base64.decode(stored.salt, Base64.NO_WRAP) }.getOrNull() ?: return null
        val expected = runCatching { Base64.decode(stored.verifier, Base64.NO_WRAP) }.getOrNull() ?: return null
        val actual = withContext(Dispatchers.Default) { derive(password, salt, stored.iterations) }

        return if (MessageDigest.isEqual(expected, actual)) stored else null
    }

    suspend fun hasCredentialFor(email: String): Boolean =
        read()?.email?.equals(email.trim(), ignoreCase = true) == true

    val hasCredential: Flow<Boolean> = dataStore.offlineCredentialJson.map { !it.isNullOrBlank() }

    /** The last verified session on this device, used by biometric unlock after the device owner is authenticated. */
    suspend fun current(): OfflineCredential? =
        read()?.takeIf { it.token.isNotBlank() && it.userId.isNotBlank() }

    suspend fun markTokenRevoked() {
        val stored = read() ?: return
        if (!stored.tokenRevoked) dataStore.saveOfflineCredential(gson.toJson(stored.copy(tokenRevoked = true)))
    }

    suspend fun clear() = dataStore.clearOfflineCredential()

    private suspend fun read(): OfflineCredential? {
        val payload = dataStore.offlineCredentialJson.firstOrNull() ?: return null
        return runCatching { gson.fromJson(payload, OfflineCredential::class.java) }.getOrNull()
    }

    private fun derive(password: String, salt: ByteArray, iterations: Int): ByteArray {
        val spec = PBEKeySpec(password.toCharArray(), salt, iterations, PBKDF2_KEY_LENGTH_BITS)
        return SecretKeyFactory.getInstance(PBKDF2_ALGORITHM).generateSecret(spec).encoded
    }
}
