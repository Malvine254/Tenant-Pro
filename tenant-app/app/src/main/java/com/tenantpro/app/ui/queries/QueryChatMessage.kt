package com.tenantpro.app.ui.queries

data class QueryChatMessage(
    val id: String,
    val topic: String,
    val message: String,
    val isFromTenant: Boolean,
    val timestamp: Long,
    val status: String = "Sent",
    val attachmentUri: String? = null,
    val attachmentName: String? = null
)
