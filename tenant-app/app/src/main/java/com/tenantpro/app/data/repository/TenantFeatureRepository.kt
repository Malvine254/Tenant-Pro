package com.tenantpro.app.data.repository

import android.content.Context
import android.net.Uri
import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.api.ApiErrorMapper
import com.tenantpro.app.data.local.CacheKeys
import com.tenantpro.app.data.local.CachePolicy
import com.tenantpro.app.data.local.OfflineActionQueue
import com.tenantpro.app.data.local.OfflineActionTypes
import com.tenantpro.app.data.local.SafeResponseCache
import com.tenantpro.app.data.model.CreateMaintenanceRequest
import com.tenantpro.app.data.model.MaintenanceRequestItem
import com.tenantpro.app.data.model.NotificationItem
import com.tenantpro.app.data.model.SupportMessageDto
import com.tenantpro.app.data.model.SupportMessageRequest
import com.tenantpro.app.data.model.UploadAttachmentResponse
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.UploadPayloadResolver
import com.google.gson.Gson
import com.google.gson.reflect.TypeToken
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import javax.inject.Inject
import javax.inject.Singleton
import java.time.Instant
import java.util.UUID

@Singleton
class TenantFeatureRepository @Inject constructor(
    private val api: ApiService,
    private val cache: SafeResponseCache,
    private val gson: Gson,
    private val offlineActions: OfflineActionQueue
) {
    private val notificationListType = object : TypeToken<List<NotificationItem>>() {}.type
    private val maintenanceListType = object : TypeToken<List<MaintenanceRequestItem>>() {}.type
    private val supportMessageListType = object : TypeToken<List<SupportMessageDto>>() {}.type

    suspend fun supportHeartbeat(): Map<String, Boolean>? = runCatching {
        val response = api.supportHeartbeat()
        if (response.isSuccessful) response.body() else null
    }.getOrNull()
    suspend fun setSupportTyping(typing: Boolean) { runCatching { api.setSupportTyping(mapOf("typing" to typing)) } }
    suspend fun getNotifications(forceRefresh: Boolean = false): Resource<List<NotificationItem>> {
        if (!forceRefresh) cachedNotifications(CachePolicy.SHORT_LIVED_MS)?.let {
            return Resource.Success(it, fromCache = true)
        }
        return try {
            val response = api.getNotifications()
            if (response.isSuccessful) {
                val items = response.body().orEmpty()
                cache.write(CacheKeys.NOTIFICATIONS, gson.toJson(items))
                Resource.Success(items)
            } else {
                cachedNotifications(CachePolicy.OFFLINE_MAX_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(ApiErrorMapper.fromResponse(response))
            }
        } catch (e: Exception) {
            cachedNotifications(CachePolicy.OFFLINE_MAX_AGE_MS)?.let {
                Resource.Success(it, fromCache = true)
            } ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    suspend fun markAllNotificationsRead(): Resource<String> = try {
        val response = api.markAllNotificationsRead()
        if (response.isSuccessful) {
            cache.remove(CacheKeys.NOTIFICATIONS)
            Resource.Success(response.body()?.message ?: "Notifications marked as read")
        } else if (response.code() == 408 || response.code() == 429 || response.code() >= 500) {
            queueMarkAllNotificationsRead()
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        queueMarkAllNotificationsRead()
    }

    suspend fun markNotificationRead(id: String): Resource<NotificationItem> = try {
        val response = api.markNotificationRead(id)
        if (response.isSuccessful) {
            response.body()?.let {
                cache.remove(CacheKeys.NOTIFICATIONS)
                Resource.Success(it)
            }
                ?: Resource.Error("Notification update was empty. Please try again.")
        } else if (response.code() == 408 || response.code() == 429 || response.code() >= 500) {
            queueNotificationRead(id, ApiErrorMapper.fromResponse(response))
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        queueNotificationRead(id, ApiErrorMapper.fromThrowable(e))
    }

    private suspend fun queueMarkAllNotificationsRead(): Resource<String> {
        offlineActions.enqueue(
            OfflineActionTypes.MARK_ALL_NOTIFICATIONS_READ,
            payload = "{}",
            dedupeKey = "all"
        )
        updateCachedNotifications { items -> items.map { it.copy(isRead = true) } }
        return Resource.Success("Notifications marked as read offline and will sync automatically")
    }

    private suspend fun queueNotificationRead(id: String, fallbackMessage: String): Resource<NotificationItem> {
        offlineActions.enqueue(
            OfflineActionTypes.MARK_NOTIFICATION_READ,
            payload = gson.toJson(mapOf("notificationId" to id)),
            dedupeKey = id
        )
        val cachedItem = updateCachedNotifications { items ->
            items.map { item -> if (item.id == id) item.copy(isRead = true) else item }
        }?.firstOrNull { it.id == id }
        return cachedItem?.let { Resource.Success(it) }
            ?: Resource.Error(fallbackMessage)
    }

    suspend fun getSupportMessages(forceRefresh: Boolean = false): Resource<List<SupportMessageDto>> {
        if (!forceRefresh) cachedSupportMessages(CachePolicy.SHORT_LIVED_MS)?.let {
            return Resource.Success(it, fromCache = true)
        }
        return try {
            val response = api.getSupportMessages()
            if (response.isSuccessful) {
                val items = response.body().orEmpty()
                cache.write(CacheKeys.SUPPORT_MESSAGES, gson.toJson(items))
                Resource.Success(items)
            } else {
                cachedSupportMessages(CachePolicy.OFFLINE_MAX_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(ApiErrorMapper.fromResponse(response))
            }
        } catch (e: Exception) {
            cachedSupportMessages(CachePolicy.OFFLINE_MAX_AGE_MS)?.let {
                Resource.Success(it, fromCache = true)
            } ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    suspend fun uploadSupportFile(uri: Uri, context: Context): Resource<UploadAttachmentResponse> {
        return try {
            val payload = UploadPayloadResolver.fromUri(
                context = context,
                uri = uri,
                fallbackName = "attachment_${System.currentTimeMillis()}"
            ) ?: return Resource.Error("Cannot open file")

            if (payload.bytes.size > 20 * 1024 * 1024) {
                return Resource.Error("Attachment must be 20 MB or smaller.")
            }

            val part = MultipartBody.Part.createFormData(
                "file",
                payload.fileName,
                payload.bytes.toRequestBody(payload.mimeType.toMediaTypeOrNull())
            )
            val response = api.uploadSupportAttachment(part)
            if (response.isSuccessful) {
                response.body()?.let { Resource.Success(it) }
                    ?: Resource.Error("Upload response was empty. Please try again.")
            } else {
                Resource.Error(ApiErrorMapper.fromResponse(response))
            }
        } catch (e: Exception) {
            Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    suspend fun sendSupportMessage(
        propertyId: String?,
        topic: String,
        text: String,
        attachmentUri: String? = null,
        attachmentName: String? = null,
        clientMessageId: String? = null
    ): Resource<List<SupportMessageDto>> = try {
        val request = SupportMessageRequest(topic, text, propertyId, attachmentUri, attachmentName, clientMessageId)
        val response = api.sendSupportMessage(request)
        if (response.isSuccessful) {
            val items = response.body().orEmpty()
            cache.write(CacheKeys.SUPPORT_MESSAGES, gson.toJson(items))
            Resource.Success(items)
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        if (!clientMessageId.isNullOrBlank()) {
            offlineActions.enqueue(
                OfflineActionTypes.SEND_SUPPORT_MESSAGE,
                payload = gson.toJson(
                    SupportMessageRequest(topic, text, propertyId, attachmentUri, attachmentName, clientMessageId)
                ),
                dedupeKey = clientMessageId
            )
        }
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    suspend fun getMaintenanceRequests(forceRefresh: Boolean = false): Resource<List<MaintenanceRequestItem>> {
        if (!forceRefresh) cachedMaintenance(CachePolicy.SHORT_LIVED_MS)?.let {
            return Resource.Success(it, fromCache = true)
        }
        return try {
            val response = api.getMaintenanceRequests()
            if (response.isSuccessful) {
                val items = pendingMaintenanceItems() + response.body().orEmpty()
                cache.write(CacheKeys.MAINTENANCE, gson.toJson(items))
                Resource.Success(items)
            } else {
                cachedMaintenance(CachePolicy.OFFLINE_MAX_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(ApiErrorMapper.fromResponse(response))
            }
        } catch (e: Exception) {
            cachedMaintenance(CachePolicy.OFFLINE_MAX_AGE_MS)?.let {
                Resource.Success(it, fromCache = true)
            } ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    suspend fun createMaintenanceRequest(
        title: String,
        description: String,
        priority: String
    ): Resource<MaintenanceRequestItem> {
        val clientRequestId = UUID.randomUUID().toString()
        val request = CreateMaintenanceRequest(
            title = title,
            description = description,
            priority = priority,
            clientRequestId = clientRequestId
        )
        return try {
            val response = api.createMaintenanceRequest(request)
            if (response.isSuccessful) {
                response.body()?.let { item ->
                    val cached = cachedMaintenance(CachePolicy.OFFLINE_MAX_AGE_MS).orEmpty()
                    cache.write(CacheKeys.MAINTENANCE, gson.toJson(listOf(item) + cached.filterNot { it.id == item.id }))
                    Resource.Success(item)
                } ?: Resource.Error("Maintenance response was empty. Please try again.")
            } else if (response.code() == 408 || response.code() == 429 || response.code() >= 500) {
                queueMaintenanceRequest(request, title, description, priority, ApiErrorMapper.fromResponse(response))
            } else {
                Resource.Error(ApiErrorMapper.fromResponse(response))
            }
        } catch (e: Exception) {
            queueMaintenanceRequest(request, title, description, priority, ApiErrorMapper.fromThrowable(e))
        }
    }

    private suspend fun queueMaintenanceRequest(
        request: CreateMaintenanceRequest,
        title: String,
        description: String,
        priority: String,
        fallbackMessage: String
    ): Resource<MaintenanceRequestItem> {
        val clientRequestId = request.clientRequestId ?: return Resource.Error(fallbackMessage)
        val queued = offlineActions.enqueue(
            OfflineActionTypes.CREATE_MAINTENANCE,
            payload = gson.toJson(request),
            dedupeKey = clientRequestId
        ) ?: return Resource.Error(fallbackMessage)
        val pendingItem = request.toPendingMaintenanceItem()
        val cached = cachedMaintenance(CachePolicy.OFFLINE_MAX_AGE_MS).orEmpty()
        cache.write(CacheKeys.MAINTENANCE, gson.toJson(listOf(pendingItem) + cached.filterNot { it.id == pendingItem.id }))
        return Resource.Success(pendingItem)
    }

    private suspend fun pendingMaintenanceItems(): List<MaintenanceRequestItem> = offlineActions.pending()
        .filter { it.actionType == OfflineActionTypes.CREATE_MAINTENANCE }
        .mapNotNull { action ->
            runCatching { gson.fromJson(action.payload, CreateMaintenanceRequest::class.java) }
                .getOrNull()
                ?.toPendingMaintenanceItem()
        }

    private fun CreateMaintenanceRequest.toPendingMaintenanceItem() = MaintenanceRequestItem(
        id = "offline-$clientRequestId",
        title = title,
        description = description,
        priority = priority ?: "MEDIUM",
        status = "PENDING_SYNC",
        createdAt = Instant.now().toString()
    )

    private suspend fun cachedNotifications(maxAgeMillis: Long?): List<NotificationItem>? =
        cache.read(CacheKeys.NOTIFICATIONS, maxAgeMillis)?.let { payload ->
            runCatching { gson.fromJson<List<NotificationItem>>(payload, notificationListType) }.getOrNull()
        }

    private suspend fun cachedMaintenance(maxAgeMillis: Long?): List<MaintenanceRequestItem>? =
        cache.read(CacheKeys.MAINTENANCE, maxAgeMillis)?.let { payload ->
            runCatching { gson.fromJson<List<MaintenanceRequestItem>>(payload, maintenanceListType) }.getOrNull()
        }

    private suspend fun cachedSupportMessages(maxAgeMillis: Long?): List<SupportMessageDto>? =
        cache.read(CacheKeys.SUPPORT_MESSAGES, maxAgeMillis)?.let { payload ->
            runCatching { gson.fromJson<List<SupportMessageDto>>(payload, supportMessageListType) }.getOrNull()
        }

    private suspend fun updateCachedNotifications(
        transform: (List<NotificationItem>) -> List<NotificationItem>
    ): List<NotificationItem>? {
        val items = cachedNotifications(CachePolicy.OFFLINE_MAX_AGE_MS) ?: return null
        return transform(items).also { cache.write(CacheKeys.NOTIFICATIONS, gson.toJson(it)) }
    }
}
