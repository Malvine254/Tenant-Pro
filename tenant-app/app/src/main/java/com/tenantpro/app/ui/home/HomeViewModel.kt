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

data class BillItem(
    val id: String,
    val billingType: String,
    val amount: Double,
    val paidAmount: Double,
    val balance: Double,
    val dueDate: String?,
    val period: String?,
    val status: String,
    val description: String?
)

data class HomeUnitItem(
    val id: String,
    val propertyName: String,
    val unitName: String,
    val floor: String?,
    val rentAmount: Double?,
    val address: String
)

data class HomeSummary(
    val userName: String,
    val propertyUnit: String,
    val propertyName: String,
    val unitName: String,
    val rentAmount: Double,
    val units: List<HomeUnitItem>,
    val outstandingBalance: Double,
    val pendingCount: Int,
    val overdueCount: Int,
    val paidAmount: Double,
    val recentInvoices: List<Invoice>,
    val monthlyTrend: List<MonthlyBucket>,
    val thisMonthBills: List<BillItem>,
    val totalDueThisMonth: Double,
    val currentMonth: String
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

        viewModelScope.launch {
            connectivity.isConnected.collect { connected ->
                _isOffline.value = !connected
            }
        }

        viewModelScope.launch {
            connectivity.isConnected
                .drop(1)
                .filter { it }
                .collect { loadSummary() }
        }
    }

    fun loadSummary() {
        viewModelScope.launch {
            _summaryState.value = Resource.Loading

            val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.US)
            val cal = Calendar.getInstance()
            val thisYear = cal.get(Calendar.YEAR)
            val thisMonth = cal.get(Calendar.MONTH)
            val currentMonth = SimpleDateFormat("MMMM yyyy", Locale.getDefault()).format(cal.time)

            var userName = dataStore.userName.first()
                ?.trim()
                ?.substringBefore(" ")
                .orEmpty()
                .ifBlank { "Tenant" }
            var rentalLabel = ""
            var propertyName = ""
            var unitName = ""
            var rentAmount = 0.0
            var profileUnits = emptyList<HomeUnitItem>()
            var activeUnitIds = emptySet<String>()
            var profileLoaded = false

            when (val profileResult = authRepository.getMyProfile()) {
                is Resource.Success -> {
                    profileLoaded = true
                    val profile = profileResult.data
                    userName = profile.firstName
                        ?.trim()
                        ?.takeIf { it.isNotBlank() }
                        ?: profile.fullName
                            ?.trim()
                            ?.substringBefore(" ")
                            ?.takeIf { it.isNotBlank() }
                        ?: userName
                    rentalLabel = profile.tenantProfile?.unit?.let { unit ->
                        listOfNotNull(unit.property?.name, unit.unitName).joinToString(" · ")
                    }.orEmpty()
                    propertyName = profile.tenantProfile?.unit?.property?.name.orEmpty()
                    unitName = profile.tenantProfile?.unit?.unitName.orEmpty()
                    rentAmount = profile.tenantProfile?.unit?.rentAmount ?: 0.0
                    profileUnits = profile.tenantProfiles
                        .ifEmpty { listOfNotNull(profile.tenantProfile) }
                        .filter { it.isActive }
                        .mapNotNull { tenancy ->
                            tenancy.unit?.let { unit ->
                                val property = unit.property
                                val address = listOfNotNull(
                                    property?.addressLine,
                                    property?.city
                                ).joinToString(", ")
                                HomeUnitItem(
                                    id = unit.id,
                                    propertyName = property?.name ?: "Property",
                                    unitName = unit.unitName.ifBlank { "Unit" },
                                    floor = unit.floor,
                                    rentAmount = unit.rentAmount,
                                    address = address.ifBlank { "Address not set" }
                                )
                            }
                        }
                    activeUnitIds = profileUnits.map { it.id }.toSet()
                }
                is Resource.Error, Resource.Loading -> Unit
            }

            when (val result = invoiceRepository.getInvoices()) {
                is Resource.Success -> {
                    // Once the profile has loaded, only invoices belonging to an
                    // active tenancy may contribute to the Home dashboard.
                    val invoices = if (profileLoaded) {
                        result.data.filter { invoice -> invoice.unit?.id in activeUnitIds }
                    } else {
                        result.data
                    }
                    val outstanding = invoices
                        .filter { it.statusCode() in setOf("PENDING", "PARTIAL", "PARTIALLY_PAID", "UNPAID", "OVERDUE") }
                        .sumOf { it.effectiveBalance() }
                    val pending  = invoices.count { it.statusCode() in setOf("PENDING", "PARTIAL", "PARTIALLY_PAID", "UNPAID") }
                    val overdue  = invoices.count { it.statusCode() == "OVERDUE" }
                    val paid     = invoices.sumOf { it.paidAmount }
                    val propUnit = invoices.firstOrNull()?.unit?.let { u ->
                        listOfNotNull(u.property?.name, u.unitName).joinToString(" · ")
                    }.orEmpty().ifBlank { rentalLabel }

                    val propName = invoices.firstOrNull()?.unit?.property?.name.orEmpty().ifBlank { propertyName }
                    val uName = invoices.firstOrNull()?.unit?.unitName.orEmpty().ifBlank { unitName }
                    val rent = invoices.firstOrNull()?.unit?.rentAmount ?: rentAmount
                    val invoiceUnits = invoices
                        .mapNotNull { it.unit }
                        .distinctBy { it.id }
                        .map { unit ->
                            val property = unit.property
                            val address = listOfNotNull(
                                property?.addressLine,
                                property?.city
                            ).joinToString(", ")
                            HomeUnitItem(
                                id = unit.id,
                                propertyName = property?.name ?: "Property",
                                unitName = unit.unitName.ifBlank { "Unit" },
                                floor = unit.floor,
                                rentAmount = unit.rentAmount,
                                address = address.ifBlank { "Address not set" }
                            )
                        }
                    // A successful profile response is authoritative. Falling back to
                    // historical invoice units here made revoked tenancies reappear.
                    val displayUnits = if (profileLoaded) profileUnits else invoiceUnits
                    val displayUnitLabel = when (displayUnits.size) {
                        0 -> propUnit
                        1 -> propUnit
                        else -> "${displayUnits.size} active units"
                    }
                    val displayRent = displayUnits
                        .sumOf { it.rentAmount ?: 0.0 }
                        .takeIf { it > 0.0 }
                        ?: rent

                    val dueAndOverdueInvoices = invoices.filter { inv ->
                        try {
                            val isOverdue = inv.statusCode() == "OVERDUE"
                            val periodMatches =
                                inv.periodYear == thisYear && inv.periodMonth == thisMonth + 1
                            val dueDateMatches = inv.dueDate?.take(10)?.let { dateStr ->
                                val date = sdf.parse(dateStr) ?: return@let false
                                cal.time = date
                                cal.get(Calendar.YEAR) == thisYear && cal.get(Calendar.MONTH) == thisMonth
                            } ?: false
                            isOverdue || periodMatches || dueDateMatches
                        } catch (_: Exception) { false }
                    }

                    val invoiceBillItems = dueAndOverdueInvoices
                        .filter { it.statusCode() != "CANCELLED" }
                        .map { inv ->
                            BillItem(
                                id = inv.id,
                                billingType = inv.billingType,
                                amount = inv.effectiveTotalAmount(),
                                paidAmount = inv.paidAmount,
                                balance = inv.effectiveBalance().coerceAtLeast(0.0),
                                dueDate = inv.dueDate,
                                period = inv.displayPeriod(),
                                status = inv.status,
                                description = inv.description
                            )
                        }

                    val billItems = invoiceBillItems
                        .filter { it.status.uppercase(Locale.ROOT) != "CANCELLED" }
                        .sortedBy { when (it.billingType.uppercase()) { "RENT" -> 0; "WATER" -> 1; "GARBAGE" -> 2; else -> 3 } }

                    val totalDueThisMonth = billItems
                        .filter { it.status.uppercase(Locale.ROOT) in setOf("PENDING", "PARTIAL", "PARTIALLY_PAID", "UNPAID", "OVERDUE") }
                        .sumOf { it.balance }

                    _summaryState.value = Resource.Success(
                        HomeSummary(
                            userName           = userName,
                            propertyUnit       = displayUnitLabel,
                            propertyName       = propName,
                            unitName           = uName,
                            rentAmount         = displayRent,
                            units              = displayUnits,
                            outstandingBalance = outstanding,
                            pendingCount       = pending,
                            overdueCount       = overdue,
                            paidAmount         = paid,
                            recentInvoices     = invoices.take(3),
                            monthlyTrend       = buildMonthlyTrend(invoices),
                            thisMonthBills     = billItems,
                            totalDueThisMonth  = totalDueThisMonth,
                            currentMonth       = currentMonth
                        )
                    )
                }
                is Resource.Error -> {
                    if (rentalLabel.isNotBlank()) {
                        _summaryState.value = Resource.Success(
                            HomeSummary(
                                userName = userName,
                                propertyUnit = rentalLabel,
                                propertyName = propertyName,
                                unitName = unitName,
                                rentAmount = rentAmount,
                                units = profileUnits,
                                outstandingBalance = 0.0,
                                pendingCount = 0,
                                overdueCount = 0,
                                paidAmount = 0.0,
                                recentInvoices = emptyList(),
                                monthlyTrend = buildMonthlyTrend(emptyList()),
                                thisMonthBills = emptyList(),
                                totalDueThisMonth = 0.0,
                                currentMonth = currentMonth
                            )
                        )
                    } else {
                        _summaryState.value = Resource.Error(result.message)
                    }
                }
                Resource.Loading -> { }
            }
        }
    }

    private fun buildMonthlyTrend(invoices: List<Invoice>): List<MonthlyBucket> {
        val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.US)
        val monthFmt = SimpleDateFormat("MMM", Locale.US)
        val cal = Calendar.getInstance()
        val now = System.currentTimeMillis()

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
                billed = slice.sumOf { it.effectiveTotalAmount() }.toFloat(),
                paid   = slice.sumOf { it.paidAmount }.toFloat()
            )
        }
    }
}

private fun Invoice.statusCode(): String = status.uppercase(Locale.ROOT)
