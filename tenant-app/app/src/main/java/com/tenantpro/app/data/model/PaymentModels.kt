package com.tenantpro.app.data.model

import com.google.gson.annotations.SerializedName

// ─── Payment initiation ───────────────────────────────────────────────────────

data class InitiatePaymentRequest(
    @SerializedName("invoiceId") val invoiceId: String,
    @SerializedName("phoneNumber") val phoneNumber: String,
    @SerializedName("amount") val amount: Double? = null
)

data class InitiatePaymentResponse(
    @SerializedName("message") val message: String?,
    @SerializedName("checkoutRequestID") val checkoutRequestId: String?,
    @SerializedName("merchantRequestID") val merchantRequestId: String?
)

// ─── Payment record ───────────────────────────────────────────────────────────

data class Payment(
    @SerializedName("id") val id: String,
    @SerializedName("status") val status: String,          // INITIATED | PENDING | SUCCESS | FAILED | REVERSED
    @SerializedName("amount") val amount: Double,
    @SerializedName("phoneNumber") val phoneNumber: String?,
    @SerializedName("mpesaReceiptNumber") val mpesaReceiptNumber: String?,
    @SerializedName("createdAt") val createdAt: String,
    @SerializedName("invoice") val invoice: InvoiceSummary?,
    @SerializedName("transactions") val transactions: List<MpesaTransaction>?
)

data class InvoiceSummary(
    @SerializedName("id") val id: String,
    @SerializedName("billingType") val billingType: String,
    @SerializedName("billingPeriod") val billingPeriod: String?
)

data class MpesaTransaction(
    @SerializedName("id") val id: String,
    @SerializedName("type") val type: String,
    @SerializedName("rawPayload") val rawPayload: String?,
    @SerializedName("createdAt") val createdAt: String
)
