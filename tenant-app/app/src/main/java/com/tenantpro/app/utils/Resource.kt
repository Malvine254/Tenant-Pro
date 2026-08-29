package com.tenantpro.app.utils

/**
 * Generic sealed class wrapping async operation states.
 *
 * [Success.fromCache] is true when data was served from the local Room cache.
 * Callers can use it to disclose that a screen may not yet reflect server changes.
 */
sealed class Resource<out T> {
    data class Success<T>(val data: T, val fromCache: Boolean = false) : Resource<T>()
    data class Error(val message: String) : Resource<Nothing>()
    object Loading : Resource<Nothing>()
}
