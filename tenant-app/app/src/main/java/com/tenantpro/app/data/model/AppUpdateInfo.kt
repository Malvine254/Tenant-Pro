package com.tenantpro.app.data.model

import com.google.gson.annotations.SerializedName
import java.io.Serializable
import java.util.Locale

data class AppUpdateInfo(
    @SerializedName("available") val available: Boolean = false,
    @SerializedName("version_name") val versionName: String? = null,
    @SerializedName("version_code") val versionCode: Int = 0,
    @SerializedName("channel") val channel: String? = null,
    @SerializedName("release_notes") val releaseNotes: String? = null,
    @SerializedName("is_mandatory") val isMandatory: Boolean = false,
    @SerializedName("file_size") val fileSize: Long = 0L,
    @SerializedName("checksum") val checksum: String? = null,
    @SerializedName("download_url") val downloadUrl: String? = null,
    @SerializedName("released_at") val releasedAt: String? = null
) : Serializable {

    fun isNewerThan(currentVersionCode: Int): Boolean {
        return available && versionCode > currentVersionCode && !downloadUrl.isNullOrBlank()
    }

    val displayVersion: String
        get() = versionName?.takeIf { it.isNotBlank() } ?: (if (versionCode > 0) "v$versionCode" else "Latest")

    val formattedSize: String
        get() {
            if (fileSize <= 0L) return ""
            val mb = fileSize.toDouble() / (1024.0 * 1024.0)
            return String.format(Locale.US, "%.1f MB", mb)
        }
}
