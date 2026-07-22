package com.tenantpro.app.ui.queries

import android.graphics.drawable.Drawable
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.bumptech.glide.load.engine.GlideException
import com.bumptech.glide.request.RequestListener
import com.bumptech.glide.request.target.Target
import com.tenantpro.app.BuildConfig
import com.tenantpro.app.databinding.ItemQueryChatIncomingBinding
import com.tenantpro.app.databinding.ItemQueryChatOutgoingBinding
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class QueryChatAdapter(var outgoingInitials: String = "U") :
    ListAdapter<QueryChatMessage, RecyclerView.ViewHolder>(
        object : DiffUtil.ItemCallback<QueryChatMessage>() {
            override fun areItemsTheSame(oldItem: QueryChatMessage, newItem: QueryChatMessage) =
                oldItem.id == newItem.id

            override fun areContentsTheSame(oldItem: QueryChatMessage, newItem: QueryChatMessage) =
                oldItem == newItem
        }
    ) {

    var outgoingProfileImage: String? = null

    override fun getItemViewType(position: Int): Int {
        return if (getItem(position).isFromTenant) TYPE_OUTGOING else TYPE_INCOMING
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): RecyclerView.ViewHolder {
        return if (viewType == TYPE_OUTGOING) {
            OutgoingVH(
                ItemQueryChatOutgoingBinding.inflate(
                    LayoutInflater.from(parent.context),
                    parent,
                    false
                )
            )
        } else {
            IncomingVH(
                ItemQueryChatIncomingBinding.inflate(
                    LayoutInflater.from(parent.context),
                    parent,
                    false
                )
            )
        }
    }

    override fun onBindViewHolder(holder: RecyclerView.ViewHolder, position: Int) {
        val item = getItem(position)
        when (holder) {
            is IncomingVH -> holder.bind(item)
            is OutgoingVH -> holder.bind(item)
        }
    }

    private inner class IncomingVH(
        private val binding: ItemQueryChatIncomingBinding
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(item: QueryChatMessage) {
            binding.tvAvatarLabel.text = "PM"
            binding.tvTopic.text = item.topic
            val visibleMessage = displayMessage(item)
            binding.tvMessage.text = visibleMessage
            binding.tvMeta.text = formatMeta(item.timestamp, item.status)
            binding.tvMessage.visibility = if (visibleMessage.isNotBlank()) View.VISIBLE else View.GONE
            bindAttachment(
                item = item,
                image = binding.ivAttachmentImage,
                attachment = binding.layoutAttachment,
                attachmentName = binding.tvAttachmentName
            )
        }
    }

    private inner class OutgoingVH(
        private val binding: ItemQueryChatOutgoingBinding
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(item: QueryChatMessage) {
            binding.tvAvatarLabel.text = outgoingInitials
            val profileImage = outgoingProfileImage
            if (profileImage.isNullOrBlank()) {
                binding.ivAvatar.visibility = View.GONE
                binding.tvAvatarLabel.visibility = View.VISIBLE
                Glide.with(binding.ivAvatar).clear(binding.ivAvatar)
            } else {
                binding.ivAvatar.visibility = View.VISIBLE
                binding.tvAvatarLabel.visibility = View.GONE
                Glide.with(binding.ivAvatar)
                    .load(buildFullUrl(profileImage))
                    .circleCrop()
                    .listener(object : RequestListener<Drawable> {
                        override fun onLoadFailed(
                            e: GlideException?,
                            model: Any?,
                            target: Target<Drawable>,
                            isFirstResource: Boolean
                        ): Boolean {
                            binding.ivAvatar.visibility = View.GONE
                            binding.tvAvatarLabel.visibility = View.VISIBLE
                            return false
                        }

                        override fun onResourceReady(
                            resource: Drawable,
                            model: Any,
                            target: Target<Drawable>?,
                            dataSource: com.bumptech.glide.load.DataSource,
                            isFirstResource: Boolean
                        ): Boolean {
                            binding.ivAvatar.visibility = View.VISIBLE
                            binding.tvAvatarLabel.visibility = View.GONE
                            return false
                        }
                    })
                    .into(binding.ivAvatar)
            }
            binding.tvTopic.text = item.topic
            val visibleMessage = displayMessage(item)
            binding.tvMessage.text = visibleMessage
            binding.tvMeta.text = formatMeta(item.timestamp, item.status)
            binding.tvMessage.visibility = if (visibleMessage.isNotBlank()) View.VISIBLE else View.GONE
            bindAttachment(
                item = item,
                image = binding.ivAttachmentImage,
                attachment = binding.layoutAttachment,
                attachmentName = binding.tvAttachmentName
            )
        }
    }

    private fun bindAttachment(
        item: QueryChatMessage,
        image: android.widget.ImageView,
        attachment: View,
        attachmentName: android.widget.TextView
    ) {
        when {
            isImage(item.attachmentUri, item.attachmentName) && !item.attachmentUri.isNullOrBlank() -> {
                image.visibility = View.VISIBLE
                attachment.visibility = View.GONE
                Glide.with(image)
                    .load(buildFullUrl(item.attachmentUri))
                    .centerCrop()
                    .into(image)
            }
            !item.attachmentName.isNullOrBlank() -> {
                image.visibility = View.GONE
                attachment.visibility = View.VISIBLE
                attachmentName.text = item.attachmentName
            }
            else -> {
                image.visibility = View.GONE
                attachment.visibility = View.GONE
            }
        }
    }

    private fun displayMessage(item: QueryChatMessage): String {
        val text = item.message.trim()
        return if (text.equals("Attachment shared", ignoreCase = true) &&
            (!item.attachmentUri.isNullOrBlank() || !item.attachmentName.isNullOrBlank())) "" else text
    }

    companion object {
        private const val TYPE_INCOMING = 1
        private const val TYPE_OUTGOING = 2

        private val IMAGE_EXTENSIONS = setOf("jpg", "jpeg", "png", "gif", "webp")

        fun isImage(uri: String?, name: String? = null): Boolean {
            val source = listOfNotNull(uri, name).firstOrNull { it.contains('.') } ?: return false
            val ext = source.substringAfterLast('.', "").substringBefore('?').lowercase()
            return ext in IMAGE_EXTENSIONS
        }

        fun buildFullUrl(relativePath: String): String {
            if (relativePath.startsWith("http")) return relativePath
            val serverBase = BuildConfig.BASE_URL.removeSuffix("/").removeSuffix("api").trimEnd('/')
            return "$serverBase$relativePath"
        }

        private fun formatMeta(timestamp: Long, status: String): String {
            val time = SimpleDateFormat("HH:mm", Locale.getDefault()).format(Date(timestamp))
            return "$time  $status"
        }
    }
}
