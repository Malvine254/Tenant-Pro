package com.tenantpro.app.ui.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.Invoice
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.data.repository.InvoiceRepository
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.NetworkConnectivityObserver
import com.tenantpro.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.drop
import kotlinx.coroutines.flow.filter
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale
import javax.inject.Inject

data class MonthlyBucket(val label: String, val billed: Float, val paid: Float)

data class HomeSummary(
    val userName: String,
    val propertyUnit: String,
    val outstandingBalance: Double,
    val pendingCount: Int,
    val overdueCount: Int,
    val paidAmount: Double,
    val recentInvoices: List<Invoice>,
    val monthlyTrend: List<MonthlyBucket>
)

@HiltViewModel
class HomeViewModel @Inject constructor(
    private val authRepository: AuthRepository,
    private val invoiceRepository: InvoiceRepository,
    private val dataStore: DataStoreManager,
    private val connectivity: NetworkConnectivityObserver
) : ViewModel() {

    private val _summaryState = MutableStateFlow<Resource<HomeSummary>>(Resource.Loading)
    val summaryState: StateFlow<Resource<HomeSummary>> = _summaryState.asStateFlow()

    private val _isOffline = MutableStateFlow(false)
    val isOffline: StateFlow<Boolean> = _isOffline.asStateFlow()

    init {
        loadSummary()

        // Track offline/online status
        viewModelScope.launch {
            connectivity.isConnected.collect { connected ->
                _isOffline.value = !connected
            }
        }

        // Auto-sync when connection is restored
        viewModelScope.launch {
            connectivity.isConnected
                .drop(1)         // skip first emission (initial state)
                .filter { it }   // only fire when going online
                .collect { loadSummary() }
        }
    }

    fun loadSummary() {
        viewModelScope.launch {
            _summaryState.value = Resource.Loading

            val userName = dataStore.userName.first() ?: "Tenant"
            var rentalLabel = ""

            when (val profileResult = authRepository.getMyProfile()) {
                is Resource.Success -> {
                    rentalLabel = profileResult.data.tenantProfile?.unit?.let { unit ->
                        listOfNotNull(unit.property?.name, unit.unitName).joinToString(" · ")
                    }.orEmpty()
                }
                is Resource.Error, Resource.Loading -> Unit
            }

            when (val result = invoiceRepository.getInvoices()) {
                is Resource.Success -> {
                    val invoices = result.data
                    val outstanding = invoices
                        .filter { it.status == "PENDING" || it.status == "OVERDUE" }
                        .sumOf { it.totalAmount - it.paidAmount }
                    val pending  = invoices.count { it.status == "PENDING" }
                    val overdue  = invoices.count { it.status == "OVERDUE" }
                    val paid     = invoices.sumOf { it.paidAmount }
                    val propUnit = invoices.firstOrNull()?.unit?.let { u ->
                        listOfNotNull(u.property?.name, u.unitName).joinToString(" · ")
                    }.orEmpty().ifBlank { rentalLabel }

                    _summaryState.value = Resource.Success(
                        HomeSummary(
                            userName           = userName,
                            propertyUnit       = propUnit,
                            outstandingBalance = outstanding,
                            pendingCount       = pending,
                            overdueCount       = overdue,
                            paidAmount         = paid,
                            recentInvoices     = invoices.take(3),
                            monthlyTrend       = buildMonthlyTrend(invoices)
                        )
                    )
                }
                is Resource.Error -> {
                    if (rentalLabel.isNotBlank()) {
                        _summaryState.value = Resource.Success(
                            HomeSummary(
                                userName = userName,
                                propertyUnit = rentalLabel,
                                outstandingBalance = 0.0,
                                pendingCount = 0,
                                overdueCount = 0,
                                paidAmount = 0.0,
                                recentInvoices = emptyList(),
                                monthlyTrend = buildMonthlyTrend(emptyList())
                            )
                        )
                    } else {
                        _summaryState.value = Resource.Error(result.message)
                    }
                }
                Resource.Loading  -> { /* already set above */ }
            }
        }
    }

    private fun buildMonthlyTrend(invoices: List<Invoice>): List<MonthlyBucket> {
        val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.US)
        val monthFmt = SimpleDateFormat("MMM", Locale.US)
        val cal = Calendar.getInstance()
        val now = System.currentTimeMillis()

        // Build last 6 months in ascending order (oldest → newest)
        val buckets = (5 downTo 0).map { offset ->
            cal.timeInMillis = now
            cal.add(Calendar.MONTH, -offset)
            Triple(cal.get(Calendar.YEAR), cal.get(Calendar.MONTH), monthFmt.format(cal.time))
        }

        return buckets.map { (year, month, label) ->
            val slice = invoices.filter { inv ->
                try {
                    val dateStr = inv.dueDate?.take(10) ?: return@filter false
                    val date = sdf.parse(dateStr) ?: return@filter false
                    cal.time = date
                    cal.get(Calendar.YEAR) == year && cal.get(Calendar.MONTH) == month
                } catch (_: Exception) { false }
            }
            MonthlyBucket(
                label  = label,
                billed = slice.sumOf { it.totalAmount }.toFloat(),
                paid   = slice.sumOf { it.paidAmount }.toFloat()
            )
        }
    }
}
