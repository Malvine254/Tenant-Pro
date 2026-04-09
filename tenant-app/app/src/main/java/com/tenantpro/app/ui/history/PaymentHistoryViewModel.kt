package com.tenantpro.app.ui.history

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.Payment
import com.tenantpro.app.data.repository.PaymentRepository
import com.tenantpro.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class PaymentHistoryViewModel @Inject constructor(
    private val paymentRepository: PaymentRepository
) : ViewModel() {

    private val _historyState = MutableStateFlow<Resource<List<Payment>>>(Resource.Loading)
    val historyState: StateFlow<Resource<List<Payment>>> = _historyState.asStateFlow()

    fun load(invoiceId: String) {
        viewModelScope.launch {
            _historyState.value = Resource.Loading
            _historyState.value = paymentRepository.getPaymentsByInvoice(invoiceId)
        }
    }
}
