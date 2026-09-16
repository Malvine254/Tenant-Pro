package com.tenantpro.app.utils

import android.app.Activity
import android.content.Context
import android.content.Intent
import android.net.Uri
import androidx.core.content.FileProvider
import com.tenantpro.app.BuildConfig
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.withContext
import okhttp3.OkHttpClient
import okhttp3.Request
import java.io.File
import java.io.FileOutputStream
import java.io.IOException
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class PdfDownloadHelper @Inject constructor(
    private val okHttpClient: OkHttpClient,
    private val dataStoreManager: DataStoreManager,
    @ApplicationContext private val context: Context
) {

    suspend fun downloadAndOpenPdf(
        activity: Activity,
        endpointPath: String,
        fileName: String,
        onProgress: ((Boolean) -> Unit)? = null
    ) = withContext(Dispatchers.IO) {
        val documentsDir = File(context.filesDir, "documents").apply { mkdirs() }
        val targetFile = File(documentsDir, fileName)
        val temporaryFile = File(documentsDir, "$fileName.download")
        try {
            withContext(Dispatchers.Main) { onProgress?.invoke(true) }

            val apiBase = BuildConfig.BASE_URL.trimEnd('/')
            val fullUrl = if (endpointPath.startsWith("http://") || endpointPath.startsWith("https://")) {
                endpointPath
            } else {
                "$apiBase/${endpointPath.trimStart('/')}"
            }

            val token = dataStoreManager.accessToken.firstOrNull()
            val requestBuilder = Request.Builder().url(fullUrl)
            if (!token.isNullOrBlank()) {
                requestBuilder.header("Authorization", "Bearer $token")
            }
            if (BuildConfig.MOBILE_API_KEY.isNotBlank()) {
                requestBuilder.header("X-Mobile-App-Key", BuildConfig.MOBILE_API_KEY)
            }

            val response = okHttpClient.newCall(requestBuilder.build()).execute()
            if (!response.isSuccessful) {
                throw IOException("Server returned HTTP ${response.code}")
            }

            val body = response.body ?: throw IOException("Empty document response")

            body.byteStream().use { input ->
                FileOutputStream(temporaryFile).use { output ->
                    input.copyTo(output)
                }
            }
            if (!temporaryFile.renameTo(targetFile)) {
                temporaryFile.copyTo(targetFile, overwrite = true)
                temporaryFile.delete()
            }

            withContext(Dispatchers.Main) {
                onProgress?.invoke(false)
                openPdfFile(activity, targetFile)
            }
        } catch (e: Exception) {
            temporaryFile.delete()
            withContext(Dispatchers.Main) {
                onProgress?.invoke(false)
                if (targetFile.exists() && targetFile.length() > 0L) {
                    activity.toast("Opening the saved offline copy")
                    openPdfFile(activity, targetFile)
                } else {
                    activity.toast("Could not download PDF: ${e.localizedMessage ?: "Network error"}")
                }
            }
        }
    }

    private fun openPdfFile(activity: Activity, file: File) {
        try {
            val authority = "${activity.packageName}.provider"
            val uri: Uri = FileProvider.getUriForFile(activity, authority, file)

            val viewIntent = Intent(Intent.ACTION_VIEW).apply {
                setDataAndType(uri, "application/pdf")
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            }

            val chooser = Intent.createChooser(viewIntent, "Open PDF Statement")
            activity.startActivity(chooser)
        } catch (e: Exception) {
            activity.toast("No PDF viewer app found on device.")
        }
    }
}
