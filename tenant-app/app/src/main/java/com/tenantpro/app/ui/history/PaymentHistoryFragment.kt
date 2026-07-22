package com.tenantpro.app.ui.history

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.recyclerview.widget.LinearLayoutManager
import com.tenantpro.app.databinding.FragmentPaymentHistoryBinding
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

@AndroidEntryPoint
class PaymentHistoryFragment : Fragment() {

    private var _binding: FragmentPaymentHistoryBinding? = null
    private val binding get() = _binding!!
    private val viewModel: PaymentHistoryViewModel by viewModels()

    private val adapter = PaymentHistoryAdapter()

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentPaymentHistoryBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val invoiceId = arguments?.getString("invoiceId") ?: ""
        val invoiceLabel = arguments?.getString("invoiceLabel") ?: ""

        binding.tvInvoiceLabel.text = invoiceLabel
        binding.rvPayments.layoutManager = LinearLayoutManager(requireContext())
        binding.rvPayments.adapter = adapter

        binding.swipeRefresh.setOnRefreshListener { viewModel.load(invoiceId) }

        viewModel.load(invoiceId)

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.historyState.collect { state ->
                    binding.swipeRefresh.isRefreshing = state is Resource.Loading
                    when (state) {
                        is Resource.Loading -> {
                            binding.tvEmpty.gone()
                            binding.tvError.gone()
                        }
                        is Resource.Success -> {
                            if (state.data.isEmpty()) {
                                binding.tvEmpty.visible()
                                binding.rvPayments.gone()
                            } else {
                                binding.tvEmpty.gone()
                                binding.rvPayments.visible()
                                adapter.submitList(state.data)
                            }
                            binding.tvError.gone()
                        }
                        is Resource.Error -> {
                            binding.tvError.text = state.message
                            binding.tvError.visible()
                            binding.tvEmpty.gone()
                        }
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
