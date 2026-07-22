package com.tenantpro.app.ui.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ForgotPasswordViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _requestOtpState = MutableStateFlow<Resource<String>?>(null)
    val requestOtpState = _requestOtpState.asStateFlow()

    private val _resetPasswordState = MutableStateFlow<Resource<String>?>(null)
    val resetPasswordState = _resetPasswordState.asStateFlow()

    fun requestPasswordResetOtp(email: String) {
        viewModelScope.launch {
            _requestOtpState.value = Resource.Loading
            _requestOtpState.value = authRepository.forgotPassword(email)
        }
    }

    fun resetPassword(email: String, code: String, newPassword: String) {
        viewModelScope.launch {
            _resetPasswordState.value = Resource.Loading
            _resetPasswordState.value = authRepository.resetPassword(email, code, newPassword)
        }
    }

    fun resetRequestOtpState() {
        _requestOtpState.value = null
    }

    fun resetResetPasswordState() {
        _resetPasswordState.value = null
    }
}
