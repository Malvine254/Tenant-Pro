package com.tenantpro.app.data.api

import com.tenantpro.app.data.local.SafeResponseCache
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.NotificationWorkScheduler
import com.tenantpro.app.utils.SessionManager
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.runBlocking
import okhttp3.Interceptor
import okhttp3.Response
import java.util.concurrent.atomic.AtomicBoolean
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthInterceptor @Inject constructor(
    private val dataStoreManager: DataStoreManager,
    private val sessionManager: SessionManager,
    private val notificationWorkScheduler: NotificationWorkScheduler,
    private val cache: SafeResponseCache
) : Interceptor {
    private val sessionExpiredNotified = AtomicBoolean(false)

    override fun intercept(chain: Interceptor.Chain): Response {
        val token = runBlocking { dataStoreManager.accessToken.firstOrNull() }
        val request = if (token.isNullOrBlank()) {
            chain.request()
        } else {
            chain.request().newBuilder()
                .addHeader("Authorization", "Bearer $token")
                .build()
        }

        val response = chain.proceed(request)

        if (response.code == 401 && !token.isNullOrBlank() && sessionExpiredNotified.compareAndSet(false, true)) {
            notificationWorkScheduler.cancel()
            runBlocking {
                cache.clearCurrentUser()
                dataStoreManager.clearSession()
            }
            sessionManager.notifyExpired()
        } else if (response.code != 401) {
            sessionExpiredNotified.set(false)
        }

        return response
    }
}
