package com.tenantpro.app.data.model

import com.google.gson.annotations.SerializedName
import java.text.SimpleDateFormat
import java.util.GregorianCalendar
import java.util.Locale

// ─── Invoice ─────────────────────────────────────────────────────────────────

data class Invoice(
    @SerializedName("id") val id: String = "",
    @SerializedName(value = "billingType", alternate = ["billing_type"]) val billingType: String = "RENT",
    @SerializedName("status") val status: String = "UNPAID",
    @SerializedName("amount") val amount: Double = 0.0,
    @SerializedName(value = "totalAmount", alternate = ["total_amount"]) val totalAmount: Double = 0.0,
    @SerializedName(value = "paidAmount", alternate = ["paid_amount"]) val paidAmount: Double = 0.0,
    @SerializedName(value = "dueDate", alternate = ["due_date"]) val dueDate: String? = null,
    @SerializedName(value = "billingPeriod", alternate = ["billing_period"]) val billingPeriod: String? = null,
    @SerializedName(value = "periodMonth", alternate = ["period_month"]) val periodMonth: Int? = null,
    @SerializedName(value = "periodYear", alternate = ["period_year"]) val periodYear: Int? = null,
    @SerializedName("description") val description: String? = null,
    @SerializedName("unit") val unit: UnitSummary? = null,
    @SerializedName(value = "createdAt", alternate = ["created_at"]) val createdAt: String = ""
) {
    fun effectiveTotalAmount(): Double = if (totalAmount > 0.0) totalAmount else amount

    fun effectiveBalance(): Double = (effectiveTotalAmount() - paidAmount).coerceAtLeast(0.0)

    fun displayPeriod(): String? {
        billingPeriod?.takeIf { it.isNotBlank() }?.let { return it }
        val month = periodMonth
        val year = periodYear
        if (month != null && year != null && month in 1..12) {
            val date = GregorianCalendar(year, month - 1, 1).time
            return SimpleDateFormat("MMM yyyy", Locale.getDefault()).format(date)
        }
        return null
    }
}

data class UnitSummary(
    @SerializedName("id") val id: String = "",
    @SerializedName(value = "unitName", alternate = ["unitNumber", "unit_number"]) val unitName: String = "",
    @SerializedName("floor") val floor: String? = null,
    @SerializedName(value = "rentAmount", alternate = ["rent_amount"]) val rentAmount: Double? = null,
    @SerializedName(value = "imageUrls", alternate = ["image_urls"]) val imageUrls: List<String>? = null,
    @SerializedName(value = "displayImageUrl", alternate = ["display_image_url"]) val displayImageUrl: String? = null,
    @SerializedName("property") val property: PropertySummary? = null
)

data class PropertySummary(
    @SerializedName("id") val id: String = "",
    @SerializedName("name") val name: String = "",
    @SerializedName("description") val description: String? = null,
    @SerializedName(value = "coverImageUrl", alternate = ["cover_image_url"]) val coverImageUrl: String? = null,
    @SerializedName(value = "addressLine", alternate = ["address_line"]) val addressLine: String? = null,
    @SerializedName("city") val city: String? = null,
    @SerializedName("state") val state: String? = null,
    @SerializedName("country") val country: String? = null,
    @SerializedName("landlord") val landlord: PropertyManagerSummary? = null
)

data class PropertyManagerSummary(
    @SerializedName("id") val id: String = "",
    @SerializedName(value = "phoneNumber", alternate = ["phone_number"]) val phoneNumber: String = "",
    @SerializedName("email") val email: String? = null,
    @SerializedName(value = "firstName", alternate = ["first_name"]) val firstName: String? = null,
    @SerializedName(value = "lastName", alternate = ["last_name"]) val lastName: String? = null,
    @SerializedName(value = "fullName", alternate = ["full_name"]) val fullName: String? = null
)
