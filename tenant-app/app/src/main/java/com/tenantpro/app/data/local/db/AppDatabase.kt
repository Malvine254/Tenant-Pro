package com.tenantpro.app.data.local.db

import androidx.room.Database
import androidx.room.RoomDatabase
import com.tenantpro.app.data.local.dao.CachedResponseDao
import com.tenantpro.app.data.local.entity.CachedResponseEntity

@Database(
    entities = [CachedResponseEntity::class],
    version  = 3,
    exportSchema = false
)
abstract class AppDatabase : RoomDatabase() {
    abstract fun cachedResponseDao(): CachedResponseDao
}
