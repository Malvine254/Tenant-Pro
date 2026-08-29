package com.tenantpro.app

import android.app.Application
import com.tenantpro.app.utils.DeviceNotificationHelper
import com.tenantpro.app.utils.NotificationWorkScheduler
import dagger.hilt.android.HiltAndroidApp
import javax.inject.Inject

@HiltAndroidApp
class TenantProApp : Application() {
    @Inject lateinit var notificationWorkScheduler: NotificationWorkScheduler

    override fun onCreate() {
        super.onCreate()
        DeviceNotificationHelper.ensureNotificationChannels(this)
        notificationWorkScheduler.cancel()
    }
}
