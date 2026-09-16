package com.tenantpro.app.data.local.entity

import androidx.room.Entity
import androidx.room.Index

@Entity(
    tableName = "offline_actions",
    primaryKeys = ["actionId"],
    indices = [Index(value = ["userId", "actionType", "dedupeKey"], unique = true)]
)
data class OfflineActionEntity(
    val actionId: String,
    val userId: String,
    val actionType: String,
    val dedupeKey: String,
    val payload: String,
    val createdAt: Long = System.currentTimeMillis(),
    val attemptCount: Int = 0
)