package com.tenantpro.app.ui.account

import android.net.Uri
import android.os.Bundle
import android.os.Build
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import androidx.activity.result.contract.ActivityResultContracts
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricManager.Authenticators.BIOMETRIC_STRONG
import androidx.biometric.BiometricManager.Authenticators.DEVICE_CREDENTIAL
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.navigation.fragment.findNavController
import androidx.core.os.bundleOf
import com.bumptech.glide.Glide
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.google.android.material.switchmaterial.SwitchMaterial
import com.google.android.material.textfield.TextInputEditText
import com.google.android.material.textfield.TextInputLayout
import com.tenantpro.app.R
import com.tenantpro.app.MainActivity
import com.tenantpro.app.databinding.FragmentAccountSettingsBinding
import com.tenantpro.app.utils.toast
import com.tenantpro.app.utils.toAbsoluteAssetUrl
import com.tenantpro.app.utils.normalizeKenyanPhone
import com.tenantpro.app.utils.dismissKeyboard
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

@AndroidEntryPoint
class AccountSettingsFragment : Fragment() {

    private var _binding: FragmentAccountSettingsBinding? = null
    private val binding get() = _binding!!
    private val viewModel: AccountSettingsViewModel by viewModels()
    private var latestState = AccountUiState()
    private var suppressSwitchEvents = false

    private val imagePicker = registerForActivityResult(ActivityResultContracts.OpenDocument()) { uri ->
        if (uri != null) {
            persistReadPermission(uri)
            binding.ivProfile.setImageURI(uri)
            viewModel.uploadProfileImage(uri, requireContext())
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
                    viewModel.uiState.collect { state -> updateUI(state) }
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

        setupSettingsActions()

        binding.btnLogout.setOnClickListener {
            viewModel.logout {
                findNavController().navigate(
                    R.id.loginFragment,
                    null,
                    androidx.navigation.NavOptions.Builder()
                        .setPopUpTo(R.id.nav_graph, true)
                        .build()
                )
            }
        }
    }

    private fun setupSettingsActions() {
        binding.root.findViewById<View>(R.id.rowAccountDetails).setOnClickListener {
            showAccountDetailsDialog()
        }
        binding.root.findViewById<View>(R.id.rowPaymentPhone).setOnClickListener {
            showPaymentPhoneDialog()
        }
        binding.root.findViewById<View>(R.id.rowSecurity).setOnClickListener {
            showChangePasswordDialog()
        }
        binding.root.findViewById<View>(R.id.rowSessions).setOnClickListener {
            showCurrentSessionDialog()
        }
        binding.root.findViewById<View>(R.id.rowHelp).setOnClickListener {
            runCatching { findNavController().navigate(R.id.queriesFragment) }
                .onFailure { toast("Support is available from Chats.") }
        }

        binding.root.findViewById<SwitchMaterial>(R.id.switchNotifications)
            ?.setOnCheckedChangeListener { _, checked ->
                if (!suppressSwitchEvents) {
                    if (checked) (activity as? MainActivity)?.ensureNotificationPermission()
                    viewModel.setNotificationsEnabled(checked)
                }
            }
        binding.root.findViewById<SwitchMaterial>(R.id.switchEmailNotifications)
            ?.setOnCheckedChangeListener { _, checked ->
                if (!suppressSwitchEvents) {
                    viewModel.setEmailNotificationsEnabled(checked)
                }
            }
        binding.root.findViewById<SwitchMaterial>(R.id.switchBiometric)
            ?.setOnCheckedChangeListener { _, checked ->
                if (!suppressSwitchEvents) {
                    if (checked && !canUseBiometricLogin()) {
                        suppressSwitchEvents = true
                        binding.root.findViewById<SwitchMaterial>(R.id.switchBiometric)?.isChecked = false
                        suppressSwitchEvents = false
                        toast("Set up fingerprint, face unlock, or phone screen lock first")
                    } else {
                        viewModel.setBiometricLockEnabled(checked)
                    }
                }
            }
    }

    private fun showRemovePhotoDialog() {
        val content = layoutInflater.inflate(R.layout.dialog_remove_profile_photo, null)
        val dialog = MaterialAlertDialogBuilder(requireContext(), R.style.Theme_TenantPro_Dialog_Form)
            .setView(content)
            .create()
        content.findViewById<View>(R.id.btnKeepPhoto).setOnClickListener { dialog.dismiss() }
        content.findViewById<View>(R.id.btnRemovePhoto).setOnClickListener {
            viewModel.removeProfileImage()
            binding.ivProfile.setImageResource(R.drawable.ic_account_circle)
            dialog.dismiss()
        }
        dialog.show()
    }

    private fun updateUI(state: AccountUiState) {
        latestState = state
        binding.progressLoading.visibility = if (state.loading || state.saving) View.VISIBLE else View.GONE

        val displayName = state.name.ifBlank { "Tenant User" }
        val displayEmail = state.email.ifBlank { "No email added" }
        val displayPhone = state.phone.ifBlank { "No phone added" }

        binding.tvUserName.text = displayName
        binding.tvUserEmail.text = displayEmail
        binding.tvUserPhone.text = displayPhone
        binding.tvSubscriptionStatus.visibility = if (state.showSubscriptionStatus) View.VISIBLE else View.GONE
        binding.tvSubscriptionDetail.visibility =
            if (state.showSubscriptionStatus && state.subscriptionDetailText.isNotBlank()) View.VISIBLE else View.GONE
        binding.tvSubscriptionStatus.text = state.subscriptionStatusText
        binding.tvSubscriptionDetail.text = state.subscriptionDetailText

        val statusColor = when {
            state.subscriptionStatusText.contains("Trial", ignoreCase = true) -> R.color.info
            state.subscriptionStatusText.contains("Active", ignoreCase = true) -> R.color.success
            state.subscriptionStatusText.contains("Past due", ignoreCase = true) -> R.color.error
            else -> R.color.on_surface_variant
        }
        binding.tvSubscriptionStatus.setTextColor(ContextCompat.getColor(requireContext(), statusColor))
        binding.root.findViewById<TextView>(R.id.tvSettingsPaymentPhone)?.text = displayPhone
        bindSwitchStates(state)

        val imageLoaded = if (!state.imageUri.isNullOrBlank()) {
            runCatching {
                Glide.with(this)
                    .load(state.imageUri.toAbsoluteAssetUrl())
                    .placeholder(R.drawable.ic_account_circle)
                    .error(R.drawable.ic_account_circle)
                    .into(binding.ivProfile)
            }.isSuccess
        } else false

        if (!imageLoaded) {
            Glide.with(this).clear(binding.ivProfile)
            binding.ivProfile.setImageResource(R.drawable.ic_account_circle)
            binding.ivProfile.imageTintList = android.content.res.ColorStateList.valueOf(
                androidx.core.content.ContextCompat.getColor(requireContext(), R.color.primary)
            )
        } else {
            binding.ivProfile.imageTintList = null
        }
    }

    private fun bindSwitchStates(state: AccountUiState) {
        suppressSwitchEvents = true
        binding.root.findViewById<SwitchMaterial>(R.id.switchNotifications)?.isChecked =
            state.notificationsEnabled
        binding.root.findViewById<SwitchMaterial>(R.id.switchEmailNotifications)?.isChecked =
            state.emailNotificationsEnabled
        binding.root.findViewById<SwitchMaterial>(R.id.switchBiometric)?.isChecked =
            state.biometricLockEnabled
        suppressSwitchEvents = false
    }

    private fun canUseBiometricLogin(): Boolean {
        return BiometricManager.from(requireContext())
            .canAuthenticate(BIOMETRIC_STRONG or DEVICE_CREDENTIAL) ==
            BiometricManager.BIOMETRIC_SUCCESS
    }

    private fun showAccountDetailsDialog() {
        val content = layoutInflater.inflate(R.layout.dialog_account_details, null)
        val name = content.findViewById<TextInputEditText>(R.id.etAccountName).apply { setText(latestState.name) }
        val email = content.findViewById<TextInputEditText>(R.id.etAccountEmail).apply { setText(latestState.email) }
        val emergency = content.findViewById<TextInputEditText>(R.id.etEmergencyPhone).apply { setText(latestState.emergencyContact) }
        val bio = content.findViewById<TextInputEditText>(R.id.etAccountBio).apply { setText(latestState.bio) }
        val nameLayout = content.findViewById<TextInputLayout>(R.id.tilAccountName)
        val emailLayout = content.findViewById<TextInputLayout>(R.id.tilAccountEmail)

        val dialog = MaterialAlertDialogBuilder(requireContext(), R.style.Theme_TenantPro_Dialog_Form)
            .setView(content)
            .create()
        content.findViewById<View>(R.id.btnAccountCancel).setOnClickListener { dialog.dismiss() }
        content.findViewById<View>(R.id.btnAccountSave).setOnClickListener {
            val nameValue = name.text?.toString()?.trim().orEmpty()
            val emailValue = email.text?.toString()?.trim().orEmpty()
            nameLayout.error = if (nameValue.length < 2) "Enter your full name" else null
            emailLayout.error = if (!android.util.Patterns.EMAIL_ADDRESS.matcher(emailValue).matches()) "Enter a valid email" else null
            if (nameLayout.error != null || emailLayout.error != null) return@setOnClickListener
            dismissKeyboard()
            viewModel.saveProfile(
                name = nameValue,
                phone = latestState.phone,
                email = emailValue,
                emergencyContact = emergency.text?.toString().orEmpty(),
                bio = bio.text?.toString().orEmpty()
            )
            dialog.dismiss()
        }
        dialog.show()
    }

    private fun showPaymentPhoneDialog() {
        val content = layoutInflater.inflate(R.layout.dialog_payment_phone, null)
        val phone = content.findViewById<TextInputEditText>(R.id.etPaymentPhone).apply { setText(latestState.phone) }
        val phoneLayout = content.findViewById<TextInputLayout>(R.id.tilPaymentPhone)
        val dialog = MaterialAlertDialogBuilder(requireContext(), R.style.Theme_TenantPro_Dialog_Form)
            .setView(content)
            .create()
        content.findViewById<View>(R.id.btnPhoneCancel).setOnClickListener { dialog.dismiss() }
        content.findViewById<View>(R.id.btnPhoneSave).setOnClickListener {
            val normalizedPhone = phone.text?.toString().orEmpty().normalizeKenyanPhone()
            phoneLayout.error = if (normalizedPhone == null) "Enter a valid Kenyan mobile number" else null
            if (normalizedPhone == null) return@setOnClickListener
            dismissKeyboard()
            viewModel.saveProfile(
                name = latestState.name,
                phone = normalizedPhone,
                email = latestState.email,
                emergencyContact = latestState.emergencyContact,
                bio = latestState.bio
            )
            dialog.dismiss()
        }
        dialog.show()
    }

    private fun showChangePasswordDialog() {
        val content = layoutInflater.inflate(R.layout.dialog_change_password, null)
        val current = content.findViewById<TextInputEditText>(R.id.etCurrentPassword)
        val password = content.findViewById<TextInputEditText>(R.id.etNewPassword)
        val confirmation = content.findViewById<TextInputEditText>(R.id.etConfirmPassword)
        val currentLayout = content.findViewById<TextInputLayout>(R.id.tilCurrentPassword)
        val passwordLayout = content.findViewById<TextInputLayout>(R.id.tilNewPassword)
        val confirmationLayout = content.findViewById<TextInputLayout>(R.id.tilConfirmPassword)

        val dialog = MaterialAlertDialogBuilder(requireContext(), R.style.Theme_TenantPro_Dialog_Form)
            .setView(content)
            .create()
        content.findViewById<View>(R.id.btnPasswordCancel).setOnClickListener { dialog.dismiss() }
        content.findViewById<View>(R.id.btnForgotPassword).setOnClickListener {
            dismissKeyboard()
            dialog.dismiss()
            findNavController().navigate(
                R.id.forgotPasswordFragment,
                bundleOf("email" to latestState.email)
            )
        }
        content.findViewById<View>(R.id.btnPasswordSave).setOnClickListener {
            val currentValue = current.text?.toString().orEmpty()
            val passwordValue = password.text?.toString().orEmpty()
            val confirmationValue = confirmation.text?.toString().orEmpty()
            currentLayout.error = if (currentValue.isBlank()) "Enter your current password" else null
            passwordLayout.error = when {
                passwordValue.length < 8 -> "Use at least 8 characters"
                passwordValue == currentValue -> "Choose a different password"
                else -> null
            }
            confirmationLayout.error = if (confirmationValue != passwordValue) "Passwords do not match" else null
            if (currentLayout.error != null || passwordLayout.error != null || confirmationLayout.error != null) return@setOnClickListener
            dismissKeyboard()
            viewModel.changePassword(currentValue, passwordValue, confirmationValue)
            dialog.dismiss()
        }
        dialog.show()
    }

    private fun showCurrentSessionDialog() {
        val device = listOf(Build.MANUFACTURER, Build.MODEL)
            .filter { it.isNotBlank() }
            .joinToString(" ")
        val content = layoutInflater.inflate(R.layout.dialog_settings_session, null)
        content.findViewById<TextView>(R.id.tvSessionDevice).text = device
        content.findViewById<TextView>(R.id.tvSessionSystem).text = "Android ${Build.VERSION.RELEASE}"
        val dialog = MaterialAlertDialogBuilder(requireContext(), R.style.Theme_TenantPro_Dialog_Form)
            .setView(content)
            .create()
        content.findViewById<View>(R.id.btnSessionClose).setOnClickListener { dialog.dismiss() }
        content.findViewById<View>(R.id.btnSessionSignOut).setOnClickListener {
            dialog.dismiss()
            binding.btnLogout.performClick()
        }
        dialog.show()
    }

    private fun persistReadPermission(uri: Uri) {
        val resolver = requireContext().contentResolver
        try {
            resolver.takePersistableUriPermission(uri, android.content.Intent.FLAG_GRANT_READ_URI_PERMISSION)
        } catch (_: SecurityException) {
            // Some providers do not grant persistable permissions.
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
