package com.tenantpro.app.data.local.dao

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import com.tenantpro.app.data.local.entity.InvoiceEntity

@Dao
interface InvoiceDao {

    @Query("SELECT * FROM invoices ORDER BY createdAt DESC")
    suspend fun getAllInvoices(): List<InvoiceEntity>

    /** Upsert – replaces existing rows on ID conflict. */
    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(invoices: List<InvoiceEntity>)

    @Query("DELETE FROM invoices")
    suspend fun clearAll()
}
