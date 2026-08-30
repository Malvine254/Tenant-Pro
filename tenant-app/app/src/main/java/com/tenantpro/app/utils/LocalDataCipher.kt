package com.tenantpro.app.utils

import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import java.nio.charset.StandardCharsets
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec
import javax.inject.Inject
import javax.inject.Singleton

/** Authenticated local encryption backed by a non-exportable Android Keystore key. */
@Singleton
class LocalDataCipher @Inject constructor() {
    fun encrypt(value: String, associatedData: String): String? {
        return runCatching { encryptInternal(value, associatedData) }.getOrElse {
            // A lock-screen change or restored app data can invalidate a key.
            // Replace it for future writes; old ciphertext will safely fail closed.
            resetKey()
            runCatching { encryptInternal(value, associatedData) }.getOrNull()
        }
    }

    private fun encryptInternal(value: String, associatedData: String): String {
        val cipher = Cipher.getInstance(TRANSFORMATION)
        cipher.init(Cipher.ENCRYPT_MODE, getOrCreateKey())
        cipher.updateAAD(associatedData.toByteArray(StandardCharsets.UTF_8))
        val ciphertext = cipher.doFinal(value.toByteArray(StandardCharsets.UTF_8))
        listOf(
            PREFIX,
            Base64.encodeToString(cipher.iv, Base64.NO_WRAP),
            Base64.encodeToString(ciphertext, Base64.NO_WRAP)
        ).joinToString(":")
    }

    fun decrypt(value: String, associatedData: String): String? {
        if (!isEncrypted(value)) return null
        return runCatching {
            val parts = value.split(':', limit = 4)
            if (parts.size != 4 || "${parts[0]}:${parts[1]}" != PREFIX) return null
            val iv = Base64.decode(parts[2], Base64.NO_WRAP)
            val ciphertext = Base64.decode(parts[3], Base64.NO_WRAP)
            val cipher = Cipher.getInstance(TRANSFORMATION)
            cipher.init(Cipher.DECRYPT_MODE, getOrCreateKey(), GCMParameterSpec(TAG_LENGTH_BITS, iv))
            cipher.updateAAD(associatedData.toByteArray(StandardCharsets.UTF_8))
            String(cipher.doFinal(ciphertext), StandardCharsets.UTF_8)
        }.getOrNull()
    }

    fun isEncrypted(value: String?): Boolean = value?.startsWith("$PREFIX:") == true

    private fun getOrCreateKey(): SecretKey = synchronized(KEY_LOCK) {
        val keyStore = KeyStore.getInstance(KEYSTORE_PROVIDER).apply { load(null) }
        (keyStore.getKey(KEY_ALIAS, null) as? SecretKey) ?: KeyGenerator
            .getInstance(KeyProperties.KEY_ALGORITHM_AES, KEYSTORE_PROVIDER)
            .apply {
                init(
                    KeyGenParameterSpec.Builder(
                        KEY_ALIAS,
                        KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT
                    )
                        .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                        .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                        .setRandomizedEncryptionRequired(true)
                        .build()
                )
            }
            .generateKey()
    }

    private fun resetKey() = synchronized(KEY_LOCK) {
        runCatching {
            KeyStore.getInstance(KEYSTORE_PROVIDER).apply {
                load(null)
                deleteEntry(KEY_ALIAS)
            }
        }
    }

    private companion object {
        const val KEYSTORE_PROVIDER = "AndroidKeyStore"
        const val KEY_ALIAS = "tenantpro_local_data_v1"
        const val TRANSFORMATION = "AES/GCM/NoPadding"
        const val PREFIX = "enc:v1"
        const val TAG_LENGTH_BITS = 128
        val KEY_LOCK = Any()
    }
}
