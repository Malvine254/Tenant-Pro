package com.tenantpro.app.ui.auth

import android.os.Bundle
import android.util.Patterns
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.widget.doAfterTextChanged
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.navigation.fragment.findNavController
import com.tenantpro.app.databinding.FragmentForgotPasswordBinding
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.toast
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

@AndroidEntryPoint
class ForgotPasswordFragment : Fragment() {

    private var _binding: FragmentForgotPasswordBinding? = null
    private val binding get() = _binding!!

    private val viewModel: ForgotPasswordViewModel by viewModels()

    private var currentEmail: String = ""
    private var temporaryPasswordSetup: Boolean = false
    private var otpRequestInFlight: Boolean = false

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentForgotPasswordBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        currentEmail = arguments?.getString("email").orEmpty()
        temporaryPasswordSetup = arguments?.getBoolean("temporaryPassword", false) == true
        if (currentEmail.isNotBlank()) {
            binding.etEmail.setText(currentEmail)
        }
        if (temporaryPasswordSetup) {
            setupTemporaryPasswordCopy()
        }

        setupRequestOtpScreen()
        observeRequestOtpState()
        observeResetPasswordState()

        if (temporaryPasswordSetup && currentEmail.isValidEmail()) {
            requestResetCode(currentEmail, showFormImmediately = true)
        } else {
            updateSendCodeButton()
            updateResetButton()
        }
    }

    private fun setupRequestOtpScreen() {
        binding.tvBackToLogin.setOnClickListener {
            findNavController().popBackStack()
        }

        binding.etEmail.doAfterTextChanged {
            binding.tilEmail.error = null
            updateSendCodeButton()
        }
        binding.etOtpCode.doAfterTextChanged {
            binding.tilOtpCode.error = null
            updateResetButton()
        }
        binding.etNewPassword.doAfterTextChanged { updateResetButton() }
        binding.etConfirmPassword.doAfterTextChanged { updateResetButton() }

        binding.btnSendOtp.setOnClickListener {
            val email = binding.etEmail.text?.toString()?.trim().orEmpty()
            if (!email.isValidEmail()) {
                binding.tilEmail.error = "Enter a valid email address"
                return@setOnClickListener
            }
            requestResetCode(email)
        }

        binding.btnResetPassword.setOnClickListener {
            submitPasswordReset()
        }
    }

    private fun setupTemporaryPasswordCopy() {
        binding.tvRequestTitle.text = "Update your password"
        binding.tvResetIntro.text = "Choose a password only you know. We'll email a verification code to confirm the change."
        binding.btnSendOtp.text = "Send Code"
        binding.tvResetTitle.text = "Create new password"
        binding.tvResetSubtitle.text = "Enter the code sent to $currentEmail, then save your new password."
        binding.btnResetPassword.text = "Save New Password"
    }

    private fun requestResetCode(email: String, showFormImmediately: Boolean = false) {
        currentEmail = email.trim()
        binding.tilEmail.error = null
        if (showFormImmediately) {
            showResetPasswordScreen()
            binding.tvResetSubtitle.text = "Enter the code sent to $currentEmail, then save your new password."
        }
        viewModel.requestPasswordResetOtp(currentEmail)
    }

    private fun submitPasswordReset() {
        val code = binding.etOtpCode.text?.toString()?.trim().orEmpty()
        val newPassword = binding.etNewPassword.text?.toString().orEmpty()
        val confirmPassword = binding.etConfirmPassword.text?.toString().orEmpty()

        if (!currentEmail.isValidEmail()) {
            toast("Enter a valid email address first")
            showRequestOtpScreen()
            return
        }
        if (code.length != 6) {
            binding.tilOtpCode.error = "Enter the 6 digit code"
            return
        }
        if (newPassword.length < 8) {
            toast("Password must be at least 8 characters")
            return
        }
        if (newPassword != confirmPassword) {
            toast("Passwords do not match")
            return
        }

        viewModel.resetPassword(currentEmail, code, newPassword)
    }

    private fun observeRequestOtpState() {
        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.requestOtpState.collect { state ->
                    when (state) {
                        is Resource.Loading -> {
                            otpRequestInFlight = true
                            binding.progressBar.visible()
                            binding.btnSendOtp.isEnabled = false
                        }
                        is Resource.Success -> {
                            otpRequestInFlight = false
                            binding.progressBar.gone()
                            viewModel.resetRequestOtpState()
                            toast(state.data)
                            showResetPasswordScreen()
                            updateSendCodeButton()
                        }
                        is Resource.Error -> {
                            otpRequestInFlight = false
                            binding.progressBar.gone()
                            if (temporaryPasswordSetup) {
                                showRequestOtpScreen()
                            }
                            toast(state.message)
                            viewModel.resetRequestOtpState()
                            updateSendCodeButton()
                        }
                        null -> {
                            otpRequestInFlight = false
                            binding.progressBar.gone()
                            updateSendCodeButton()
                        }
                    }
                }
            }
        }
    }

    private fun observeResetPasswordState() {
        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.resetPasswordState.collect { state ->
                    when (state) {
                        is Resource.Loading -> {
                            binding.progressBarReset.visible()
                            binding.btnResetPassword.isEnabled = false
                        }
                        is Resource.Success -> {
                            binding.progressBarReset.gone()
                            viewModel.resetResetPasswordState()
                            toast(if (temporaryPasswordSetup) "Password changed. Please sign in." else "Password reset and email verified. Please sign in.")
                            findNavController().navigate(
                                com.tenantpro.app.R.id.action_forgotPasswordFragment_to_loginFragment
                            )
                        }
                        is Resource.Error -> {
                            binding.progressBarReset.gone()
                            toast(state.message)
                            viewModel.resetResetPasswordState()
                            updateResetButton()
                        }
                        null -> {
                            binding.progressBarReset.gone()
                            updateResetButton()
                        }
                    }
                }
            }
        }
    }

    private fun showResetPasswordScreen() {
        binding.layoutRequestOtp.gone()
        binding.layoutResetPassword.visible()
        binding.tvResetSubtitle.text = if (currentEmail.isNotBlank()) {
            "Enter the code sent to $currentEmail and choose a new password."
        } else {
            "Enter the code sent to your email and choose a new password."
        }
        updateResetButton()
    }

    private fun showRequestOtpScreen() {
        binding.layoutResetPassword.gone()
        binding.layoutRequestOtp.visible()
        updateSendCodeButton()
    }

    private fun updateSendCodeButton() {
        val email = binding.etEmail.text?.toString()?.trim().orEmpty()
        binding.btnSendOtp.isEnabled = email.isValidEmail() && !otpRequestInFlight
    }

    private fun updateResetButton() {
        val code = binding.etOtpCode.text?.toString()?.trim().orEmpty()
        val newPassword = binding.etNewPassword.text?.toString().orEmpty()
        val confirmPassword = binding.etConfirmPassword.text?.toString().orEmpty()
        binding.btnResetPassword.isEnabled =
            code.length == 6 && newPassword.length >= 8 && confirmPassword.length >= 8
    }

    private fun String.isValidEmail(): Boolean {
        return isNotBlank() && Patterns.EMAIL_ADDRESS.matcher(this).matches()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
