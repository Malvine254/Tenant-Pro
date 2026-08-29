package com.tenantpro.app.data.repository

import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.api.ApiErrorMapper
import com.tenantpro.app.data.model.InitiatePaymentRequest
import com.tenantpro.app.data.model.InitiatePaymentResponse
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
    private val api: ApiService
) {
    private val gson = Gson()
    private val paymentListType = object : TypeToken<List<Payment>>() {}.type

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
            Resource.Success(response.body() ?: InitiatePaymentResponse("STK Push sent. Check your phone.", null, null))
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    /** Fetches payment records for a specific invoice. */
    suspend fun getPayments(): Resource<List<Payment>> = try {
        val response = api.getPayments()
        if (response.isSuccessful) {
            Resource.Success(parsePayments(response.body()))
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
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

    suspend fun getPaymentsByInvoice(invoiceId: String): Resource<List<Payment>> = try {
        val response = api.getPaymentsByInvoice(invoiceId)
        if (response.isSuccessful) {
            Resource.Success(response.body() ?: emptyList())
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }
}
