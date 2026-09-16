package com.tenantpro.app.workers

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.tenantpro.app.utils.OfflineSyncCoordinator
import dagger.hilt.EntryPoint
import dagger.hilt.InstallIn
import dagger.hilt.android.EntryPointAccessors
import dagger.hilt.components.SingletonComponent

class OfflineSyncWorker(
    appContext: Context,
    params: WorkerParameters
) : CoroutineWorker(appContext, params) {

    override suspend fun doWork(): Result = runCatching {
        EntryPointAccessors.fromApplication(
            applicationContext,
            OfflineSyncEntryPoint::class.java
        ).coordinator().syncNow()
    }.fold(
        onSuccess = { Result.success() },
        onFailure = { Result.retry() }
    )
}

@EntryPoint
@InstallIn(SingletonComponent::class)
interface OfflineSyncEntryPoint {
    fun coordinator(): OfflineSyncCoordinator
}