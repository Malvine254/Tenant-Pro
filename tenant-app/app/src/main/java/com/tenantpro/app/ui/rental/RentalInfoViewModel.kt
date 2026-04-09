package com.tenantpro.app.ui.rental

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.data.repository.InvoiceRepository
import com.tenantpro.app.ui.account.ApartmentInfo
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

            var updatedInfo = _uiState.value.apartment

            when (val profileResult = authRepository.getMyProfile()) {
                is Resource.Success -> {
                    val tenancy = profileResult.data.tenantProfile
                    val address = listOfNotNull(
                        tenancy?.unit?.property?.addressLine,
                        tenancy?.unit?.property?.city
                    ).joinToString(", ")

                    updatedInfo = updatedInfo.copy(
                        propertyName = tenancy?.unit?.property?.name?.takeIf { it.isNotBlank() }
                            ?: updatedInfo.propertyName,
                        unitName = tenancy?.unit?.unitName?.takeIf { it.isNotBlank() }
                            ?: updatedInfo.unitName,
                        moveInDate = tenancy?.moveInDate?.toDisplayDate()
                            ?.takeIf { it.isNotBlank() }
                            ?: updatedInfo.moveInDate,
                        addressText = address.ifBlank { updatedInfo.addressText },
                        coverImageUrl = tenancy?.unit?.property?.coverImageUrl ?: updatedInfo.coverImageUrl
                    )
                }
                is Resource.Error -> {
                    _events.emit("Could not load rental profile: ${profileResult.message}")
                }
                Resource.Loading -> Unit
            }

            when (val invoiceResult = invoiceRepository.getInvoices()) {
                is Resource.Success -> {
                    val invoices = invoiceResult.data
                    val current = invoices.firstOrNull { it.status == "PENDING" || it.status == "OVERDUE" }
                        ?: invoices.firstOrNull()

                    val totalOutstanding = invoices
                        .filter { it.status == "PENDING" || it.status == "OVERDUE" }
                        .sumOf { (it.totalAmount - it.paidAmount).coerceAtLeast(0.0) }

                    updatedInfo = updatedInfo.copy(
                        propertyName = current?.unit?.property?.name?.takeIf { it.isNotBlank() }
                            ?: updatedInfo.propertyName,
                        unitName = current?.unit?.unitName?.takeIf { it.isNotBlank() }
                            ?: updatedInfo.unitName,
                        nextDueDate = current?.dueDate?.toDisplayDate() ?: updatedInfo.nextDueDate,
                        outstandingText = totalOutstanding.toKes(),
                        pendingCount = invoices.count { it.status == "PENDING" },
                        overdueCount = invoices.count { it.status == "OVERDUE" }
                    )
                }
                is Resource.Error -> Unit
                Resource.Loading -> Unit
            }

            _uiState.value = _uiState.value.copy(
                apartment = updatedInfo,
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

data class RentalInfoUiState(
    val apartment: ApartmentInfo = ApartmentInfo(),
    val loading: Boolean = true
)
