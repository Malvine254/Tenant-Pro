package com.tenantpro.app.data.repository

import android.content.Context
import android.net.Uri
import android.provider.OpenableColumns
import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.model.CreateMaintenanceRequest
import com.tenantpro.app.data.model.MaintenanceRequestItem
import com.tenantpro.app.data.model.NotificationItem
import com.tenantpro.app.data.model.SupportMessageDto
import com.tenantpro.app.data.model.SupportMessageRequest
import com.tenantpro.app.data.model.UploadAttachmentResponse
import com.tenantpro.app.utils.Resource
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class TenantFeatureRepository @Inject constructor(
    private val api: ApiService
) {
    suspend fun getNotifications(): Resource<List<NotificationItem>> = try {
        val response = api.getNotifications()
        if (response.isSuccessful) {
            Resource.Success(response.body().orEmpty())
        } else {
            Resource.Error("Could not load notifications (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    suspend fun markAllNotificationsRead(): Resource<String> = try {
        val response = api.markAllNotificationsRead()
        if (response.isSuccessful) {
            Resource.Success(response.body()?.message ?: "Notifications marked as read")
        } else {
            Resource.Error("Could not update notifications (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    suspend fun getSupportMessages(): Resource<List<SupportMessageDto>> = try {
        val response = api.getSupportMessages()
        if (response.isSuccessful) {
            Resource.Success(response.body().orEmpty())
        } else {
            Resource.Error("Could not load support messages (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    suspend fun uploadSupportFile(uri: Uri, context: Context): Resource<UploadAttachmentResponse> = try {
        val stream = context.contentResolver.openInputStream(uri)
            ?: return Resource.Error("Cannot open file")
        val bytes = stream.use { it.readBytes() }
        val mimeType = context.contentResolver.getType(uri) ?: "application/octet-stream"
        val fileName = resolveFileName(context, uri)
        val part = MultipartBody.Part.createFormData(
            "file", fileName,
            bytes.toRequestBody(mimeType.toMediaTypeOrNull())
        )
        val response = api.uploadSupportAttachment(part)
        if (response.isSuccessful) Resource.Success(response.body()!!)
        else Resource.Error("Upload failed (${response.code()})")
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Upload failed")
    }

    private fun resolveFileName(context: Context, uri: Uri): String {
        var name = "file_${System.currentTimeMillis()}"
        context.contentResolver.query(uri, null, null, null, null)?.use { cursor ->
            val idx = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
            if (idx >= 0 && cursor.moveToFirst()) name = cursor.getString(idx)
        }
        return name
    }

    suspend fun sendSupportMessage(
        topic: String,
        text: String,
        attachmentUri: String? = null,
        attachmentName: String? = null
    ): Resource<List<SupportMessageDto>> = try {
        val response = api.sendSupportMessage(
            SupportMessageRequest(topic, text, attachmentUri, attachmentName)
        )
        if (response.isSuccessful) {
            Resource.Success(response.body().orEmpty())
        } else {
            Resource.Error("Could not send message (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    suspend fun getMaintenanceRequests(): Resource<List<MaintenanceRequestItem>> = try {
        val response = api.getMaintenanceRequests()
        if (response.isSuccessful) {
            Resource.Success(response.body().orEmpty())
        } else {
            Resource.Error("Could not load maintenance requests (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
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
            Resource.Success(response.body()!!)
        } else {
            Resource.Error("Could not submit request (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }
}
