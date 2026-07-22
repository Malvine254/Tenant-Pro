package com.tenantpro.app.ui.payment

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.InitiatePaymentResponse
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.data.repository.PaymentRepository
import com.tenantpro.app.utils.Resource
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

    init {
        viewModelScope.launch {
            _savedPhone.value = authRepository.getSavedPhone()
        }
    }

    /**
     * Triggers an M-Pesa STK Push.
     * @param amount null means pay the full remaining balance.
     */
    fun pay(invoiceId: String, phoneNumber: String, amount: Double?) {
        viewModelScope.launch {
            _payState.value = Resource.Loading
            _payState.value = paymentRepository.initiatePayment(invoiceId, phoneNumber, amount)
        }
    }

    fun reset() { _payState.value = null }
}
