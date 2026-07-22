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
import com.tenantpro.app.R
import com.tenantpro.app.databinding.FragmentEmailVerificationBinding
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.toast
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

@AndroidEntryPoint
class EmailVerificationFragment : Fragment() {

    private var _binding: FragmentEmailVerificationBinding? = null
    private val binding get() = _binding!!
    private val viewModel: EmailVerificationViewModel by viewModels()

    private lateinit var email: String

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentEmailVerificationBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        email = arguments?.getString("email").orEmpty()
        val fromRegister = arguments?.getBoolean("fromRegister", true) ?: true

        binding.tvEmailHint.text = "We sent a 6-digit code to\n$email"

        binding.etCode.doAfterTextChanged { text ->
            binding.btnVerify.isEnabled = text?.length == 6
        }

        binding.btnVerify.setOnClickListener {
            val code = binding.etCode.text?.toString()?.trim().orEmpty()
            viewModel.verify(email, code)
        }

        binding.tvResend.setOnClickListener {
            viewModel.resendCode(email)
        }

        binding.tvBackToLogin.setOnClickListener {
            findNavController().navigate(
                R.id.loginFragment,
                null,
                androidx.navigation.NavOptions.Builder()
                    .setPopUpTo(R.id.loginFragment, true)
                    .build()
            )
        }

        // If coming from login (not register), trigger a resend immediately
        if (!fromRegister) {
            viewModel.resendCode(email)
        }

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                launch { collectVerifyState() }
                launch { collectResendState() }
                launch { viewModel.events.collect { toast(it) } }
            }
        }
    }

    private suspend fun collectVerifyState() {
        viewModel.verifyState.collect { state ->
            when (state) {
                is Resource.Loading -> {
                    binding.progressBar.visible()
                    binding.btnVerify.isEnabled = false
                    binding.tvResend.isEnabled = false
                }
                is Resource.Success -> {
                    binding.progressBar.gone()
                    viewModel.resetVerifyState()
                    toast("Email verified! Welcome.")
                    findNavController().navigate(
                        R.id.homeFragment,
                        null,
                        androidx.navigation.NavOptions.Builder()
                            .setPopUpTo(R.id.loginFragment, true)
                            .build()
                    )
                }
                is Resource.Error -> {
                    binding.progressBar.gone()
                    binding.btnVerify.isEnabled = binding.etCode.text?.length == 6
                    binding.tvResend.isEnabled = true
                    binding.tilCode.error = state.message
                    viewModel.resetVerifyState()
                }
                null -> {
                    binding.progressBar.gone()
                    binding.tvResend.isEnabled = true
                }
            }
        }
    }

    private suspend fun collectResendState() {
        viewModel.resendState.collect { state ->
            when (state) {
                is Resource.Loading -> binding.tvResend.isEnabled = false
                is Resource.Success, is Resource.Error -> {
                    binding.tvResend.isEnabled = true
                    viewModel.resetResendState()
                }
                null -> binding.tvResend.isEnabled = true
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
