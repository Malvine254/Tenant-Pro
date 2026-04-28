package com.tenantpro.app.ui.queries

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.tenantpro.app.BuildConfig
import com.tenantpro.app.databinding.ItemQueryChatIncomingBinding
import com.tenantpro.app.databinding.ItemQueryChatOutgoingBinding
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class QueryChatAdapter : ListAdapter<QueryChatMessage, RecyclerView.ViewHolder>(
    object : DiffUtil.ItemCallback<QueryChatMessage>() {
        override fun areItemsTheSame(oldItem: QueryChatMessage, newItem: QueryChatMessage) =
            oldItem.id == newItem.id
        override fun areContentsTheSame(oldItem: QueryChatMessage, newItem: QueryChatMessage) =
            oldItem == newItem
    }
) {

    override fun getItemViewType(position: Int): Int {
        return if (getItem(position).isFromTenant) TYPE_OUTGOING else TYPE_INCOMING
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): RecyclerView.ViewHolder {
        return if (viewType == TYPE_OUTGOING) {
            val binding = ItemQueryChatOutgoingBinding.inflate(
                LayoutInflater.from(parent.context),
                parent,
                false
            )
            OutgoingVH(binding)
        } else {
            val binding = ItemQueryChatIncomingBinding.inflate(
                LayoutInflater.from(parent.context),
                parent,
                false
            )
            IncomingVH(binding)
        }
    }

    override fun onBindViewHolder(holder: RecyclerView.ViewHolder, position: Int) {
        val item = getItem(position)
        when (holder) {
            is IncomingVH -> holder.bind(item)
            is OutgoingVH -> holder.bind(item)
        }
    }

    private class IncomingVH(
        private val binding: ItemQueryChatIncomingBinding
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(item: QueryChatMessage) {
            binding.tvTopic.text = item.topic
            binding.tvMessage.text = item.message
            binding.tvMeta.text = formatMeta(item.timestamp, item.status)
            binding.tvMessage.visibility = if (item.message.isNotBlank()) View.VISIBLE else View.GONE

            when {
                isImage(item.attachmentUri) -> {
                    binding.ivAttachmentImage.visibility = View.VISIBLE
                    binding.layoutAttachment.visibility = View.GONE
                    Glide.with(binding.ivAttachmentImage)
                        .load(buildFullUrl(item.attachmentUri!!))
                        .centerCrop()
                        .into(binding.ivAttachmentImage)
                }
                item.attachmentName != null -> {
                    binding.ivAttachmentImage.visibility = View.GONE
                    binding.layoutAttachment.visibility = View.VISIBLE
                    binding.tvAttachmentName.text = item.attachmentName
                }
                else -> {
                    binding.ivAttachmentImage.visibility = View.GONE
                    binding.layoutAttachment.visibility = View.GONE
                }
            }
        }
    }

    private class OutgoingVH(
        private val binding: ItemQueryChatOutgoingBinding
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(item: QueryChatMessage) {
            binding.tvTopic.text = item.topic
            binding.tvMessage.text = item.message
            binding.tvMeta.text = formatMeta(item.timestamp, item.status)
            binding.tvMessage.visibility = if (item.message.isNotBlank()) View.VISIBLE else View.GONE

            when {
                isImage(item.attachmentUri) -> {
                    binding.ivAttachmentImage.visibility = View.VISIBLE
                    binding.layoutAttachment.visibility = View.GONE
                    Glide.with(binding.ivAttachmentImage)
                        .load(buildFullUrl(item.attachmentUri!!))
                        .centerCrop()
                        .into(binding.ivAttachmentImage)
                }
                item.attachmentName != null -> {
                    binding.ivAttachmentImage.visibility = View.GONE
                    binding.layoutAttachment.visibility = View.VISIBLE
                    binding.tvAttachmentName.text = item.attachmentName
                }
                else -> {
                    binding.ivAttachmentImage.visibility = View.GONE
                    binding.layoutAttachment.visibility = View.GONE
                }
            }
        }
    }

    companion object {
        private const val TYPE_INCOMING = 1
        private const val TYPE_OUTGOING = 2

        private val IMAGE_EXTENSIONS = setOf("jpg", "jpeg", "png", "gif", "webp")


        fun isImage(uri: String?): Boolean {
            if (uri.isNullOrBlank()) return false
            val ext = uri.substringAfterLast('.', "").lowercase()
            return ext in IMAGE_EXTENSIONS
        }

        fun buildFullUrl(relativePath: String): String {
            if (relativePath.startsWith("http")) return relativePath
            val serverBase = BuildConfig.BASE_URL.removeSuffix("/").removeSuffix("api").trimEnd('/')
            return "$serverBase$relativePath"
        }

        private fun formatMeta(timestamp: Long, status: String): String {
            val time = SimpleDateFormat("HH:mm", Locale.getDefault()).format(Date(timestamp))
            return "$time • $status"
        }
    }
}
