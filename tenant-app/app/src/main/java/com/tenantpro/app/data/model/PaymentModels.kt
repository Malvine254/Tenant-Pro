package com.tenantpro.app.data.model

import com.google.gson.JsonElement
import com.google.gson.annotations.SerializedName

// ─── Payment initiation ───────────────────────────────────────────────────────

data class InitiatePaymentRequest(
    @SerializedName("invoiceId") val invoiceId: String? = null,
    @SerializedName("invoiceIds") val invoiceIds: List<String>? = null,
    @SerializedName("phoneNumber") val phoneNumber: String,
    @SerializedName("amount") val amount: Double? = null
)

data class InitiatePaymentResponse(
    @SerializedName("message") val message: String? = null,
    @SerializedName(value = "checkoutRequestID", alternate = ["checkoutRequestId", "mpesaCheckoutRequestId"]) val checkoutRequestId: String? = null,
    @SerializedName(value = "merchantRequestID", alternate = ["merchantRequestId", "mpesaRequestId"]) val merchantRequestId: String? = null
)

data class ManualPaymentInstructionsRequest(
    @SerializedName("invoiceIds") val invoiceIds: List<String>
)

data class ManualPaymentInstructions(
    @SerializedName("available") val available: Boolean = false,
    @SerializedName("stkAvailable") val stkAvailable: Boolean = false,
    @SerializedName("stkMessage") val stkMessage: String? = null,
    @SerializedName("paymentType") val paymentType: String = "PAYBILL",
    @SerializedName("businessNumber") val businessNumber: String = "",
    @SerializedName("accountReference") val accountReference: String? = null,
    @SerializedName("businessName") val businessName: String? = null,
    @SerializedName("note") val note: String? = null,
    @SerializedName("verificationRequired") val verificationRequired: Boolean = true,
    @SerializedName("message") val message: String? = null
)

// ─── Payment record ───────────────────────────────────────────────────────────

data class Payment(
    @SerializedName("id") val id: String = "",
    @SerializedName("status") val status: String = "PENDING",
    @SerializedName("amount") val amount: Double = 0.0,
    @SerializedName(value = "phoneNumber", alternate = ["phone_number", "payment_phone", "msisdn"]) val phoneNumber: String? = null,
    @SerializedName(
        value = "mpesaReceiptNumber",
        alternate = ["mpesa_receipt_number", "mpesa_receipt", "transactionCode", "transaction_code", "receiptNumber"]
    ) val mpesaReceiptNumber: String? = null,
    @SerializedName(value = "paidAt", alternate = ["paid_at", "transactionDate", "transaction_date"]) val paidAt: String? = null,
    @SerializedName(value = "createdAt", alternate = ["created_at"]) val createdAt: String = "",
    @SerializedName("invoice") val invoice: InvoiceSummary? = null,
    @SerializedName("metadata") val metadata: PaymentMetadata? = null,
    @SerializedName("transactions") val transactions: List<MpesaTransaction>? = emptyList()
)

data class PaymentMetadata(
    @SerializedName(value = "invoiceAllocations", alternate = ["invoice_allocations"])
    val invoiceAllocations: List<PaymentAllocation> = emptyList()
)

data class PaymentAllocation(
    @SerializedName(value = "invoiceId", alternate = ["invoice_id"]) val invoiceId: String = "",
    @SerializedName("amount") val amount: Double = 0.0
)

data class InvoiceSummary(
    @SerializedName("id") val id: String = "",
    @SerializedName(value = "billingType", alternate = ["billing_type"]) val billingType: String = "RENT",
    @SerializedName(value = "billingPeriod", alternate = ["billing_period"]) val billingPeriod: String? = null,
    @SerializedName(value = "periodMonth", alternate = ["period_month"]) val periodMonth: Int? = null,
    @SerializedName(value = "periodYear", alternate = ["period_year"]) val periodYear: Int? = null
)

data class MpesaTransaction(
    @SerializedName("id") val id: String = "",
    @SerializedName("type") val type: String = "",
    @SerializedName(value = "externalReference", alternate = ["external_reference", "transactionCode", "transaction_code"]) val externalReference: String? = null,
    @SerializedName(value = "processedAt", alternate = ["processed_at", "transactionDate", "transaction_date"]) val processedAt: String? = null,
    @SerializedName(value = "rawPayload", alternate = ["raw_payload"]) val rawPayload: JsonElement? = null,
    @SerializedName("description") val description: String? = null,
    @SerializedName(value = "createdAt", alternate = ["created_at"]) val createdAt: String = ""
)
