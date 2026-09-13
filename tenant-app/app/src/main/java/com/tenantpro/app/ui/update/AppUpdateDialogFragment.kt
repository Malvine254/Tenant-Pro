package com.tenantpro.app.ui.update

import android.content.DialogInterface
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.os.bundleOf
import androidx.fragment.app.DialogFragment
import androidx.fragment.app.FragmentManager
import androidx.lifecycle.lifecycleScope
import com.tenantpro.app.R
import com.tenantpro.app.data.model.AppUpdateInfo
import com.tenantpro.app.databinding.DialogAppUpdateBinding
import com.tenantpro.app.utils.AppUpdateManager
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.File
import java.util.Locale
import javax.inject.Inject

@AndroidEntryPoint
class AppUpdateDialogFragment : DialogFragment() {

    private var _binding: DialogAppUpdateBinding? = null
    private val binding get() = _binding!!

    @Inject
    lateinit var appUpdateManager: AppUpdateManager

    private var updateInfo: AppUpdateInfo? = null
    private var downloadJob: Job? = null
    private var downloadedApkFile: File? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        @Suppress("DEPRECATION")
        updateInfo = arguments?.getSerializable(ARG_UPDATE_INFO) as? AppUpdateInfo
        isCancelable = updateInfo?.isMandatory != true
    }

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        dialog?.window?.setBackgroundDrawableResource(android.R.color.transparent)
        _binding = DialogAppUpdateBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        val info = updateInfo ?: run {
            dismissAllowingStateLoss()
            return
        }

        bindUpdateInfo(info)
        setupActions(info)
    }

    override fun onStart() {
        super.onStart()
        dialog?.window?.let { window ->
            val displayMetrics = resources.displayMetrics
            val width = (displayMetrics.widthPixels * 0.92).toInt()
            window.setLayout(width, ViewGroup.LayoutParams.WRAP_CONTENT)
        }
    }

    private fun bindUpdateInfo(info: AppUpdateInfo) {
        val displayVer = info.displayVersion
        binding.tvUpdateSubtitle.text = getString(R.string.app_update_modal_subtitle, displayVer)
        binding.tvVersionChip.text = displayVer

        if (info.formattedSize.isNotBlank()) {
            binding.tvSizeChip.text = info.formattedSize
            binding.tvSizeChip.visible()
        } else {
            binding.tvSizeChip.gone()
        }

        if (info.isMandatory) {
            binding.tvMandatoryBadge.visible()
            binding.btnUpdateLater.gone()
            isCancelable = false
        } else {
            binding.tvMandatoryBadge.gone()
            binding.btnUpdateLater.visible()
        }

        binding.tvReleaseNotes.text = info.releaseNotes?.takeIf { it.isNotBlank() }
            ?: "• Latest improvements, security and reliability updates for Starmax Tenant Services."
    }

    private fun setupActions(info: AppUpdateInfo) {
        binding.btnDownloadAndInstall.setOnClickListener {
            val completedFile = downloadedApkFile
            if (completedFile != null && completedFile.exists() && completedFile.length() > 0) {
                // File already downloaded, trigger install
                appUpdateManager.installApk(requireActivity(), completedFile)
            } else {
                startInAppDownload(info)
            }
        }

        binding.btnOpenInBrowser.setOnClickListener {
            info.downloadUrl?.let { url ->
                appUpdateManager.openBrowserDownload(requireContext(), url)
                if (!info.isMandatory) {
                    dismissAllowingStateLoss()
                }
            }
        }

        binding.btnUpdateLater.setOnClickListener {
            if (!info.isMandatory) {
                dismissAllowingStateLoss()
            }
        }
    }

    private fun startInAppDownload(info: AppUpdateInfo) {
        if (downloadJob?.isActive == true) return

        binding.layoutDownloadProgress.visible()
        binding.btnDownloadAndInstall.isEnabled = false
        binding.btnDownloadAndInstall.text = "Downloading…"
        binding.btnDownloadAndInstall.setIconResource(0)
        binding.btnUpdateLater.isEnabled = !info.isMandatory
        binding.progressDownload.isIndeterminate = false
        binding.progressDownload.progress = 0
        binding.tvDownloadStatus.text = "Downloading update…"
        binding.tvDownloadPercent.text = "0%"
        binding.tvDownloadBytes.text = "Starting download…"

        downloadJob = viewLifecycleOwner.lifecycleScope.launch {
            try {
                val file = withContext(Dispatchers.IO) {
                    appUpdateManager.downloadApk(info) { percent, downloadedBytes, totalBytes ->
                        viewLifecycleOwner.lifecycleScope.launch(Dispatchers.Main) {
                            if (_binding == null) return@launch
                            binding.progressDownload.progress = percent
                            binding.tvDownloadPercent.text = "$percent%"
                            binding.tvDownloadStatus.text = getString(R.string.app_update_downloading, percent)

                            if (totalBytes > 0) {
                                val currentMb = downloadedBytes.toDouble() / (1024.0 * 1024.0)
                                val totalMb = totalBytes.toDouble() / (1024.0 * 1024.0)
                                binding.tvDownloadBytes.text = String.format(
                                    Locale.US,
                                    "%.1f MB / %.1f MB",
                                    currentMb,
                                    totalMb
                                )
                            } else {
                                val currentMb = downloadedBytes.toDouble() / (1024.0 * 1024.0)
                                binding.tvDownloadBytes.text = String.format(Locale.US, "%.1f MB downloaded", currentMb)
                            }
                        }
                    }
                }

                downloadedApkFile = file
                binding.progressDownload.progress = 100
                binding.tvDownloadPercent.text = "100%"
                binding.tvDownloadStatus.text = getString(R.string.app_update_ready_to_install)
                binding.btnDownloadAndInstall.isEnabled = true
                binding.btnDownloadAndInstall.text = getString(R.string.app_update_btn_install_now)
                binding.btnDownloadAndInstall.setIconResource(R.drawable.ic_system_update)

                // Instantly launch installer
                activity?.let { act ->
                    appUpdateManager.installApk(act, file)
                }
            } catch (e: Exception) {
                if (_binding != null) {
                    binding.tvDownloadStatus.text = "Download encountered an issue."
                    binding.tvDownloadBytes.text = e.localizedMessage ?: "Network error"
                    binding.btnDownloadAndInstall.isEnabled = true
                    binding.btnDownloadAndInstall.text = "Retry Download"
                    binding.btnDownloadAndInstall.setIconResource(R.drawable.ic_download)
                    binding.btnOpenInBrowser.visible()
                }
            }
        }
    }

    override fun onDismiss(dialog: DialogInterface) {
        downloadJob?.cancel()
        super.onDismiss(dialog)
    }

    override fun onDestroyView() {
        downloadJob?.cancel()
        _binding = null
        super.onDestroyView()
    }

    companion object {
        const val TAG = "AppUpdateDialogFragment"
        private const val ARG_UPDATE_INFO = "arg_update_info"

        fun newInstance(updateInfo: AppUpdateInfo): AppUpdateDialogFragment {
            return AppUpdateDialogFragment().apply {
                arguments = bundleOf(ARG_UPDATE_INFO to updateInfo)
            }
        }

        fun show(fragmentManager: FragmentManager, updateInfo: AppUpdateInfo) {
            if (fragmentManager.findFragmentByTag(TAG) != null) return
            val dialog = newInstance(updateInfo)
            dialog.show(fragmentManager, TAG)
        }
    }
}
