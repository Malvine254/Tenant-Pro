package com.tenantpro.app.ui.account

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.data.repository.InvoiceRepository
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.toDisplayDate
import com.tenantpro.app.utils.toKes
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class AccountSettingsViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val invoiceRepository: InvoiceRepository,
    private val dataStoreManager: DataStoreManager
) : ViewModel() {

    private val _apartmentInfo = MutableStateFlow(ApartmentInfo())
    private val _saving = MutableStateFlow(false)
    private val _loading = MutableStateFlow(true)
    private val _events = MutableSharedFlow<String>()

    val events = _events.asSharedFlow()

    private val basicProfileFlow = combine(
        dataStoreManager.userName,
        dataStoreManager.phoneNumber,
        dataStoreManager.userEmail
    ) { name, phone, email ->
        BasicProfile(name = name, phone = phone, email = email)
    }

    private val extraProfileFlow = combine(
        dataStoreManager.emergencyContact,
        dataStoreManager.profileBio,
        dataStoreManager.profileImageUri
    ) { emergency, bio, imageUri ->
        ExtraProfile(emergencyContact = emergency, bio = bio, imageUri = imageUri)
    }

    val uiState: StateFlow<AccountUiState> = combine(
        basicProfileFlow,
        extraProfileFlow,
        _apartmentInfo,
        _saving,
        _loading
    ) { basic, extra, apartment, saving, loading ->
        val completion = calculateProfileCompletion(
            name = basic.name,
            phone = basic.phone,
            email = basic.email,
            emergencyContact = extra.emergencyContact,
            bio = extra.bio,
            imageUri = extra.imageUri,
            apartment = apartment
        )
        AccountUiState(
            name = basic.name.orEmpty(),
            phone = basic.phone.orEmpty(),
            email = basic.email.orEmpty(),
            emergencyContact = extra.emergencyContact.orEmpty(),
            bio = extra.bio.orEmpty(),
            imageUri = extra.imageUri,
            apartment = apartment,
            profileCompletionPercent = completion,
            profileCompletionText = "$completion% complete",
            saving = saving,
            loading = loading
        )
    }.stateIn(viewModelScope, SharingStarted.WhileSubscribed(5_000), AccountUiState())

    init {
        fetchUserProfile()
        refreshApartmentInfo()
    }

    private fun fetchUserProfile() {
        viewModelScope.launch {
            _loading.value = true
            when (val result = authRepository.getMyProfile()) {
                is Resource.Success -> {
                    val profile = result.data
                    // Use first active tenancy from list, or fall back to singular profile
                    val tenancy = profile.tenantProfiles.firstOrNull { it.isActive }
                        ?: profile.tenantProfile
                    val currentInfo = _apartmentInfo.value
                    val resolvedAddress = listOfNotNull(
                        tenancy?.unit?.property?.addressLine,
                        tenancy?.unit?.property?.city
                    ).joinToString(", ")

                    _apartmentInfo.value = currentInfo.copy(
                        propertyName = tenancy?.unit?.property?.name?.takeIf { it.isNotBlank() }
                            ?: currentInfo.propertyName,
                        unitName = tenancy?.unit?.unitName?.takeIf { it.isNotBlank() }
                            ?: currentInfo.unitName,
                        moveInDate = tenancy?.moveInDate?.toDisplayDate()
                            ?.takeIf { it.isNotBlank() }
                            ?: currentInfo.moveInDate,
                        addressText = resolvedAddress.ifBlank { currentInfo.addressText },
                        coverImageUrl = tenancy?.unit?.property?.coverImageUrl ?: currentInfo.coverImageUrl
                    )
                    _loading.value = false
                }
                is Resource.Error -> {
                    _loading.value = false
                    _events.emit("Could not load profile: ${result.message}")
                }
                Resource.Loading -> Unit
            }
        }
    }

    fun refreshApartmentInfo() {
        viewModelScope.launch {
            when (val result = invoiceRepository.getInvoices()) {
                is Resource.Success -> {
                    val invoices = result.data
                    val currentInfo = _apartmentInfo.value
                    val current = invoices.firstOrNull { it.status == "PENDING" || it.status == "OVERDUE" }
                        ?: invoices.firstOrNull()

                    val unitName = current?.unit?.unitName?.takeIf { it.isNotBlank() }
                        ?: currentInfo.unitName
                    val propertyName = current?.unit?.property?.name?.takeIf { it.isNotBlank() }
                        ?: currentInfo.propertyName
                    val dueDate = current?.dueDate?.toDisplayDate() ?: currentInfo.nextDueDate

                    val totalOutstanding = invoices
                        .filter { it.status == "PENDING" || it.status == "OVERDUE" }
                        .sumOf { (it.totalAmount - it.paidAmount).coerceAtLeast(0.0) }

                    val pendingCount = invoices.count { it.status == "PENDING" }
                    val overdueCount = invoices.count { it.status == "OVERDUE" }

                    _apartmentInfo.value = currentInfo.copy(
                        propertyName = propertyName,
                        unitName = unitName,
                        nextDueDate = dueDate,
                        outstandingText = totalOutstanding.toKes(),
                        pendingCount = pendingCount,
                        overdueCount = overdueCount
                    )
                }

                is Resource.Error -> {
                    _apartmentInfo.value = _apartmentInfo.value.copy(
                        nextDueDate = _apartmentInfo.value.nextDueDate.ifBlank { "—" }
                    )
                }

                Resource.Loading -> Unit
            }
        }
    }

    fun saveProfile(
        name: String,
        phone: String,
        email: String,
        emergencyContact: String,
        bio: String
    ) {
        viewModelScope.launch {
            _saving.value = true
            when (val result = authRepository.updateMyProfile(
                fullName = name,
                phone = phone,
                email = email,
                emergencyContact = emergencyContact,
                bio = bio,
                profileImageUrl = uiState.value.imageUri
            )) {
                is Resource.Success -> {
                    _events.emit("Profile updated")
                }
                is Resource.Error -> {
                    _events.emit(result.message)
                }
                Resource.Loading -> Unit
            }
            _saving.value = false
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
                    fetchUserProfile()
                    refreshApartmentInfo()
                }
                is Resource.Error -> _events.emit(result.message)
                Resource.Loading -> Unit
            }
        }
    }

    fun saveProfileImage(uri: String?) {
        viewModelScope.launch {
            if (uri != null) {
                dataStoreManager.saveProfileImageUri(uri)
                _events.emit("Profile photo updated")
            } else {
                dataStoreManager.saveProfileImageUri("")
                _events.emit("Profile photo removed")
            }
        }
    }

    fun logout(onDone: () -> Unit) {
        viewModelScope.launch {
            authRepository.logout()
            onDone()
        }
    }

    private fun calculateProfileCompletion(
        name: String?,
        phone: String?,
        email: String?,
        emergencyContact: String?,
        bio: String?,
        imageUri: String?,
        apartment: ApartmentInfo
    ): Int {
        val checks = listOf(
            !name.isNullOrBlank() && name != "Tenant User",
            !phone.isNullOrBlank(),
            !email.isNullOrBlank(),
            !emergencyContact.isNullOrBlank(),
            !bio.isNullOrBlank(),
            !imageUri.isNullOrBlank(),
            apartment.unitName != "Not assigned" && apartment.propertyName != "No property linked" && apartment.propertyName != "Unavailable"
        )
        val complete = checks.count { it }
        return (complete * 100) / checks.size
    }
}

data class AccountUiState(
    val name: String = "",
    val phone: String = "",
    val email: String = "",
    val emergencyContact: String = "",
    val bio: String = "",
    val imageUri: String? = null,
    val apartment: ApartmentInfo = ApartmentInfo(),
    val profileCompletionPercent: Int = 0,
    val profileCompletionText: String = "0% complete",
    val saving: Boolean = false,
    val loading: Boolean = true
)

data class ApartmentInfo(
    val propertyName: String = "No property linked",
    val unitName: String = "Not assigned",
    val nextDueDate: String = "—",
    val moveInDate: String = "—",
    val addressText: String = "No address available",
    val coverImageUrl: String? = null,
    val outstandingText: String = "KES 0.00",
    val pendingCount: Int = 0,
    val overdueCount: Int = 0
)

private data class BasicProfile(
    val name: String?,
    val phone: String?,
    val email: String?
)

private data class ExtraProfile(
    val emergencyContact: String?,
    val bio: String?,
    val imageUri: String?
)