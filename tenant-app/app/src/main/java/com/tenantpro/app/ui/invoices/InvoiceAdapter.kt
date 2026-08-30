package com.tenantpro.app.ui.invoices

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.tenantpro.app.R
import com.tenantpro.app.data.model.Invoice
import com.tenantpro.app.databinding.ItemInvoiceGroupBinding
import com.tenantpro.app.utils.toBillingLabel
import com.tenantpro.app.utils.toInvoiceDate
import com.tenantpro.app.utils.toKes
import java.time.LocalDate

data class InvoiceGroup(val key: String, val title: String, val invoices: List<Invoice>) {
    val total: Double get() = invoices.sumOf { it.effectiveTotalAmount() }
    val balance: Double get() = invoices.sumOf { it.effectiveBalance() }
}

class InvoiceAdapter(
    private val onGroupClick: (InvoiceGroup) -> Unit,
    private val onGroupPayClick: (InvoiceGroup) -> Unit,
    private val onGroupDownloadClick: (InvoiceGroup) -> Unit
) : ListAdapter<InvoiceGroup, InvoiceAdapter.ViewHolder>(DIFF_CALLBACK) {

    inner class ViewHolder(private val binding: ItemInvoiceGroupBinding) : RecyclerView.ViewHolder(binding.root) {
        fun bind(group: InvoiceGroup) {
            binding.tvGroupTitle.text = group.title
            binding.tvGroupTypes.text = group.invoices.map { it.billingType.toBillingLabel() }.distinct().joinToString(" · ")
            val earliestDueDate = group.invoices.mapNotNull { it.dueDate?.take(10) }.minOrNull()
            val isUpcoming = earliestDueDate?.let { it > LocalDate.now().toString() } == true
            binding.tvGroupDueDate.text = when {
                earliestDueDate == null -> "Due date not set"
                isUpcoming -> "Upcoming  ·  Due ${earliestDueDate.toInvoiceDate()}"
                else -> "Due ${earliestDueDate.toInvoiceDate()}"
            }
            binding.tvGroupDueDate.setTextColor(
                binding.root.context.getColor(if (isUpcoming) R.color.info else R.color.on_surface_variant)
            )
            binding.tvGroupTotal.text = group.total.toKes()
            binding.tvGroupBalance.text = when {
                group.balance <= 0.0 -> "All bills paid"
                group.invoices.size == 1 -> "Balance ${group.balance.toKes()}"
                else -> "${group.invoices.size} bills · Balance ${group.balance.toKes()}"
            }
            binding.tvGroupBalance.setTextColor(binding.root.context.getColor(
                if (group.balance <= 0.0) R.color.success else R.color.error
            ))
            binding.btnPayGroup.visibility = if (group.balance > 0.0) android.view.View.VISIBLE else android.view.View.GONE
            val payableCount = group.invoices.count { invoice ->
                invoice.status.uppercase() != "CANCELLED" && invoice.effectiveBalance() > 0.0
            }
            binding.btnPayGroup.text = if (payableCount > 1) "Pay $payableCount bills" else "Pay"
            binding.btnPayGroup.setOnClickListener { onGroupPayClick(group) }
            binding.btnDownloadGroup.setOnClickListener { onGroupDownloadClick(group) }
            binding.root.setOnClickListener { onGroupClick(group) }
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder =
        ViewHolder(ItemInvoiceGroupBinding.inflate(LayoutInflater.from(parent.context), parent, false))

    override fun onBindViewHolder(holder: ViewHolder, position: Int) = holder.bind(getItem(position))

    companion object {
        private val DIFF_CALLBACK = object : DiffUtil.ItemCallback<InvoiceGroup>() {
            override fun areItemsTheSame(old: InvoiceGroup, new: InvoiceGroup) = old.key == new.key
            override fun areContentsTheSame(old: InvoiceGroup, new: InvoiceGroup) = old == new
        }
    }
}
