package com.tenantpro.app.data.local.dao

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import com.tenantpro.app.data.local.entity.CachedResponseEntity

@Dao
interface CachedResponseDao {
    @Query("SELECT * FROM cached_responses WHERE userId = :userId AND cacheKey = :cacheKey LIMIT 1")
    suspend fun get(userId: String, cacheKey: String): CachedResponseEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun put(entry: CachedResponseEntity)

    @Query("DELETE FROM cached_responses WHERE userId = :userId AND cacheKey = :cacheKey")
    suspend fun remove(userId: String, cacheKey: String)

    @Query("DELETE FROM cached_responses WHERE userId = :userId")
    suspend fun clearUser(userId: String)
}
