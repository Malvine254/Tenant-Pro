package com.tenantpro.app.utils

import android.graphics.Color
import android.view.View
import android.widget.Toast
import android.content.Context
import android.view.inputmethod.InputMethodManager
import androidx.fragment.app.Fragment
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.google.android.material.snackbar.Snackbar
import com.tenantpro.app.BuildConfig
import java.text.NumberFormat
import java.util.Locale

// ─── View helpers ────────────────────────────────────────────────────────────

fun View.visible() { visibility = View.VISIBLE }
fun View.gone()    { visibility = View.GONE }
fun View.invisible() { visibility = View.INVISIBLE }

/** Clears field focus and closes the software keyboard when an action starts. */
fun Fragment.dismissKeyboard() {
    val root = view ?: return
    root.findFocus()?.clearFocus()
    ViewCompat.getWindowInsetsController(root)?.hide(WindowInsetsCompat.Type.ime())
    (requireContext().getSystemService(Context.INPUT_METHOD_SERVICE) as? InputMethodManager)
        ?.hideSoftInputFromWindow(root.windowToken, 0)
}

// ─── Toast shorthand ─────────────────────────────────────────────────────────

fun Fragment.toast(msg: String) =
    Toast.makeText(requireContext(), msg, Toast.LENGTH_SHORT).show()

fun Context.toast(msg: String) =
    Toast.makeText(this, msg, Toast.LENGTH_SHORT).show()

fun AppCompatActivity.toast(msg: String) =
    Toast.makeText(this, msg, Toast.LENGTH_SHORT).show()

// ─── Snackbar helpers ────────────────────────────────────────────────────────

fun Fragment.showSnackbar(msg: String, duration: Int = Snackbar.LENGTH_SHORT) =
    Snackbar.make(requireView(), msg, duration).show()

fun Fragment.showSuccessSnackbar(msg: String) {
    val snack = Snackbar.make(requireView(), msg, Snackbar.LENGTH_SHORT)
    snack.view.setBackgroundColor(Color.parseColor("#16A34A"))
    snack.setTextColor(Color.WHITE)
    snack.show()
}

fun Fragment.showErrorSnackbar(msg: String, action: String? = null, onAction: (() -> Unit)? = null) {
    val snack = Snackbar.make(requireView(), msg, Snackbar.LENGTH_LONG)
    snack.view.setBackgroundColor(Color.parseColor("#DC2626"))
    snack.setTextColor(Color.WHITE)
    if (action != null && onAction != null) {
        snack.setAction(action) { onAction() }
        snack.setActionTextColor(Color.WHITE)
    }
    snack.show()
}

fun Fragment.showInfoSnackbar(msg: String) {
    val snack = Snackbar.make(requireView(), msg, Snackbar.LENGTH_SHORT)
    snack.view.setBackgroundColor(Color.parseColor("#4338CA"))
    snack.setTextColor(Color.WHITE)
    snack.show()
}

// ─── Formatting helpers ───────────────────────────────────────────────────────

private val kenyaLocale = Locale("en", "KE")

/** Formats a Double as Kenyan Shillings, e.g. "KES 12,500.00" */
fun Double.toKes(): String {
    val nf = NumberFormat.getNumberInstance(Locale.US)
    nf.maximumFractionDigits = if (this % 1.0 == 0.0) 0 else 2
    nf.minimumFractionDigits = 0
    return "KES ${nf.format(this)}"
}

fun String.normalizeKenyanPhone(): String? {
    val digits = trim().replace(Regex("[\\s-]"), "").removePrefix("+")
    return when {
        Regex("^07\\d{8}$").matches(digits) -> "254${digits.drop(1)}"
        Regex("^01\\d{8}$").matches(digits) -> "254${digits.drop(1)}"
        Regex("^2547\\d{8}$").matches(digits) -> digits
        Regex("^2541\\d{8}$").matches(digits) -> digits
        else -> null
    }
}

fun String.toAbsoluteAssetUrl(): String {
    if (startsWith("http://") || startsWith("https://") || startsWith("content://")) return this
    val apiBase = BuildConfig.BASE_URL.trimEnd('/')
    val serverBase = apiBase.removeSuffix("/api")
    return "$serverBase/${trimStart('/')}"
}

/**
 * Parses an ISO date and returns the app-wide month-first format,
 * e.g. "Aug 31, 2026".
 */
fun String?.toDisplayDate(): String {
    return toInvoiceDate()
}

/** Formats an invoice date month-first, e.g. "Aug 31, 2026". */
fun String?.toInvoiceDate(): String {
    if (isNullOrBlank()) return "—"
    return runCatching {
        val date = java.time.LocalDate.parse(take(10))
        date.format(java.time.format.DateTimeFormatter.ofPattern("MMM d, yyyy", Locale.getDefault()))
    }.getOrDefault(this)
}

/** Maps backend invoice status string to a user-friendly label. */
fun String.toStatusLabel(): String = when (this.uppercase()) {
    "PENDING"   -> "Pending"
    "UNPAID"    -> "Unpaid"
    "PARTIAL"   -> "Partially Paid"
    "PARTIALLY_PAID" -> "Partially Paid"
    "PAID"      -> "Paid"
    "OVERDUE"   -> "Overdue"
    "CANCELLED" -> "Cancelled"
    "WAIVED"    -> "Waived"
    "IN_PROGRESS" -> "In Progress"
    "WAITING_TENANT" -> "Waiting for Tenant"
    "PAYMENT_FAILED" -> "Payment Failed"
    else        -> this
}

/** Maps backend billing type to a user-friendly label. */
fun String.toBillingLabel(): String = when (this.uppercase()) {
    "RENT"    -> "Rent"
    "WATER"   -> "Water"
    "GARBAGE" -> "Garbage"
    "ELECTRIC" -> "Electricity"
    "OTHER" -> "Other"
    else      -> this
}
