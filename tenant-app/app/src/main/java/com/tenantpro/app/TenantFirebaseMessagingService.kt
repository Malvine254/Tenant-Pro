package com.tenantpro.app

import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.DeviceNotificationHelper
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.launch
import kotlinx.coroutines.runBlocking
import javax.inject.Inject

@AndroidEntryPoint
class TenantFirebaseMessagingService : FirebaseMessagingService() {

    @Inject lateinit var api: ApiService
    @Inject lateinit var dataStore: DataStoreManager

    private val supervisorJob = SupervisorJob()
    private val serviceScope = CoroutineScope(supervisorJob + Dispatchers.IO)

    override fun onNewToken(token: String) {
        serviceScope.launch {
            dataStore.savePendingFcmToken(token)
            val jwt = dataStore.accessToken.firstOrNull()
            if (!jwt.isNullOrBlank()) {
                runCatching { api.saveDeviceToken(mapOf("token" to token)) }
                    .onSuccess { response ->
                        if (response.isSuccessful) dataStore.clearPendingFcmToken()
                    }
            }
        }
    }

    override fun onMessageReceived(message: RemoteMessage) {
        // Complete notification creation before returning. Launching this in the service scope
        // allowed Android to destroy the service and cancel delivery while the app was closed.
        if (runBlocking { dataStore.notificationsEnabled.firstOrNull() } == false) return

        val title = message.data["title"] ?: message.notification?.title ?: "Tenant Pro"
        val body = message.data["body"] ?: message.notification?.body ?: return
        DeviceNotificationHelper.showPushNotification(
            context = this,
            id = message.data["notification_id"]?.hashCode() ?: message.messageId?.hashCode() ?: body.hashCode(),
            title = title,
            message = body,
            destination = message.data["destination"] ?: "NOTIFICATIONS",
            entityId = message.data["conversation_id"]
                ?: message.data["invoice_id"]
                ?: message.data["payment_id"]
                ?: message.data["maintenance_request_id"]
        )
    }

    override fun onDestroy() {
        supervisorJob.cancel()
        super.onDestroy()
    }
}
