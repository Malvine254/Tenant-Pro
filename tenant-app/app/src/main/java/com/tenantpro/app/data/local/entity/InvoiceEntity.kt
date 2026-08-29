package com.tenantpro.app.data.local.entity

import androidx.room.Entity
import androidx.room.PrimaryKey
import com.tenantpro.app.data.model.Invoice
import com.tenantpro.app.data.model.PropertySummary
import com.tenantpro.app.data.model.UnitSummary

@Entity(tableName = "invoices")
data class InvoiceEntity(
    @PrimaryKey val id: String,
    val billingType: String,
    val status: String,
    val totalAmount: Double,
    val paidAmount: Double,
    val dueDate: String?,
    val billingPeriod: String?,
    val description: String?,
    // Denormalised unit / property fields
    val unitId: String?,
    val unitName: String?,
    val propertyId: String?,
    val propertyName: String?,
    val createdAt: String,
    val cachedAt: Long = System.currentTimeMillis()
)

// ── Mappers ───────────────────────────────────────────────────────────────────

fun Invoice.toEntity() = InvoiceEntity(
    id            = id,
    billingType   = billingType,
    status        = status,
    totalAmount   = totalAmount,
    paidAmount    = paidAmount,
    dueDate       = dueDate,
    billingPeriod = billingPeriod,
    description   = description,
    unitId        = unit?.id,
    unitName      = unit?.unitName,
    propertyId    = unit?.property?.id,
    propertyName  = unit?.property?.name,
    createdAt     = createdAt
)

fun InvoiceEntity.toInvoice() = Invoice(
    id            = id,
    billingType   = billingType,
    status        = status,
    totalAmount   = totalAmount,
    paidAmount    = paidAmount,
    dueDate       = dueDate,
    billingPeriod = billingPeriod,
    description   = description,
    unit          = if (unitId != null) UnitSummary(
        id       = unitId,
        unitName = unitName ?: "",
        property = if (propertyId != null) PropertySummary(propertyId, propertyName ?: "") else null
    ) else null,
    createdAt     = createdAt
)
