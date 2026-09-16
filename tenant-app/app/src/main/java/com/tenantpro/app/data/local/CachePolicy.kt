package com.tenantpro.app.data.local

object CacheKeys {
    const val INVOICES = "invoices:v1"
    const val PAYMENTS = "payments:v1"
    const val PROFILE = "profile:v1"
    const val PROFILE_BASIC = "profile:basic:v1"
    const val NOTIFICATIONS = "notifications:v1"
    const val MAINTENANCE = "maintenance:v1"
    const val SUPPORT_MESSAGES = "support:messages:v1"
    const val CHAT_HISTORY = "support:chat-ui:v1"
    const val PENDING_SUPPORT_QUEUE = "support:pending-ui:v1"

    fun paymentsForInvoice(invoiceId: String): String = "payments:invoice:$invoiceId:v1"
}

object CachePolicy {
    const val SHORT_LIVED_MS = 2 * 60 * 1000L
    const val PROFILE_MS = 10 * 60 * 1000L
    val OFFLINE_MAX_AGE_MS: Long? = null
}
