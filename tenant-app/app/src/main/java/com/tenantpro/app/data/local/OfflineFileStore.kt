package com.tenantpro.app.data.local

import android.content.Context
import com.tenantpro.app.data.model.SupportMessageRequest
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import java.io.File
import java.util.UUID
import javax.inject.Inject
import javax.inject.Singleton

/** A file copied into app-private storage so a queued upload survives restarts and revoked content:// grants. */
data class QueuedFile(
    val path: String = "",
    val fileName: String = "",
    val mimeType: String = "application/octet-stream"
)

data class QueuedSupportAttachment(
    val file: QueuedFile = QueuedFile(),
    val request: SupportMessageRequest = SupportMessageRequest(topic = "General", text = "")
)

@Singleton
class OfflineFileStore @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val root: File get() = File(context.filesDir, ROOT_DIR)

    suspend fun save(category: String, bytes: ByteArray, fileName: String, mimeType: String): QueuedFile =
        withContext(Dispatchers.IO) {
            val dir = File(root, category).apply { mkdirs() }
            val file = File(dir, UUID.randomUUID().toString())
            file.writeBytes(bytes)
            QueuedFile(path = file.absolutePath, fileName = fileName, mimeType = mimeType)
        }

    suspend fun clear(category: String) = withContext(Dispatchers.IO) {
        File(root, category).deleteRecursively()
    }

    suspend fun delete(path: String) = withContext(Dispatchers.IO) {
        fileInsideRoot(path)?.delete()
    }

    /** Returns null when the queued file no longer exists, so the action can be dropped. */
    fun toPart(queued: QueuedFile, fieldName: String = "file"): MultipartBody.Part? {
        val file = fileInsideRoot(queued.path)?.takeIf { it.exists() } ?: return null
        return MultipartBody.Part.createFormData(
            fieldName,
            queued.fileName,
            file.asRequestBody(queued.mimeType.toMediaTypeOrNull())
        )
    }

    // Payload paths are decrypted from the queue, but never follow them outside our own directory.
    private fun fileInsideRoot(path: String): File? {
        val file = File(path).canonicalFile
        return file.takeIf { it.path.startsWith(root.canonicalPath + File.separator) }
    }

    companion object {
        private const val ROOT_DIR = "offline-uploads"
        const val PROFILE_IMAGE_DIR = "profile-image"
        const val SUPPORT_ATTACHMENT_DIR = "support-attachments"
    }
}
