package com.tenantpro.app.ui.home

import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.FrameLayout
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.TextView
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.navigation.fragment.findNavController
import com.github.mikephil.charting.components.Legend
import com.github.mikephil.charting.components.XAxis
import com.github.mikephil.charting.data.Entry
import com.github.mikephil.charting.data.LineData
import com.github.mikephil.charting.data.LineDataSet
import com.github.mikephil.charting.formatter.IndexAxisValueFormatter
import com.github.mikephil.charting.formatter.ValueFormatter
import com.tenantpro.app.R
import com.tenantpro.app.data.model.Invoice
import com.tenantpro.app.databinding.FragmentHomeBinding
import com.tenantpro.app.databinding.ItemBillCardBinding
import com.tenantpro.app.databinding.ItemMonthlyBillSummaryBinding
import com.tenantpro.app.databinding.ItemRecentInvoiceBinding
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.toKes
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch
import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Date
import java.util.Locale

@AndroidEntryPoint
class HomeFragment : Fragment() {

    private var _binding: FragmentHomeBinding? = null
    private val binding get() = _binding!!
    private val viewModel: HomeViewModel by viewModels()
    private var firstPayableBill: BillItem? = null

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentHomeBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.tvGreeting.text = getGreeting()

        binding.swipeRefresh.setOnRefreshListener { viewModel.loadSummary() }

        binding.tvViewAll.setOnClickListener {
            openInvoices(openOnly = false)
        }

        binding.btnPayAll.setOnClickListener {
            firstPayableBill?.let(::openPayment) ?: openInvoices(openOnly = true)
        }

        binding.tvTotalDue.setOnClickListener { openInvoices(openOnly = true) }
        binding.tvBillCount.setOnClickListener { openInvoices(openOnly = true) }

        observeOfflineBanner()

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.summaryState.collect { state ->
                    binding.swipeRefresh.isRefreshing = state is Resource.Loading
                    when (state) {
                        is Resource.Loading -> {
                            binding.progressBar.visible()
                            binding.tvEmpty.gone()
                        }
                        is Resource.Success -> {
                            binding.progressBar.gone()
                            val s = state.data

                            if (state.fromCache) binding.tvOfflineBanner.visible()
                            else binding.tvOfflineBanner.gone()

                            binding.tvUserName.text = "Hi, ${s.userName}"
                            binding.tvCurrentMonth.text = s.currentMonth

                            if (s.propertyUnit.isNotBlank()) {
                                binding.tvPropertyUnit.text = s.propertyUnit
                                binding.tvPropertyUnit.visible()
                            } else {
                                binding.tvPropertyUnit.gone()
                            }

                            binding.tvPropertyName.text = s.propertyName.ifBlank { "Property Name" }
                            binding.tvUnitName.text = s.unitName.ifBlank { "Unit Name" }
                            binding.tvRentAmount.text = if (s.rentAmount > 0)
                                s.rentAmount.toKes()
                            else
                                "Not set"
                            bindHomeUnits(s.units)

                            val payableStatuses = setOf("PENDING", "PARTIAL", "PARTIALLY_PAID", "UNPAID", "OVERDUE")
                            val payableBills = s.thisMonthBills.filter {
                                it.status.uppercase(Locale.ROOT) in payableStatuses && it.balance > 0
                            }
                            binding.tvTotalDue.text = payableBills.sumOf { it.balance }.toKes()
                            val billCount = payableBills.size
                            binding.tvBillCount.text = when {
                                s.thisMonthBills.isEmpty() -> "No bill issued yet"
                                billCount == 0 -> "All bills paid"
                                billCount == 1 -> "1 bill due"
                                else -> "$billCount bills due"
                            }
                            binding.btnPayAll.isEnabled = billCount > 0

                            binding.tvOutstanding.text = s.outstandingBalance.toKes()
                            binding.tvPendingCount.text = "${s.pendingCount}"
                            binding.tvOverdueCount.text = "${s.overdueCount}"
                            binding.tvPaidAmount.text = NumberFormat.getNumberInstance(Locale.US)
                                .apply { maximumFractionDigits = 0 }
                                .format(s.paidAmount)

                            bindBillCards(s.thisMonthBills)
                            bindMiniDashboardGraph(s)

                            binding.llRecentInvoices.removeAllViews()
                            if (s.recentInvoices.isEmpty()) {
                                binding.tvEmpty.visible()
                            } else {
                                binding.tvEmpty.gone()
                                s.recentInvoices.forEach { invoice ->
                                    addInvoiceRow(invoice, binding.llRecentInvoices)
                                }
                            }
                        }
                        is Resource.Error -> {
                            binding.progressBar.gone()
                        }
                    }
                }
            }
        }
    }

    private fun bindBillCards(bills: List<BillItem>) {
        binding.llBillCards.removeAllViews()

        val payableStatuses = setOf("PENDING", "PARTIAL", "PARTIALLY_PAID", "UNPAID", "OVERDUE")
        val unpaidBills = bills.filter {
            it.status.uppercase(Locale.ROOT) in payableStatuses && it.balance > 0
        }
        firstPayableBill = unpaidBills.firstOrNull()

        if (unpaidBills.isEmpty()) {
            if (bills.isEmpty()) {
                binding.tvNoBillsTitle.text = "No bill issued yet"
                binding.tvNoBillsMessage.text = "Your unit is assigned, but no invoice has been created for this month."
            } else {
                binding.tvNoBillsTitle.text = "All clear this month!"
                binding.tvNoBillsMessage.text = "No outstanding bills for this month."
            }
            binding.llNoBills.visible()
            return
        }

        binding.llNoBills.gone()

        val summary = ItemMonthlyBillSummaryBinding.inflate(layoutInflater, binding.llBillCards, false)
        val nextDueBill = unpaidBills.minByOrNull { it.dueDate ?: "9999-12-31" }
        summary.tvMonthlyBillTitle.text = "${unpaidBills.size} bill${if (unpaidBills.size == 1) "" else "s"} due this month"
        summary.tvMonthlyBillTotal.text = unpaidBills.sumOf { it.balance }.toKes()
        summary.tvMonthlyBillDue.text = nextDueBill?.dueDate?.let { "Next due ${formatDueDate(it)}" }
            ?: "Review the items below"
        unpaidBills.forEach { bill -> addBillBreakdownRow(summary.llBillBreakdown, bill) }
        summary.btnViewMonthlyBills.setOnClickListener { openInvoices(openOnly = true) }
        binding.llBillCards.addView(summary.root)
        return

        unpaidBills.take(6).forEach { bill ->
            val row = ItemBillCardBinding.inflate(layoutInflater, binding.llBillCards, false)

            val ctx = requireContext()

            val (iconRes, iconColor, bgColorRes, statusBg) = when (bill.billingType.uppercase()) {
                "RENT" -> BillStyle(
                    R.drawable.ic_home,
                    ctx.getColor(R.color.primary),
                    R.color.info_surface,
                    ctx.getColor(R.color.primary)
                )
                "WATER" -> BillStyle(
                    R.drawable.ic_water_drop,
                    ctx.getColor(R.color.info),
                    R.color.info_surface,
                    ctx.getColor(R.color.info)
                )
                "GARBAGE" -> BillStyle(
                    R.drawable.ic_delete,
                    ctx.getColor(R.color.warning),
                    R.color.warning_surface,
                    ctx.getColor(R.color.warning)
                )
                else -> BillStyle(
                    R.drawable.ic_receipt,
                    ctx.getColor(R.color.on_surface_variant),
                    R.color.surface,
                    ctx.getColor(R.color.on_surface_variant)
                )
            }

            // Icon background tinted circle
            val iconBg = GradientDrawable().apply {
                shape = GradientDrawable.OVAL
                setColor(Color.argb(30, Color.red(iconColor), Color.green(iconColor), Color.blue(iconColor)))
            }
            row.viewBillIconBg.background = iconBg
            row.ivBillIcon.setImageResource(iconRes)
            row.ivBillIcon.setColorFilter(iconColor)

            row.tvBillType.text = bill.billingType.replaceFirstChar { it.uppercase() }

            val detailText = when {
                !bill.description.isNullOrBlank() -> bill.description
                !bill.period.isNullOrBlank() -> bill.period
                !bill.dueDate.isNullOrBlank() -> "Due ${formatDueDate(bill.dueDate)}"
                else -> "—"
            }
            row.tvBillDetail.text = detailText

            // Card background
            row.root.setCardBackgroundColor(ctx.getColor(bgColorRes))

            // Status badge
            val (statusText, statusColor, statusBgAlpha) = when (bill.status) {
                "OVERDUE" -> Triple("Overdue", ctx.getColor(R.color.error), ctx.getColor(R.color.error_surface))
                "PENDING" -> Triple("Pending", ctx.getColor(R.color.warning), ctx.getColor(R.color.warning_surface))
                "PARTIAL" -> Triple("Partially paid", ctx.getColor(R.color.info), ctx.getColor(R.color.info_surface))
                else -> Triple(bill.status, ctx.getColor(R.color.on_surface_variant), ctx.getColor(R.color.surface))
            }
            row.tvBillStatus.text = statusText
            row.tvBillStatus.setTextColor(statusColor)
            val badgeBg = GradientDrawable().apply {
                shape = GradientDrawable.RECTANGLE
                cornerRadius = 20f
                setColor(statusBgAlpha)
            }
            row.tvBillStatus.background = badgeBg

            // Amount
            row.tvBillAmount.text = bill.amount.toKes()

            // Balance (only if partial payment)
            if (bill.paidAmount > 0 && bill.balance < bill.amount) {
                row.tvBillBalance.text = "Balance: ${bill.balance.toKes()}"
                row.tvBillBalance.setTextColor(ctx.getColor(R.color.error))
                row.tvBillBalance.visible()
            } else {
                row.tvBillBalance.gone()
            }

            // Due date
            row.tvBillDue.text = bill.dueDate?.let { "Due ${formatDueDate(it)}" }.orEmpty()

            // Pay button
            row.btnPayBill.setOnClickListener {
                openPayment(bill)
            }
            // Outline color matching bill type
            row.btnPayBill.setStrokeColorResource(
                when (bill.billingType.uppercase()) {
                    "RENT" -> R.color.primary
                    "WATER" -> R.color.info
                    "GARBAGE" -> R.color.warning
                    else -> R.color.outline_variant
                }
            )
            row.btnPayBill.setTextColor(iconColor)

            binding.llBillCards.addView(row.root)
        }

        if (unpaidBills.size > 3) {
            binding.llBillCards.addView(TextView(requireContext()).apply {
                text = "+${unpaidBills.size - 3} more bills  •  View all invoices"
                setTextColor(requireContext().getColor(R.color.secondary))
                textSize = 13f
                setTypeface(typeface, android.graphics.Typeface.BOLD)
                gravity = android.view.Gravity.CENTER
                setPadding(12.dp(), 10.dp(), 12.dp(), 14.dp())
                isClickable = true
                isFocusable = true
                setOnClickListener { openInvoices(openOnly = true) }
            })
        }
    }

    private fun addBillBreakdownRow(container: LinearLayout, bill: BillItem) {
        val context = requireContext()
        val row = LinearLayout(context).apply {
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            )
            gravity = android.view.Gravity.CENTER_VERTICAL
            setPadding(4.dp(), 7.dp(), 4.dp(), 7.dp())
        }
        val title = TextView(context).apply {
            layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
            text = bill.billingType.replaceFirstChar { it.uppercase() }
            setTextColor(context.getColor(R.color.on_surface))
            textSize = 14f
        }
        val amount = TextView(context).apply {
            text = bill.balance.toKes()
            setTextColor(context.getColor(R.color.on_surface))
            textSize = 14f
            setTypeface(typeface, android.graphics.Typeface.BOLD)
        }
        row.addView(title)
        row.addView(amount)
        container.addView(row)
    }

    private fun openInvoices(openOnly: Boolean) {
        val bundle = Bundle().apply {
            if (openOnly) putString("initialFilter", "OPEN")
        }
        findNavController().navigate(R.id.invoicesFragment, bundle)
    }

    private fun openPayment(bill: BillItem) {
        findNavController().navigate(R.id.paymentFragment, Bundle().apply {
            putString("invoiceId", bill.id)
            putString("invoiceLabel", bill.billingType.replaceFirstChar { it.uppercase() })
            putFloat("remainingAmount", bill.balance.toFloat())
        })
    }

    private fun formatDueDate(value: String): String {
        val dateValue = value.take(10)
        return runCatching {
            val parsed = SimpleDateFormat("yyyy-MM-dd", Locale.US).parse(dateValue) ?: return@runCatching dateValue
            SimpleDateFormat("EEE, d MMM", Locale.getDefault()).format(Date(parsed.time))
        }.getOrDefault(dateValue)
    }

    private data class BillStyle(
        val iconRes: Int,
        val iconColor: Int,
        val bgColorRes: Int,
        val statusColor: Int
    )

    private fun bindHomeUnits(units: List<HomeUnitItem>) {
        binding.llHomeUnits.removeAllViews()
        binding.tvHomeUnitsCount.text = if (units.size == 1) "1 active rental" else "${units.size} active rentals"

        if (units.isEmpty()) {
            binding.cardHomeUnits.gone()
            return
        }

        binding.cardHomeUnits.visible()
        val cardWidth = if (units.size == 1) 300.dp() else 238.dp()
        units.forEachIndexed { index, unit ->
            binding.llHomeUnits.addView(LinearLayout(requireContext()).apply {
                orientation = LinearLayout.VERTICAL
                background = GradientDrawable().apply {
                    shape = GradientDrawable.RECTANGLE
                    cornerRadius = 10.dp().toFloat()
                    setColor(requireContext().getColor(R.color.surface_variant))
                    setStroke(1.dp(), requireContext().getColor(R.color.outline_variant))
                }
                setPadding(12.dp(), 11.dp(), 12.dp(), 11.dp())
                layoutParams = LinearLayout.LayoutParams(cardWidth, LinearLayout.LayoutParams.WRAP_CONTENT).apply {
                    if (index < units.lastIndex) marginEnd = 10.dp()
                }

                addView(LinearLayout(requireContext()).apply {
                    orientation = LinearLayout.HORIZONTAL
                    gravity = android.view.Gravity.CENTER_VERTICAL
                    addView(FrameLayout(requireContext()).apply {
                        background = GradientDrawable().apply {
                            shape = GradientDrawable.OVAL
                            setColor(Color.argb(22, 31, 46, 219))
                        }
                        layoutParams = LinearLayout.LayoutParams(34.dp(), 34.dp()).apply { marginEnd = 9.dp() }
                        addView(ImageView(requireContext()).apply {
                            setImageResource(R.drawable.ic_home)
                            setColorFilter(requireContext().getColor(R.color.secondary))
                            layoutParams = FrameLayout.LayoutParams(17.dp(), 17.dp(), android.view.Gravity.CENTER)
                        })
                    })
                    addView(LinearLayout(requireContext()).apply {
                        orientation = LinearLayout.VERTICAL
                        layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1f)
                        addView(TextView(requireContext()).apply {
                            text = unit.unitName
                            setTextColor(requireContext().getColor(R.color.on_surface))
                            textSize = 14f
                            setTypeface(typeface, android.graphics.Typeface.BOLD)
                            maxLines = 1
                            ellipsize = android.text.TextUtils.TruncateAt.END
                        })
                        addView(TextView(requireContext()).apply {
                            text = unit.propertyName
                            setTextColor(requireContext().getColor(R.color.on_surface_variant))
                            textSize = 11f
                            maxLines = 1
                            ellipsize = android.text.TextUtils.TruncateAt.END
                        })
                    })
                })

                addView(TextView(requireContext()).apply {
                    text = listOfNotNull(
                        unit.floor?.takeIf { it.isNotBlank() }?.let { "Floor $it" },
                        unit.address
                    ).joinToString("  •  ")
                    setTextColor(requireContext().getColor(R.color.on_surface_variant))
                    textSize = 11f
                    maxLines = 1
                    ellipsize = android.text.TextUtils.TruncateAt.END
                    setPadding(0, 9.dp(), 0, 0)
                })

                addView(TextView(requireContext()).apply {
                    text = unit.rentAmount?.takeIf { it > 0.0 }?.let { "${it.toKes()} / month" } ?: "Rent not set"
                    setTextColor(requireContext().getColor(R.color.primary))
                    textSize = 13f
                    setTypeface(typeface, android.graphics.Typeface.BOLD)
                    setPadding(0, 5.dp(), 0, 0)
                })
            })
        }
    }

    private fun observeOfflineBanner() {
        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.isOffline.collect { offline ->
                    if (offline) binding.tvOfflineBanner.visible()
                    else binding.tvOfflineBanner.gone()
                }
            }
        }
    }

    private fun addInvoiceRow(invoice: Invoice, container: LinearLayout) {
        val row = ItemRecentInvoiceBinding.inflate(layoutInflater, container, false)

        val title = invoice.description?.takeIf { it.isNotBlank() }
            ?: when (invoice.billingType.uppercase()) {
                "RENT" -> "Rent activity"
                "WATER" -> "Water activity"
                "GARBAGE" -> "Garbage activity"
                "ELECTRIC" -> "Electricity activity"
                else -> "Billing activity"
            }

        row.tvInvoiceTitle.text = title
        row.tvInvoiceType.text = invoice.billingType.replaceFirstChar { it.uppercase() }
        row.tvInvoicePeriod.text = invoice.displayPeriod() ?: invoice.dueDate?.take(10) ?: "—"
        row.tvInvoiceAmount.text = invoice.effectiveTotalAmount().toKes()

        val statusColor = when (invoice.status) {
            "PAID"    -> requireContext().getColor(R.color.success)
            "OVERDUE" -> requireContext().getColor(R.color.error)
            "PENDING" -> requireContext().getColor(R.color.warning)
            else      -> requireContext().getColor(R.color.on_surface_variant)
        }
        row.tvInvoiceStatus.text = invoice.status
        row.tvInvoiceStatus.setTextColor(statusColor)

        row.root.setOnClickListener {
            findNavController().navigate(R.id.invoicesFragment)
        }

        container.addView(row.root)
    }

    private fun bindMiniDashboardGraph(summary: HomeSummary) {
        val trend = summary.monthlyTrend
        if (trend.isEmpty()) return
        val ctx = requireContext()

        fun makeSet(label: String, color: Int, selector: (MonthlyBucket) -> Float): LineDataSet {
            val entries = trend.mapIndexed { i, b -> Entry(i.toFloat(), selector(b)) }
            return LineDataSet(entries, label).apply {
                this.color = color
                lineWidth = 2.5f
                setCircleColor(color)
                circleRadius = 4f
                circleHoleRadius = 2f
                setDrawValues(false)
                mode = LineDataSet.Mode.CUBIC_BEZIER
                setDrawFilled(true)
                fillAlpha = 25
                fillColor = color
            }
        }

        val billedSet = makeSet("Billed", ctx.getColor(R.color.primary)) { it.billed }
        val paidSet   = makeSet("Paid",   ctx.getColor(R.color.success)) { it.paid }

        binding.lineChart.apply {
            data = LineData(billedSet, paidSet)
            description.isEnabled = false
            setTouchEnabled(true)
            isDragEnabled = false
            setScaleEnabled(false)
            setPinchZoom(false)
            setDrawGridBackground(false)
            setBackgroundColor(ctx.getColor(R.color.surface))

            xAxis.apply {
                position = XAxis.XAxisPosition.BOTTOM
                valueFormatter = IndexAxisValueFormatter(trend.map { it.label })
                granularity = 1f
                setDrawGridLines(false)
                textColor = ctx.getColor(R.color.on_surface_variant)
                textSize = 10f
                axisLineColor = ctx.getColor(R.color.primary_variant)
            }

            axisLeft.apply {
                setDrawGridLines(true)
                gridColor = ctx.getColor(R.color.primary_variant)
                textColor = ctx.getColor(R.color.on_surface_variant)
                textSize = 9f
                axisLineColor = ctx.getColor(R.color.primary_variant)
                valueFormatter = object : ValueFormatter() {
                    override fun getFormattedValue(value: Float): String =
                        if (value >= 1_000f) "%.0fK".format(value / 1_000f)
                        else value.toInt().toString()
                }
            }

            axisRight.isEnabled = false

            legend.apply {
                isEnabled = true
                textColor = ctx.getColor(R.color.on_surface_variant)
                textSize = 11f
                form = Legend.LegendForm.LINE
                verticalAlignment = Legend.LegendVerticalAlignment.BOTTOM
                horizontalAlignment = Legend.LegendHorizontalAlignment.CENTER
                orientation = Legend.LegendOrientation.HORIZONTAL
                setDrawInside(false)
            }

            animateX(600)
            invalidate()
        }
    }

    private fun getGreeting(): String = when (Calendar.getInstance().get(Calendar.HOUR_OF_DAY)) {
        in 0..11  -> "Good morning!"
        in 12..16 -> "Good afternoon!"
        else      -> "Good evening!"
    }

    private fun Int.dp(): Int = (this * resources.displayMetrics.density).toInt()

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
