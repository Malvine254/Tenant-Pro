package com.tenantpro.app.ui.notifications

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import com.google.android.material.card.MaterialCardView
import com.tenantpro.app.R
import com.tenantpro.app.data.model.NotificationItem
import com.tenantpro.app.databinding.FragmentNotificationsBinding
import com.tenantpro.app.utils.toast
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch
import java.util.Locale

@AndroidEntryPoint
class NotificationsFragment : Fragment() {

    private var _binding: FragmentNotificationsBinding? = null
    private val binding get() = _binding!!
    private val viewModel: NotificationsViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentNotificationsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.btnRefreshNotifications.setOnClickListener { viewModel.loadNotifications() }
        binding.btnMarkAllRead.setOnClickListener { viewModel.markAllRead() }

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                launch {
                    viewModel.items.collect { bindNotifications(it) }
                }
                launch {
                    viewModel.loading.collect { isLoading ->
                        binding.progressNotifications.visibility = if (isLoading) View.VISIBLE else View.GONE
                    }
                }
                launch {
                    viewModel.events.collect { toast(it) }
                }
            }
        }
    }

    private fun bindNotifications(items: List<NotificationItem>) {
        binding.llNotificationItems.removeAllViews()
        binding.tvNotificationsEmpty.visibility = if (items.isEmpty()) View.VISIBLE else View.GONE

        items.forEach { item ->
            val card = layoutInflater.inflate(
                R.layout.item_notification,
                binding.llNotificationItems,
                false
            ) as MaterialCardView

            card.findViewById<TextView>(R.id.tvNotificationTitle).text = item.title
            card.findViewById<TextView>(R.id.tvNotificationMessage).text = item.message
            card.findViewById<TextView>(R.id.tvNotificationMeta).text = buildMeta(item)
            card.strokeWidth = if (item.isRead) 1 else 2
            card.strokeColor = ContextCompat.getColor(
                requireContext(),
                if (item.isRead) R.color.outline_variant else R.color.primary
            )
            binding.llNotificationItems.addView(card)
        }
    }

    private fun buildMeta(item: NotificationItem): String {
        val prettyType = item.type
            .lowercase(Locale.getDefault())
            .replace('_', ' ')
            .replaceFirstChar { if (it.isLowerCase()) it.titlecase(Locale.getDefault()) else it.toString() }

        return listOf(prettyType, item.createdAt)
            .filter { it.isNotBlank() }
            .joinToString(" • ")
    }

    override fun onStart() {
        super.onStart()
        viewModel.startPolling()
    }

    override fun onStop() {
        viewModel.stopPolling()
        super.onStop()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}