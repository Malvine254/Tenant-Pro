package com.tenantpro.app.data.local

import com.google.gson.Gson
import com.google.gson.JsonObject
import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.model.CreateMaintenanceRequest
import com.tenantpro.app.data.model.SupportMessageRequest
import com.tenantpro.app.data.model.UpdateProfileRequest
import retrofit2.Response
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class OfflineActionProcessor @Inject constructor(
    private val api: ApiService,
    private val queue: OfflineActionQueue,
    private val gson: Gson
) {
    suspend fun flush() {
        for (action in queue.pending()) {
            val response = runCatching { execute(action) }.getOrElse {
                queue.markAttempt(action.actionId)
                break
            }

            when {
                response.isSuccessful -> queue.remove(action.actionId)
                response.code() in 400..499 && response.code() !in setOf(408, 429) -> {
                    queue.remove(action.actionId)
                }
                else -> {
                    queue.markAttempt(action.actionId)
                    break
                }
            }
        }
    }

    private suspend fun execute(action: PendingOfflineAction): Response<*> = when (action.actionType) {
        OfflineActionTypes.CREATE_MAINTENANCE -> api.createMaintenanceRequest(
            gson.fromJson(action.payload, CreateMaintenanceRequest::class.java)
        )
        OfflineActionTypes.SEND_SUPPORT_MESSAGE -> api.sendSupportMessage(
            gson.fromJson(action.payload, SupportMessageRequest::class.java)
        )
        OfflineActionTypes.UPDATE_PROFILE,
        OfflineActionTypes.UPDATE_SETTINGS -> api.updateMyProfile(
            gson.fromJson(action.payload, UpdateProfileRequest::class.java)
        )
        OfflineActionTypes.MARK_NOTIFICATION_READ -> {
            val notificationId = gson.fromJson(action.payload, JsonObject::class.java)
                .get("notificationId")
                .asString
            api.markNotificationRead(notificationId)
        }
        OfflineActionTypes.MARK_ALL_NOTIFICATIONS_READ -> api.markAllNotificationsRead()
        else -> throw IllegalArgumentException("Unknown offline action ${action.actionType}")
    }
}