package com.tenantpro.app.ui.queries

import android.content.Context
import android.net.Uri
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.SupportMessageDto
import com.tenantpro.app.data.repository.TenantFeatureRepository
import com.tenantpro.app.utils.DataStoreManager
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
    @ApplicationContext private val context: Context
) : ViewModel() {

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

    init {
        viewModelScope.launch {
            loadMessages(showCached = true, emitErrors = true)
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
                        _events.emit("Upload failed: ${upload.message}")
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
                attachmentUri = serverUri,
                attachmentName = serverName
            )

            val updated = (_messages.value + outbound).takeLast(300)
            _messages.value = updated
            persist(updated)

            when (val result = repository.sendSupportMessage(topic, message, serverUri, serverName)) {
                is Resource.Success -> {
                    val mapped = result.data.toChatMessages()
                    _messages.value = mapped
                    persist(mapped)
                }
                is Resource.Error -> {
                    delay(400)
                    val fallbackReply = QueryChatMessage(
                        id = generateId(),
                        topic = topic,
                        message = supportReply(topic),
                        isFromTenant = false,
                        timestamp = System.currentTimeMillis(),
                        status = "Queued offline"
                    )
                    val withReply = (_messages.value.dropLast(1) + outbound.copy(status = "Queued") + fallbackReply).takeLast(300)
                    _messages.value = withReply
                    persist(withReply)
                    _events.emit(result.message)
                }
                Resource.Loading -> Unit
            }

            _sending.value = false
        }
    }

    private suspend fun persist(list: List<QueryChatMessage>) {
        dataStoreManager.saveQueryChatHistory(toJson(list))
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
                            attachmentUri = obj.optString("attachmentUri", "").ifBlank { null },
                            attachmentName = obj.optString("attachmentName", "").ifBlank { null }
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
                    item.attachmentUri?.let { put("attachmentUri", it) }
                    item.attachmentName?.let { put("attachmentName", it) }
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
            attachmentUri = it.attachmentUri,
            attachmentName = it.attachmentName
        )
    }

    private fun supportReply(topic: String): String {
        return when (topic) {
            "Maintenance" -> "Thanks for raising this. Maintenance has been notified and will update you shortly."
            "Billing" -> "We received your billing request. We are reviewing the invoice details and will respond soon."
            "Lease" -> "Your lease question is logged. Property management will share guidance in this thread."
            "Security" -> "Security concern noted. The team has been alerted and will follow up quickly."
            "Utilities" -> "Utilities request received. We are checking meter and service records now."
            else -> "Thanks for your message. Support has received it and will reply here shortly."
        }
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