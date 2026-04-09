package com.tenantpro.app.ui.history

import android.graphics.Color
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.tenantpro.app.R
import com.tenantpro.app.data.model.Payment
import com.tenantpro.app.databinding.ItemPaymentHistoryBinding
import com.tenantpro.app.utils.toDisplayDate
import com.tenantpro.app.utils.toKes

class PaymentHistoryAdapter :
    ListAdapter<Payment, PaymentHistoryAdapter.ViewHolder>(DIFF_CALLBACK) {

    inner class ViewHolder(private val binding: ItemPaymentHistoryBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(payment: Payment) {
            binding.tvAmount.text          = payment.amount.toKes()
            binding.tvDate.text            = payment.createdAt.toDisplayDate()
            binding.tvPhone.text           = payment.phoneNumber ?: "—"
            binding.tvReceipt.text         = payment.mpesaReceiptNumber ?: "Pending"
            binding.tvStatus.text          = payment.status.replaceFirstChar { it.uppercase() }

            val (textColor, bgRes) = when (payment.status) {
                "SUCCESS"  -> Color.parseColor("#14532d") to R.drawable.bg_badge_green
                "FAILED",
                "REVERSED" -> Color.parseColor("#7f1d1d") to R.drawable.bg_badge_red
                else       -> Color.parseColor("#78350f") to R.drawable.bg_badge_yellow
            }
            binding.tvStatus.setTextColor(textColor)
            binding.tvStatus.setBackgroundResource(bgRes)
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder =
        ViewHolder(
            ItemPaymentHistoryBinding.inflate(
                LayoutInflater.from(parent.context), parent, false
            )
        )

    override fun onBindViewHolder(holder: ViewHolder, position: Int) =
        holder.bind(getItem(position))

    companion object {
        private val DIFF_CALLBACK = object : DiffUtil.ItemCallback<Payment>() {
            override fun areItemsTheSame(old: Payment, new: Payment) = old.id == new.id
            override fun areContentsTheSame(old: Payment, new: Payment) = old == new
        }
    }
}
