package com.tenantpro.app.data.api

import com.google.gson.Gson
import com.google.gson.JsonElement
import com.google.gson.JsonObject
import com.google.gson.JsonParser
import okhttp3.ResponseBody
import okhttp3.ResponseBody.Companion.toResponseBody
import retrofit2.Converter
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.lang.reflect.ParameterizedType
import java.lang.reflect.Type
import javax.inject.Inject
import javax.inject.Singleton

/**
 * Accepts both legacy direct payloads and the backend envelope:
 * { success: true, message: "...", data: ... }.
 */
@Singleton
class ApiResponseConverterFactory @Inject constructor(
    private val gson: Gson
) : Converter.Factory() {
    private val delegateFactory = GsonConverterFactory.create(gson)

    override fun responseBodyConverter(
        type: Type,
        annotations: Array<Annotation>,
        retrofit: Retrofit
    ): Converter<ResponseBody, *> {
        val delegate = delegateFactory.responseBodyConverter(type, annotations, retrofit)
        return Converter<ResponseBody, Any> { body ->
            val raw = body.string()
            val normalized = normalizeEnvelope(raw, type)
            val normalizedBody = normalized.toResponseBody(body.contentType())
            @Suppress("UNCHECKED_CAST")
            (delegate as Converter<ResponseBody, Any>).convert(normalizedBody)
        }
    }

    override fun requestBodyConverter(
        type: Type,
        parameterAnnotations: Array<Annotation>,
        methodAnnotations: Array<Annotation>,
        retrofit: Retrofit
    ): Converter<*, okhttp3.RequestBody>? {
        return delegateFactory.requestBodyConverter(
            type,
            parameterAnnotations,
            methodAnnotations,
            retrofit
        )
    }

    private fun normalizeEnvelope(raw: String, targetType: Type): String {
        val parsed = runCatching { JsonParser.parseString(raw) }.getOrNull() ?: return raw
        if (!parsed.isJsonObject) return raw

        val obj = parsed.asJsonObject
        val hasEnvelope = obj.has("success") && (obj.has("data") || obj.has("message"))
        if (obj.get("success")?.asBoolean == false) {
            throw IllegalStateException(obj.get("message")?.asString ?: "Something went wrong. Please try again.")
        }
        if (!hasEnvelope || obj.get("success")?.asBoolean != true) return raw

        val data = obj.get("data")
        return when {
            data == null || data.isJsonNull -> copyMessageOnly(obj).toString()
            data.isJsonObject && isListType(targetType) -> unwrapNestedArray(data.asJsonObject).toString()
            data.isJsonObject -> mergeEnvelopeFields(data.asJsonObject, obj).toString()
            else -> data.toString()
        }
    }

    private fun isListType(type: Type): Boolean =
        type is ParameterizedType && type.rawType == List::class.java

    private fun unwrapNestedArray(data: JsonObject): JsonElement {
        val nestedArray = listOf("items", "results", "records", "invoices", "payments", "notifications", "messages", "requests")
            .firstNotNullOfOrNull { key -> data.get(key)?.takeIf { it.isJsonArray } }
        return nestedArray ?: data
    }

    private fun copyMessageOnly(envelope: JsonObject): JsonObject = JsonObject().apply {
        copyIfPresent(envelope, this, "message")
        copyIfPresent(envelope, this, "code")
        copyIfPresent(envelope, this, "statusCode")
    }

    private fun mergeEnvelopeFields(data: JsonObject, envelope: JsonObject): JsonObject {
        val merged = data.deepCopy()
        copyIfMissing(envelope, merged, "message")
        copyIfMissing(envelope, merged, "code")
        copyIfMissing(envelope, merged, "statusCode")
        return merged
    }

    private fun copyIfMissing(source: JsonObject, target: JsonObject, key: String) {
        if (!target.has(key)) copyIfPresent(source, target, key)
    }

    private fun copyIfPresent(source: JsonObject, target: JsonObject, key: String) {
        source.get(key)?.let { value: JsonElement -> target.add(key, value) }
    }
}
