package com.tenantpro.app.utils

import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.data.local.OfflineActionProcessor
import com.tenantpro.app.data.repository.InvoiceRepository
import com.tenantpro.app.data.repository.PaymentRepository
import com.tenantpro.app.data.repository.TenantFeatureRepository
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.launch
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class OfflineSyncCoordinator @Inject constructor(
    private val connectivity: NetworkConnectivityObserver,
    private val dataStore: DataStoreManager,
    private val offlineActionProcessor: OfflineActionProcessor,
    private val offlineMediaCache: OfflineMediaCache,
    private val authRepository: AuthRepository,
    private val invoiceRepository: InvoiceRepository,
    private val paymentRepository: PaymentRepository,
    private val tenantFeatureRepository: TenantFeatureRepository
) {
    private val syncMutex = Mutex()

    fun observeConnectivity(scope: CoroutineScope) {
        scope.launch {
            connectivity.isConnected.collect { connected ->
                if (connected) syncNow()
            }
        }
    }

    suspend fun syncNow() = syncMutex.withLock {
        if (dataStore.accessToken.firstOrNull().isNullOrBlank()) return@withLock

        authRepository.syncFcmToken()
        offlineActionProcessor.flush()
        val profile = authRepository.getMyProfile(forceRefresh = true)
        if (profile is Resource.Success) {
            offlineMediaCache.cacheProfileMedia(profile.data)
        }
        invoiceRepository.getInvoices(forceRefresh = true)
        paymentRepository.getPayments(forceRefresh = true)
        tenantFeatureRepository.getNotifications(forceRefresh = true)
        tenantFeatureRepository.getMaintenanceRequests(forceRefresh = true)
        tenantFeatureRepository.getSupportMessages(forceRefresh = true)
    }
}