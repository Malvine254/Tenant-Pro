package com.tenantpro.app.data.local.dao

import androidx.room.Dao
import androidx.room.Insert
import androidx.room.OnConflictStrategy
import androidx.room.Query
import com.tenantpro.app.data.local.entity.OfflineActionEntity

@Dao
interface OfflineActionDao {
    @Query("SELECT * FROM offline_actions WHERE userId = :userId ORDER BY createdAt ASC")
    suspend fun pending(userId: String): List<OfflineActionEntity>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun put(action: OfflineActionEntity)

    @Query("DELETE FROM offline_actions WHERE actionId = :actionId")
    suspend fun remove(actionId: String)

    @Query("UPDATE offline_actions SET attemptCount = attemptCount + 1 WHERE actionId = :actionId")
    suspend fun incrementAttempts(actionId: String)

    @Query("DELETE FROM offline_actions WHERE userId = :userId")
    suspend fun clearUser(userId: String)
}