package com.tenantpro.app.ui.payment

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.InitiatePaymentResponse
import com.tenantpro.app.data.model.ManualPaymentInstructions
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.data.repository.PaymentRepository
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.normalizeKenyanPhone
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class PaymentViewModel @Inject constructor(
    private val paymentRepository: PaymentRepository,
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _payState = MutableStateFlow<Resource<InitiatePaymentResponse>?>(null)
    val payState: StateFlow<Resource<InitiatePaymentResponse>?> = _payState.asStateFlow()

    private val _savedPhone = MutableStateFlow<String?>(null)
    val savedPhone: StateFlow<String?> = _savedPhone.asStateFlow()

    private val _manualInstructions = MutableStateFlow<Resource<ManualPaymentInstructions>?>(null)
    val manualInstructions: StateFlow<Resource<ManualPaymentInstructions>?> = _manualInstructions.asStateFlow()

    init {
        viewModelScope.launch {
            val cachedPhone = authRepository.getSavedPhone()?.trim().orEmpty()
            val cachedMpesaPhone = cachedPhone.asMpesaDisplayNumber()
            if (cachedMpesaPhone != null) {
                _savedPhone.value = cachedMpesaPhone
            }

            // Show the locally saved number immediately, then reconcile it with
            // the server without clearing a usable value when the device is offline.
            when (val profile = authRepository.getMyProfile(forceRefresh = true)) {
                is Resource.Success -> _savedPhone.value = profile.data.phoneNumber
                    .trim()
                    .asMpesaDisplayNumber()
                    ?: _savedPhone.value
                is Resource.Error, Resource.Loading -> Unit
            }
        }
    }

    /**
     * Triggers an M-Pesa STK Push.
     * @param amount null means pay the full remaining balance.
     */
    fun pay(invoiceIds: List<String>, phoneNumber: String, amount: Double?) {
        viewModelScope.launch {
            _payState.value = Resource.Loading
            _payState.value = paymentRepository.initiatePayment(invoiceIds, phoneNumber, amount)
        }
    }

    fun loadManualInstructions(invoiceIds: List<String>) {
        if (invoiceIds.isEmpty() || _manualInstructions.value is Resource.Loading) return
        viewModelScope.launch {
            _manualInstructions.value = Resource.Loading
            _manualInstructions.value = paymentRepository.getManualPaymentInstructions(invoiceIds)
        }
    }

    fun reset() { _payState.value = null }

    private fun String.asMpesaDisplayNumber(): String? {
        val normalized = normalizeKenyanPhone() ?: return null
        return "0${normalized.removePrefix("254")}"
    }
}
