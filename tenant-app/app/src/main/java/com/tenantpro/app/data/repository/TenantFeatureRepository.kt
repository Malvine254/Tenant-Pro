package com.tenantpro.app.data.repository

import android.content.Context
import android.net.Uri
import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.api.ApiErrorMapper
import com.tenantpro.app.data.local.CacheKeys
import com.tenantpro.app.data.local.CachePolicy
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

@Singleton
class TenantFeatureRepository @Inject constructor(
    private val api: ApiService,
    private val cache: SafeResponseCache,
    private val gson: Gson
) {
    private val notificationListType = object : TypeToken<List<NotificationItem>>() {}.type
    private val maintenanceListType = object : TypeToken<List<MaintenanceRequestItem>>() {}.type

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
                cachedNotifications(CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(ApiErrorMapper.fromResponse(response))
            }
        } catch (e: Exception) {
            cachedNotifications(CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                Resource.Success(it, fromCache = true)
            } ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    suspend fun markAllNotificationsRead(): Resource<String> = try {
        val response = api.markAllNotificationsRead()
        if (response.isSuccessful) {
            cache.remove(CacheKeys.NOTIFICATIONS)
            Resource.Success(response.body()?.message ?: "Notifications marked as read")
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    suspend fun markNotificationRead(id: String): Resource<NotificationItem> = try {
        val response = api.markNotificationRead(id)
        if (response.isSuccessful) {
            response.body()?.let {
                cache.remove(CacheKeys.NOTIFICATIONS)
                Resource.Success(it)
            }
                ?: Resource.Error("Notification update was empty. Please try again.")
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    suspend fun getSupportMessages(): Resource<List<SupportMessageDto>> = try {
        val response = api.getSupportMessages()
        if (response.isSuccessful) {
            Resource.Success(response.body().orEmpty())
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
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
        val response = api.sendSupportMessage(
            SupportMessageRequest(topic, text, propertyId, attachmentUri, attachmentName, clientMessageId)
        )
        if (response.isSuccessful) {
            Resource.Success(response.body().orEmpty())
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    suspend fun getMaintenanceRequests(forceRefresh: Boolean = false): Resource<List<MaintenanceRequestItem>> {
        if (!forceRefresh) cachedMaintenance(CachePolicy.SHORT_LIVED_MS)?.let {
            return Resource.Success(it, fromCache = true)
        }
        return try {
            val response = api.getMaintenanceRequests()
            if (response.isSuccessful) {
                val items = response.body().orEmpty()
                cache.write(CacheKeys.MAINTENANCE, gson.toJson(items))
                Resource.Success(items)
            } else {
                cachedMaintenance(CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(ApiErrorMapper.fromResponse(response))
            }
        } catch (e: Exception) {
            cachedMaintenance(CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                Resource.Success(it, fromCache = true)
            } ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    suspend fun createMaintenanceRequest(
        title: String,
        description: String,
        priority: String
    ): Resource<MaintenanceRequestItem> = try {
        val response = api.createMaintenanceRequest(
            CreateMaintenanceRequest(title = title, description = description, priority = priority)
        )
        if (response.isSuccessful) {
            response.body()?.let { item ->
                val cached = cachedMaintenance(CachePolicy.MAX_OFFLINE_AGE_MS).orEmpty()
                cache.write(CacheKeys.MAINTENANCE, gson.toJson(listOf(item) + cached.filterNot { it.id == item.id }))
                Resource.Success(item)
            } ?: Resource.Error("Maintenance response was empty. Please try again.")
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    private suspend fun cachedNotifications(maxAgeMillis: Long): List<NotificationItem>? =
        cache.read(CacheKeys.NOTIFICATIONS, maxAgeMillis)?.let { payload ->
            runCatching { gson.fromJson<List<NotificationItem>>(payload, notificationListType) }.getOrNull()
        }

    private suspend fun cachedMaintenance(maxAgeMillis: Long): List<MaintenanceRequestItem>? =
        cache.read(CacheKeys.MAINTENANCE, maxAgeMillis)?.let { payload ->
            runCatching { gson.fromJson<List<MaintenanceRequestItem>>(payload, maintenanceListType) }.getOrNull()
        }
}
