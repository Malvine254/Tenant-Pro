package com.tenantpro.app.data.local.db

import androidx.room.Database
import androidx.room.RoomDatabase
import com.tenantpro.app.data.local.dao.InvoiceDao
import com.tenantpro.app.data.local.entity.InvoiceEntity

@Database(
    entities = [InvoiceEntity::class],
    version  = 1,
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun invoiceDao(): InvoiceDao
}
