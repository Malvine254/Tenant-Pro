package com.tenantpro.app.data.repository

import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.api.ApiErrorMapper
import com.tenantpro.app.data.local.CacheKeys
import com.tenantpro.app.data.local.CachePolicy
import com.tenantpro.app.data.local.SafeResponseCache
import com.tenantpro.app.data.model.InitiatePaymentRequest
import com.tenantpro.app.data.model.InitiatePaymentResponse
import com.tenantpro.app.data.model.ManualPaymentInstructions
import com.tenantpro.app.data.model.ManualPaymentInstructionsRequest
import com.tenantpro.app.data.model.Payment
import com.tenantpro.app.utils.Resource
import com.google.gson.Gson
import com.google.gson.JsonArray
import com.google.gson.JsonElement
import com.google.gson.reflect.TypeToken
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class PaymentRepository @Inject constructor(
    private val api: ApiService,
    private val cache: SafeResponseCache
) {
    private val gson = Gson()
    private val paymentListType = object : TypeToken<List<Payment>>() {}.type

    suspend fun getManualPaymentInstructions(invoiceIds: List<String>): Resource<ManualPaymentInstructions> = try {
        val response = api.getManualPaymentInstructions(ManualPaymentInstructionsRequest(invoiceIds))
        if (response.isSuccessful) {
            response.body()?.let { Resource.Success(it) }
                ?: Resource.Error("Manual payment details were empty.")
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    /** Triggers an M-Pesa STK Push for the given invoice. */
    suspend fun initiatePayment(
        invoiceIds: List<String>,
        phoneNumber: String,
        amount: Double? = null
    ): Resource<InitiatePaymentResponse> = try {
        val response = api.initiatePayment(
            InitiatePaymentRequest(
                // Always include the primary invoice for compatibility with
                // production servers that predate multi-invoice payments.
                invoiceId = invoiceIds.firstOrNull(),
                invoiceIds = invoiceIds.takeIf { it.size > 1 },
                phoneNumber = phoneNumber,
                amount = amount
            )
        )
        if (response.isSuccessful) {
            cache.remove(CacheKeys.INVOICES)
            cache.remove(CacheKeys.PAYMENTS)
            invoiceIds.forEach { cache.remove(CacheKeys.paymentsForInvoice(it)) }
            Resource.Success(response.body() ?: InitiatePaymentResponse("STK Push sent. Check your phone.", null, null))
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    /** Fetches payment records for a specific invoice. */
    suspend fun getPayments(forceRefresh: Boolean = false): Resource<List<Payment>> {
        if (!forceRefresh) {
            cachedPayments(CacheKeys.PAYMENTS, CachePolicy.SHORT_LIVED_MS)?.let {
                return Resource.Success(it, fromCache = true)
            }
        }
        return try {
            val response = api.getPayments()
            if (response.isSuccessful) {
                val payload = response.body()
                val payments = parsePayments(payload)
                payload?.let { cache.write(CacheKeys.PAYMENTS, it.toString()) }
                Resource.Success(payments)
            } else {
                cachedPayments(CacheKeys.PAYMENTS, CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(ApiErrorMapper.fromResponse(response))
            }
        } catch (e: Exception) {
            cachedPayments(CacheKeys.PAYMENTS, CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                Resource.Success(it, fromCache = true)
            } ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    private fun parsePayments(payload: JsonElement?): List<Payment> {
        if (payload == null || payload.isJsonNull) return emptyList()

        val paymentArray = when {
            payload.isJsonArray -> payload.asJsonArray
            payload.isJsonObject -> {
                val obj = payload.asJsonObject
                listOf("data", "payments", "items", "results")
                    .firstNotNullOfOrNull { key ->
                        obj.get(key)?.takeIf { it.isJsonArray }?.asJsonArray
                    }
                    ?: throw IllegalStateException(
                        obj.get("message")?.asString
                            ?: obj.get("error")?.asString
                            ?: "Unexpected payment-history response from server"
                    )
            }
            else -> JsonArray()
        }

        return gson.fromJson(paymentArray, paymentListType)
    }

    suspend fun getPaymentsByInvoice(invoiceId: String, forceRefresh: Boolean = false): Resource<List<Payment>> {
        val key = CacheKeys.paymentsForInvoice(invoiceId)
        if (!forceRefresh) {
            cachedPayments(key, CachePolicy.SHORT_LIVED_MS)?.let {
                return Resource.Success(it, fromCache = true)
            }
        }
        return try {
            val response = api.getPaymentsByInvoice(invoiceId)
            if (response.isSuccessful) {
                val payments = response.body() ?: emptyList()
                cache.write(key, gson.toJson(payments))
                Resource.Success(payments)
            } else {
                cachedPayments(key, CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(ApiErrorMapper.fromResponse(response))
            }
        } catch (e: Exception) {
            cachedPayments(key, CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                Resource.Success(it, fromCache = true)
            } ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    private suspend fun cachedPayments(key: String, maxAgeMillis: Long): List<Payment>? =
        cache.read(key, maxAgeMillis)?.let { payload ->
            runCatching { parsePayments(com.google.gson.JsonParser.parseString(payload)) }.getOrNull()
        }
}
