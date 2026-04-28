package com.tenantpro.app.ui.rental

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import com.google.android.material.textview.MaterialTextView
import com.tenantpro.app.R
import com.tenantpro.app.databinding.FragmentRentalInfoBinding
import com.tenantpro.app.utils.showErrorSnackbar
import com.tenantpro.app.utils.showSuccessSnackbar
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

@AndroidEntryPoint
class RentalInfoFragment : Fragment() {

    private var _binding: FragmentRentalInfoBinding? = null
    private val binding get() = _binding!!
    private val viewModel: RentalInfoViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentRentalInfoBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.btnRefreshRental.setOnClickListener { viewModel.refreshRentalInfo() }

        binding.btnLinkRental.setOnClickListener {
            val code = binding.etRentalInvitationCode.text?.toString().orEmpty()
            viewModel.acceptInvitation(code)
            if (code.isNotBlank()) binding.etRentalInvitationCode.text?.clear()
        }

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                launch {
                    viewModel.uiState.collect { state ->
                        binding.progressRental.visibility =
                            if (state.loading) View.VISIBLE else View.GONE
                        binding.tvOutstandingValue.text = state.outstandingText
                        binding.tvPendingValue.text = state.pendingCount.toString()
                        binding.tvOverdueValue.text = state.overdueCount.toString()
                        bindUnits(state.units)
                    }
                }
                launch {
                    viewModel.events.collect { msg ->
                        if (msg.contains("success", ignoreCase = true) ||
                            msg.contains("accepted", ignoreCase = true)
                        ) {
                            showSuccessSnackbar(msg)
                        } else {
                            showErrorSnackbar(msg)
                        }
                    }
                }
            }
        }
    }

    private fun bindUnits(units: List<RentalUnitItem>) {
        binding.llRentalUnits.removeAllViews()

        if (units.isEmpty()) {
            binding.llRentalEmpty.visibility = View.VISIBLE
            return
        }

        binding.llRentalEmpty.visibility = View.GONE
        units.forEach { item ->
            val card = layoutInflater.inflate(
                R.layout.item_rental_unit,
                binding.llRentalUnits,
                false
            )
            card.findViewById<MaterialTextView>(R.id.tvUnitPropertyName).text = item.propertyName
            card.findViewById<MaterialTextView>(R.id.tvUnitNumber).text = "Unit ${item.unitNumber}"
            card.findViewById<MaterialTextView>(R.id.tvUnitFloor).text = item.floor ?: "—"
            card.findViewById<MaterialTextView>(R.id.tvUnitRent).text = item.rentAmountText ?: "—"
            card.findViewById<MaterialTextView>(R.id.tvUnitMoveIn).text = item.moveInDate
            card.findViewById<MaterialTextView>(R.id.tvUnitAddress).text = item.address
            binding.llRentalUnits.addView(card)
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
