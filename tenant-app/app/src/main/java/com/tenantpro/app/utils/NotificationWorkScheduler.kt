package com.tenantpro.app.utils

import android.content.Context
import androidx.work.WorkManager
import dagger.hilt.android.qualifiers.ApplicationContext
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class NotificationWorkScheduler @Inject constructor(
    @ApplicationContext private val context: Context
) {
    fun schedule() {
        // Notifications are delivered by FCM. Cancel legacy polling so opening
        // the app does not release a queue of old device notifications.
        cancel()
    }

    fun cancel() {
        WorkManager.getInstance(context).cancelUniqueWork(PERIODIC_WORK_NAME)
        WorkManager.getInstance(context).cancelUniqueWork(IMMEDIATE_WORK_NAME)
    }

    companion object {
        private const val PERIODIC_WORK_NAME = "tenant_device_notifications"
        private const val IMMEDIATE_WORK_NAME = "tenant_device_notifications_now"
    }
}
