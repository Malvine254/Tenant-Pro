package com.tenantpro.app.ui.maintenance

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.MaintenanceRequestItem
import com.tenantpro.app.data.repository.TenantFeatureRepository
import com.tenantpro.app.utils.Resource
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

    private val _requests = MutableStateFlow<List<MaintenanceRequestItem>>(emptyList())
    val requests = _requests.asStateFlow()

    private val _submitting = MutableStateFlow(false)
    val submitting = _submitting.asStateFlow()

    private val _events = MutableSharedFlow<String>()
    val events = _events.asSharedFlow()

    val priorities = listOf("LOW", "MEDIUM", "HIGH", "URGENT")

    init {
        loadRequests()
    }

    fun loadRequests() {
        viewModelScope.launch {
            when (val result = repository.getMaintenanceRequests()) {
                is Resource.Success -> _requests.value = result.data
                is Resource.Error -> _events.emit(result.message)
                Resource.Loading -> Unit
            }
        }
    }

    fun submitRequest(title: String, description: String, priority: String) {
        viewModelScope.launch {
            if (title.isBlank() || description.isBlank()) {
                _events.emit("Enter both a title and description")
                return@launch
            }
            _submitting.value = true
            when (val result = repository.createMaintenanceRequest(title, description, priority)) {
                is Resource.Success -> {
                    _events.emit("Maintenance request submitted")
                    loadRequests()
                }
                is Resource.Error -> _events.emit(result.message)
                Resource.Loading -> Unit
            }
            _submitting.value = false
        }
    }
}
