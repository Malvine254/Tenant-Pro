package com.tenantpro.app.data.local

import com.tenantpro.app.data.local.dao.OfflineActionDao
import com.tenantpro.app.data.local.entity.OfflineActionEntity
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.LocalDataCipher
import com.tenantpro.app.utils.OfflineSyncScheduler
import kotlinx.coroutines.flow.firstOrNull
import java.security.MessageDigest
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton

object OfflineActionTypes {
    const val CREATE_MAINTENANCE = "create_maintenance"
    const val SEND_SUPPORT_MESSAGE = "send_support_message"
    const val UPDATE_PROFILE = "update_profile"
    const val UPDATE_SETTINGS = "update_settings"
    const val MARK_NOTIFICATION_READ = "mark_notification_read"
    const val MARK_ALL_NOTIFICATIONS_READ = "mark_all_notifications_read"
}

data class PendingOfflineAction(
    val actionId: String,
    val actionType: String,
    val payload: String,
    val attemptCount: Int
)

@Singleton
class OfflineActionQueue @Inject constructor(
    private val dao: OfflineActionDao,
    private val dataStore: DataStoreManager,
    private val cipher: LocalDataCipher,
    private val scheduler: OfflineSyncScheduler
) {
    suspend fun enqueue(
        actionType: String,
        payload: String,
        dedupeKey: String = UUID.randomUUID().toString()
    ): String? {
        val namespace = currentUserNamespace() ?: return null
        val actionId = digest("$namespace\u0000$actionType\u0000$dedupeKey")
        val encryptedPayload = cipher.encrypt(payload, associatedData(namespace, actionId)) ?: return null
        dao.put(
            OfflineActionEntity(
                actionId = actionId,
                userId = namespace,
                actionType = actionType,
                dedupeKey = dedupeKey,
                payload = encryptedPayload
            )
        )
        scheduler.schedule()
        return actionId
    }

    suspend fun pending(): List<PendingOfflineAction> {
        val namespace = currentUserNamespace() ?: return emptyList()
        return buildList {
            dao.pending(namespace).forEach { action ->
                val payload = cipher.decrypt(action.payload, associatedData(namespace, action.actionId))
                if (payload == null) {
                    dao.remove(action.actionId)
                } else {
                    add(PendingOfflineAction(action.actionId, action.actionType, payload, action.attemptCount))
                }
            }
        }
    }

    suspend fun remove(actionId: String) = dao.remove(actionId)

    suspend fun markAttempt(actionId: String) = dao.incrementAttempts(actionId)

    suspend fun clearCurrentUser() {
        currentUserNamespace()?.let { dao.clearUser(it) }
    }

    private suspend fun currentUserNamespace(): String? = dataStore.userId.firstOrNull()
        ?.takeIf { it.isNotBlank() }
        ?.let(::digest)

    private fun associatedData(namespace: String, actionId: String) = "$namespace\u0000offline\u0000$actionId"

    private fun digest(value: String): String = MessageDigest.getInstance("SHA-256")
        .digest(value.toByteArray())
        .joinToString("") { byte -> (byte.toInt() and 0xff).toString(16).padStart(2, '0') }
}