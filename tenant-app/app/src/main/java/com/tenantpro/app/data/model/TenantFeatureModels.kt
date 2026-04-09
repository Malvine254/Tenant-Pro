package com.tenantpro.app.data.model

import com.google.gson.annotations.SerializedName

data class TenantTenancyProfile(
    @SerializedName("id") val id: String,
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
    @SerializedName("profileImageUrl") val profileImageUrl: String? = null
)

data class NotificationItem(
    @SerializedName("id") val id: String,
    @SerializedName("type") val type: String,
    @SerializedName("title") val title: String,
    @SerializedName("message") val message: String,
    @SerializedName("isRead") val isRead: Boolean,
    @SerializedName("createdAt") val createdAt: String,
    @SerializedName("readAt") val readAt: String? = null
)

data class SupportMessageRequest(
    @SerializedName("topic") val topic: String,
    @SerializedName("text") val text: String,
    @SerializedName("attachmentUri") val attachmentUri: String? = null,
    @SerializedName("attachmentName") val attachmentName: String? = null
)

data class SupportMessageDto(
    @SerializedName("id") val id: String,
    @SerializedName("topic") val topic: String,
    @SerializedName("message") val message: String,
    @SerializedName("isFromTenant") val isFromTenant: Boolean,
    @SerializedName("timestamp") val timestamp: Long,
    @SerializedName("status") val status: String,
    @SerializedName("attachmentUri") val attachmentUri: String? = null,
    @SerializedName("attachmentName") val attachmentName: String? = null
)

data class MaintenanceRequestItem(
    @SerializedName("id") val id: String,
    @SerializedName("title") val title: String,
    @SerializedName("description") val description: String,
    @SerializedName("priority") val priority: String,
    @SerializedName("status") val status: String,
    @SerializedName("createdAt") val createdAt: String,
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
