package com.tenantpro.app.data.local

import com.tenantpro.app.data.local.dao.CachedResponseDao
import com.tenantpro.app.data.local.entity.CachedResponseEntity
import com.tenantpro.app.utils.DataStoreManager
import kotlinx.coroutines.flow.firstOrNull
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SafeResponseCache @Inject constructor(
    private val dao: CachedResponseDao,
    private val dataStore: DataStoreManager
) {
    suspend fun read(key: String, maxAgeMillis: Long? = null): String? {
        val userId = dataStore.userId.firstOrNull()?.takeIf { it.isNotBlank() } ?: return null
        val entry = dao.get(userId, key) ?: return null
        if (maxAgeMillis != null && System.currentTimeMillis() - entry.updatedAt > maxAgeMillis) return null
        return entry.payload
    }

    suspend fun write(key: String, payload: String) {
        val userId = dataStore.userId.firstOrNull()?.takeIf { it.isNotBlank() } ?: return
        dao.put(CachedResponseEntity(userId = userId, cacheKey = key, payload = payload))
    }

    suspend fun remove(key: String) {
        val userId = dataStore.userId.firstOrNull()?.takeIf { it.isNotBlank() } ?: return
        dao.remove(userId, key)
    }

    suspend fun clearCurrentUser() {
        val userId = dataStore.userId.firstOrNull()?.takeIf { it.isNotBlank() } ?: return
        dao.clearUser(userId)
    }
}
