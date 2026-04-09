package com.tenantpro.app

import android.app.Application
import androidx.work.Constraints
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import com.tenantpro.app.utils.DeviceNotificationHelper
import com.tenantpro.app.workers.NotificationSyncWorker
import dagger.hilt.android.HiltAndroidApp
import java.util.concurrent.TimeUnit

@HiltAndroidApp
class TenantProApp : Application() {
    override fun onCreate() {
        super.onCreate()
        DeviceNotificationHelper.ensureNotificationChannels(this)
        scheduleNotificationSync()
    }

    private fun scheduleNotificationSync() {
        val constraints = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()

        val periodicWork = PeriodicWorkRequestBuilder<NotificationSyncWorker>(15, TimeUnit.MINUTES)
            .setConstraints(constraints)
            .build()

        val immediateWork = OneTimeWorkRequestBuilder<NotificationSyncWorker>()
            .setConstraints(constraints)
            .build()

        WorkManager.getInstance(this).enqueueUniquePeriodicWork(
            "tenant_device_notifications",
            ExistingPeriodicWorkPolicy.UPDATE,
            periodicWork
        )

        WorkManager.getInstance(this).enqueueUniqueWork(
            "tenant_device_notifications_now",
            ExistingWorkPolicy.REPLACE,
            immediateWork
        )
    }
}
