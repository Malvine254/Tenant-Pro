package com.tenantpro.app.ui.queries

import android.database.Cursor
import android.net.Uri
import android.os.Bundle
import android.provider.OpenableColumns
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.view.inputmethod.EditorInfo
import android.widget.ArrayAdapter
import androidx.activity.result.contract.ActivityResultContracts
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.recyclerview.widget.LinearLayoutManager
import com.google.android.material.textfield.MaterialAutoCompleteTextView
import com.tenantpro.app.databinding.FragmentQueriesBinding
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.toast
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

@AndroidEntryPoint
class QueriesFragment : Fragment() {

    private var _binding: FragmentQueriesBinding? = null
    private val binding get() = _binding!!
    private val viewModel: QueriesViewModel by viewModels()

    private val chatAdapter by lazy { QueryChatAdapter() }

    // Pending attachment selected by the user
    private var pendingAttachmentUri: Uri? = null
    private var pendingAttachmentName: String? = null

    // File-picker launcher — must be registered before onStart
    private val pickFileLauncher =
        registerForActivityResult(ActivityResultContracts.GetContent()) { uri ->
            if (uri != null) onFilePicked(uri)
        }

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentQueriesBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupChatList()
        setupTopicDropdown()
        bindUi()

        binding.btnAttachment.setOnClickListener {
            pickFileLauncher.launch("*/*")
        }

        binding.btnClearAttachment.setOnClickListener {
            clearAttachment()
        }

        binding.btnSendMessage.setOnClickListener { submitMessage() }

        binding.actQueryTopic.setOnItemClickListener { parent, _, position, _ ->
            val selected = parent.getItemAtPosition(position)?.toString().orEmpty()
            viewModel.selectTopic(selected)
        }

        binding.etQueryMessage.setOnEditorActionListener { _, actionId, _ ->
            if (actionId == EditorInfo.IME_ACTION_SEND) {
                submitMessage()
                true
            } else false
        }
    }

    private fun onFilePicked(uri: Uri) {
        pendingAttachmentUri = uri
        pendingAttachmentName = getFileName(uri)
        binding.tvAttachmentPreview.text = pendingAttachmentName
        binding.layoutAttachmentPreview.visible()
    }

    private fun getFileName(uri: Uri): String {
        var name = "attachment"
        val cursor: Cursor? = requireContext().contentResolver.query(uri, null, null, null, null)
        cursor?.use {
            val idx = it.getColumnIndex(OpenableColumns.DISPLAY_NAME)
            if (idx >= 0 && it.moveToFirst()) name = it.getString(idx)
        }
        return name
    }

    private fun clearAttachment() {
        pendingAttachmentUri = null
        pendingAttachmentName = null
        binding.layoutAttachmentPreview.gone()
    }

    private fun submitMessage() {
        val topic = binding.actQueryTopic.text?.toString()?.trim().orEmpty()
        val message = binding.etQueryMessage.text?.toString()?.trim().orEmpty()

        if (message.isBlank() && pendingAttachmentUri == null) {
            binding.tilQueryMessage.error = getString(com.tenantpro.app.R.string.query_message_required)
            return
        }
        binding.tilQueryMessage.error = null

        viewModel.sendMessage(
            topic = topic.ifBlank { "General" },
            text = message,
            attachmentUri = pendingAttachmentUri?.toString(),
            attachmentName = pendingAttachmentName
        )
        binding.etQueryMessage.text?.clear()
        clearAttachment()
    }

    private fun setupChatList() {
        val manager = LinearLayoutManager(requireContext()).apply {
            stackFromEnd = true
        }
        binding.rvChats.layoutManager = manager
        binding.rvChats.adapter = chatAdapter
    }

    private fun setupTopicDropdown() {
        val topicAdapter = ArrayAdapter(
            requireContext(),
            android.R.layout.simple_list_item_1,
            viewModel.topics
        )
        binding.actQueryTopic.setAdapter(topicAdapter)
        binding.actQueryTopic.setText(viewModel.topics.first(), false)
    }

    private fun bindUi() {
        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                launch {
                    viewModel.visibleMessages.collect { messages ->
                        chatAdapter.submitList(messages)
                        if (messages.isEmpty()) {
                            binding.tvEmptyChats.visible()
                            binding.rvChats.gone()
                        } else {
                            binding.tvEmptyChats.gone()
                            binding.rvChats.visible()
                            binding.rvChats.post {
                                binding.rvChats.scrollToPosition(messages.lastIndex)
                            }
                        }
                    }
                }
                launch {
                    viewModel.sending.collect { sending ->
                        binding.btnSendMessage.isEnabled = !sending
                        binding.btnAttachment.isEnabled = !sending
                    }
                }
                launch {
                    viewModel.events.collect { msg ->
                        toast(msg)
                    }
                }
            }
        }
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