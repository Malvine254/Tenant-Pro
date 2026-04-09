package com.tenantpro.app.ui.invoices

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.Invoice
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.data.repository.InvoiceRepository
import com.tenantpro.app.utils.NetworkConnectivityObserver
import com.tenantpro.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.drop
import kotlinx.coroutines.flow.filter
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class InvoicesViewModel @Inject constructor(
    private val invoiceRepository: InvoiceRepository,
    private val authRepository: AuthRepository,
    private val connectivity: NetworkConnectivityObserver
) : ViewModel() {

    private val _invoicesState = MutableStateFlow<Resource<List<Invoice>>>(Resource.Loading)
    val invoicesState: StateFlow<Resource<List<Invoice>>> = _invoicesState.asStateFlow()

    init {
        loadInvoices()

        viewModelScope.launch {
            connectivity.isConnected
                .drop(1)
                .filter { it }
                .collect { loadInvoices() }
        }
    }

    fun loadInvoices() {
        viewModelScope.launch {
            _invoicesState.value = Resource.Loading
            _invoicesState.value = invoiceRepository.getInvoices()
        }
    }

    fun logout(onDone: () -> Unit) {
        viewModelScope.launch {
            authRepository.logout()
            onDone()
        }
    }
}
