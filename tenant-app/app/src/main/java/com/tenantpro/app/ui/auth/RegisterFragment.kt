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
import com.tenantpro.app.databinding.FragmentRegisterBinding
import com.tenantpro.app.utils.Resource
import com.tenantpro.app.utils.gone
import com.tenantpro.app.utils.toast
import com.tenantpro.app.utils.visible
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

@AndroidEntryPoint
class RegisterFragment : Fragment() {

    private var _binding: FragmentRegisterBinding? = null
    private val binding get() = _binding!!

    private val viewModel: RegisterViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentRegisterBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val updateButtonState = {
            val email = binding.etRegEmail.text?.toString()?.trim().orEmpty()
            val password = binding.etRegPassword.text?.toString()?.trim().orEmpty()
            val confirmPassword = binding.etRegConfirmPassword.text?.toString()?.trim().orEmpty()
            val name = binding.etRegName.text?.toString()?.trim().orEmpty()
            val phone = binding.etRegPhone.text?.toString()?.trim().orEmpty()
            val termsAccepted = binding.cbTerms.isChecked

            val isPasswordValid = password.length >= 8 &&
                    password.any { it.isUpperCase() } &&
                    password.any { it.isLowerCase() } &&
                    password.any { it.isDigit() }

            binding.btnRegister.isEnabled =
                email.isNotBlank() &&
                        name.isNotBlank() &&
                        phone.isNotBlank() &&
                        password.isNotBlank() &&
                        confirmPassword.isNotBlank() &&
                        password == confirmPassword &&
                        isPasswordValid &&
                        termsAccepted
        }

        binding.etRegEmail.doAfterTextChanged { updateButtonState() }
        binding.etRegPassword.doAfterTextChanged { updateButtonState() }
        binding.etRegConfirmPassword.doAfterTextChanged { updateButtonState() }
        binding.etRegName.doAfterTextChanged { updateButtonState() }
        binding.etRegPhone.doAfterTextChanged { updateButtonState() }
        binding.cbTerms.setOnCheckedChangeListener { _, _ -> updateButtonState() }

        binding.tvLoginLink.setOnClickListener {
            findNavController().popBackStack()
        }

        binding.btnRegister.setOnClickListener {
            val email = binding.etRegEmail.text?.toString()?.trim() ?: return@setOnClickListener
            val password = binding.etRegPassword.text?.toString()?.trim() ?: return@setOnClickListener
            val name = binding.etRegName.text?.toString()?.trim() ?: return@setOnClickListener
            val phone = binding.etRegPhone.text?.toString()?.trim() ?: return@setOnClickListener

            viewModel.register(
                email = email,
                password = password,
                fullName = name,
                phoneNumber = phone
            )
        }

        viewLifecycleOwner.lifecycleScope.launch {
            repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.registerState.collect { state ->
                    when (state) {
                        is Resource.Loading -> {
                            binding.progressBar.visible()
                            binding.btnRegister.isEnabled = false
                        }
                        is Resource.Success -> {
                            binding.progressBar.gone()
                            viewModel.resetRegisterState()
                            toast("Account created successfully! Redirecting...")
                            findNavController().navigate(
                                R.id.homeFragment,
                                null,
                                androidx.navigation.NavOptions.Builder()
                                    .setPopUpTo(R.id.registerFragment, true)
                                    .build()
                            )
                        }
                        is Resource.Error -> {
                            binding.progressBar.gone()
                            binding.btnRegister.isEnabled = true
                            toast(state.message)
                            viewModel.resetRegisterState()
                        }
                        null -> {
                            binding.progressBar.gone()
                        }
                    }
                }
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
