package com.tenantpro.app.ui.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.AuthResponse
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class OtpViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _verifyState = MutableStateFlow<Resource<AuthResponse>?>(null)
    val verifyState: StateFlow<Resource<AuthResponse>?> = _verifyState.asStateFlow()

    fun verifyOtp(phoneNumber: String, code: String) {
        viewModelScope.launch {
            _verifyState.value = Resource.Loading
            _verifyState.value = authRepository.verifyOtp(phoneNumber, code)
        }
    }

    fun reset() { _verifyState.value = null }
}
