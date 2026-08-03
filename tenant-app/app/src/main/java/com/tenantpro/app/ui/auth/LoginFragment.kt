package com.tenantpro.app.ui.auth

import android.os.Bundle
import android.util.Patterns
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricManager.Authenticators.BIOMETRIC_STRONG
import androidx.biometric.BiometricManager.Authenticators.DEVICE_CREDENTIAL
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import androidx.core.widget.doAfterTextChanged
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.navigation.fragment.findNavController
import com.tenantpro.app.MainActivity
import com.tenantpro.app.R
import com.tenantpro.app.databinding.FragmentLoginBinding
import com.tenantpro.app.utils.DataStoreManager
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.toast
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.launch
import javax.inject.Inject

@AndroidEntryPoint
class LoginFragment : Fragment() {

    private var _binding: FragmentLoginBinding? = null
    private val binding get() = _binding!!

    private val viewModel: LoginViewModel by viewModels()

    @Inject
    lateinit var dataStoreManager: DataStoreManager

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentLoginBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val updateButtonState = {
            val email = binding.etEmail.text?.toString()?.trim().orEmpty()
            val password = binding.etPassword.text?.toString()?.trim().orEmpty()
            binding.btnLogin.isEnabled = email.isNotBlank() && password.length >= 6
        }

        binding.etEmail.doAfterTextChanged { updateButtonState() }
        binding.etPassword.doAfterTextChanged { updateButtonState() }

        binding.btnLogin.setOnClickListener {
            val email = binding.etEmail.text?.toString()?.trim() ?: return@setOnClickListener
            val password = binding.etPassword.text?.toString()?.trim() ?: return@setOnClickListener
            if (!Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
                binding.tilEmail.error = "Enter a valid email address"
                return@setOnClickListener
            }
            if (password.length < 6) {
                binding.tilPassword.error = "Enter your password"
                return@setOnClickListener
            }
            binding.tilEmail.error = null
            binding.tilPassword.error = null
            viewModel.login(email, password)
        }

        binding.btnBiometricLogin.setOnClickListener {
            showBiometricLoginPrompt()
        }
        updateBiometricLoginVisibility()

        binding.tvRegisterLink.setOnClickListener {
            findNavController().navigate(R.id.action_loginFragment_to_registerFragment)
        }

        binding.tvForgotPassword.setOnClickListener {
            findNavController().navigate(R.id.action_loginFragment_to_forgotPasswordFragment)
        }

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.loginState.collect { state ->
                    when (state) {
                        is Resource.Loading -> {
                            binding.progressBar.visible()
                            binding.btnLogin.isEnabled = false
                        }
                        is Resource.Success -> {
                            binding.progressBar.gone()
                            viewModel.resetLoginState()
                            navigateHome()
                        }
                        is Resource.Error -> {
                            binding.progressBar.gone()
                            binding.btnLogin.isEnabled = true
                            viewModel.resetLoginState()
                            if (state.message.contains("verify", ignoreCase = true)) {
                                val email = binding.etEmail.text?.toString()?.trim().orEmpty()
                                showUnverifiedDialog(email)
                            } else {
                                toast(state.message)
                            }
                        }
                        null -> {
                            binding.progressBar.gone()
                        }
                    }
                }
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                dataStoreManager.biometricLockEnabled.collect {
                    updateBiometricLoginVisibility()
                }
            }
        }
    }

    private fun updateBiometricLoginVisibility() {
        viewLifecycleOwner.lifecycleScope.launch {
            val enabled = dataStoreManager.biometricLockEnabled.firstOrNull() ?: false
            val hasBiometricSession = viewModel.hasSavedBiometricSession()
            val canAuthenticate = canAuthenticateWithBiometric()

            if (!enabled) {
                binding.btnBiometricLogin.visibility = View.GONE
                return@launch
            }

            binding.btnBiometricLogin.visibility = View.VISIBLE
            binding.btnBiometricLogin.isEnabled = enabled && hasBiometricSession && canAuthenticate
            binding.btnBiometricLogin.text = when {
                enabled && hasBiometricSession && canAuthenticate -> "Unlock with biometric"
                !canAuthenticate -> "Set phone lock for biometric"
                else -> "Sign in once to use biometric"
            }
        }
    }

    private fun showBiometricLoginPrompt() {
        viewLifecycleOwner.lifecycleScope.launch {
            val enabled = dataStoreManager.biometricLockEnabled.firstOrNull() ?: false
            val hasBiometricSession = viewModel.hasSavedBiometricSession()
            when {
                !canAuthenticateWithBiometric() -> toast("Set up fingerprint, face unlock, or phone screen lock first")
                !enabled -> toast("Sign in, open Settings, then enable biometric lock")
                !hasBiometricSession -> toast("Sign in once before using biometric login")
                else -> launchBiometricPrompt()
            }
        }
    }

    private fun launchBiometricPrompt() {
        val executor = ContextCompat.getMainExecutor(requireContext())
        val prompt = BiometricPrompt(
            this,
            executor,
            object : BiometricPrompt.AuthenticationCallback() {
                override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
                    super.onAuthenticationSucceeded(result)
                    viewLifecycleOwner.lifecycleScope.launch {
                        if (viewModel.restoreBiometricSession()) {
                            navigateHome()
                        } else {
                            toast("Biometric session expired. Sign in with password once.")
                            updateBiometricLoginVisibility()
                        }
                    }
                }

                override fun onAuthenticationError(errorCode: Int, errString: CharSequence) {
                    super.onAuthenticationError(errorCode, errString)
                    if (errorCode != BiometricPrompt.ERROR_NEGATIVE_BUTTON &&
                        errorCode != BiometricPrompt.ERROR_USER_CANCELED &&
                        errorCode != BiometricPrompt.ERROR_CANCELED
                    ) {
                        toast(errString.toString())
                    }
                }
            }
        )

        val promptInfo = BiometricPrompt.PromptInfo.Builder()
            .setTitle("Unlock Tenant Pro")
            .setSubtitle("Use fingerprint, face unlock, or your phone screen lock")
            .setAllowedAuthenticators(BIOMETRIC_STRONG or DEVICE_CREDENTIAL)
            .build()

        prompt.authenticate(promptInfo)
    }

    private fun canAuthenticateWithBiometric(): Boolean {
        return BiometricManager.from(requireContext())
            .canAuthenticate(BIOMETRIC_STRONG or DEVICE_CREDENTIAL) ==
            BiometricManager.BIOMETRIC_SUCCESS
    }

    private fun navigateHome() {
        (activity as? MainActivity)?.markAppUnlockedForSession()
        findNavController().navigate(
            R.id.homeFragment,
            null,
            androidx.navigation.NavOptions.Builder()
                .setPopUpTo(R.id.loginFragment, true)
                .build()
        )
    }

    private fun showUnverifiedDialog(email: String) {
        androidx.appcompat.app.AlertDialog.Builder(requireContext())
            .setTitle("Email not verified")
            .setMessage("Your account is not activated yet. Please verify your email address to continue.")
            .setPositiveButton("Send code") { _, _ ->
                findNavController().navigate(
                    R.id.action_loginFragment_to_emailVerificationFragment,
                    android.os.Bundle().apply {
                        putString("email", email)
                        putBoolean("fromRegister", false)
                    }
                )
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
