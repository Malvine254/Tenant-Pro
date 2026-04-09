package com.tenantpro.app.ui.notifications

import android.content.Context
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.NotificationItem
import com.tenantpro.app.data.repository.TenantFeatureRepository
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.DeviceNotificationHelper
import com.tenantpro.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import java.time.Instant
import javax.inject.Inject

@HiltViewModel
class NotificationsViewModel @Inject constructor(
    private val repository: TenantFeatureRepository,
    private val dataStoreManager: DataStoreManager,
    @ApplicationContext private val context: Context
) : ViewModel() {

    private val _items = MutableStateFlow<List<NotificationItem>>(emptyList())
    val items = _items.asStateFlow()

    private val _loading = MutableStateFlow(false)
    val loading = _loading.asStateFlow()

    private val _events = MutableSharedFlow<String>()
    val events = _events.asSharedFlow()

    private var pollingJob: Job? = null

    init {
        loadNotifications(emitErrors = true, notifyDevice = false)
    }

    fun startPolling() {
        if (pollingJob?.isActive == true) return
        pollingJob = viewModelScope.launch {
            while (isActive) {
                loadNotifications(emitErrors = false, notifyDevice = true)
                delay(POLLING_INTERVAL_MS)
            }
        }
    }

    fun stopPolling() {
        pollingJob?.cancel()
        pollingJob = null
    }

    fun loadNotifications(emitErrors: Boolean = true, notifyDevice: Boolean = true) {
        viewModelScope.launch {
            _loading.value = true
            when (val result = repository.getNotifications()) {
                is Resource.Success -> {
                    handleDeviceAlerts(result.data, notifyDevice)
                    _items.value = result.data
                }
                is Resource.Error -> if (emitErrors) _events.emit(result.message)
                Resource.Loading -> Unit
            }
            _loading.value = false
        }
    }

    fun markAllRead() {
        viewModelScope.launch {
            when (val result = repository.markAllNotificationsRead()) {
                is Resource.Success -> {
                    _events.emit(result.data)
                    loadNotifications()
                }
                is Resource.Error -> _events.emit(result.message)
                Resource.Loading -> Unit
            }
        }
    }

    private suspend fun handleDeviceAlerts(items: List<NotificationItem>, notifyDevice: Boolean) {
        val latestTimestamp = items.maxOfOrNull { parseEpochMillis(it.createdAt) } ?: 0L
        val checkpoint = dataStoreManager.lastNotificationCheckpoint.firstOrNull()?.toLongOrNull() ?: 0L

        if (checkpoint == 0L) {
            if (latestTimestamp > 0L) dataStoreManager.saveLastNotificationCheckpoint(latestTimestamp)
            return
        }

        if (notifyDevice) {
            items.filter { !it.isRead && parseEpochMillis(it.createdAt) > checkpoint }
                .sortedBy { parseEpochMillis(it.createdAt) }
                .forEach { DeviceNotificationHelper.showBackendNotification(context, it) }
        }

        if (latestTimestamp > checkpoint) {
            dataStoreManager.saveLastNotificationCheckpoint(latestTimestamp)
        }
    }

    private fun parseEpochMillis(value: String?): Long {
        if (value.isNullOrBlank()) return 0L
        return try {
            Instant.parse(value).toEpochMilli()
        } catch (_: Exception) {
            0L
        }
    }

    override fun onCleared() {
        stopPolling()
        super.onCleared()
    }

    companion object {
        private const val POLLING_INTERVAL_MS = 15_000L
    }
}
