package com.tenantpro.app.ui.payment

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

        binding.btnPay.setOnClickListener {
            val phone = binding.etPhone.text?.toString()?.trim() ?: return@setOnClickListener
            val amountText = binding.etAmount.text?.toString()?.trim()
            val amount = amountText?.toDoubleOrNull()
            val normalizedPhone = phone.normalizeKenyanPhone()

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
                            binding.btnPay.isEnabled = true
                            viewModel.reset()
                            showSuccessSnackbar(
                                state.data.message ?: "Payment completed successfully."
                            )
                            findNavController().popBackStack()
                        }
                        is Resource.Error -> {
                            binding.progressBar.gone()
                            binding.btnPay.isEnabled = true
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

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
