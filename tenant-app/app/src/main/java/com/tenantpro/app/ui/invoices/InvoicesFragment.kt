package com.tenantpro.app.ui.invoices

import android.content.ContentValues
import android.content.Intent
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.graphics.RectF
import android.graphics.pdf.PdfDocument
import android.os.Build
import android.os.Bundle
import android.provider.MediaStore
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.content.FileProvider
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.widget.doAfterTextChanged
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.tenantpro.app.R
import com.tenantpro.app.data.model.Invoice
import com.tenantpro.app.databinding.FragmentInvoicesBinding
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.toBillingLabel
import com.tenantpro.app.utils.toDisplayDate
import com.tenantpro.app.utils.toKes
import com.tenantpro.app.utils.toStatusLabel
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch
import java.io.File
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

@AndroidEntryPoint
class InvoicesFragment : Fragment() {

    companion object {
        private const val ARG_INITIAL_FILTER = "initialFilter"
        private const val FILTER_OPEN = "OPEN"
        private const val FILTER_PAID = "PAID"
        private const val FILTER_OVERDUE = "OVERDUE"
    }

    private var _binding: FragmentInvoicesBinding? = null
    private val binding get() = _binding!!
    private val viewModel: InvoicesViewModel by viewModels()

    private val pageSize = 8

    private var fullList: List<Invoice> = emptyList()
    private var filteredList: List<Invoice> = emptyList()
    private var currentPage = 1
    private var sortByDateAscending = false
    private var selectedStatusFilter: String? = null
    private var hasResumedOnce = false

    private val adapter by lazy {
        InvoiceAdapter(
            onPayClick = { invoice ->
                val bundle = Bundle().apply {
                    putString("invoiceId", invoice.id)
                    putString(
                        "invoiceLabel",
                        listOfNotNull(
                            invoice.billingType.toBillingLabel(),
                            invoice.displayPeriod()
                        ).joinToString(" - ")
                    )
                    putFloat("remainingAmount", invoice.effectiveBalance().toFloat())
                }
                findNavController().navigate(R.id.paymentFragment, bundle)
            },
            onExportPdfClick = { invoice -> exportInvoicePdf(invoice) },
            onCardClick = { invoice -> showDetailDialog(invoice) }
        )
    }

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentInvoicesBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.rvInvoices.layoutManager = LinearLayoutManager(requireContext())
        binding.rvInvoices.adapter = adapter

        binding.swipeRefresh.setOnRefreshListener { viewModel.loadInvoices() }
        binding.btnRetry.setOnClickListener { viewModel.loadInvoices() }

        setupSearch()
        setupSortToggle()
        setupChipFilters()
        setupPagination()
        applyInitialFilterFromArgs()
        observeInvoices()
    }

    override fun onResume() {
        super.onResume()
        if (hasResumedOnce) {
            // Refresh after returning from Make Payment so a completed real or
            // simulated payment immediately updates the balance and status.
            viewModel.loadInvoices()
        } else {
            hasResumedOnce = true
        }
    }

    // ── Search ──────────────────────────────────────────────────────────────

    private fun setupSearch() {
        binding.etSearch.doAfterTextChanged { applyFilters(resetPage = true) }
    }

    // ── Sort toggle ─────────────────────────────────────────────────────────

    private fun setupSortToggle() {
        binding.btnSortToggle.setOnClickListener {
            sortByDateAscending = !sortByDateAscending
            binding.btnSortToggle.animate()
                .rotation(if (sortByDateAscending) 180f else 0f)
                .setDuration(200)
                .start()
            applyFilters(resetPage = true)
        }
    }

    // ── Chip filters ─────────────────────────────────────────────────────────

    private fun setupChipFilters() {
        binding.chipGroupFilter.setOnCheckedStateChangeListener { _, checkedIds ->
            selectedStatusFilter = when {
                checkedIds.contains(R.id.chipPending)   -> FILTER_OPEN
                checkedIds.contains(R.id.chipOverdue)   -> FILTER_OVERDUE
                checkedIds.contains(R.id.chipPaid)      -> FILTER_PAID
                else -> null
            }
            applyFilters(resetPage = true)
        }
    }

    private fun applyInitialFilterFromArgs() {
        when (arguments?.getString(ARG_INITIAL_FILTER)?.uppercase(Locale.ROOT)) {
            FILTER_OPEN -> binding.chipGroupFilter.check(R.id.chipPending)
            FILTER_PAID -> binding.chipGroupFilter.check(R.id.chipPaid)
            FILTER_OVERDUE -> binding.chipGroupFilter.check(R.id.chipOverdue)
            else -> binding.chipGroupFilter.check(R.id.chipAll)
        }
        arguments?.remove(ARG_INITIAL_FILTER)
    }

    // ── Pagination ───────────────────────────────────────────────────────────

    private fun setupPagination() {
        ViewCompat.setOnApplyWindowInsetsListener(binding.paginationBar) { v, insets ->
            val navBottom = insets.getInsets(WindowInsetsCompat.Type.systemBars()).bottom
            v.setPadding(v.paddingLeft, v.paddingTop, v.paddingRight, navBottom + 14)
            insets
        }
        binding.btnPrevPage.setOnClickListener {
            if (currentPage > 1) { currentPage--; renderCurrentPage() }
        }
        binding.btnNextPage.setOnClickListener {
            if (currentPage < totalPages()) { currentPage++; renderCurrentPage() }
        }
    }

    // ── Observe ──────────────────────────────────────────────────────────────

    private fun observeInvoices() {
        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.invoicesState.collect { state ->
                    binding.swipeRefresh.isRefreshing = state is Resource.Loading
                    when (state) {
                        is Resource.Loading -> {
                            binding.tvEmpty.gone()
                            binding.tvError.gone()
                        }
                        is Resource.Success -> {
                            fullList = state.data
                            updateSummaryCards(fullList)
                            applyFilters(resetPage = true)
                            binding.tvError.gone()
                        }
                        is Resource.Error -> {
                            binding.tvErrorMessage.text = state.message
                            binding.tvError.visible()
                            binding.tvEmpty.gone()
                        }
                    }
                }
            }
        }
    }

    // ── Summary cards ─────────────────────────────────────────────────────────

    private fun updateSummaryCards(invoices: List<Invoice>) {
        val paid = invoices.filter { it.status.uppercase() == "PAID" }
        val pending = invoices.filter { isOpenStatus(it.status) }
        val overdue = invoices.filter { it.statusCode() == FILTER_OVERDUE }

        binding.tvPaidAmount.text = paid.sumOf { it.paidAmount }.toKes()
        binding.tvPaidCount.text  = "Paid"

        binding.tvPendingAmount.text = pending.sumOf { it.effectiveBalance() }.toKes()
        binding.tvPendingCount.text  = "Pending"

        binding.tvOverdueAmount.text = overdue.size.toString()
        binding.tvOverdueCount.text  = "Overdue"
    }

    // ── Filter / sort / paginate ──────────────────────────────────────────────

    private fun applyFilters(resetPage: Boolean) {
        val search = binding.etSearch.text?.toString()?.trim()?.lowercase().orEmpty()

        val searched = if (search.isBlank()) fullList else fullList.filter { invoice ->
            val property = invoice.unit?.property?.name.orEmpty().lowercase()
            val unit     = invoice.unit?.unitName.orEmpty().lowercase()
            val period   = invoice.billingPeriod.orEmpty().lowercase()
            val billing  = invoice.billingType.toBillingLabel().lowercase()
            val status   = invoice.status.toStatusLabel().lowercase()
            property.contains(search) || unit.contains(search) || period.contains(search) ||
                billing.contains(search) || status.contains(search)
        }

        val chipFiltered = when (selectedStatusFilter) {
            FILTER_OPEN -> searched.filter { isOpenStatus(it.status) }
            FILTER_PAID -> searched.filter { it.statusCode() == FILTER_PAID }
            FILTER_OVERDUE -> searched.filter { it.statusCode() == FILTER_OVERDUE }
            else -> searched
        }

        filteredList = if (sortByDateAscending) {
            chipFiltered.sortedWith(
                compareBy({ statusPriority(it.status) }, { it.dueDate ?: it.createdAt ?: "" })
            )
        } else {
            chipFiltered.sortedWith(
                compareBy<Invoice> { statusPriority(it.status) }
                    .thenByDescending { it.dueDate ?: it.createdAt ?: "" }
            )
        }

        if (resetPage) currentPage = 1
        currentPage = currentPage.coerceIn(1, totalPages())
        renderCurrentPage()
    }

    private fun renderCurrentPage() {
        if (filteredList.isEmpty()) {
            binding.tvEmpty.visible()
            binding.rvInvoices.gone()
            binding.tvPageIndicator.text = getString(R.string.invoice_page_empty)
            binding.btnPrevPage.isEnabled = false
            binding.btnNextPage.isEnabled = false
        } else {
            binding.tvEmpty.gone()
            binding.rvInvoices.visible()
            val from = (currentPage - 1) * pageSize
            val to   = (from + pageSize).coerceAtMost(filteredList.size)
            adapter.setNumberingOffset(from)
            adapter.submitList(filteredList.subList(from, to))
            val total = totalPages()
            binding.tvPageIndicator.text =
                getString(R.string.invoice_page_indicator, currentPage, total)
            binding.btnPrevPage.isEnabled = currentPage > 1
            binding.btnNextPage.isEnabled = currentPage < total
        }
    }

    private fun totalPages(): Int =
        if (filteredList.isEmpty()) 1 else ((filteredList.size - 1) / pageSize) + 1

    private fun statusPriority(status: String): Int = when (statusCode(status)) {
        FILTER_OVERDUE -> 0
        "PENDING", "PARTIAL", "PARTIALLY_PAID", "UNPAID" -> 1
        FILTER_PAID -> 2
        else -> 3
    }

    private fun isOpenStatus(status: String): Boolean =
        statusCode(status) in setOf("PENDING", "PARTIAL", "PARTIALLY_PAID", "UNPAID", FILTER_OVERDUE)

    private fun statusCode(status: String): String = status.uppercase(Locale.ROOT)

    private fun Invoice.statusCode(): String = statusCode(status)

    // ── Detail dialog ─────────────────────────────────────────────────────────

    private fun showDetailDialog(invoice: Invoice) {
        val remaining = invoice.effectiveBalance()
        val displayPeriod = invoice.displayPeriod() ?: "—"
        val message = buildString {
            appendLine("${getString(R.string.invoice_detail_type)}:       ${invoice.billingType.toBillingLabel()}")
            appendLine("${getString(R.string.invoice_detail_period)}:     $displayPeriod")
            appendLine("${getString(R.string.invoice_detail_property)}:   ${invoice.unit?.property?.name ?: "—"}")
            appendLine("${getString(R.string.invoice_detail_unit)}:       ${invoice.unit?.unitName ?: "—"}")
            appendLine()
            appendLine("${getString(R.string.invoice_detail_total)}:      ${invoice.effectiveTotalAmount().toKes()}")
            appendLine("${getString(R.string.invoice_detail_paid)}:       ${invoice.paidAmount.toKes()}")
            appendLine("${getString(R.string.invoice_detail_balance)}:    ${remaining.toKes()}")
            appendLine()
            appendLine("${getString(R.string.invoice_detail_due_date)}:   ${invoice.dueDate.toDisplayDate()}")
            appendLine("${getString(R.string.invoice_detail_status)}:     ${invoice.status.toStatusLabel()}")
        }
        MaterialAlertDialogBuilder(requireContext())
            .setTitle(getString(R.string.invoice_detail_title))
            .setMessage(message)
            .setPositiveButton(getString(R.string.invoice_close), null)
            .setNegativeButton(getString(R.string.btn_history)) { _, _ ->
                openPaymentHistory(invoice)
            }
            .setNeutralButton(getString(R.string.invoice_share)) { _, _ -> shareInvoice(invoice) }
            .show()
    }

    private fun openPaymentHistory(invoice: Invoice) {
        val bundle = Bundle().apply {
            putString("invoiceId", invoice.id)
            putString(
                "invoiceLabel",
                listOfNotNull(invoice.billingType.toBillingLabel(), invoice.displayPeriod())
                    .joinToString(" - ")
            )
        }
        findNavController().navigate(R.id.action_invoicesFragment_to_paymentHistoryFragment, bundle)
    }

    // ── Share (plain text) ────────────────────────────────────────────────────

    private fun shareInvoice(invoice: Invoice) {
        val remaining = invoice.effectiveBalance()
        val displayPeriod = invoice.displayPeriod() ?: "—"
        val text = buildString {
            appendLine(getString(R.string.invoice_share_header))
            appendLine("${getString(R.string.invoice_detail_type)}:     ${invoice.billingType.toBillingLabel()}")
            appendLine("${getString(R.string.invoice_detail_period)}:   $displayPeriod")
            appendLine("${getString(R.string.invoice_detail_property)}: ${invoice.unit?.property?.name ?: "—"}")
            appendLine("${getString(R.string.invoice_detail_unit)}:     ${invoice.unit?.unitName ?: "—"}")
            appendLine()
            appendLine("${getString(R.string.invoice_detail_total)}:    ${invoice.effectiveTotalAmount().toKes()}")
            appendLine("${getString(R.string.invoice_detail_paid)}:     ${invoice.paidAmount.toKes()}")
            appendLine("${getString(R.string.invoice_detail_balance)}:  ${remaining.toKes()}")
            appendLine("${getString(R.string.invoice_detail_due_date)}: ${invoice.dueDate.toDisplayDate()}")
            appendLine("${getString(R.string.invoice_detail_status)}:   ${invoice.status.toStatusLabel()}")
        }
        val intent = Intent(Intent.ACTION_SEND).apply {
            type = "text/plain"
            putExtra(Intent.EXTRA_SUBJECT,
                getString(R.string.invoice_share_subject, invoice.billingType.toBillingLabel()))
            putExtra(Intent.EXTRA_TEXT, text)
        }
        startActivity(Intent.createChooser(intent, getString(R.string.invoice_share_chooser)))
    }

    // ── PDF export ────────────────────────────────────────────────────────────

    private fun exportInvoicePdf(invoice: Invoice) {
        try {
            val doc  = PdfDocument()
            val page = doc.startPage(PdfDocument.PageInfo.Builder(595, 842, 1).create())
            drawInvoicePage(page.canvas, invoice)
            doc.finishPage(page)

            val safe     = invoice.displayPeriod()?.replace(" ", "_")?.replace("/", "-") ?: invoice.id
            val filename = "Invoice_${invoice.billingType}_$safe.pdf"

            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                val values = ContentValues().apply {
                    put(MediaStore.Downloads.DISPLAY_NAME, filename)
                    put(MediaStore.Downloads.MIME_TYPE, "application/pdf")
                    put(MediaStore.Downloads.IS_PENDING, 1)
                }
                val resolver = requireContext().contentResolver
                val uri = resolver.insert(MediaStore.Downloads.EXTERNAL_CONTENT_URI, values)
                if (uri != null) {
                    resolver.openOutputStream(uri)?.use { doc.writeTo(it) }
                    values.clear()
                    values.put(MediaStore.Downloads.IS_PENDING, 0)
                    resolver.update(uri, values, null, null)
                    doc.close()
                    Toast.makeText(requireContext(), "PDF saved to Downloads", Toast.LENGTH_SHORT).show()
                    try {
                        startActivity(Intent(Intent.ACTION_VIEW).apply {
                            setDataAndType(uri, "application/pdf")
                            flags = Intent.FLAG_GRANT_READ_URI_PERMISSION
                        })
                    } catch (_: Exception) {}
                    return
                }
            }

            // Fallback for API 26–28: write to external cache then share via FileProvider
            val dir  = File(requireContext().externalCacheDir, "invoices").also { it.mkdirs() }
            val file = File(dir, filename)
            file.outputStream().use { doc.writeTo(it) }
            doc.close()
            val uri = FileProvider.getUriForFile(
                requireContext(),
                "${requireContext().packageName}.provider",
                file
            )
            startActivity(Intent.createChooser(
                Intent(Intent.ACTION_VIEW).apply {
                    setDataAndType(uri, "application/pdf")
                    flags = Intent.FLAG_GRANT_READ_URI_PERMISSION
                }, "Open Invoice PDF"
            ))
            Toast.makeText(requireContext(), "Invoice PDF ready", Toast.LENGTH_SHORT).show()

        } catch (e: Exception) {
            Toast.makeText(requireContext(), "Could not generate PDF", Toast.LENGTH_SHORT).show()
        }
    }

    private fun drawInvoicePage(canvas: Canvas, invoice: Invoice) {
        val m         = 50f
        val pageWidth = 595f
        val remaining = invoice.effectiveBalance()

        val accentColor = when (invoice.status.uppercase()) {
            "PAID"      -> Color.parseColor("#16A34A")
            "OVERDUE"   -> Color.parseColor("#DC2626")
            "CANCELLED" -> Color.parseColor("#6B7280")
            else        -> Color.parseColor("#D97706")
        }
        val typeColor = when (invoice.billingType.uppercase()) {
            "RENT"    -> Color.parseColor("#4338CA")
            "WATER"   -> Color.parseColor("#0EA5E9")
            "GARBAGE" -> Color.parseColor("#16A34A")
            else      -> Color.parseColor("#D97706")
        }

        // Header bar
        canvas.drawRect(0f, 0f, pageWidth, 80f,
            Paint().apply { color = Color.parseColor("#0F172A") })
        canvas.drawText("TenantPro", m, 38f,
            Paint().apply { textSize = 22f; isFakeBoldText = true; color = Color.WHITE; isAntiAlias = true })
        canvas.drawText("Invoice Receipt", m, 60f,
            Paint().apply { textSize = 13f; color = Color.parseColor("#94A3B8"); isAntiAlias = true })

        // Status badge (top-right)
        val badgeRight = pageWidth - m
        val badgeLeft  = badgeRight - 90f
        canvas.drawRoundRect(RectF(badgeLeft, 22f, badgeRight, 58f), 8f, 8f,
            Paint().apply { color = accentColor; isAntiAlias = true })
        canvas.drawText(invoice.status.toStatusLabel().uppercase(), badgeLeft + 45f, 44f,
            Paint().apply {
                textSize = 11f; isFakeBoldText = true; color = Color.WHITE
                isAntiAlias = true; textAlign = Paint.Align.CENTER
            })

        var y = 100f

        // Billing type colour strip
        canvas.drawRect(0f, y + 2f, 6f, y + 42f,
            Paint().apply { color = typeColor })

        // Invoice title
        canvas.drawText("${invoice.billingType.toBillingLabel()} Invoice", m, y + 28f,
            Paint().apply {
                textSize = 20f; isFakeBoldText = true
                color = Color.parseColor("#0F172A"); isAntiAlias = true
            })
        y += 56f

        val divPaint = Paint().apply { color = Color.parseColor("#E2E8F0"); strokeWidth = 1.5f }
        canvas.drawLine(m, y, pageWidth - m, y, divPaint)
        y += 28f

        val labelP = Paint().apply {
            textSize = 12f; color = Color.parseColor("#64748B"); isAntiAlias = true
        }
        val valueP = Paint().apply {
            textSize = 12f; isFakeBoldText = true
            color = Color.parseColor("#0F172A"); isAntiAlias = true
        }

        fun row(label: String, value: String, vPaint: Paint = valueP) {
            canvas.drawText(label, m, y, labelP)
            canvas.drawText(value, 220f, y, vPaint)
            y += 26f
        }

        row("Type:",     invoice.billingType.toBillingLabel())
        row("Period:",   invoice.billingPeriod ?: "—")
        row("Property:", invoice.unit?.property?.name ?: "—")
        row("Unit:",     invoice.unit?.unitName ?: "—")
        row("Due Date:", invoice.dueDate.toDisplayDate())
        y += 10f

        canvas.drawLine(m, y, pageWidth - m, y, divPaint)
        y += 28f

        row("Total Amount:", invoice.effectiveTotalAmount().toKes())
        row("Amount Paid:",  invoice.paidAmount.toKes())
        row("Balance Due:",  remaining.toKes(),
            Paint().apply {
                textSize = 12f; isFakeBoldText = true
                color = if (remaining <= 0) Color.parseColor("#16A34A") else accentColor
                isAntiAlias = true
            })
        y += 20f

        canvas.drawLine(m, y, pageWidth - m, y, divPaint)
        y += 30f

        val today = SimpleDateFormat("dd MMM yyyy", Locale.getDefault()).format(Date())
        canvas.drawText("Generated by TenantPro  •  $today", m, y,
            Paint().apply { textSize = 10f; color = Color.parseColor("#94A3B8"); isAntiAlias = true })
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
