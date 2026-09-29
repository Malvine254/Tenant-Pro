package com.tenantpro.app.data.local

import com.google.gson.Gson
import com.google.gson.JsonObject
import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.model.CreateMaintenanceRequest
import com.tenantpro.app.data.model.SupportMessageRequest
import com.tenantpro.app.data.model.UpdateProfileRequest
import okhttp3.ResponseBody.Companion.toResponseBody
import retrofit2.Response
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class OfflineActionProcessor @Inject constructor(
    private val api: ApiService,
    private val queue: OfflineActionQueue,
    private val files: OfflineFileStore,
    private val gson: Gson
) {
    suspend fun flush() {
        for (action in queue.pending()) {
            val response = try {
                execute(action)
            } catch (_: Exception) {
                queue.markAttempt(action.actionId)
                break
            }

            when {
                response.isSuccessful -> complete(action)
                response.code() in 400..499 && response.code() !in setOf(408, 429) -> complete(action)
                else -> {
                    queue.markAttempt(action.actionId)
                    break
                }
            }
        }
    }

    private suspend fun complete(action: PendingOfflineAction) {
        queue.remove(action.actionId)
        queuedFileOf(action)?.let { files.delete(it.path) }
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
        OfflineActionTypes.UPLOAD_PROFILE_IMAGE -> {
            val queued = gson.fromJson(action.payload, QueuedFile::class.java)
            files.toPart(queued)?.let { api.uploadMyProfileImage(it) } ?: missingFileResponse()
        }
        OfflineActionTypes.SEND_SUPPORT_ATTACHMENT -> sendSupportAttachment(
            gson.fromJson(action.payload, QueuedSupportAttachment::class.java)
        )
        else -> throw IllegalArgumentException("Unknown offline action ${action.actionType}")
    }

    private suspend fun sendSupportAttachment(queued: QueuedSupportAttachment): Response<*> {
        val part = files.toPart(queued.file) ?: return missingFileResponse()
        val upload = api.uploadSupportAttachment(part)
        val uploaded = upload.body()
        if (!upload.isSuccessful || uploaded == null) return upload

        // clientMessageId lets the backend ignore a resend if this step is retried.
        return api.sendSupportMessage(
            queued.request.copy(
                attachmentUri = uploaded.attachmentUri,
                attachmentName = uploaded.attachmentName.ifBlank { queued.file.fileName }
            )
        )
    }

    private fun missingFileResponse(): Response<Any> =
        Response.error(410, "".toResponseBody(null))

    private fun queuedFileOf(action: PendingOfflineAction): QueuedFile? = runCatching {
        when (action.actionType) {
            OfflineActionTypes.UPLOAD_PROFILE_IMAGE -> gson.fromJson(action.payload, QueuedFile::class.java)
            OfflineActionTypes.SEND_SUPPORT_ATTACHMENT ->
                gson.fromJson(action.payload, QueuedSupportAttachment::class.java).file
            else -> null
        }
    }.getOrNull()
}