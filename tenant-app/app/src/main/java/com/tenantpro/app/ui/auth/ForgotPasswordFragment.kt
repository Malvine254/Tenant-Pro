package com.tenantpro.app.ui.auth

import android.os.Bundle
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
    private var otpSent: Boolean = false

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentForgotPasswordBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupRequestOtpScreen()
        observeRequestOtpState()
        observeResetPasswordState()
    }

    private fun setupRequestOtpScreen() {
        binding.tvBackToLogin.setOnClickListener {
            findNavController().popBackStack()
        }

        val updateButtonState = {
            val email = binding.etEmail.text?.toString()?.trim().orEmpty()
            binding.btnSendOtp.isEnabled = email.isNotBlank() && android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()
        }

        binding.etEmail.doAfterTextChanged { updateButtonState() }

        binding.btnSendOtp.setOnClickListener {
            val email = binding.etEmail.text?.toString()?.trim() ?: return@setOnClickListener
            currentEmail = email
            viewModel.requestPasswordResetOtp(email)
        }

        binding.btnResetPassword.setOnClickListener {
            val code = binding.etOtpCode.text?.toString()?.trim() ?: return@setOnClickListener
            val newPassword = binding.etNewPassword.text?.toString()?.trim() ?: return@setOnClickListener
            val confirmPassword = binding.etConfirmPassword.text?.toString()?.trim() ?: return@setOnClickListener

            if (code.length < 4) {
                toast("Please enter the OTP code")
                return@setOnClickListener
            }

            if (newPassword.length < 8) {
                toast("Password must be at least 8 characters")
                return@setOnClickListener
            }

            if (newPassword != confirmPassword) {
                toast("Passwords do not match")
                return@setOnClickListener
            }

            viewModel.resetPassword(currentEmail, code, newPassword)
        }
    }

    private fun observeRequestOtpState() {
        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.requestOtpState.collect { state ->
                    when (state) {
                        is Resource.Loading -> {
                            binding.progressBar.visible()
                            binding.btnSendOtp.isEnabled = false
                        }
                        is Resource.Success -> {
                            binding.progressBar.gone()
                            binding.btnSendOtp.isEnabled = true
                            viewModel.resetRequestOtpState()
                            toast(state.data)
                            showResetPasswordScreen()
                        }
                        is Resource.Error -> {
                            binding.progressBar.gone()
                            binding.btnSendOtp.isEnabled = true
                            toast(state.message)
                            viewModel.resetRequestOtpState()
                        }
                        null -> {
                            binding.progressBar.gone()
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
                            toast("Password reset successfully! Please login.")
                            findNavController().popBackStack()
                        }
                        is Resource.Error -> {
                            binding.progressBarReset.gone()
                            binding.btnResetPassword.isEnabled = true
                            toast(state.message)
                            viewModel.resetResetPasswordState()
                        }
                        null -> {
                            binding.progressBarReset.gone()
                        }
                    }
                }
            }
        }
    }

    private fun showResetPasswordScreen() {
        otpSent = true
        binding.layoutRequestOtp.gone()
        binding.layoutResetPassword.visible()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
