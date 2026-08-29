package com.tenantpro.app.data.local.entity

import androidx.room.Entity

@Entity(
    tableName = "cached_responses",
    primaryKeys = ["userId", "cacheKey"]
)
data class CachedResponseEntity(
    val userId: String,
    val cacheKey: String,
    val payload: String,
    val updatedAt: Long = System.currentTimeMillis()
)
