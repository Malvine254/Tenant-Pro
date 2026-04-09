package com.tenantpro.app.ui.home

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.LinearLayout
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
import com.tenantpro.app.databinding.ItemRecentInvoiceBinding
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch
import java.text.NumberFormat
import java.util.Calendar
import java.util.Locale

@AndroidEntryPoint
class HomeFragment : Fragment() {

    private var _binding: FragmentHomeBinding? = null
    private val binding get() = _binding!!
    private val viewModel: HomeViewModel by viewModels()

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

        binding.cardMyRental.setOnClickListener {
            findNavController().navigate(R.id.rentalInfoFragment)
        }

        binding.cardSupport.setOnClickListener {
            findNavController().navigate(R.id.queriesFragment)
        }

        binding.cardMaintenance.setOnClickListener {
            findNavController().navigate(R.id.maintenanceFragment)
        }

        binding.tvViewAll.setOnClickListener {
            findNavController().navigate(R.id.invoicesFragment)
        }

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

                            // Show offline banner if data came from local cache
                            if (state.fromCache) binding.tvOfflineBanner.visible()
                            else binding.tvOfflineBanner.gone()


                            binding.tvUserName.text = "Hi, ${s.userName}"

                            if (s.propertyUnit.isNotBlank()) {
                                binding.tvPropertyUnit.text = s.propertyUnit
                                binding.tvPropertyUnit.visible()
                            } else {
                                binding.tvPropertyUnit.gone()
                            }

                            binding.tvOutstanding.text = formatKes(s.outstandingBalance)
                            binding.tvPendingCount.text =
                                "${s.pendingCount} invoice${if (s.pendingCount != 1) "s" else ""}"
                            binding.tvOverdueCount.text = "${s.overdueCount}"
                            binding.tvPaidAmount.text = formatKes(s.paidAmount)

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

        row.tvInvoiceType.text =
            invoice.billingType.replaceFirstChar { it.uppercase() }
        row.tvInvoicePeriod.text =
            invoice.billingPeriod ?: invoice.dueDate?.take(10) ?: "—"
        row.tvInvoiceAmount.text = formatKes(invoice.totalAmount)

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

    private fun formatKes(amount: Double): String {
        val nf = NumberFormat.getNumberInstance(Locale.US)
        nf.maximumFractionDigits = 0
        return "KES ${nf.format(amount)}"
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
