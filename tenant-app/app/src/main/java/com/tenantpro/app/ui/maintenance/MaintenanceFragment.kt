package com.tenantpro.app.ui.maintenance

import android.os.Bundle
import android.util.TypedValue
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import android.widget.LinearLayout
import android.widget.TextView
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.google.android.material.textfield.MaterialAutoCompleteTextView
import com.google.android.material.textfield.TextInputEditText
import com.tenantpro.app.R
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

        binding.btnCreateMaintenance.setOnClickListener { showCreateRequestDialog() }
        binding.btnRefreshMaintenance.setOnClickListener { viewModel.refresh() }

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                launch {
                    viewModel.uiState.collect { state ->
                        binding.progressMaintenance.visibility = if (state.loading) View.VISIBLE else View.GONE
                        binding.btnCreateMaintenance.isEnabled = !state.submitting
                        binding.btnRefreshMaintenance.isEnabled = !state.loading
                        binding.tvOpenCount.text = state.openCount.toString()
                        binding.tvInProgressCount.text = state.inProgressCount.toString()
                        binding.tvResolvedCount.text = state.resolvedCount.toString()
                        bindRequests(state.requests)
                    }
                }
                launch {
                    viewModel.events.collect { msg ->
                        if (msg.contains("submitted", ignoreCase = true)) {
                            showSuccessSnackbar(msg)
                        } else {
                            showErrorSnackbar(msg)
                        }
                    }
                }
            }
        }
    }

    private fun bindRequests(requests: List<MaintenanceRequestUi>) {
        binding.llMaintenanceRequests.removeAllViews()

        if (requests.isEmpty()) {
            binding.llMaintenanceEmpty.visible()
            binding.scrollMaintenanceRequests.gone()
            return
        }

        binding.llMaintenanceEmpty.gone()
        binding.scrollMaintenanceRequests.visible()

        requests.forEach { request ->
            val card = layoutInflater.inflate(
                R.layout.item_maintenance_request,
                binding.llMaintenanceRequests,
                false
            )

            card.findViewById<TextView>(R.id.tvMaintenanceTitle).text = request.title
            card.findViewById<TextView>(R.id.tvMaintenanceMeta).text =
                "${request.unitLabel} • ${request.createdAt}"
            card.findViewById<TextView>(R.id.tvMaintenanceDescription).text = request.description
            card.findViewById<TextView>(R.id.tvMaintenancePriority).apply {
                text = request.priority
                setTextColor(priorityColor(request.priorityKey))
                background = ContextCompat.getDrawable(requireContext(), priorityBackground(request.priorityKey))
            }
            card.findViewById<TextView>(R.id.tvMaintenanceStatus).apply {
                text = request.status
                setTextColor(statusColor(request.statusKey))
                background = ContextCompat.getDrawable(requireContext(), statusBackground(request.statusKey))
            }
            card.findViewById<TextView>(R.id.tvMaintenanceResolved).apply {
                if (request.resolvedAt == "—") {
                    visibility = View.GONE
                } else {
                    visibility = View.VISIBLE
                    text = getString(R.string.maintenance_resolved_on, request.resolvedAt)
                }
            }

            binding.llMaintenanceRequests.addView(card)
        }
    }

    private fun showCreateRequestDialog() {
        val horizontalPadding = TypedValue.applyDimension(
            TypedValue.COMPLEX_UNIT_DIP,
            20f,
            resources.displayMetrics
        ).toInt()
        val topPadding = TypedValue.applyDimension(
            TypedValue.COMPLEX_UNIT_DIP,
            6f,
            resources.displayMetrics
        ).toInt()

        val container = LinearLayout(requireContext()).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(horizontalPadding, topPadding, horizontalPadding, 0)
        }

        val titleInput = com.google.android.material.textfield.TextInputLayout(requireContext()).apply {
            hint = getString(R.string.maintenance_title)
        }
        val titleEdit = TextInputEditText(titleInput.context).apply {
            setText("")
        }
        titleInput.addView(titleEdit)

        val priorityInput = com.google.android.material.textfield.TextInputLayout(requireContext()).apply {
            hint = getString(R.string.maintenance_priority)
        }
        val priorityView = MaterialAutoCompleteTextView(priorityInput.context).apply {
            setAdapter(
                ArrayAdapter(
                    requireContext(),
                    android.R.layout.simple_list_item_1,
                    listOf("LOW", "MEDIUM", "HIGH", "URGENT")
                )
            )
            setText("MEDIUM", false)
            inputType = 0
        }
        priorityInput.addView(priorityView)

        val descriptionInput = com.google.android.material.textfield.TextInputLayout(requireContext()).apply {
            hint = getString(R.string.maintenance_description)
        }
        val descriptionEdit = TextInputEditText(descriptionInput.context).apply {
            minLines = 4
            maxLines = 6
        }
        descriptionInput.addView(descriptionEdit)

        container.addView(titleInput)
        container.addView(priorityInput)
        container.addView(descriptionInput)

        MaterialAlertDialogBuilder(requireContext(), R.style.Theme_TenantPro_Dialog_Form)
            .setTitle(R.string.maintenance_new_request)
            .setView(container)
            .setNegativeButton(R.string.cancel, null)
            .setPositiveButton(R.string.maintenance_submit) { _, _ ->
                viewModel.submitRequest(
                    title = titleEdit.text?.toString().orEmpty(),
                    description = descriptionEdit.text?.toString().orEmpty(),
                    priority = priorityView.text?.toString().orEmpty().ifBlank { "MEDIUM" }
                )
            }
            .show()
    }

    private fun priorityBackground(priority: String): Int = when (priority) {
        "URGENT" -> R.drawable.bg_badge_red
        "HIGH" -> R.drawable.bg_badge_yellow
        else -> R.drawable.bg_badge_gray
    }

    private fun priorityColor(priority: String): Int = ContextCompat.getColor(
        requireContext(),
        when (priority) {
            "URGENT" -> R.color.badge_red_text
            "HIGH" -> R.color.badge_yellow_text
            else -> R.color.badge_gray_text
        }
    )

    private fun statusBackground(status: String): Int = when (status) {
        "RESOLVED", "CLOSED" -> R.drawable.bg_badge_green
        "IN_PROGRESS" -> R.drawable.bg_badge_yellow
        else -> R.drawable.bg_badge_red
    }

    private fun statusColor(status: String): Int = ContextCompat.getColor(
        requireContext(),
        when (status) {
            "RESOLVED", "CLOSED" -> R.color.badge_green_text
            "IN_PROGRESS" -> R.color.badge_yellow_text
            else -> R.color.badge_red_text
        }
    )

    override fun onResume() {
        super.onResume()
        viewModel.refresh()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
