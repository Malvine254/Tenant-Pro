package com.tenantpro.app.ui.maintenance

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.MaintenanceRequestItem
import com.tenantpro.app.data.repository.TenantFeatureRepository
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.toDisplayDate
import com.tenantpro.app.utils.toStatusLabel
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class MaintenanceViewModel @Inject constructor(
    private val repository: TenantFeatureRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(MaintenanceUiState())
    val uiState = _uiState.asStateFlow()

    private val _events = MutableSharedFlow<String>()
    val events = _events.asSharedFlow()

    init {
        refresh()
    }

    fun refresh() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(loading = true)
            when (val result = repository.getMaintenanceRequests()) {
                is Resource.Success -> {
                    val requests = result.data
                        .sortedByDescending { it.createdAt }
                        .map { it.toUiModel() }

                    _uiState.value = MaintenanceUiState(
                        loading = false,
                        requests = requests,
                        openCount = requests.count { it.statusKey == "OPEN" },
                        inProgressCount = requests.count { it.statusKey == "IN_PROGRESS" },
                        resolvedCount = requests.count { it.statusKey in setOf("RESOLVED", "CLOSED") }
                    )
                }
                is Resource.Error -> {
                    _uiState.value = _uiState.value.copy(loading = false)
                    _events.emit(result.message)
                }
                Resource.Loading -> Unit
            }
        }
    }

    fun submitRequest(title: String, description: String, priority: String) {
        viewModelScope.launch {
            val normalizedTitle = title.trim()
            val normalizedDescription = description.trim()
            if (normalizedTitle.length < 4) {
                _events.emit("Enter a clear maintenance title")
                return@launch
            }
            if (normalizedDescription.length < 10) {
                _events.emit("Describe the issue in a little more detail")
                return@launch
            }

            _uiState.value = _uiState.value.copy(submitting = true)
            when (val result = repository.createMaintenanceRequest(normalizedTitle, normalizedDescription, priority)) {
                is Resource.Success -> {
                    _events.emit("Maintenance request submitted")
                    val newItem = result.data.toUiModel()
                    val updated = listOf(newItem) + _uiState.value.requests.filterNot { it.id == newItem.id }
                    _uiState.value = _uiState.value.copy(
                        submitting = false,
                        requests = updated,
                        openCount = updated.count { it.statusKey == "OPEN" },
                        inProgressCount = updated.count { it.statusKey == "IN_PROGRESS" },
                        resolvedCount = updated.count { it.statusKey in setOf("RESOLVED", "CLOSED") }
                    )
                }
                is Resource.Error -> _events.emit(result.message)
                Resource.Loading -> Unit
            }
            if (_uiState.value.submitting) {
                _uiState.value = _uiState.value.copy(submitting = false)
            }
        }
    }

    private fun MaintenanceRequestItem.toUiModel(): MaintenanceRequestUi {
        val propertyName = unit?.property?.name.orEmpty()
        val unitName = unit?.unitName.orEmpty()
        val unitLabel = listOfNotNull(
            propertyName.takeIf { it.isNotBlank() },
            unitName.takeIf { it.isNotBlank() }
        ).joinToString(" · ").ifBlank { "Assigned unit" }

        return MaintenanceRequestUi(
            id = id,
            title = title,
            description = description,
            priority = priority.formatEnumLabel(),
            priorityKey = priority.uppercase(),
            status = status.toStatusLabel(),
            statusKey = status.uppercase(),
            createdAt = createdAt.toDisplayDate(),
            resolvedAt = resolvedAt.toDisplayDate(),
            unitLabel = unitLabel
        )
    }

    private fun String.formatEnumLabel(): String = lowercase()
        .replace('_', ' ')
        .replaceFirstChar { if (it.isLowerCase()) it.titlecase() else it.toString() }

}

data class MaintenanceUiState(
    val loading: Boolean = true,
    val submitting: Boolean = false,
    val requests: List<MaintenanceRequestUi> = emptyList(),
    val openCount: Int = 0,
    val inProgressCount: Int = 0,
    val resolvedCount: Int = 0
)

data class MaintenanceRequestUi(
    val id: String,
    val title: String,
    val description: String,
    val priority: String,
    val priorityKey: String,
    val status: String,
    val statusKey: String,
    val createdAt: String,
    val resolvedAt: String,
    val unitLabel: String
)
