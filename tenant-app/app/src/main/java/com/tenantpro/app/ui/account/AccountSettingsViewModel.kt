package com.tenantpro.app.ui.account

import android.content.Context
import android.net.Uri
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
    private val _subscriptionInfo = MutableStateFlow(SubscriptionInfoUi())
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

    private val settingsPreferenceFlow = combine(
        dataStoreManager.notificationsEnabled,
        dataStoreManager.emailNotificationsEnabled,
        dataStoreManager.biometricLockEnabled
    ) { notificationsEnabled, emailNotificationsEnabled, biometricLockEnabled ->
        SettingsPreferences(
            notificationsEnabled = notificationsEnabled,
            emailNotificationsEnabled = emailNotificationsEnabled,
            biometricLockEnabled = biometricLockEnabled
        )
    }

    private val accountSettingsFlow = combine(
        basicProfileFlow,
        extraProfileFlow,
        settingsPreferenceFlow
    ) { basic, extra, settings ->
        AccountSettingsData(
            basic = basic,
            extra = extra,
            settings = settings
        )
    }

    val uiState: StateFlow<AccountUiState> = combine(
        accountSettingsFlow,
        _apartmentInfo,
        _subscriptionInfo,
        _saving,
        _loading
    ) { account, apartment, subscription, saving, loading ->
        val completion = calculateProfileCompletion(
            name = account.basic.name,
            phone = account.basic.phone,
            email = account.basic.email,
            emergencyContact = account.extra.emergencyContact,
            bio = account.extra.bio,
            imageUri = account.extra.imageUri,
            apartment = apartment
        )
        AccountUiState(
            name = account.basic.name.orEmpty(),
            phone = account.basic.phone.orEmpty(),
            email = account.basic.email.orEmpty(),
            emergencyContact = account.extra.emergencyContact.orEmpty(),
            bio = account.extra.bio.orEmpty(),
            imageUri = account.extra.imageUri,
            apartment = apartment,
            subscriptionStatusText = subscription.statusText,
            subscriptionDetailText = subscription.detailText,
            showSubscriptionStatus = subscription.show,
            profileCompletionPercent = completion,
            profileCompletionText = "$completion% complete",
            notificationsEnabled = account.settings.notificationsEnabled,
            emailNotificationsEnabled = account.settings.emailNotificationsEnabled,
            biometricLockEnabled = account.settings.biometricLockEnabled,
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
                    val role = profile.role.uppercase()
                    if (role == "LANDLORD") {
                        val rawStatus = (profile.subscriptionStatus ?: profile.billingStatus ?: "").lowercase()
                        val statusText = when (rawStatus) {
                            "trial" -> "Subscription: Trial active"
                            "active" -> "Subscription: Active"
                            "past_due" -> "Subscription: Past due"
                            "not_required" -> "Subscription: Not required"
                            else -> "Subscription: Unknown"
                        }
                        val detailText = when {
                            !profile.subscriptionMessage.isNullOrBlank() -> profile.subscriptionMessage
                            rawStatus == "trial" && !profile.trialEndsAt.isNullOrBlank() -> "Trial ends ${profile.trialEndsAt.toDisplayDate()}"
                            rawStatus == "active" && !profile.servicePaidUntil.isNullOrBlank() -> "Paid until ${profile.servicePaidUntil.toDisplayDate()}"
                            else -> ""
                        }
                        _subscriptionInfo.value = SubscriptionInfoUi(
                            show = true,
                            statusText = statusText,
                            detailText = detailText
                        )
                    } else {
                        _subscriptionInfo.value = SubscriptionInfoUi()
                    }

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
                        .sumOf { it.effectiveBalance() }

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

    fun uploadProfileImage(uri: Uri, context: Context) {
        viewModelScope.launch {
            _saving.value = true
            when (val result = authRepository.uploadProfileImage(uri, context)) {
                is Resource.Success -> _events.emit("Profile photo uploaded")
                is Resource.Error -> _events.emit(result.message)
                Resource.Loading -> Unit
            }
            _saving.value = false
        }
    }

    fun removeProfileImage() {
        viewModelScope.launch {
            _saving.value = true
            when (val result = authRepository.clearProfileImage()) {
                is Resource.Success -> _events.emit("Profile photo removed")
                is Resource.Error -> _events.emit(result.message)
                Resource.Loading -> Unit
            }
            _saving.value = false
        }
    }

    fun setNotificationsEnabled(enabled: Boolean) {
        viewModelScope.launch {
            val current = uiState.value
            when (val result = authRepository.updateAppSettings(
                notificationsEnabled = enabled,
                emailNotificationsEnabled = current.emailNotificationsEnabled,
                biometricLockEnabled = current.biometricLockEnabled
            )) {
                is Resource.Success -> {
                    _events.emit(if (enabled) "Notifications enabled" else "Notifications muted")
                }
                is Resource.Error -> {
                    dataStoreManager.saveNotificationsEnabled(enabled)
                    _events.emit("${result.message} (saved on this device)")
                }
                Resource.Loading -> Unit
            }
        }
    }

    fun changePassword(currentPassword: String, newPassword: String, confirmation: String) {
        viewModelScope.launch {
            _saving.value = true
            when (val result = authRepository.changePassword(currentPassword, newPassword, confirmation)) {
                is Resource.Success -> _events.emit(result.data)
                is Resource.Error -> _events.emit(result.message)
                Resource.Loading -> Unit
            }
            _saving.value = false
        }
    }

    fun setEmailNotificationsEnabled(enabled: Boolean) {
        viewModelScope.launch {
            val current = uiState.value
            when (val result = authRepository.updateAppSettings(
                notificationsEnabled = current.notificationsEnabled,
                emailNotificationsEnabled = enabled,
                biometricLockEnabled = current.biometricLockEnabled
            )) {
                is Resource.Success -> {
                    _events.emit(if (enabled) "Email notifications enabled" else "Email notifications disabled")
                }
                is Resource.Error -> {
                    dataStoreManager.saveEmailNotificationsEnabled(enabled)
                    _events.emit("${result.message} (saved on this device)")
                }
                Resource.Loading -> Unit
            }
        }
    }

    fun setBiometricLockEnabled(enabled: Boolean) {
        viewModelScope.launch {
            if (enabled && !dataStoreManager.saveCurrentSessionForBiometric()) {
                dataStoreManager.saveBiometricLockEnabled(false)
                _events.emit("Sign in again before enabling biometric login")
                return@launch
            }

            val current = uiState.value
            when (val result = authRepository.updateAppSettings(
                notificationsEnabled = current.notificationsEnabled,
                emailNotificationsEnabled = current.emailNotificationsEnabled,
                biometricLockEnabled = enabled
            )) {
                is Resource.Success -> {
                    if (enabled) {
                        dataStoreManager.saveCurrentSessionForBiometric()
                    }
                    _events.emit(if (enabled) "Biometric login enabled" else "Biometric login disabled")
                }
                is Resource.Error -> {
                    dataStoreManager.saveBiometricLockEnabled(enabled)
                    if (enabled) {
                        dataStoreManager.saveCurrentSessionForBiometric()
                    }
                    _events.emit("${result.message} (saved on this device)")
                }
                Resource.Loading -> Unit
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
    val subscriptionStatusText: String = "",
    val subscriptionDetailText: String = "",
    val showSubscriptionStatus: Boolean = false,
    val profileCompletionPercent: Int = 0,
    val profileCompletionText: String = "0% complete",
    val notificationsEnabled: Boolean = true,
    val emailNotificationsEnabled: Boolean = true,
    val biometricLockEnabled: Boolean = false,
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
    val outstandingText: String = 0.0.toKes(),
    val pendingCount: Int = 0,
    val overdueCount: Int = 0
)

data class SubscriptionInfoUi(
    val show: Boolean = false,
    val statusText: String = "",
    val detailText: String = ""
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

private data class SettingsPreferences(
    val notificationsEnabled: Boolean,
    val emailNotificationsEnabled: Boolean,
    val biometricLockEnabled: Boolean
)

private data class AccountSettingsData(
    val basic: BasicProfile,
    val extra: ExtraProfile,
    val settings: SettingsPreferences
)
