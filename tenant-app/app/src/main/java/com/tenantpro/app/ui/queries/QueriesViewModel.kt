package com.tenantpro.app.ui.queries

import android.content.Context
import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.SupportMessageDto
import com.tenantpro.app.data.repository.TenantFeatureRepository
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.NetworkConnectivityObserver
import com.tenantpro.app.utils.Resource
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import org.json.JSONArray
import org.json.JSONObject
import javax.inject.Inject
import kotlin.random.Random

@HiltViewModel
class QueriesViewModel @Inject constructor(
    private val dataStoreManager: DataStoreManager,
    private val repository: TenantFeatureRepository,
    private val connectivity: NetworkConnectivityObserver,
    @ApplicationContext private val context: Context
) : ViewModel() {

    private val _managerOnline = MutableStateFlow(false)
    val managerOnline: StateFlow<Boolean> = _managerOnline.asStateFlow()
    private val _managerTyping = MutableStateFlow(false)
    val managerTyping: StateFlow<Boolean> = _managerTyping.asStateFlow()
    fun heartbeat() { viewModelScope.launch {
        repository.supportHeartbeat()?.let {
            _managerOnline.value = it["adminOnline"] == true
            _managerTyping.value = it["adminTyping"] == true
        }
    } }
    fun setTyping(typing: Boolean) { viewModelScope.launch { repository.setSupportTyping(typing) } }

    private val _messages = MutableStateFlow<List<QueryChatMessage>>(emptyList())
    val messages: StateFlow<List<QueryChatMessage>> = _messages.asStateFlow()

    private val _selectedTopic = MutableStateFlow("General")
    val selectedTopic: StateFlow<String> = _selectedTopic.asStateFlow()

    val visibleMessages: StateFlow<List<QueryChatMessage>> = combine(_messages, _selectedTopic) { messages, topic ->
        messages.filter { it.topic.equals(topic, ignoreCase = true) }
    }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), emptyList())

    private val _sending = MutableStateFlow(false)
    val sending: StateFlow<Boolean> = _sending.asStateFlow()

    private val _events = MutableSharedFlow<String>()
    val events = _events.asSharedFlow()

    private var pollingJob: Job? = null

    val topics = listOf("General", "Billing", "Maintenance", "Lease", "Security", "Utilities")

    val userInitials: StateFlow<String> = dataStoreManager.userName
        .map { toInitials(it) }
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), "U")

    val userProfileImage: StateFlow<String?> = dataStoreManager.profileImageUri
        .stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), null)

    private fun toInitials(name: String?): String {
        if (name.isNullOrBlank()) return "U"
        val parts = name.trim().split(" ").filter { it.isNotBlank() }
        return if (parts.size >= 2)
            "${parts[0].first().uppercaseChar()}${parts[1].first().uppercaseChar()}"
        else
            parts[0].take(2).uppercase()
    }

    init {
        viewModelScope.launch {
            loadMessages(showCached = true, emitErrors = true)
        }

        viewModelScope.launch {
            connectivity.isConnected.collect { connected ->
                if (connected) {
                    flushPendingQueue()
                }
            }
        }
    }

    fun selectTopic(topic: String) {
        _selectedTopic.value = topic.ifBlank { "General" }
    }

    fun startPolling() {
        if (pollingJob?.isActive == true) return

        pollingJob = viewModelScope.launch {
            while (isActive) {
                loadMessages(showCached = false, emitErrors = false)
                delay(POLLING_INTERVAL_MS)
            }
        }
    }

    fun stopPolling() {
        pollingJob?.cancel()
        pollingJob = null
    }

    private suspend fun loadMessages(showCached: Boolean, emitErrors: Boolean) {
        val cached = parseMessages(dataStoreManager.queryChatHistoryJson.firstOrNull())
        if (showCached && cached.isNotEmpty() && _messages.value != cached) {
            _messages.value = cached
        }

        when (val result = repository.getSupportMessages()) {
            is Resource.Success -> {
                val mapped = result.data.toChatMessages()
                if (_messages.value != mapped) {
                    _messages.value = mapped
                    persist(mapped)
                }
            }
            is Resource.Error -> {
                if (emitErrors && cached.isEmpty()) _events.emit(result.message)
            }
            Resource.Loading -> Unit
        }
    }

    fun sendMessage(topic: String, text: String, attachmentUri: Uri? = null, attachmentName: String? = null) {
        val message = text.trim()
        if (message.isBlank() && attachmentUri == null) {
            viewModelScope.launch { _events.emit("Message cannot be empty") }
            return
        }

        viewModelScope.launch {
            _sending.value = true
            val clientMessageId = java.util.UUID.randomUUID().toString()

            // Upload file first to get a server-side path
            var serverUri: String? = null
            var serverName: String? = attachmentName
            if (attachmentUri != null) {
                when (val upload = repository.uploadSupportFile(attachmentUri, context)) {
                    is Resource.Success -> {
                        serverUri = upload.data.attachmentUri
                        serverName = upload.data.attachmentName
                    }
                    is Resource.Error -> {
                        queueOfflineMessage(
                            QueryChatMessage(
                                id = generateId(),
                                topic = topic,
                                message = message.ifBlank { "Attachment shared" },
                                isFromTenant = true,
                                timestamp = System.currentTimeMillis(),
                                status = "Queued",
                                clientMessageId = clientMessageId,
                                attachmentName = attachmentName,
                                localAttachmentUri = attachmentUri.toString()
                            )
                        )
                        _events.emit("Offline: queued message with attachment. Will resend automatically.")
                        _sending.value = false
                        return@launch
                    }
                    Resource.Loading -> Unit
                }
            }

            val outbound = QueryChatMessage(
                id = generateId(),
                topic = topic,
                message = message.ifBlank { "Attachment shared" },
                isFromTenant = true,
                timestamp = System.currentTimeMillis(),
                status = "Sending",
                clientMessageId = clientMessageId,
                attachmentUri = serverUri,
                attachmentName = serverName,
                localAttachmentUri = attachmentUri?.toString()
            )

            val updated = (_messages.value + outbound).takeLast(300)
            _messages.value = updated
            persist(updated)

            when (val result = repository.sendSupportMessage(topic, message, serverUri, serverName, clientMessageId)) {
                is Resource.Success -> {
                    val mapped = result.data.toChatMessages()
                    _messages.value = mapped
                    persist(mapped)
                    removeQueuedMessage(clientMessageId)
                }
                is Resource.Error -> {
                    val queued = outbound.copy(status = "Queued")
                    queueOfflineMessage(queued)
                    val withReply = (_messages.value.dropLast(1) + queued).takeLast(300)
                    _messages.value = withReply
                    persist(withReply)
                    _events.emit("Offline: message queued. Will resend when internet returns.")
                }
                Resource.Loading -> Unit
            }

            _sending.value = false
        }
    }

    private suspend fun persist(list: List<QueryChatMessage>) {
        dataStoreManager.saveQueryChatHistory(toJson(list))
    }

    private suspend fun flushPendingQueue() {
        val queue = parseMessages(dataStoreManager.pendingSupportQueueJson.firstOrNull()).toMutableList()
        if (queue.isEmpty()) return

        var changed = false
        val iterator = queue.iterator()
        while (iterator.hasNext()) {
            val queued = iterator.next()
            var serverUri = queued.attachmentUri
            var serverName = queued.attachmentName

            if (serverUri.isNullOrBlank() && !queued.localAttachmentUri.isNullOrBlank()) {
                val localUri = Uri.parse(queued.localAttachmentUri)
                when (val upload = repository.uploadSupportFile(localUri, context)) {
                    is Resource.Success -> {
                        serverUri = upload.data.attachmentUri
                        serverName = upload.data.attachmentName
                    }
                    is Resource.Error -> continue
                    Resource.Loading -> continue
                }
            }

            when (repository.sendSupportMessage(
                topic = queued.topic,
                text = queued.message,
                attachmentUri = serverUri,
                attachmentName = serverName,
                clientMessageId = queued.clientMessageId
            )) {
                is Resource.Success -> {
                    iterator.remove()
                    changed = true
                }
                is Resource.Error, Resource.Loading -> continue
            }
        }

        if (changed) {
            dataStoreManager.savePendingSupportQueue(toJson(queue))
            loadMessages(showCached = false, emitErrors = false)
        }
    }

    private suspend fun queueOfflineMessage(message: QueryChatMessage) {
        val existingQueue = parseMessages(dataStoreManager.pendingSupportQueueJson.firstOrNull()).toMutableList()
        existingQueue.removeAll { it.clientMessageId != null && it.clientMessageId == message.clientMessageId }
        existingQueue.add(message)
        dataStoreManager.savePendingSupportQueue(toJson(existingQueue.takeLast(100)))
    }

    private suspend fun removeQueuedMessage(clientMessageId: String?) {
        if (clientMessageId.isNullOrBlank()) return
        val existingQueue = parseMessages(dataStoreManager.pendingSupportQueueJson.firstOrNull()).toMutableList()
        if (existingQueue.removeAll { it.clientMessageId == clientMessageId }) {
            dataStoreManager.savePendingSupportQueue(toJson(existingQueue))
        }
    }

    private fun parseMessages(json: String?): List<QueryChatMessage> {
        if (json.isNullOrBlank()) return emptyList()
        return try {
            val arr = JSONArray(json)
            buildList {
                for (index in 0 until arr.length()) {
                    val obj = arr.getJSONObject(index)
                    add(
                        QueryChatMessage(
                            id = obj.optString("id"),
                            topic = obj.optString("topic", "General"),
                            message = obj.optString("message"),
                            isFromTenant = obj.optBoolean("isFromTenant", true),
                            timestamp = obj.optLong("timestamp", 0L),
                            status = obj.optString("status", "Sent"),
                            clientMessageId = obj.optString("clientMessageId", "").ifBlank { null },
                            attachmentUri = obj.optString("attachmentUri", "").ifBlank { null },
                            attachmentName = obj.optString("attachmentName", "").ifBlank { null },
                            localAttachmentUri = obj.optString("localAttachmentUri", "").ifBlank { null }
                        )
                    )
                }
            }
        } catch (_: Exception) {
            emptyList()
        }
    }

    private fun toJson(list: List<QueryChatMessage>): String {
        val arr = JSONArray()
        list.forEach { item ->
            arr.put(
                JSONObject().apply {
                    put("id", item.id)
                    put("topic", item.topic)
                    put("message", item.message)
                    put("isFromTenant", item.isFromTenant)
                    put("timestamp", item.timestamp)
                    put("status", item.status)
                    item.clientMessageId?.let { put("clientMessageId", it) }
                    item.attachmentUri?.let { put("attachmentUri", it) }
                    item.attachmentName?.let { put("attachmentName", it) }
                    item.localAttachmentUri?.let { put("localAttachmentUri", it) }
                }
            )
        }
        return arr.toString()
    }

    private fun List<SupportMessageDto>.toChatMessages(): List<QueryChatMessage> = map {
        QueryChatMessage(
            id = it.id,
            topic = it.topic,
            message = it.message,
            isFromTenant = it.isFromTenant,
            timestamp = it.timestamp,
            status = it.status,
            clientMessageId = null,
            attachmentUri = it.attachmentUri,
            attachmentName = it.attachmentName
        )
    }

    private fun generateId(): String = "m_${System.currentTimeMillis()}_${Random.nextInt(1000, 9999)}"

    override fun onCleared() {
        stopPolling()
        super.onCleared()
    }

    companion object {
        private const val POLLING_INTERVAL_MS = 3_000L
    }
}
