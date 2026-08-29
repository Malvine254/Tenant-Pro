package com.tenantpro.app.ui.payment

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.navigation.fragment.findNavController
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.tenantpro.app.data.model.ManualPaymentInstructions
import com.tenantpro.app.databinding.FragmentPaymentBinding
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.normalizeKenyanPhone
import com.tenantpro.app.utils.showErrorSnackbar
import com.tenantpro.app.utils.showSuccessSnackbar
import com.tenantpro.app.utils.toKes
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch
import java.util.Locale

@AndroidEntryPoint
class PaymentFragment : Fragment() {

    private var _binding: FragmentPaymentBinding? = null
    private val binding get() = _binding!!
    private val viewModel: PaymentViewModel by viewModels()
    private var stkPaymentConfigured = false

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentPaymentBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val invoiceIds = arguments?.getStringArrayList("invoiceIds")
            ?.filter { it.isNotBlank() }
            ?.distinct()
            ?.takeIf { it.isNotEmpty() }
            ?: listOfNotNull(arguments?.getString("invoiceId")?.takeIf { it.isNotBlank() })
        val invoiceLabel = arguments?.getString("invoiceLabel") ?: ""
        val remainingAmount = arguments?.getFloat("remainingAmount") ?: 0f

        binding.tvInvoiceLabel.text = invoiceLabel
        binding.tvRemainingAmount.text = remainingAmount.toDouble().toKes()
        if (binding.etAmount.text.isNullOrBlank() && remainingAmount > 0f) {
            binding.etAmount.setText(
                String.format(Locale.US, "%.2f", remainingAmount)
                    .removeSuffix(".00")
            )
        }

        // Pre-fill phone from DataStore
        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.savedPhone.collect { phone ->
                    if (!phone.isNullOrBlank() && binding.etPhone.text.isNullOrBlank()) {
                        binding.etPhone.setText(phone)
                    }
                }
            }
        }

        // Amount hint
        binding.tilAmount.helperText =
            getString(
                com.tenantpro.app.R.string.mpesa_full_balance_hint,
                remainingAmount.toDouble().toKes()
            )

        viewModel.loadManualInstructions(invoiceIds)

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.manualInstructions.collect { state ->
                    when (state) {
                        is Resource.Loading -> {
                            stkPaymentConfigured = false
                            binding.btnPay.isEnabled = false
                            binding.progressManualPayment.visible()
                            binding.tvManualStatus.gone()
                            binding.layoutManualDetails.gone()
                        }
                        is Resource.Success -> {
                            binding.progressManualPayment.gone()
                            val details = state.data
                            stkPaymentConfigured = details.stkAvailable
                            binding.btnPay.isEnabled = stkPaymentConfigured
                            if (!details.available) {
                                binding.layoutManualDetails.gone()
                                binding.tvManualStatus.text = details.message
                                    ?: "Manual payment details are not available. Contact your property manager in Chat."
                                binding.tvManualStatus.visible()
                            } else {
                                binding.tvManualStatus.gone()
                                showManualInstructions(details)
                            }
                        }
                        is Resource.Error -> {
                            stkPaymentConfigured = false
                            binding.btnPay.isEnabled = false
                            binding.progressManualPayment.gone()
                            binding.layoutManualDetails.gone()
                            binding.tvManualStatus.text =
                                "Could not verify the landlord's M-Pesa setup. Reconnect and reopen this page, or contact your property manager in Chat."
                            binding.tvManualStatus.visible()
                        }
                        null -> Unit
                    }
                }
            }
        }

        binding.btnPay.setOnClickListener {
            val phone = binding.etPhone.text?.toString()?.trim() ?: return@setOnClickListener
            val amountText = binding.etAmount.text?.toString()?.trim()
            val amount = amountText?.toDoubleOrNull()
            val normalizedPhone = phone.normalizeKenyanPhone()

            if (invoiceIds.isEmpty()) {
                showErrorSnackbar("The selected bill is unavailable. Return to Home and try again.")
                return@setOnClickListener
            }

            if (phone.isBlank()) {
                binding.tilPhone.error = getString(com.tenantpro.app.R.string.error_phone_required)
                return@setOnClickListener
            }
            if (normalizedPhone == null) {
                binding.tilPhone.error = "Enter a valid Kenyan M-Pesa number"
                return@setOnClickListener
            }
            if (remainingAmount <= 0f) {
                showErrorSnackbar("This invoice has no unpaid balance.")
                return@setOnClickListener
            }
            if (amount != null && (amount <= 0.0 || amount > remainingAmount.toDouble())) {
                binding.tilAmount.error = "Enter an amount up to ${remainingAmount.toDouble().toKes()}"
                return@setOnClickListener
            }
            binding.tilPhone.error = null
            binding.tilAmount.error = null

            val displayAmount = if (amount != null) amount.toKes() else remainingAmount.toDouble().toKes()
            MaterialAlertDialogBuilder(requireContext(), com.tenantpro.app.R.style.Theme_TenantPro_Dialog_Form)
                .setTitle("Confirm M-Pesa Payment")
                .setMessage("Send an STK prompt for $displayAmount to $phone?\n\nConfirm on your phone using your M-Pesa PIN.")
                .setPositiveButton("Send prompt") { _, _ -> viewModel.pay(invoiceIds, normalizedPhone, amount) }
                .setNegativeButton("Cancel", null)
                .show()
        }

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.payState.collect { state ->
                    when (state) {
                        is Resource.Loading -> {
                            binding.progressBar.visible()
                            binding.btnPay.isEnabled = false
                        }
                        is Resource.Success -> {
                            binding.progressBar.gone()
                            binding.btnPay.isEnabled = stkPaymentConfigured
                            viewModel.reset()
                            showSuccessSnackbar(
                                state.data.message ?: "Payment completed successfully."
                            )
                            findNavController().popBackStack()
                        }
                        is Resource.Error -> {
                            binding.progressBar.gone()
                            binding.btnPay.isEnabled = stkPaymentConfigured
                            showErrorSnackbar(state.message ?: "Payment failed", "Retry") {
                                binding.btnPay.performClick()
                            }
                            viewModel.reset()
                        }
                        null -> binding.progressBar.gone()
                    }
                }
            }
        }
    }

    private fun showManualInstructions(details: ManualPaymentInstructions) {
        val isTill = details.paymentType.equals("TILL", ignoreCase = true)
        binding.tvManualBusinessName.text = details.businessName.orEmpty()
        if (details.businessName.isNullOrBlank()) binding.tvManualBusinessName.gone()
        else binding.tvManualBusinessName.visible()

        binding.tvManualNumber.text = if (isTill) {
            "Till number: ${details.businessNumber}"
        } else {
            "Paybill number: ${details.businessNumber}"
        }
        if (isTill) {
            binding.tvManualReference.gone()
        } else {
            binding.tvManualReference.text = "Account number: ${details.accountReference.orEmpty()}"
            binding.tvManualReference.visible()
        }

        binding.tvManualSteps.text = manualInstructionsText(details)
        binding.btnCopyManualInstructions.setOnClickListener {
            val amount = binding.etAmount.text?.toString()?.trim().orEmpty()
            val copiedText = buildString {
                append(manualInstructionsText(details))
                if (amount.isNotBlank()) append("\nAmount: KES $amount")
            }
            val clipboard = requireContext().getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
            clipboard.setPrimaryClip(ClipData.newPlainText("M-Pesa payment instructions", copiedText))
            showSuccessSnackbar("Payment instructions copied")
        }
        binding.layoutManualDetails.visible()
    }

    private fun manualInstructionsText(details: ManualPaymentInstructions): String {
        val isTill = details.paymentType.equals("TILL", ignoreCase = true)
        return buildString {
            append("1. Open M-PESA and choose Lipa na M-PESA.\n")
            if (isTill) {
                append("2. Choose Buy Goods and Services.\n")
                append("3. Enter Till number ${details.businessNumber}.\n")
                append("4. Enter the amount shown above and your M-PESA PIN.\n")
                append("5. Confirm the business name before sending.")
            } else {
                append("2. Choose Pay Bill.\n")
                append("3. Enter Business number ${details.businessNumber}.\n")
                append("4. Enter Account number ${details.accountReference.orEmpty()}.\n")
                append("5. Enter the amount shown above and your M-PESA PIN.\n")
                append("6. Confirm the details before sending.")
            }
            details.note?.takeIf { it.isNotBlank() }?.let { append("\n\nNote: $it") }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
