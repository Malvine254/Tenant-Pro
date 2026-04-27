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
import com.tenantpro.app.utils.showErrorSnackbar
import com.tenantpro.app.utils.showSuccessSnackbar
import com.tenantpro.app.utils.toKes
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

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

        val invoiceId = arguments?.getString("invoiceId") ?: ""
        val invoiceLabel = arguments?.getString("invoiceLabel") ?: ""
        val remainingAmount = arguments?.getFloat("remainingAmount") ?: 0f

        binding.tvInvoiceLabel.text = invoiceLabel
        binding.tvRemainingAmount.text = remainingAmount.toDouble().toKes()

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

        binding.switchSimulation.setOnCheckedChangeListener { _, isChecked ->
            viewModel.setSimulationEnabled(isChecked)
            binding.tvSimulationHint.text = if (isChecked) {
                getString(com.tenantpro.app.R.string.mpesa_simulation_hint)
            } else {
                getString(com.tenantpro.app.R.string.mpesa_live_hint)
            }
        }

        binding.btnBack.setOnClickListener { findNavController().popBackStack() }

        binding.btnPay.setOnClickListener {
            val phone = binding.etPhone.text?.toString()?.trim() ?: return@setOnClickListener
            val amountText = binding.etAmount.text?.toString()?.trim()
            val amount = amountText?.toDoubleOrNull()

            if (phone.isBlank()) {
                binding.tilPhone.error = getString(com.tenantpro.app.R.string.error_phone_required)
                return@setOnClickListener
            }
            binding.tilPhone.error = null

            val displayAmount = if (amount != null) amount.toKes() else remainingAmount.toDouble().toKes()
            MaterialAlertDialogBuilder(requireContext())
                .setTitle("Confirm Payment")
                .setMessage("Send M-Pesa STK push of $displayAmount to $phone?\n\nYou will receive a prompt on your phone to enter your PIN.")
                .setPositiveButton("Confirm") { _, _ -> viewModel.pay(invoiceId, phone, amount) }
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
                            val simulated = binding.switchSimulation.isChecked
                            viewModel.reset()
                            val msg = if (simulated)
                                getString(com.tenantpro.app.R.string.mpesa_sim_success)
                            else
                                getString(com.tenantpro.app.R.string.mpesa_live_success)
                            showSuccessSnackbar(msg)
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
