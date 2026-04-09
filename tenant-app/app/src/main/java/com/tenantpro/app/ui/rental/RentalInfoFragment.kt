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
import com.tenantpro.app.databinding.FragmentRentalInfoBinding
import com.tenantpro.app.utils.toast
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

        binding.btnRefreshRental.setOnClickListener {
            viewModel.refreshRentalInfo()
        }

        binding.btnLinkRental.setOnClickListener {
            val code = binding.etRentalInvitationCode.text?.toString().orEmpty()
            viewModel.acceptInvitation(code)
            if (code.isNotBlank()) binding.etRentalInvitationCode.text?.clear()
        }

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                launch {
                    viewModel.uiState.collect { state ->
                        binding.progressRental.visibility = if (state.loading) View.VISIBLE else View.GONE
                        binding.tvRentalProperty.text = state.apartment.propertyName
                        binding.tvRentalUnit.text = state.apartment.unitName
                        binding.tvRentalMoveIn.text = state.apartment.moveInDate
                        binding.tvRentalDueDate.text = state.apartment.nextDueDate
                        binding.tvRentalAddress.text = state.apartment.addressText
                        binding.tvOutstandingValue.text = state.apartment.outstandingText
                        binding.tvPendingValue.text = state.apartment.pendingCount.toString()
                        binding.tvOverdueValue.text = state.apartment.overdueCount.toString()
                    }
                }
                launch {
                    viewModel.events.collect { toast(it) }
                }
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
