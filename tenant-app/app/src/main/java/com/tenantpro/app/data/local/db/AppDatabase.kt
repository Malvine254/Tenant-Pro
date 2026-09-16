package com.tenantpro.app.data.local.db

import androidx.room.Database
import androidx.room.RoomDatabase
import com.tenantpro.app.data.local.dao.CachedResponseDao
import com.tenantpro.app.data.local.dao.OfflineActionDao
import com.tenantpro.app.data.local.entity.CachedResponseEntity
import com.tenantpro.app.data.local.entity.OfflineActionEntity

@Database(
    entities = [CachedResponseEntity::class, OfflineActionEntity::class],
    version  = 4,
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun cachedResponseDao(): CachedResponseDao
    abstract fun offlineActionDao(): OfflineActionDao
}
