package com.tenantpro.app.ui.invoices

import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.content.ContextCompat
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.tenantpro.app.R
import com.tenantpro.app.data.model.Invoice
import com.tenantpro.app.databinding.ItemInvoiceBinding
import com.tenantpro.app.utils.toBillingLabel
import com.tenantpro.app.utils.toDisplayDate
import com.tenantpro.app.utils.toKes
import com.tenantpro.app.utils.toStatusLabel

class InvoiceAdapter(
    private val onPayClick: (Invoice) -> Unit,
    private val onExportPdfClick: (Invoice) -> Unit,
    private val onCardClick: (Invoice) -> Unit
) : ListAdapter<Invoice, InvoiceAdapter.ViewHolder>(DIFF_CALLBACK) {

    private var numberingOffset: Int = 0

    fun setNumberingOffset(offset: Int) {
        numberingOffset = offset
    }

    inner class ViewHolder(private val binding: ItemInvoiceBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(invoice: Invoice) {
            val effectiveTotal = invoice.effectiveTotalAmount()
            val remaining = invoice.effectiveBalance()
            val displayPeriod = invoice.displayPeriod()

            val typeColor = when (invoice.billingType.uppercase()) {
                "RENT" -> Color.parseColor("#2563EB")
                "WATER" -> Color.parseColor("#0EA5E9")
                "GARBAGE" -> Color.parseColor("#16A34A")
                "ELECTRIC" -> Color.parseColor("#F59E0B")
                "UTILITIES", "UTILITY" -> Color.parseColor("#7C3AED")
                else -> Color.parseColor("#7C3AED")
            }
            val iconBg = GradientDrawable().apply {
                shape = GradientDrawable.OVAL
                setColor(Color.argb(34, Color.red(typeColor), Color.green(typeColor), Color.blue(typeColor)))
            }
            binding.viewTypeIcon.background = iconBg
            binding.tvTypeIcon.setTextColor(typeColor)
            binding.tvTypeIcon.text = when (invoice.billingType.uppercase()) {
                "RENT" -> "R"
                "WATER" -> "W"
                "GARBAGE" -> "G"
                "ELECTRIC" -> "E"
                "UTILITIES", "UTILITY" -> "U"
                else -> "U"
            }

            val period = displayPeriod?.takeIf { it.isNotBlank() }
            binding.tvBillingType.text = listOfNotNull(
                invoice.billingType.toBillingLabel(),
                period
            ).joinToString(" · ")
            binding.tvTitle.text = invoice.description.orEmpty()
            binding.tvPeriod.text = period ?: "—"
            binding.tvProperty.text = listOfNotNull(
                invoice.unit?.property?.name,
                invoice.unit?.unitName
            ).joinToString(" · ").ifBlank { "—" }
            binding.tvAmount.text = effectiveTotal.toKes()
            binding.tvPaid.text = "Paid ${invoice.paidAmount.toKes()}"
            binding.tvDue.text = "Due ${invoice.dueDate.toDisplayDate()}"
            binding.tvStatus.text = invoice.status.toStatusLabel()

            val (badgeBg, badgeTextColor) = when (invoice.status.uppercase()) {
                "PAID" -> Pair(R.drawable.bg_badge_green, R.color.badge_green_text)
                "OVERDUE" -> Pair(R.drawable.bg_badge_red, R.color.badge_red_text)
                "CANCELLED" -> Pair(R.drawable.bg_badge_gray, R.color.badge_gray_text)
                else -> Pair(R.drawable.bg_badge_yellow, R.color.badge_yellow_text)
            }
            binding.tvStatus.setBackgroundResource(badgeBg)
            binding.tvStatus.setTextColor(
                ContextCompat.getColor(binding.tvStatus.context, badgeTextColor)
            )

            val canPay = invoice.status.uppercase() in setOf(
                "PENDING",
                "UNPAID",
                "PARTIAL",
                "PARTIALLY_PAID",
                "OVERDUE"
            ) && remaining > 0
            binding.btnPay.visibility = if (canPay) View.VISIBLE else View.GONE
            binding.btnPay.isEnabled = canPay

            binding.btnPay.setOnClickListener { onPayClick(invoice) }
            binding.btnExportPdf.setOnClickListener { onExportPdfClick(invoice) }
            binding.root.setOnClickListener { onCardClick(invoice) }
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder =
        ViewHolder(ItemInvoiceBinding.inflate(LayoutInflater.from(parent.context), parent, false))

    override fun onBindViewHolder(holder: ViewHolder, position: Int) =
        holder.bind(getItem(position))

    companion object {
        private val DIFF_CALLBACK = object : DiffUtil.ItemCallback<Invoice>() {
            override fun areItemsTheSame(old: Invoice, new: Invoice) = old.id == new.id
            override fun areContentsTheSame(old: Invoice, new: Invoice) = old == new
        }
    }
}
