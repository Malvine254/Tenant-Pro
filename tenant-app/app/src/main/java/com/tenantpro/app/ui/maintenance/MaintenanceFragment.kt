package com.tenantpro.app.ui.maintenance

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import com.google.android.material.card.MaterialCardView
import com.google.android.material.textview.MaterialTextView
import com.tenantpro.app.R
import com.tenantpro.app.data.model.MaintenanceRequestItem
import com.tenantpro.app.databinding.FragmentMaintenanceBinding
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.showErrorSnackbar
import com.tenantpro.app.utils.showSuccessSnackbar
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

@AndroidEntryPoint
class MaintenanceFragment : Fragment() {

    private var _binding: FragmentMaintenanceBinding? = null
    private val binding get() = _binding!!
    private val viewModel: MaintenanceViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentMaintenanceBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val priorityAdapter = ArrayAdapter(requireContext(), android.R.layout.simple_list_item_1, viewModel.priorities)
        binding.actPriority.setAdapter(priorityAdapter)
        binding.actPriority.setText(viewModel.priorities[1], false)

        binding.btnSubmitMaintenance.setOnClickListener {
            viewModel.submitRequest(
                title = binding.etMaintenanceTitle.text?.toString().orEmpty(),
                description = binding.etMaintenanceDescription.text?.toString().orEmpty(),
                priority = binding.actPriority.text?.toString().orEmpty()
            )
        }

        binding.btnRefreshMaintenance.setOnClickListener { viewModel.loadRequests() }

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                launch {
                    viewModel.requests.collect { requests ->
                        bindRequests(requests)
                    }
                }
                launch {
                    viewModel.submitting.collect { submitting ->
                        binding.btnSubmitMaintenance.isEnabled = !submitting
                        binding.btnSubmitMaintenance.text = if (submitting) "Submitting…" else getString(R.string.maintenance_submit)
                        binding.progressMaintenance.visibility = if (submitting) View.VISIBLE else View.GONE
                    }
                }
                launch {
                    viewModel.events.collect { msg ->
                        if (msg.contains("success", ignoreCase = true) || msg.contains("submitted", ignoreCase = true)) {
                            showSuccessSnackbar(msg)
                        } else {
                            showErrorSnackbar(msg)
                        }
                    }
                }
            }
        }
    }

    private fun bindRequests(requests: List<MaintenanceRequestItem>) {
        binding.llMaintenanceItems.removeAllViews()
        binding.tvMaintenanceEmpty.visibility = if (requests.isEmpty()) View.VISIBLE else View.GONE

        requests.forEach { item ->
            val card = layoutInflater.inflate(R.layout.item_maintenance_request, binding.llMaintenanceItems, false) as MaterialCardView
            card.findViewById<MaterialTextView>(R.id.tvMaintenanceTitle).text = item.title
            card.findViewById<MaterialTextView>(R.id.tvMaintenanceMeta).text = "${item.priority} • ${item.status.replace('_', ' ')}"
            card.findViewById<MaterialTextView>(R.id.tvMaintenanceBody).text = item.description
            card.findViewById<MaterialTextView>(R.id.tvMaintenanceLocation).text = listOfNotNull(item.unit?.property?.name, item.unit?.unitName).joinToString(" · ")
            binding.llMaintenanceItems.addView(card)
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
