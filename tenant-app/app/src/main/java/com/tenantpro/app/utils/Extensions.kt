package com.tenantpro.app.utils

import android.view.View
import android.widget.Toast
import android.content.Context
import androidx.fragment.app.Fragment
import androidx.appcompat.app.AppCompatActivity
import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.Locale
import java.util.TimeZone

// ─── View helpers ────────────────────────────────────────────────────────────

fun View.visible() { visibility = View.VISIBLE }
fun View.gone()    { visibility = View.GONE }
fun View.invisible() { visibility = View.INVISIBLE }

// ─── Toast shorthand ─────────────────────────────────────────────────────────

fun Fragment.toast(msg: String) =
    Toast.makeText(requireContext(), msg, Toast.LENGTH_SHORT).show()

fun Context.toast(msg: String) =
    Toast.makeText(this, msg, Toast.LENGTH_SHORT).show()

fun AppCompatActivity.toast(msg: String) =
    Toast.makeText(this, msg, Toast.LENGTH_SHORT).show()

// ─── Formatting helpers ───────────────────────────────────────────────────────

private val kenyaLocale = Locale("en", "KE")

/** Formats a Double as Kenyan Shillings, e.g. "KES 12,500.00" */
fun Double.toKes(): String =
    NumberFormat.getCurrencyInstance(kenyaLocale).format(this)

/**
 * Parses an ISO-8601 date string and returns a human-readable date,
 * e.g. "26 Mar 2026".
 */
fun String?.toDisplayDate(): String {
    if (isNullOrBlank()) return "—"
    return try {
        val sdf = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSS'Z'", Locale.getDefault()).apply {
            timeZone = TimeZone.getTimeZone("UTC")
        }
        val parsed = sdf.parse(this) ?: return this
        SimpleDateFormat("d MMM yyyy", Locale.getDefault()).format(parsed)
    } catch (e: Exception) {
        this
    }
}

/** Maps backend invoice status string to a user-friendly label. */
fun String.toStatusLabel(): String = when (this) {
    "PENDING"   -> "Pending"
    "PAID"      -> "Paid"
    "OVERDUE"   -> "Overdue"
    "CANCELLED" -> "Cancelled"
    else        -> this
}

/** Maps backend billing type to a user-friendly label. */
fun String.toBillingLabel(): String = when (this) {
    "RENT"    -> "Rent"
    "WATER"   -> "Water"
    "GARBAGE" -> "Garbage"
    else      -> this
}
