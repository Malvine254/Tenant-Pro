package com.tenantpro.app.ui.account

import android.net.Uri
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.activity.result.contract.ActivityResultContracts
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.navigation.fragment.findNavController
import com.tenantpro.app.R
import com.tenantpro.app.databinding.FragmentAccountSettingsBinding
import com.tenantpro.app.utils.toast
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

@AndroidEntryPoint
class AccountSettingsFragment : Fragment() {

    private var _binding: FragmentAccountSettingsBinding? = null
    private val binding get() = _binding!!
    private val viewModel: AccountSettingsViewModel by viewModels()

    private val imagePicker = registerForActivityResult(ActivityResultContracts.OpenDocument()) { uri ->
        if (uri != null) {
            persistReadPermission(uri)
            viewModel.saveProfileImage(uri.toString())
            binding.ivProfile.setImageURI(uri)
        } else {
            // User cancelled - keep current image or placeholder
        }
    }

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentAccountSettingsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                launch {
                    viewModel.uiState.collect { state ->
                        updateUI(state)
                    }
                }
                launch {
                    viewModel.events.collect { toast(it) }
                }
            }
        }

        binding.btnChangePhoto.setOnClickListener {
            imagePicker.launch(arrayOf("image/*"))
        }

        binding.ivProfile.setOnLongClickListener {
            showRemovePhotoDialog()
            true
        }

        binding.btnSaveProfile.setOnClickListener {
            viewModel.saveProfile(
                name = binding.etName.text?.toString().orEmpty(),
                phone = binding.etPhone.text?.toString().orEmpty(),
                email = binding.etEmail.text?.toString().orEmpty(),
                emergencyContact = binding.etEmergencyContact.text?.toString().orEmpty(),
                bio = binding.etBio.text?.toString().orEmpty()
            )
        }

        binding.btnAcceptInvitation.setOnClickListener {
            val code = binding.etInvitationCode.text?.toString().orEmpty()
            viewModel.acceptInvitation(code)
            if (code.isNotBlank()) binding.etInvitationCode.text?.clear()
        }

        binding.btnRefreshApartment.setOnClickListener {
            viewModel.refreshApartmentInfo()
        }

        binding.btnLogout.setOnClickListener {
            viewModel.logout {
                findNavController().navigate(
                    R.id.loginFragment,
                    null,
                    androidx.navigation.NavOptions.Builder()
                        .setPopUpTo(R.id.homeFragment, true)
                        .build()
                )
            }
        }
    }

    private fun showRemovePhotoDialog() {
        androidx.appcompat.app.AlertDialog.Builder(requireContext())
            .setTitle(R.string.remove_photo_title)
            .setMessage(R.string.remove_photo_message)
            .setPositiveButton(R.string.remove) { _, _ ->
                viewModel.saveProfileImage(null)
                binding.ivProfile.setImageResource(R.drawable.ic_account_circle)
                toast(getString(R.string.photo_removed))
            }
            .setNegativeButton(R.string.cancel, null)
            .show()
    }

    private fun updateUI(state: AccountUiState) {
        // Show/hide loading state
        binding.progressLoading.visibility = if (state.loading) View.VISIBLE else View.GONE
        binding.contentLayout.visibility = if (state.loading) View.GONE else View.VISIBLE

        // Update profile fields with actual user data
        if (binding.etName.text.toString() != state.name) {
            binding.etName.setText(state.name)
        }
        if (binding.etPhone.text.toString() != state.phone) {
            binding.etPhone.setText(state.phone)
        }
        if (binding.etEmail.text.toString() != state.email) {
            binding.etEmail.setText(state.email)
        }
        if (binding.etEmergencyContact.text.toString() != state.emergencyContact) {
            binding.etEmergencyContact.setText(state.emergencyContact)
        }
        if (binding.etBio.text.toString() != state.bio) {
            binding.etBio.setText(state.bio)
        }

        // Update user name display
        binding.tvUserName.text = state.name.ifBlank { "Tenant User" }
        
        // Update apartment info
        binding.tvPropertyValue.text = state.apartment.propertyName
        binding.tvUnitValue.text = state.apartment.unitName
        binding.tvNextDueValue.text = state.apartment.nextDueDate
        binding.tvMoveInValue.text = state.apartment.moveInDate
        binding.tvAddressValue.text = state.apartment.addressText
        binding.tvOutstandingValue.text = state.apartment.outstandingText
        binding.tvPendingValue.text = state.apartment.pendingCount.toString()
        binding.tvOverdueValue.text = state.apartment.overdueCount.toString()
        
        // Update profile completion
        binding.progressProfileCompletion.progress = state.profileCompletionPercent
        binding.tvProfileCompletion.text = state.profileCompletionText

        // Update profile image
        if (!state.imageUri.isNullOrBlank()) {
            binding.ivProfile.setImageURI(Uri.parse(state.imageUri))
        } else {
            binding.ivProfile.setImageResource(R.drawable.ic_account_circle)
        }

        // Update save button state
        binding.btnSaveProfile.isEnabled = !state.saving && !state.loading
        binding.btnSaveProfile.text = if (state.saving) {
            getString(R.string.profile_saving)
        } else {
            getString(R.string.profile_save)
        }
    }

    private fun persistReadPermission(uri: Uri) {
        val resolver = requireContext().contentResolver
        try {
            resolver.takePersistableUriPermission(uri, android.content.Intent.FLAG_GRANT_READ_URI_PERMISSION)
        } catch (_: SecurityException) {
            // Some providers do not grant persistable permissions; regular URI use still works.
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}