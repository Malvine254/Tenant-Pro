package com.tenantpro.app.data.model

import com.google.gson.annotations.SerializedName

data class TenantTenancyProfile(
    @SerializedName("id") val id: String = "",
    @SerializedName("moveInDate") val moveInDate: String? = null,
    @SerializedName("moveOutDate") val moveOutDate: String? = null,
    @SerializedName("isActive") val isActive: Boolean = true,
    @SerializedName("unit") val unit: UnitSummary? = null
)

data class UpdateProfileRequest(
    @SerializedName("phoneNumber") val phoneNumber: String? = null,
    @SerializedName("email") val email: String? = null,
    @SerializedName("firstName") val firstName: String? = null,
    @SerializedName("lastName") val lastName: String? = null,
    @SerializedName("emergencyContactName") val emergencyContactName: String? = null,
    @SerializedName("emergencyContactPhone") val emergencyContactPhone: String? = null,
    @SerializedName("bio") val bio: String? = null,
    @SerializedName("profileImageUrl") val profileImageUrl: String? = null,
    @SerializedName("appSettings") val appSettings: AppSettings? = null
)

data class NotificationItem(
    @SerializedName("id") val id: String = "",
    @SerializedName("type") val type: String = "INFO",
    @SerializedName("title") val title: String = "Notification",
    @SerializedName("message") val message: String = "",
    @SerializedName("isRead") val isRead: Boolean = false,
    @SerializedName("createdAt") val createdAt: String = "",
    @SerializedName("readAt") val readAt: String? = null
)

data class SupportMessageRequest(
    @SerializedName("topic") val topic: String,
    @SerializedName("text") val text: String,
    @SerializedName("propertyId") val propertyId: String? = null,
    @SerializedName("attachmentUri") val attachmentUri: String? = null,
    @SerializedName("attachmentName") val attachmentName: String? = null,
    @SerializedName("clientMessageId") val clientMessageId: String? = null
)

data class UploadAttachmentResponse(
    @SerializedName("attachmentUri") val attachmentUri: String = "",
    @SerializedName("attachmentName") val attachmentName: String = "",
    @SerializedName("fileName") val fileName: String? = null
)

data class SupportMessageDto(
    @SerializedName("id") val id: String = "",
    @SerializedName("conversationId") val conversationId: String? = null,
    @SerializedName("topic") val topic: String = "Support",
    @SerializedName("message") val message: String = "",
    @SerializedName("isFromTenant") val isFromTenant: Boolean = false,
    @SerializedName("timestamp") val timestamp: Long = 0L,
    @SerializedName("status") val status: String = "SENT",
    @SerializedName("propertyId") val propertyId: String? = null,
    @SerializedName("propertyName") val propertyName: String? = null,
    @SerializedName("attachmentUri") val attachmentUri: String? = null,
    @SerializedName("attachmentName") val attachmentName: String? = null
)

data class MaintenanceRequestItem(
    @SerializedName("id") val id: String = "",
    @SerializedName("title") val title: String = "",
    @SerializedName("description") val description: String = "",
    @SerializedName("priority") val priority: String = "MEDIUM",
    @SerializedName("status") val status: String = "OPEN",
    @SerializedName("createdAt") val createdAt: String = "",
    @SerializedName("resolvedAt") val resolvedAt: String? = null,
    @SerializedName("unit") val unit: UnitSummary? = null
)

data class CreateMaintenanceRequest(
    @SerializedName("title") val title: String,
    @SerializedName("description") val description: String,
    @SerializedName("priority") val priority: String? = null
)

data class AcceptInvitationRequest(
    @SerializedName("code") val code: String
)
