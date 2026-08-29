package com.tenantpro.app.utils

import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.asSharedFlow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SessionManager @Inject constructor() {
    private val _sessionExpired = MutableSharedFlow<String>(extraBufferCapacity = 1)
    val sessionExpired = _sessionExpired.asSharedFlow()

    private val _accessRestricted = MutableSharedFlow<String>(extraBufferCapacity = 1)
    val accessRestricted = _accessRestricted.asSharedFlow()

    fun notifyExpired(message: String = "Your session has expired. Please sign in again.") {
        _sessionExpired.tryEmit(message)
    }

    fun notifyAccessRestricted(message: String) {
        _accessRestricted.tryEmit(message)
    }
}
