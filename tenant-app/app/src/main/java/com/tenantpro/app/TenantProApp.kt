package com.tenantpro.app

import android.app.Application
import com.tenantpro.app.utils.DeviceNotificationHelper
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.NotificationWorkScheduler
import dagger.hilt.android.HiltAndroidApp
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltAndroidApp
class TenantProApp : Application() {
    @Inject lateinit var notificationWorkScheduler: NotificationWorkScheduler
    @Inject lateinit var dataStoreManager: DataStoreManager

    private val applicationScope = CoroutineScope(SupervisorJob() + Dispatchers.IO)

    override fun onCreate() {
        super.onCreate()
        DeviceNotificationHelper.ensureNotificationChannels(this)
        notificationWorkScheduler.cancel()
        applicationScope.launch { dataStoreManager.migrateSensitiveStorage() }
        applicationScope.launch {
            cacheDir.resolve("invoices").listFiles()?.forEach { file ->
                if (System.currentTimeMillis() - file.lastModified() > TEMP_FILE_MAX_AGE_MS) {
                    file.delete()
                }
            }
        }
    }

    private companion object {
        const val TEMP_FILE_MAX_AGE_MS = 60 * 60 * 1000L
    }
}
