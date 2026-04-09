package com.tenantpro.app.data.repository

import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.local.dao.InvoiceDao
import com.tenantpro.app.data.local.entity.toEntity
import com.tenantpro.app.data.local.entity.toInvoice
import com.tenantpro.app.data.model.Invoice
import com.tenantpro.app.utils.Resource
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class InvoiceRepository @Inject constructor(
    private val api: ApiService,
    private val invoiceDao: InvoiceDao
) {
    /**
     * Network-first fetch.
     *  • On success  → writes to Room, returns fresh data (fromCache = false).
     *  • On failure  → reads Room; returns cached data (fromCache = true)
     *                  or an Error if the cache is also empty.
     */
    suspend fun getInvoices(): Resource<List<Invoice>> = try {
        val response = api.getInvoices()
        if (response.isSuccessful) {
            val invoices = response.body() ?: emptyList()
            // Atomically replace cache with latest server data
            invoiceDao.clearAll()
            invoiceDao.insertAll(invoices.map { it.toEntity() })
            Resource.Success(invoices)
        } else {
            serveFromCache("Server error (${response.code()})")
        }
    } catch (e: Exception) {
        serveFromCache(e.message ?: "No connection")
    }

    // ── Internal ───────────────────────────────────────────────────────────────

    private suspend fun serveFromCache(reason: String): Resource<List<Invoice>> {
        val cached = invoiceDao.getAllInvoices()
        return if (cached.isNotEmpty()) {
            Resource.Success(cached.map { it.toInvoice() }, fromCache = true)
        } else {
            Resource.Error("$reason — no cached data available")
        }
    }
}
