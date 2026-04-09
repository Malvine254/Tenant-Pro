package com.tenantpro.app.utils

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import com.tenantpro.app.MainActivity
import com.tenantpro.app.R
import com.tenantpro.app.data.model.NotificationItem

object DeviceNotificationHelper {
    const val GENERAL_CHANNEL_ID = "tenant_updates"
    const val PRIORITY_CHANNEL_ID = "tenant_priority_updates"

    fun ensureNotificationChannels(context: Context) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return

        val manager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager

        val generalChannel = NotificationChannel(
            GENERAL_CHANNEL_ID,
            "Tenant updates",
            NotificationManager.IMPORTANCE_DEFAULT
        ).apply {
            description = "Invoice, payment, and general property updates"
        }

        val priorityChannel = NotificationChannel(
            PRIORITY_CHANNEL_ID,
            "Priority alerts",
            NotificationManager.IMPORTANCE_HIGH
        ).apply {
            description = "Urgent messages, overdue alerts, and important maintenance updates"
        }

        manager.createNotificationChannel(generalChannel)
        manager.createNotificationChannel(priorityChannel)
    }

    fun showBackendNotification(context: Context, item: NotificationItem) {
        val priority = isPriority(item.type, item.title, item.message)
        showNotification(
            context = context,
            id = item.id.hashCode(),
            title = item.title,
            message = item.message,
            priority = priority
        )
    }

    fun showSupportReply(context: Context, topic: String, message: String, timestamp: Long) {
        val preview = message.takeIf { it.isNotBlank() } ?: "You have a new reply from support."
        showNotification(
            context = context,
            id = timestamp.hashCode(),
            title = "New $topic message",
            message = preview,
            priority = true
        )
    }

    private fun showNotification(
        context: Context,
        id: Int,
        title: String,
        message: String,
        priority: Boolean
    ) {
        ensureNotificationChannels(context)

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED
        ) {
            return
        }

        val intent = Intent(context, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
        }
        val pendingIntent = PendingIntent.getActivity(
            context,
            id,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val notification = NotificationCompat.Builder(
            context,
            if (priority) PRIORITY_CHANNEL_ID else GENERAL_CHANNEL_ID
        )
            .setSmallIcon(R.drawable.ic_notifications)
            .setContentTitle(title)
            .setContentText(message)
            .setStyle(NotificationCompat.BigTextStyle().bigText(message))
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .setPriority(if (priority) NotificationCompat.PRIORITY_HIGH else NotificationCompat.PRIORITY_DEFAULT)
            .build()

        NotificationManagerCompat.from(context).notify(id, notification)
    }

    private fun isPriority(type: String, title: String, message: String): Boolean {
        val combined = "$title $message".lowercase()
        val normalizedType = type.uppercase()
        return normalizedType == "MESSAGE" ||
            normalizedType == "MAINTENANCE" ||
            combined.contains("overdue") ||
            combined.contains("urgent") ||
            combined.contains("message") ||
            combined.contains("failed")
    }
}
