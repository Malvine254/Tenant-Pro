package com.tenantpro.app.data.api

import org.json.JSONArray
import org.json.JSONObject
import retrofit2.Response
import java.io.IOException
import java.net.SocketTimeoutException
import java.net.UnknownHostException

object ApiErrorMapper {
    fun fromResponse(response: Response<*>): String {
        if (response.code() == 401) return "Your session has expired. Please log in again."

        val raw = runCatching { response.errorBody()?.string().orEmpty() }.getOrDefault("")
        val backendMessage = parseBackendMessage(raw)
        if (!backendMessage.isNullOrBlank()) return friendly(backendMessage, response.code())

        return when (response.code()) {
            400 -> "Please check the details and try again."
            403 -> "You do not have permission to perform this action."
            404 -> "We could not find that record."
            408 -> "The request timed out. Please try again."
            in 500..599 -> "The service is temporarily unavailable. Please try again shortly."
            else -> "Something went wrong. Please try again."
        }
    }

    fun fromThrowable(error: Throwable): String = when (error) {
        is UnknownHostException -> "Cannot reach the server. Check internet or local backend host settings."
        is SocketTimeoutException -> "Server timeout. Ensure your backend is running and reachable from the app."
        is IOException -> "Cannot connect to the server. Check internet or local backend availability."
        else -> error.message?.takeIf { it.isNotBlank() } ?: "Something went wrong. Please try again."
    }

    private fun parseBackendMessage(raw: String): String? {
        if (raw.isBlank()) return null
        return runCatching {
            if (raw.trimStart().startsWith("[")) {
                val array = JSONArray(raw)
                return@runCatching (0 until array.length())
                    .mapNotNull { index -> array.optString(index).takeIf { it.isNotBlank() } }
                    .joinToString(", ")
            }

            val obj = JSONObject(raw)
            when (val message = obj.opt("message")) {
                is JSONArray -> (0 until message.length()).joinToString(", ") { index ->
                    message.optString(index)
                }
                is String -> message
                else -> obj.optString("error").takeIf { it.isNotBlank() }
            }
        }.getOrNull()
    }

    private fun friendly(message: String, code: Int): String {
        val normalized = message.lowercase()
        return when {
            "invalid" in normalized && "credential" in normalized -> "Email or password is incorrect."
            "password" in normalized && "incorrect" in normalized -> "Email or password is incorrect."
            "not verified" in normalized || "verify" in normalized -> "Please verify your email before continuing."
            "otp" in normalized && "expired" in normalized -> "The OTP has expired. Please request a new one."
            "otp" in normalized && "invalid" in normalized -> "The OTP is invalid. Please check it and try again."
            "invitation" in normalized && "expired" in normalized -> "This invitation code has expired."
            "invitation" in normalized && "invalid" in normalized -> "This invitation code is invalid."
            "already" in normalized && "linked" in normalized -> "Your account is already linked to a rental unit."
            code >= 500 -> "The service is temporarily unavailable. Please try again shortly."
            else -> message
        }
    }
}
