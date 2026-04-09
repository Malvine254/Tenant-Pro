package com.tenantpro.app.data.repository

import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.model.InitiatePaymentRequest
import com.tenantpro.app.data.model.InitiatePaymentResponse
import com.tenantpro.app.data.model.Payment
import com.tenantpro.app.utils.Resource
import kotlinx.coroutines.delay
import kotlin.random.Random
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class PaymentRepository @Inject constructor(
    private val api: ApiService
) {
    /** Triggers an M-Pesa STK Push for the given invoice. */
    suspend fun initiatePayment(
        invoiceId: String,
        phoneNumber: String,
        amount: Double? = null
    ): Resource<InitiatePaymentResponse> = try {
        val response = api.initiatePayment(
            InitiatePaymentRequest(invoiceId, phoneNumber, amount)
        )
        if (response.isSuccessful) {
            Resource.Success(response.body()!!)
        } else {
            Resource.Error("Payment failed (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }

    /** Local simulation mode for demo/testing when STK callbacks are unavailable. */
    suspend fun simulatePayment(
        invoiceId: String,
        phoneNumber: String,
        amount: Double? = null
    ): Resource<InitiatePaymentResponse> {
        delay(1200)
        val checkoutId = "ws_CO_${Random.nextInt(100000, 999999)}"
        val merchantId = "ms_${Random.nextInt(100000, 999999)}"
        return Resource.Success(
            InitiatePaymentResponse(
                message = "Simulation successful for $phoneNumber on invoice $invoiceId (KES ${amount ?: "full"}).",
                checkoutRequestId = checkoutId,
                merchantRequestId = merchantId
            )
        )
    }

    /** Fetches payment records for a specific invoice. */
    suspend fun getPaymentsByInvoice(invoiceId: String): Resource<List<Payment>> = try {
        val response = api.getPaymentsByInvoice(invoiceId)
        if (response.isSuccessful) {
            Resource.Success(response.body() ?: emptyList())
        } else {
            Resource.Error("Could not load payment history (${response.code()})")
        }
    } catch (e: Exception) {
        Resource.Error(e.message ?: "Network error")
    }
}
