package com.tenantpro.app.utils

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.net.Uri
import android.provider.OpenableColumns
import java.io.ByteArrayOutputStream

object UploadPayloadResolver {
    data class Payload(
        val fileName: String,
        val mimeType: String,
        val bytes: ByteArray,
        val originalBytes: Int,
    )

    fun fromUri(
        context: Context,
        uri: Uri,
        fallbackName: String,
        imageResizeThresholdBytes: Int = 350 * 1024,
        maxImageDimension: Int = 1600,
        jpegQuality: Int = 82,
    ): Payload? {
        val resolver = context.contentResolver
        val originalMime = resolver.getType(uri)?.lowercase() ?: "application/octet-stream"
        val originalBytes = resolver.openInputStream(uri)?.use { it.readBytes() } ?: return null
        val originalName = resolveFileName(context, uri, fallbackName)

        if (!originalMime.startsWith("image/") || originalBytes.size <= imageResizeThresholdBytes) {
            return Payload(
                fileName = originalName,
                mimeType = originalMime,
                bytes = originalBytes,
                originalBytes = originalBytes.size,
            )
        }

        val compressed = compressImageJpeg(originalBytes, maxImageDimension, jpegQuality)
            ?: return Payload(
                fileName = originalName,
                mimeType = originalMime,
                bytes = originalBytes,
                originalBytes = originalBytes.size,
            )

        return if (compressed.size < originalBytes.size) {
            val nameWithoutExt = originalName.substringBeforeLast('.', originalName)
            Payload(
                fileName = "$nameWithoutExt.jpg",
                mimeType = "image/jpeg",
                bytes = compressed,
                originalBytes = originalBytes.size,
            )
        } else {
            Payload(
                fileName = originalName,
                mimeType = originalMime,
                bytes = originalBytes,
                originalBytes = originalBytes.size,
            )
        }
    }

    private fun compressImageJpeg(
        imageBytes: ByteArray,
        maxImageDimension: Int,
        jpegQuality: Int,
    ): ByteArray? {
        val bounds = BitmapFactory.Options().apply { inJustDecodeBounds = true }
        BitmapFactory.decodeByteArray(imageBytes, 0, imageBytes.size, bounds)

        val sampleSize = calculateSampleSize(bounds.outWidth, bounds.outHeight, maxImageDimension)
        val decodeOptions = BitmapFactory.Options().apply {
            inSampleSize = sampleSize
            inPreferredConfig = Bitmap.Config.ARGB_8888
        }

        val bitmap = BitmapFactory.decodeByteArray(imageBytes, 0, imageBytes.size, decodeOptions) ?: return null
        val output = ByteArrayOutputStream()
        bitmap.compress(Bitmap.CompressFormat.JPEG, jpegQuality, output)
        bitmap.recycle()

        return output.toByteArray()
    }

    private fun calculateSampleSize(srcW: Int, srcH: Int, maxDimension: Int): Int {
        if (srcW <= 0 || srcH <= 0) return 1
        var sample = 1
        while (srcW / sample > maxDimension || srcH / sample > maxDimension) {
            sample *= 2
        }
        return sample.coerceAtLeast(1)
    }

    private fun resolveFileName(context: Context, uri: Uri, fallbackName: String): String {
        var name = fallbackName
        context.contentResolver.query(uri, null, null, null, null)?.use { cursor ->
            val idx = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME)
            if (idx >= 0 && cursor.moveToFirst()) {
                name = cursor.getString(idx) ?: fallbackName
            }
        }
        return name
    }
}
