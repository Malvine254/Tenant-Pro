package com.tenantpro.app.utils

import android.app.Activity
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Build
import android.provider.Settings
import androidx.core.content.FileProvider
import androidx.fragment.app.FragmentActivity
import com.tenantpro.app.BuildConfig
import com.tenantpro.app.R
import com.tenantpro.app.data.api.ApiService
import com.tenantpro.app.data.model.AppUpdateInfo
import com.tenantpro.app.data.model.NotificationItem
import com.tenantpro.app.ui.update.AppUpdateDialogFragment
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import okhttp3.OkHttpClient
import okhttp3.Request
import java.io.File
import java.io.FileOutputStream
import java.io.IOException
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AppUpdateManager @Inject constructor(
    private val apiService: ApiService,
    private val okHttpClient: OkHttpClient,
    @ApplicationContext private val context: Context
) {

    private var pendingApkFile: File? = null
    private var lastAutomaticCheckTime: Long = 0L

    companion object {
        private const val AUTOMATIC_CHECK_COOLDOWN_MS = 60 * 1000L // 1 minute cooldown for auto-check
    }

    /**
     * Checks the backend for published releases and compares with installed versionCode.
     */
    suspend fun checkForUpdates(): Result<AppUpdateInfo?> = withContext(Dispatchers.IO) {
        runCatching {
            val response = apiService.getLatestVersion()
            if (response.isSuccessful) {
                val body = response.body()
                if (body != null && body.available && isUpdateAvailable(body)) {
                    body
                } else {
                    null
                }
            } else {
                null
            }
        }
    }

    fun isUpdateAvailable(updateInfo: AppUpdateInfo): Boolean {
        return updateInfo.isNewerThan(BuildConfig.VERSION_CODE)
    }

    /**
     * Checks backend and displays in-app update modal if newer version is found.
     * When isAutomatic = false (manual check from settings), shows feedback if up to date or on error.
     */
    fun checkAndPromptUpdate(
        activity: FragmentActivity,
        isAutomatic: Boolean = true,
        onComplete: ((Boolean) -> Unit)? = null
    ) {
        val now = System.currentTimeMillis()
        if (isAutomatic && (now - lastAutomaticCheckTime) < AUTOMATIC_CHECK_COOLDOWN_MS) {
            onComplete?.invoke(false)
            return
        }
        lastAutomaticCheckTime = now

        if (!isAutomatic) {
            activity.toast(activity.getString(R.string.app_update_checking))
        }

        CoroutineScope(Dispatchers.Main).launch {
            val result = checkForUpdates()
            result.onSuccess { updateInfo ->
                if (updateInfo != null && !activity.isFinishing && !activity.isDestroyed) {
                    showUpdateDialog(activity, updateInfo)
                    onComplete?.invoke(true)
                } else {
                    if (!isAutomatic && !activity.isFinishing) {
                        activity.toast(
                            activity.getString(
                                R.string.app_update_latest_version,
                                "v${BuildConfig.VERSION_NAME} (${BuildConfig.VERSION_CODE})"
                            )
                        )
                    }
                    onComplete?.invoke(false)
                }
            }.onFailure {
                if (!isAutomatic && !activity.isFinishing) {
                    activity.toast(activity.getString(R.string.app_update_check_error))
                }
                onComplete?.invoke(false)
            }
        }
    }

    fun showUpdateDialog(activity: FragmentActivity, updateInfo: AppUpdateInfo) {
        if (activity.isFinishing || activity.isDestroyed) return
        AppUpdateDialogFragment.show(activity.supportFragmentManager, updateInfo)
    }

    /**
     * Shows update modal from a push notification item.
     * Skips the dialog when the notified version is not newer than the installed build,
     * since the user may have already updated by the time they open the notification.
     */
    fun showUpdateFromNotification(activity: FragmentActivity, item: NotificationItem) {
        val metadataVersionCode = when (val rawVersionCode = item.metadata["version_code"]) {
            is Number -> rawVersionCode.toInt()
            is String -> rawVersionCode.toIntOrNull()
            else -> null
        }

        if (metadataVersionCode == null) {
            checkAndPromptUpdate(activity, isAutomatic = false)
            return
        }

        if (metadataVersionCode <= BuildConfig.VERSION_CODE) {
            activity.toast(
                activity.getString(
                    R.string.app_update_latest_version,
                    "v${BuildConfig.VERSION_NAME} (${BuildConfig.VERSION_CODE})"
                )
            )
            return
        }

        val downloadUrl = item.metadata["download_url"]?.toString()?.takeIf { it.isNotBlank() }
            ?: (BuildConfig.BASE_URL.trimEnd('/').removeSuffix("/api") + "/download/apk")
        val versionName = item.metadata["version_name"]?.toString()?.takeIf { it.isNotBlank() } ?: "Latest"
        val isMandatory = (item.metadata["is_mandatory"] as? Boolean) ?: false
        val releaseNotes = item.metadata["release_notes"]?.toString() ?: item.message

        val updateInfo = AppUpdateInfo(
            available = true,
            versionName = versionName,
            versionCode = metadataVersionCode,
            releaseNotes = releaseNotes,
            isMandatory = isMandatory,
            downloadUrl = downloadUrl
        )

        showUpdateDialog(activity, updateInfo)
    }


    /**
     * Downloads APK bytes from the update URL into the app's cache directory.
     */
    suspend fun downloadApk(
        updateInfo: AppUpdateInfo,
        onProgress: (percent: Int, downloadedBytes: Long, totalBytes: Long) -> Unit
    ): File = withContext(Dispatchers.IO) {
        val rawUrl = updateInfo.downloadUrl ?: throw IOException("Missing download URL")
        val finalUrl = rawUrl.toAbsoluteAssetUrl()

        val request = Request.Builder()
            .url(finalUrl)
            .header("Accept", "application/vnd.android.package-archive, */*")
            .build()

        val response = okHttpClient.newCall(request).execute()
        if (!response.isSuccessful) {
            throw IOException("Download failed with HTTP ${response.code}: ${response.message}")
        }

        val body = response.body ?: throw IOException("Empty response body from download server")
        val expectedLength = if (updateInfo.fileSize > 0L) updateInfo.fileSize else body.contentLength()

        val updatesDir = File(context.cacheDir, "updates").apply {
            if (!exists()) mkdirs()
        }

        // Clean any older leftover apk builds
        updatesDir.listFiles()?.forEach { file ->
            if (file.extension.equals("apk", ignoreCase = true)) {
                file.delete()
            }
        }

        val targetFile = File(updatesDir, "starmax-update-v${updateInfo.versionCode}.apk")
        var downloadedBytes = 0L

        body.byteStream().use { inputStream ->
            FileOutputStream(targetFile).use { outputStream ->
                val buffer = ByteArray(16 * 1024)
                var bytesRead: Int
                while (inputStream.read(buffer).also { bytesRead = it } != -1) {
                    outputStream.write(buffer, 0, bytesRead)
                    downloadedBytes += bytesRead
                    val percent = if (expectedLength > 0L) {
                        ((downloadedBytes * 100L) / expectedLength).toInt().coerceIn(0, 100)
                    } else {
                        0
                    }
                    onProgress(percent, downloadedBytes, expectedLength)
                }
                outputStream.flush()
            }
        }

        if (!targetFile.exists() || targetFile.length() <= 0L) {
            throw IOException("Downloaded APK file is empty or missing")
        }

        targetFile
    }

    /**
     * Launches Android's Package Installer to install the downloaded APK.
     * Checks and requests Unknown App Sources permission on Android 8.0+ if needed.
     */
    fun installApk(activity: Activity, apkFile: File) {
        if (!apkFile.exists() || apkFile.length() <= 0L) {
            activity.toast("Update package is missing. Please download again.")
            return
        }

        pendingApkFile = apkFile

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val canInstall = activity.packageManager.canRequestPackageInstalls()
            if (!canInstall) {
                activity.toast(activity.getString(R.string.app_update_permission_prompt))
                val manageSourcesIntent = Intent(Settings.ACTION_MANAGE_UNKNOWN_APP_SOURCES).apply {
                    data = Uri.parse("package:${activity.packageName}")
                    addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                }
                activity.startActivity(manageSourcesIntent)
                return
            }
        }

        launchPackageInstaller(activity, apkFile)
    }

    private fun launchPackageInstaller(activity: Activity, apkFile: File) {
        try {
            val authority = "${activity.packageName}.provider"
            val apkUri: Uri = FileProvider.getUriForFile(activity, authority, apkFile)

            val installIntent = Intent(Intent.ACTION_VIEW).apply {
                setDataAndType(apkUri, "application/vnd.android.package-archive")
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP)
            }

            pendingApkFile = null
            activity.startActivity(installIntent)
        } catch (e: Exception) {
            activity.toast("Could not open package installer: ${e.localizedMessage}")
        }
    }

    /**
     * Resumes APK install if user enabled unknown sources permission and returned to the app.
     */
    fun resumePendingInstallIfAllowed(activity: Activity) {
        val file = pendingApkFile ?: return
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            if (activity.packageManager.canRequestPackageInstalls() && file.exists()) {
                launchPackageInstaller(activity, file)
            }
        } else if (file.exists()) {
            launchPackageInstaller(activity, file)
        }
    }

    fun hasPendingInstall(): Boolean = pendingApkFile?.exists() == true

    /**
     * Browser download fallback.
     */
    fun openBrowserDownload(context: Context, downloadUrl: String) {
        try {
            val resolvedUrl = downloadUrl.toAbsoluteAssetUrl()
            val browserIntent = Intent(Intent.ACTION_VIEW, Uri.parse(resolvedUrl)).apply {
                addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            }
            context.startActivity(browserIntent)
        } catch (e: Exception) {
            context.toast(context.getString(R.string.app_update_open_failed))
        }
    }
}
