package com.tenantpro.app.ui.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.AuthResponse
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class EmailVerificationViewModel @Inject constructor(
    private val repository: AuthRepository
) : ViewModel() {

    private val _verifyState = MutableStateFlow<Resource<AuthResponse>?>(null)
    val verifyState = _verifyState.asStateFlow()

    private val _resendState = MutableStateFlow<Resource<Unit>?>(null)
    val resendState = _resendState.asStateFlow()

    private val _events = MutableSharedFlow<String>()
    val events = _events.asSharedFlow()

    fun verify(email: String, code: String) {
        viewModelScope.launch {
            _verifyState.value = Resource.Loading
            _verifyState.value = repository.verifyEmailOtp(email, code)
        }
    }

    fun resendCode(email: String) {
        viewModelScope.launch {
            _resendState.value = Resource.Loading
            when (val result = repository.requestEmailOtp(email)) {
                is Resource.Success -> {
                    _resendState.value = Resource.Success(Unit)
                    _events.emit("Verification code sent to $email")
                }
                is Resource.Error -> {
                    _resendState.value = Resource.Error(result.message)
                    _events.emit(result.message)
                }
                Resource.Loading -> Unit
            }
        }
    }

    fun resetVerifyState() { _verifyState.value = null }
    fun resetResendState() { _resendState.value = null }
}
