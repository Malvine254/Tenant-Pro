package com.tenantpro.app.ui.invoices

import android.graphics.Color
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.tenantpro.app.data.model.Invoice
import com.tenantpro.app.databinding.ItemInvoiceBinding
import com.tenantpro.app.utils.toBillingLabel
import com.tenantpro.app.utils.toDisplayDate
import com.tenantpro.app.utils.toKes
import com.tenantpro.app.utils.toStatusLabel

class InvoiceAdapter(
    private val onPayClick: (Invoice) -> Unit,
    private val onDownloadClick: (Invoice) -> Unit
) : ListAdapter<Invoice, InvoiceAdapter.ViewHolder>(DIFF_CALLBACK) {

    private var numberingOffset: Int = 0

    fun setNumberingOffset(offset: Int) {
        numberingOffset = offset
    }

    inner class ViewHolder(private val binding: ItemInvoiceBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(invoice: Invoice, position: Int) {
            val remaining = invoice.totalAmount - invoice.paidAmount
            val rowNumber = numberingOffset + position + 1

            binding.tvRowNumber.text    = rowNumber.toString()
            binding.tvBillingType.text = invoice.billingType.toBillingLabel()
            binding.tvPeriod.text      = invoice.billingPeriod ?: "—"
            binding.tvProperty.text    = invoice.unit?.property?.name ?: "—"
            binding.tvAmount.text      = invoice.totalAmount.toKes()
            binding.tvPaid.text        = "+ ${invoice.paidAmount.toKes()}"

            binding.tvDue.text         = invoice.dueDate.toDisplayDate()
            binding.tvStatus.text      = invoice.status.toStatusLabel()

            // Status text colour only (no background badge in new design)
            val statusColor = when (invoice.status) {
                "PAID"      -> Color.parseColor("#22c55e")
                "OVERDUE"   -> Color.parseColor("#ef4444")
                "CANCELLED" -> Color.parseColor("#6b7280")
                else        -> Color.parseColor("#f59e0b")
            }
            binding.tvStatus.setTextColor(statusColor)

            // Pay button only visible when there's an outstanding balance
            val canPay = (invoice.status == "PENDING" || invoice.status == "OVERDUE") && remaining > 0
            binding.btnPay.isEnabled = canPay
            binding.btnPay.alpha = if (canPay) 1f else 0.4f
            binding.btnPay.setOnClickListener { onPayClick(invoice) }

            binding.btnSave.setOnClickListener { onDownloadClick(invoice) }
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder =
        ViewHolder(ItemInvoiceBinding.inflate(LayoutInflater.from(parent.context), parent, false))

    override fun onBindViewHolder(holder: ViewHolder, position: Int) =
        holder.bind(getItem(position), position)

    companion object {
        private val DIFF_CALLBACK = object : DiffUtil.ItemCallback<Invoice>() {
            override fun areItemsTheSame(old: Invoice, new: Invoice) = old.id == new.id
            override fun areContentsTheSame(old: Invoice, new: Invoice) = old == new
        }
    }
}
