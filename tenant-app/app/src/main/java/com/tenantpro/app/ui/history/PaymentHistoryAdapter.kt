package com.tenantpro.app.ui.history

import android.graphics.Color
import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.google.gson.JsonElement
import com.google.gson.JsonParser
import com.tenantpro.app.R
import com.tenantpro.app.data.model.Payment
import com.tenantpro.app.databinding.ItemPaymentHistoryBinding
import com.tenantpro.app.utils.toKes
import java.text.DateFormatSymbols
import java.time.Instant
import java.time.LocalDateTime
import java.time.OffsetDateTime
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

class PaymentHistoryAdapter :
    ListAdapter<Payment, PaymentHistoryAdapter.ViewHolder>(DIFF_CALLBACK) {

    inner class ViewHolder(private val binding: ItemPaymentHistoryBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(payment: Payment) {
            val callback = payment.transactions
                ?.firstOrNull { it.type.contains("CALLBACK", ignoreCase = true) }
            val receipt = payment.mpesaReceiptNumber?.takeIf { it.isNotBlank() }
                ?: callback?.externalReference?.takeIf { it.isNotBlank() }
                ?: callbackMetadataValue(callback?.rawPayload, "MpesaReceiptNumber")
                ?: callback?.description?.let(::receiptFromDescription)
            val phone = payment.phoneNumber?.takeIf { it.isNotBlank() }
                ?: callbackMetadataValue(callback?.rawPayload, "PhoneNumber")
            val paymentTime = payment.paidAt?.takeIf { it.isNotBlank() }
                ?: callback?.processedAt?.takeIf { it.isNotBlank() }
                ?: callbackMetadataValue(callback?.rawPayload, "TransactionDate")
                ?: callback?.createdAt?.takeIf { it.isNotBlank() }
                ?: payment.createdAt.takeIf { it.isNotBlank() }

            binding.tvAmount.text = payment.amount.toKes()
            binding.tvDate.text = formatPaymentDateTime(paymentTime)
            binding.tvPhone.text = phone ?: "—"
            binding.tvReceipt.text = receipt
                ?: if (payment.status.equals("PENDING", ignoreCase = true)) "Pending" else "Not available"
            binding.tvStatus.text = payment.status
                .lowercase(Locale.getDefault())
                .replaceFirstChar { it.titlecase(Locale.getDefault()) }

            val invoice = payment.invoice
            val period = invoice?.billingPeriod?.takeIf { it.isNotBlank() }
                ?: if (invoice?.periodMonth in 1..12 && invoice?.periodYear != null) {
                    "${DateFormatSymbols(Locale.getDefault()).shortMonths[invoice.periodMonth!! - 1]} ${invoice.periodYear}"
                } else null
            val allocationCount = payment.metadata?.invoiceAllocations?.size ?: 0
            val billType = if (allocationCount > 1) {
                "$allocationCount bills"
            } else {
                invoice?.billingType
                    ?.lowercase(Locale.getDefault())
                    ?.replaceFirstChar { it.titlecase(Locale.getDefault()) }
                    ?: "Payment"
            }
            binding.tvInvoice.text = listOfNotNull(billType, period).joinToString(" · ")

            val (textColor, bgRes) = when (payment.status.uppercase(Locale.ROOT)) {
                "SUCCESS", "SUCCESSFUL" -> Color.parseColor("#14532d") to R.drawable.bg_badge_green
                "FAILED", "REVERSED" -> Color.parseColor("#7f1d1d") to R.drawable.bg_badge_red
                else -> Color.parseColor("#78350f") to R.drawable.bg_badge_yellow
            }
            binding.tvStatus.setTextColor(textColor)
            binding.tvStatus.setBackgroundResource(bgRes)
        }

        private fun callbackMetadataValue(rawPayload: JsonElement?, name: String): String? =
            runCatching {
                if (rawPayload == null || rawPayload.isJsonNull) return@runCatching null
                val root = if (rawPayload.isJsonPrimitive && rawPayload.asJsonPrimitive.isString) {
                    JsonParser.parseString(rawPayload.asString)
                } else rawPayload
                val rootObject = root.asJsonObject
                val callbackObject = rootObject.getAsJsonObject("Body")
                    ?.getAsJsonObject("stkCallback")
                    ?: rootObject
                val items = callbackObject
                    .getAsJsonObject("CallbackMetadata")
                    .getAsJsonArray("Item")
                items.firstNotNullOfOrNull { item ->
                    val obj = item.asJsonObject
                    if (obj.get("Name")?.asString == name) obj.get("Value")?.asString else null
                }
            }.getOrNull()

        private fun receiptFromDescription(description: String): String? =
            Regex("(?:receipt|code)\\s+([A-Za-z0-9-]+)", RegexOption.IGNORE_CASE)
                .find(description)
                ?.groupValues
                ?.getOrNull(1)

        private fun formatPaymentDateTime(value: String?): String {
            if (value.isNullOrBlank()) return "—"
            val output = DateTimeFormatter.ofPattern("d MMM yyyy, h:mm a", Locale.getDefault())
            val zone = ZoneId.systemDefault()
            return runCatching {
                when {
                    value.matches(Regex("\\d{14}")) -> LocalDateTime.parse(
                        value,
                        DateTimeFormatter.ofPattern("yyyyMMddHHmmss")
                    ).format(output)
                    else -> OffsetDateTime.parse(value).atZoneSameInstant(zone).format(output)
                }
            }.recoverCatching {
                Instant.parse(value).atZone(zone).format(output)
            }.recoverCatching {
                LocalDateTime.parse(value, DateTimeFormatter.ISO_LOCAL_DATE_TIME).format(output)
            }.getOrDefault(value)
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
