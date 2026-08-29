package com.tenantpro.app.data.repository

import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.api.ApiErrorMapper
import com.tenantpro.app.data.model.Invoice
import com.tenantpro.app.utils.Resource
import com.google.gson.Gson
import com.google.gson.JsonArray
import com.google.gson.JsonElement
import com.google.gson.reflect.TypeToken
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class InvoiceRepository @Inject constructor(
    private val api: ApiService
) {
    private val gson = Gson()
    private val invoiceListType = object : TypeToken<List<Invoice>>() {}.type

    /**
     * Backend-only invoice fetch.
     *  • On success → returns fresh server data.
     *  • On failure → returns an error and does not serve stale local cache.
     */
    suspend fun getInvoices(): Resource<List<Invoice>> = try {
        val response = api.getInvoices()
        if (response.isSuccessful) {
            Resource.Success(parseInvoices(response.body()))
        } else {
            Resource.Error(ApiErrorMapper.fromResponse(response))
        }
    } catch (e: Exception) {
        Resource.Error(ApiErrorMapper.fromThrowable(e))
    }

    private fun parseInvoices(payload: JsonElement?): List<Invoice> {
        if (payload == null || payload.isJsonNull) return emptyList()

        val invoiceArray = when {
            payload.isJsonArray -> payload.asJsonArray
            payload.isJsonObject -> {
                val obj = payload.asJsonObject
                val wrapped = listOf("data", "invoices", "items", "results")
                    .firstNotNullOfOrNull { key ->
                        obj.get(key)?.takeIf { it.isJsonArray }?.asJsonArray
                    }

                wrapped ?: throw IllegalStateException(
                    obj.get("message")?.asString
                        ?: obj.get("error")?.asString
                        ?: "Unexpected invoice response from server"
                )
            }
            else -> JsonArray()
        }

        return gson.fromJson(invoiceArray, invoiceListType)
    }
}
