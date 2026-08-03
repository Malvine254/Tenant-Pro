package com.tenantpro.app.ui.account

import android.content.res.ColorStateList
import android.net.Uri
import android.os.Bundle
import android.text.InputType
import android.view.ContextThemeWrapper
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.EditText
import android.widget.LinearLayout
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
import com.bumptech.glide.Glide
import com.google.android.material.color.MaterialColors
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.google.android.material.switchmaterial.SwitchMaterial
import com.google.android.material.textfield.TextInputEditText
import com.google.android.material.textfield.TextInputLayout
import com.tenantpro.app.R
import com.tenantpro.app.databinding.FragmentAccountSettingsBinding
import com.tenantpro.app.utils.toast
import com.tenantpro.app.utils.toAbsoluteAssetUrl
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
        binding.root.findViewById<View>(R.id.rowQuietHours).setOnClickListener {
            toast("Quiet hours are set to 10:00 PM - 7:00 AM")
        }
        binding.root.findViewById<View>(R.id.rowSecurity).setOnClickListener {
            MaterialAlertDialogBuilder(requireContext(), R.style.Theme_TenantPro_Dialog_Form)
                .setTitle("Security")
                .setMessage("Password changes are managed through password recovery for now.")
                .setPositiveButton("OK", null)
                .show()
        }
        binding.root.findViewById<View>(R.id.rowSessions).setOnClickListener {
            toast("This is your current signed-in session.")
        }
        binding.root.findViewById<View>(R.id.rowHelp).setOnClickListener {
            runCatching { findNavController().navigate(R.id.queriesFragment) }
                .onFailure { toast("Support is available from Chats.") }
        }

        binding.root.findViewById<SwitchMaterial>(R.id.switchNotifications)
            ?.setOnCheckedChangeListener { _, checked ->
                if (!suppressSwitchEvents) {
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
        MaterialAlertDialogBuilder(requireContext(), R.style.Theme_TenantPro_Dialog_Form)
            .setTitle(R.string.remove_photo_title)
            .setMessage(R.string.remove_photo_message)
            .setPositiveButton(R.string.remove) { _, _ ->
                viewModel.removeProfileImage()
                binding.ivProfile.setImageResource(R.drawable.ic_account_circle)
            }
            .setNegativeButton(R.string.cancel, null)
            .show()
    }

    private fun updateUI(state: AccountUiState) {
        latestState = state
        binding.progressLoading.visibility = if (state.loading) View.VISIBLE else View.GONE

        val displayName = state.name.ifBlank { "Tenant User" }
        val displayEmail = state.email.ifBlank { "No email added" }
        val displayPhone = state.phone.ifBlank { "No phone added" }

        binding.tvUserName.text = displayName
        binding.tvUserEmail.text = displayEmail
        binding.tvUserPhone.text = displayPhone
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
        val name = dialogField("Name", latestState.name)
        val email = dialogField("Email", latestState.email).apply {
            inputType = InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_EMAIL_ADDRESS
        }
        val emergency = dialogField("Emergency contact", latestState.emergencyContact).apply {
            inputType = InputType.TYPE_CLASS_PHONE
        }
        val bio = dialogField("Bio", latestState.bio).apply {
            minLines = 2
            maxLines = 3
        }

        val content = LinearLayout(dialogContext()).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(24, 8, 24, 0)
            addView(wrapField("Name", name))
            addView(wrapField("Email", email))
            addView(wrapField("Emergency contact", emergency))
            addView(wrapField("Bio", bio))
        }

        MaterialAlertDialogBuilder(requireContext(), R.style.Theme_TenantPro_Dialog_Form)
            .setTitle("Account details")
            .setView(content)
            .setNegativeButton("Cancel", null)
            .setPositiveButton("Save") { _, _ ->
                viewModel.saveProfile(
                    name = name.text?.toString().orEmpty(),
                    phone = latestState.phone,
                    email = email.text?.toString().orEmpty(),
                    emergencyContact = emergency.text?.toString().orEmpty(),
                    bio = bio.text?.toString().orEmpty()
                )
            }
            .show()
    }

    private fun showPaymentPhoneDialog() {
        val phone = dialogField("Payment phone", latestState.phone).apply {
            inputType = InputType.TYPE_CLASS_PHONE
        }
        val content = LinearLayout(dialogContext()).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(24, 8, 24, 0)
            addView(wrapField("Payment phone", phone))
        }

        MaterialAlertDialogBuilder(requireContext(), R.style.Theme_TenantPro_Dialog_Form)
            .setTitle("Payment phone")
            .setMessage("This number is used for M-Pesa prompts and payment updates.")
            .setView(content)
            .setNegativeButton("Cancel", null)
            .setPositiveButton("Save") { _, _ ->
                viewModel.saveProfile(
                    name = latestState.name,
                    phone = phone.text?.toString().orEmpty(),
                    email = latestState.email,
                    emergencyContact = latestState.emergencyContact,
                    bio = latestState.bio
                )
            }
            .show()
    }

    private fun dialogField(label: String, value: String): TextInputEditText =
        TextInputEditText(dialogContext()).apply {
            hint = label
            setText(value)
            textSize = 14f
            setTextColor(themedColor(com.google.android.material.R.attr.colorOnSurface, R.color.on_surface))
            setHintTextColor(
                themedColor(
                    com.google.android.material.R.attr.colorOnSurfaceVariant,
                    R.color.on_surface_variant
                )
            )
            backgroundTintList = ColorStateList.valueOf(
                themedColor(com.google.android.material.R.attr.colorPrimary, R.color.primary)
            )
            selectAll()
        }

    private fun wrapField(label: String, editText: EditText): TextInputLayout =
        TextInputLayout(dialogContext()).apply {
            hint = label
            setPadding(0, 6, 0, 6)
            boxBackgroundMode = TextInputLayout.BOX_BACKGROUND_OUTLINE
            setBoxBackgroundColor(
                themedColor(
                    com.google.android.material.R.attr.colorSurfaceContainerHigh,
                    R.color.surface
                )
            )
            setBoxStrokeColorStateList(dialogStrokeColors())
            hintTextColor = dialogHintColors()
            setHelperTextColor(dialogHintColors())
            addView(editText)
        }

    private fun dialogContext(): ContextThemeWrapper =
        ContextThemeWrapper(requireContext(), R.style.Theme_TenantPro_Dialog_Form)

    private fun dialogStrokeColors(): ColorStateList {
        val primary = themedColor(com.google.android.material.R.attr.colorPrimary, R.color.primary)
        val outline = themedColor(com.google.android.material.R.attr.colorOutline, R.color.outline_variant)
        return ColorStateList(
            arrayOf(
                intArrayOf(android.R.attr.state_focused),
                intArrayOf()
            ),
            intArrayOf(primary, outline)
        )
    }

    private fun dialogHintColors(): ColorStateList =
        ColorStateList.valueOf(
            themedColor(com.google.android.material.R.attr.colorOnSurfaceVariant, R.color.on_surface_variant)
        )

    private fun themedColor(attr: Int, fallbackRes: Int): Int =
        MaterialColors.getColor(requireContext(), attr, ContextCompat.getColor(requireContext(), fallbackRes))

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
