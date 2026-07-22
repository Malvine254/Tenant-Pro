package com.tenantpro.app.workers

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.tenantpro.app.BuildConfig
import com.tenantpro.app.data.api.ApiResponseConverterFactory
import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.DeviceNotificationHelper
import com.google.gson.GsonBuilder
import kotlinx.coroutines.flow.firstOrNull
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import java.time.Instant

class NotificationSyncWorker(
    appContext: Context,
    params: WorkerParameters
) : CoroutineWorker(appContext, params) {

    override suspend fun doWork(): Result {
        val dataStore = DataStoreManager(applicationContext)
        val token = dataStore.accessToken.firstOrNull().orEmpty()

        if (token.isBlank()) return Result.success()
        if (dataStore.notificationsEnabled.firstOrNull() == false) return Result.success()

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

        items.asSequence()
            .filter { parseEpochMillis(it.createdAt) > lastCheckpoint }
            .sortedBy { parseEpochMillis(it.createdAt) }
            .forEach { item ->
                DeviceNotificationHelper.showBackendNotification(applicationContext, item)
            }

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

        items.asSequence()
            .filter { !it.isFromTenant && it.timestamp > lastCheckpoint }
            .sortedBy { it.timestamp }
            .forEach { reply ->
                DeviceNotificationHelper.showSupportReply(
                    applicationContext,
                    reply.topic,
                    reply.message,
                    reply.timestamp
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
                    .addHeader("X-Mobile-App-Key", BuildConfig.MOBILE_API_KEY)
                    .addHeader("Accept", "application/json")
                    .build()
                chain.proceed(request)
            }
            .addInterceptor(logging)
            .build()

        val gson = GsonBuilder()
            .setLenient()
            .create()

        return Retrofit.Builder()
            .baseUrl(BuildConfig.BASE_URL)
            .client(client)
            .addConverterFactory(ApiResponseConverterFactory(gson))
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
