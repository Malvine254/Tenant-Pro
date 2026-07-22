package com.tenantpro.app

import android.app.Application
import com.tenantpro.app.utils.DeviceNotificationHelper
import dagger.hilt.android.HiltAndroidApp

@HiltAndroidApp
class TenantProApp : Application() {
    override fun onCreate() {
        super.onCreate()
        DeviceNotificationHelper.ensureNotificationChannels(this)
    }
}
