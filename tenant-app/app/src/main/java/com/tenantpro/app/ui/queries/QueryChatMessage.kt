package com.tenantpro.app.ui.queries

data class QueryChatMessage(
    val id: String,
    val conversationId: String? = null,
    val propertyId: String? = null,
    val propertyName: String? = null,
    val topic: String,
    val message: String,
    val isFromTenant: Boolean,
    val timestamp: Long,
    val status: String = "Sent",
    val clientMessageId: String? = null,
    val attachmentUri: String? = null,
    val attachmentName: String? = null,
    val localAttachmentUri: String? = null
)
