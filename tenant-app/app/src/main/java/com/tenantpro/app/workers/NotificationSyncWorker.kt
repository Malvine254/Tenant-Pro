package com.tenantpro.app.workers

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.tenantpro.app.BuildConfig
import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.DeviceNotificationHelper
import kotlinx.coroutines.flow.firstOrNull
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.time.Instant

class NotificationSyncWorker(
    appContext: Context,
    params: WorkerParameters
) : CoroutineWorker(appContext, params) {

    override suspend fun doWork(): Result {
        val dataStore = DataStoreManager(applicationContext)
        val token = dataStore.accessToken.firstOrNull().orEmpty()

        if (token.isBlank()) return Result.success()

        return try {
            val api = createApi(token)
            syncNotifications(api, dataStore)
            syncSupportReplies(api, dataStore)
            Result.success()
        } catch (_: Exception) {
            Result.retry()
        }
    }

    private suspend fun syncNotifications(api: ApiService, dataStore: DataStoreManager) {
        val response = api.getNotifications()
        if (!response.isSuccessful) return

        val items = response.body().orEmpty()
        val lastCheckpoint = dataStore.lastNotificationCheckpoint.firstOrNull()?.toLongOrNull() ?: 0L
        val latestTimestamp = items.maxOfOrNull { parseEpochMillis(it.createdAt) } ?: 0L

        if (lastCheckpoint == 0L) {
            dataStore.saveLastNotificationCheckpoint(latestTimestamp)
            return
        }

        items
            .filter { !it.isRead && parseEpochMillis(it.createdAt) > lastCheckpoint }
            .sortedBy { parseEpochMillis(it.createdAt) }
            .forEach { DeviceNotificationHelper.showBackendNotification(applicationContext, it) }

        if (latestTimestamp > 0L) {
            dataStore.saveLastNotificationCheckpoint(latestTimestamp)
        }
    }

    private suspend fun syncSupportReplies(api: ApiService, dataStore: DataStoreManager) {
        val response = api.getSupportMessages()
        if (!response.isSuccessful) return

        val items = response.body().orEmpty()
        val lastCheckpoint = dataStore.lastSupportReplyCheckpoint.firstOrNull()?.toLongOrNull() ?: 0L
        val latestTimestamp = items.maxOfOrNull { it.timestamp } ?: 0L

        if (lastCheckpoint == 0L) {
            dataStore.saveLastSupportReplyCheckpoint(latestTimestamp)
            return
        }

        items
            .filter { !it.isFromTenant && it.timestamp > lastCheckpoint }
            .sortedBy { it.timestamp }
            .forEach {
                DeviceNotificationHelper.showSupportReply(
                    applicationContext,
                    topic = it.topic,
                    message = it.message,
                    timestamp = it.timestamp
                )
            }

        if (latestTimestamp > 0L) {
            dataStore.saveLastSupportReplyCheckpoint(latestTimestamp)
        }
    }

    private fun createApi(token: String): ApiService {
        val logging = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.NONE
        }

        val client = OkHttpClient.Builder()
            .addInterceptor { chain ->
                val request = chain.request().newBuilder()
                    .addHeader("Authorization", "Bearer $token")
                    .addHeader("Accept", "application/json")
                    .build()
                chain.proceed(request)
            }
            .addInterceptor(logging)
            .build()

        return Retrofit.Builder()
            .baseUrl(BuildConfig.BASE_URL)
            .client(client)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(ApiService::class.java)
    }

    private fun parseEpochMillis(value: String?): Long {
        if (value.isNullOrBlank()) return 0L
        return try {
            Instant.parse(value).toEpochMilli()
        } catch (_: Exception) {
            0L
        }
    }
}
