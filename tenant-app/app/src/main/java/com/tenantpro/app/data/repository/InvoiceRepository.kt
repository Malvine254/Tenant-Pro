package com.tenantpro.app.data.repository

import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.api.ApiErrorMapper
import com.tenantpro.app.data.local.CacheKeys
import com.tenantpro.app.data.local.CachePolicy
import com.tenantpro.app.data.local.SafeResponseCache
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
    private val api: ApiService,
    private val cache: SafeResponseCache
) {
    private val gson = Gson()
    private val invoiceListType = object : TypeToken<List<Invoice>>() {}.type

    /**
     * Backend-only invoice fetch.
     *  • On success → returns fresh server data.
     *  • On failure → returns an error and does not serve stale local cache.
     */
    suspend fun getInvoices(forceRefresh: Boolean = false): Resource<List<Invoice>> {
        if (!forceRefresh) {
            cachedInvoices(CachePolicy.SHORT_LIVED_MS)?.let {
                return Resource.Success(it, fromCache = true)
            }
        }
        return try {
            val response = api.getInvoices()
            if (response.isSuccessful) {
                val payload = response.body()
                val invoices = parseInvoices(payload)
                payload?.let { cache.write(CacheKeys.INVOICES, it.toString()) }
                Resource.Success(invoices)
            } else {
                cachedInvoices(CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                    Resource.Success(it, fromCache = true)
                } ?: Resource.Error(ApiErrorMapper.fromResponse(response))
            }
        } catch (e: Exception) {
            cachedInvoices(CachePolicy.MAX_OFFLINE_AGE_MS)?.let {
                Resource.Success(it, fromCache = true)
            } ?: Resource.Error(ApiErrorMapper.fromThrowable(e))
        }
    }

    suspend fun invalidateCache() = cache.remove(CacheKeys.INVOICES)

    private suspend fun cachedInvoices(maxAgeMillis: Long): List<Invoice>? =
        cache.read(CacheKeys.INVOICES, maxAgeMillis)?.let { payload ->
            runCatching { parseInvoices(com.google.gson.JsonParser.parseString(payload)) }.getOrNull()
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
