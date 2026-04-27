package com.tenantpro.app.ui.invoices

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.widget.doAfterTextChanged
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
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

@AndroidEntryPoint
class InvoicesFragment : Fragment() {

    private var _binding: FragmentInvoicesBinding? = null
    private val binding get() = _binding!!
    private val viewModel: InvoicesViewModel by viewModels()

    private val pageSize = 8

    /** Full list from the server before filter/search/sort/pagination. */
    private var fullList: List<Invoice> = emptyList()
    private var filteredList: List<Invoice> = emptyList()
    private var currentPage = 1
    private var sortByDateAscending = false

    private val adapter by lazy {
        InvoiceAdapter(
            onPayClick = { invoice ->
                val bundle = Bundle().apply {
                    putString("invoiceId", invoice.id)
                    putString("invoiceLabel",
                        "${invoice.billingType.replaceFirstChar { it.uppercase() }} – ${invoice.billingPeriod ?: ""}")
                    putFloat("remainingAmount",
                        (invoice.totalAmount - invoice.paidAmount).toFloat())
                }
                findNavController().navigate(R.id.paymentFragment, bundle)
            },
            onDownloadClick = { invoice -> shareInvoice(invoice) }
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

        setupSearchAndSortControls()
        setupPagination()
        observeInvoices()
    }

    // ── Search / date sort ───────────────────────────────────────────────

    private fun setupSearchAndSortControls() {
        binding.etSearch.doAfterTextChanged { applyFilters(resetPage = true) }

        updateDateSortHeader()
        binding.tvHeaderInvoiceSort.setOnClickListener {
            sortByDateAscending = !sortByDateAscending
            updateDateSortHeader()
            applyFilters(resetPage = true)
        }
    }

    private fun updateDateSortHeader() {
        binding.tvHeaderInvoiceSort.text = if (sortByDateAscending) {
            getString(R.string.invoice_table_header_invoice_asc)
        } else {
            getString(R.string.invoice_table_header_invoice_desc)
        }
    }

    private fun setupPagination() {
        ViewCompat.setOnApplyWindowInsetsListener(binding.paginationBar) { view, insets ->
            val navBottom = insets.getInsets(WindowInsetsCompat.Type.systemBars()).bottom
            view.setPadding(
                view.paddingLeft,
                view.paddingTop,
                view.paddingRight,
                navBottom + 14
            )
            insets
        }

        binding.btnPrevPage.setOnClickListener {
            if (currentPage > 1) {
                currentPage -= 1
                renderCurrentPage()
            }
        }
        binding.btnNextPage.setOnClickListener {
            val totalPages = totalPages()
            if (currentPage < totalPages) {
                currentPage += 1
                renderCurrentPage()
            }
        }
    }

    // ── Observe ─────────────────────────────────────────────────────────

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

    private fun applyFilters(resetPage: Boolean) {
        val search = binding.etSearch.text?.toString()?.trim()?.lowercase().orEmpty()

        val searched = if (search.isBlank()) {
            fullList
        } else {
            fullList.filter { invoice ->
                val property = invoice.unit?.property?.name.orEmpty().lowercase()
                val unit = invoice.unit?.unitName.orEmpty().lowercase()
                val period = invoice.billingPeriod.orEmpty().lowercase()
                val billing = invoice.billingType.toBillingLabel().lowercase()
                val status = invoice.status.toStatusLabel().lowercase()
                property.contains(search) || unit.contains(search) || period.contains(search) ||
                    billing.contains(search) || status.contains(search)
            }
        }

        // Status priority: PENDING and OVERDUE always appear before PAID / CANCELLED
        filteredList = if (sortByDateAscending) {
            searched.sortedWith(
                compareBy({ statusPriority(it.status) }, { it.dueDate ?: it.createdAt ?: "" })
            )
        } else {
            searched.sortedWith(
                compareBy<Invoice> { statusPriority(it.status) }
                    .thenByDescending { it.dueDate ?: it.createdAt ?: "" }
            )
        }

        if (resetPage) currentPage = 1
        val totalPages = totalPages()
        if (currentPage > totalPages) currentPage = totalPages
        if (currentPage < 1) currentPage = 1

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
            val to = (from + pageSize).coerceAtMost(filteredList.size)
            val page = filteredList.subList(from, to)
            adapter.setNumberingOffset(from)
            adapter.submitList(page)

            val total = totalPages()
            binding.tvPageIndicator.text =
                getString(R.string.invoice_page_indicator, currentPage, total)
            binding.btnPrevPage.isEnabled = currentPage > 1
            binding.btnNextPage.isEnabled = currentPage < total
        }
    }

    private fun totalPages(): Int {
        if (filteredList.isEmpty()) return 1
        return ((filteredList.size - 1) / pageSize) + 1
    }

    /** Lower value = appears first. PENDING=0, OVERDUE=1, everything else=2 */
    private fun statusPriority(status: String): Int = when (status.uppercase()) {
        "PENDING" -> 0
        "OVERDUE" -> 1
        else      -> 2
    }

    // ── Detail dialog ────────────────────────────────────────────────────

    private fun showDetailDialog(invoice: Invoice) {
        val remaining = invoice.totalAmount - invoice.paidAmount
        val message = buildString {
            appendLine("${getString(R.string.invoice_detail_type)}:       ${invoice.billingType.toBillingLabel()}")
            appendLine("${getString(R.string.invoice_detail_period)}:     ${invoice.billingPeriod ?: "—"}")
            appendLine("${getString(R.string.invoice_detail_property)}:   ${invoice.unit?.property?.name ?: "—"}")
            appendLine("${getString(R.string.invoice_detail_unit)}:       ${invoice.unit?.unitName ?: "—"}")
            appendLine()
            appendLine("${getString(R.string.invoice_detail_total)}:      ${invoice.totalAmount.toKes()}")
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
            .setNeutralButton(getString(R.string.invoice_share)) { _, _ -> shareInvoice(invoice) }
            .show()
    }

    // ── Share / download ─────────────────────────────────────────────────

    private fun shareInvoice(invoice: Invoice) {
        val remaining = invoice.totalAmount - invoice.paidAmount
        val text = buildString {
            appendLine(getString(R.string.invoice_share_header))
            appendLine("${getString(R.string.invoice_detail_type)}:     ${invoice.billingType.toBillingLabel()}")
            appendLine("${getString(R.string.invoice_detail_period)}:   ${invoice.billingPeriod ?: "—"}")
            appendLine("${getString(R.string.invoice_detail_property)}: ${invoice.unit?.property?.name ?: "—"}")
            appendLine("${getString(R.string.invoice_detail_unit)}:     ${invoice.unit?.unitName ?: "—"}")
            appendLine()
            appendLine("${getString(R.string.invoice_detail_total)}:    ${invoice.totalAmount.toKes()}")
            appendLine("${getString(R.string.invoice_detail_paid)}:     ${invoice.paidAmount.toKes()}")
            appendLine("${getString(R.string.invoice_detail_balance)}:  ${remaining.toKes()}")
            appendLine("${getString(R.string.invoice_detail_due_date)}:      ${invoice.dueDate.toDisplayDate()}")
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

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
