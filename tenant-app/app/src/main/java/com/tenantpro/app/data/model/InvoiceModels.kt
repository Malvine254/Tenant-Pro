package com.tenantpro.app.data.model

import com.google.gson.annotations.SerializedName

// ─── Invoice ─────────────────────────────────────────────────────────────────

data class Invoice(
    @SerializedName("id") val id: String,
    @SerializedName("billingType") val billingType: String,      // RENT | WATER | GARBAGE
    @SerializedName("status") val status: String,                // PENDING | PAID | OVERDUE | CANCELLED
    @SerializedName("totalAmount") val totalAmount: Double,
    @SerializedName("paidAmount") val paidAmount: Double,
    @SerializedName("dueDate") val dueDate: String?,
    @SerializedName("billingPeriod") val billingPeriod: String?,
    @SerializedName("description") val description: String?,
    @SerializedName("unit") val unit: UnitSummary?,
    @SerializedName("createdAt") val createdAt: String
)

data class UnitSummary(
    @SerializedName("id") val id: String,
    @SerializedName(value = "unitName", alternate = ["unitNumber"]) val unitName: String,
    @SerializedName("floor") val floor: String? = null,
    @SerializedName("rentAmount") val rentAmount: Double? = null,
    @SerializedName("imageUrls") val imageUrls: List<String>? = null,
    @SerializedName("property") val property: PropertySummary?
)

data class PropertySummary(
    @SerializedName("id") val id: String,
    @SerializedName("name") val name: String,
    @SerializedName("description") val description: String? = null,
    @SerializedName("coverImageUrl") val coverImageUrl: String? = null,
    @SerializedName("addressLine") val addressLine: String? = null,
    @SerializedName("city") val city: String? = null,
    @SerializedName("state") val state: String? = null,
    @SerializedName("country") val country: String? = null
)
