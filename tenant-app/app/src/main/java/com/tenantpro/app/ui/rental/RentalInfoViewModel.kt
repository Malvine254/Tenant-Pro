package com.tenantpro.app.ui.rental

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.TenantTenancyProfile
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.data.repository.InvoiceRepository
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.toDisplayDate
import com.tenantpro.app.utils.toKes
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class RentalInfoViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val invoiceRepository: InvoiceRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(RentalInfoUiState())
    val uiState = _uiState.asStateFlow()

    private val _events = MutableSharedFlow<String>()
    val events = _events.asSharedFlow()

    init {
        refreshRentalInfo()
    }

    fun refreshRentalInfo() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(loading = true)

            var units = emptyList<RentalUnitItem>()
            var outstanding = "KES 0.00"
            var pendingCount = 0
            var overdueCount = 0

            when (val profileResult = authRepository.getMyProfile()) {
                is Resource.Success -> {
                    val profile = profileResult.data
                    // Prefer tenantProfiles list; fall back to singular tenantProfile for old API
                    val profiles: List<TenantTenancyProfile> =
                        profile.tenantProfiles.ifEmpty {
                            listOfNotNull(profile.tenantProfile)
                        }

                    units = profiles.filter { it.isActive }.map { tenancy ->
                        val unit = tenancy.unit
                        val property = unit?.property
                        val address = listOfNotNull(
                            property?.addressLine,
                            property?.city
                        ).joinToString(", ")
                        RentalUnitItem(
                            tenancyId = tenancy.id,
                            propertyName = property?.name ?: "—",
                            unitNumber = unit?.unitName ?: "—",
                            floor = unit?.floor,
                            rentAmountText = unit?.rentAmount?.let { "KES ${"%,.2f".format(it)}" },
                            moveInDate = tenancy.moveInDate?.toDisplayDate() ?: "—",
                            address = address.ifBlank { "—" },
                        )
                    }
                }
                is Resource.Error -> {
                    _events.emit("Could not load rental profile: ${profileResult.message}")
                }
                Resource.Loading -> Unit
            }

            when (val invoiceResult = invoiceRepository.getInvoices()) {
                is Resource.Success -> {
                    val invoices = invoiceResult.data
                    val total = invoices
                        .filter { it.status == "PENDING" || it.status == "OVERDUE" }
                        .sumOf { (it.totalAmount - it.paidAmount).coerceAtLeast(0.0) }
                    outstanding = total.toKes()
                    pendingCount = invoices.count { it.status == "PENDING" }
                    overdueCount = invoices.count { it.status == "OVERDUE" }
                }
                is Resource.Error -> Unit
                Resource.Loading -> Unit
            }

            _uiState.value = _uiState.value.copy(
                units = units,
                outstandingText = outstanding,
                pendingCount = pendingCount,
                overdueCount = overdueCount,
                loading = false
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
)

data class RentalInfoUiState(
    val units: List<RentalUnitItem> = emptyList(),
    val outstandingText: String = "KES 0.00",
    val pendingCount: Int = 0,
    val overdueCount: Int = 0,
    val loading: Boolean = true
)
