package com.tenantpro.app.data.local

import com.tenantpro.app.data.local.dao.CachedResponseDao
import com.tenantpro.app.data.local.entity.CachedResponseEntity
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.LocalDataCipher
import kotlinx.coroutines.flow.firstOrNull
import java.nio.charset.StandardCharsets
import java.security.MessageDigest
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SafeResponseCache @Inject constructor(
    private val dao: CachedResponseDao,
    private val dataStore: DataStoreManager,
    private val cipher: LocalDataCipher
) {
    suspend fun read(key: String, maxAgeMillis: Long? = null): String? {
        val namespace = currentUserNamespace() ?: return null
        val entry = dao.get(namespace, key) ?: return null
        val age = System.currentTimeMillis() - entry.updatedAt
        if (age < 0 || (maxAgeMillis != null && age > maxAgeMillis)) {
            dao.remove(namespace, key)
            return null
        }
        if (entry.payload.length > MAX_ENCRYPTED_PAYLOAD_CHARS) {
            dao.remove(namespace, key)
            return null
        }
        return cipher.decrypt(entry.payload, associatedData(namespace, key)).also { decrypted ->
            // Plaintext legacy rows, tampered ciphertext and invalidated keys all
            // fail closed and are replaced by the next successful network fetch.
            if (decrypted == null) dao.remove(namespace, key)
        }
    }

    suspend fun write(key: String, payload: String) {
        val namespace = currentUserNamespace() ?: return
        if (payload.toByteArray(StandardCharsets.UTF_8).size > MAX_PLAINTEXT_PAYLOAD_BYTES) return
        val encrypted = cipher.encrypt(payload, associatedData(namespace, key)) ?: return
        dao.put(CachedResponseEntity(userId = namespace, cacheKey = key, payload = encrypted))
    }

    suspend fun remove(key: String) {
        val namespace = currentUserNamespace() ?: return
        dao.remove(namespace, key)
    }

    suspend fun clearCurrentUser() {
        val namespace = currentUserNamespace() ?: return
        dao.clearUser(namespace)
    }

    private suspend fun currentUserNamespace(): String? = dataStore.userId.firstOrNull()
        ?.takeIf { it.isNotBlank() }
        ?.let { userId ->
            MessageDigest.getInstance("SHA-256")
                .digest(userId.toByteArray(StandardCharsets.UTF_8))
                .joinToString("") { byte ->
                    (byte.toInt() and 0xff).toString(16).padStart(2, '0')
                }
        }

    private fun associatedData(namespace: String, key: String) = "$namespace\u0000$key"

    private companion object {
        const val MAX_PLAINTEXT_PAYLOAD_BYTES = 1024 * 1024
        const val MAX_ENCRYPTED_PAYLOAD_CHARS = 2 * 1024 * 1024
    }
}
