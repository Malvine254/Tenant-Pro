package com.tenantpro.app.data.local

object CacheKeys {
    const val INVOICES = "invoices:v1"
    const val PAYMENTS = "payments:v1"
    const val PROFILE = "profile:v1"
    const val PROFILE_BASIC = "profile:basic:v1"

    fun paymentsForInvoice(invoiceId: String): String = "payments:invoice:$invoiceId:v1"
}

object CachePolicy {
    const val SHORT_LIVED_MS = 2 * 60 * 1000L
    const val PROFILE_MS = 10 * 60 * 1000L
    const val MAX_OFFLINE_AGE_MS = 24 * 60 * 60 * 1000L
}
