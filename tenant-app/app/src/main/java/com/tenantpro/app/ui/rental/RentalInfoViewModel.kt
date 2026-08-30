package com.tenantpro.app.ui.rental

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.TenantTenancyProfile
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.toDisplayDate
import com.tenantpro.app.utils.toKes
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.Job
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class RentalInfoViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(RentalInfoUiState())
    val uiState = _uiState.asStateFlow()

    private val _events = MutableSharedFlow<String>()
    val events = _events.asSharedFlow()
    private var refreshJob: Job? = null

    fun refreshRentalInfo() {
        if (refreshJob?.isActive == true) return
        refreshJob = viewModelScope.launch {
            val hasVisibleContent = _uiState.value.units.isNotEmpty()
            if (!hasVisibleContent) {
                _uiState.value = _uiState.value.copy(loading = true)
            }

            val initialResult = authRepository.getMyProfile(forceRefresh = false)
            applyProfileResult(initialResult, showError = !hasVisibleContent)

            val connectedInvitation = authRepository.claimMatchingInvitations()
            if ((initialResult as? Resource.Success<*>)?.fromCache == true || connectedInvitation) {
                applyProfileResult(
                    authRepository.getMyProfile(forceRefresh = true),
                    showError = _uiState.value.units.isEmpty()
                )
            }

            _uiState.value = _uiState.value.copy(loading = false)
        }
    }

    private suspend fun applyProfileResult(
        result: Resource<com.tenantpro.app.data.model.UserProfile>,
        showError: Boolean
    ) {
        when (result) {
            is Resource.Success -> _uiState.value = RentalInfoUiState(
                units = result.data.toRentalUnits(),
                loading = false
            )
            is Resource.Error -> if (showError) {
                _events.emit("Could not load rental profile: ${result.message}")
            }
            Resource.Loading -> Unit
        }
    }

    private fun com.tenantpro.app.data.model.UserProfile.toRentalUnits(): List<RentalUnitItem> {
        val profiles: List<TenantTenancyProfile> = tenantProfiles.ifEmpty {
            listOfNotNull(tenantProfile)
        }
        return profiles.filter { it.isActive }.map { tenancy ->
            val unit = tenancy.unit
            val property = unit?.property
            val address = listOfNotNull(property?.addressLine, property?.city).joinToString(", ")
            RentalUnitItem(
                tenancyId = tenancy.id,
                propertyName = property?.name ?: "—",
                unitNumber = unit?.unitName ?: "—",
                floor = unit?.floor,
                rentAmountText = unit?.rentAmount?.toKes(),
                moveInDate = tenancy.moveInDate?.toDisplayDate() ?: "—",
                address = address.ifBlank { "—" },
                apartmentImageUrl = unit?.displayImageUrl
                    ?: unit?.imageUrls?.firstOrNull()
                    ?: property?.coverImageUrl,
            )
        }
    }

    fun acceptInvitation(code: String) {
        viewModelScope.launch {
            if (code.isBlank()) {
                _events.emit("Enter a valid invitation code")
                return@launch
            }
            when (val result = authRepository.acceptInvitation(code)) {
                is Resource.Success -> {
                    _events.emit(result.data)
                    refreshRentalInfo()
                }
                is Resource.Error -> _events.emit(result.message)
                Resource.Loading -> Unit
            }
        }
    }
}

data class RentalUnitItem(
    val tenancyId: String,
    val propertyName: String,
    val unitNumber: String,
    val floor: String?,
    val rentAmountText: String?,
    val moveInDate: String,
    val address: String,
    val apartmentImageUrl: String? = null,
)

data class RentalInfoUiState(
    val units: List<RentalUnitItem> = emptyList(),
    val loading: Boolean = true
)
