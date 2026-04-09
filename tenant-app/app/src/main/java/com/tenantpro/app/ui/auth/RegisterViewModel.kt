package com.tenantpro.app.ui.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.AuthResponse
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class RegisterViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    private val _registerState = MutableStateFlow<Resource<AuthResponse>?>(null)
    val registerState = _registerState.asStateFlow()

    fun register(
        email: String,
        password: String,
        fullName: String,
        phoneNumber: String
    ) {
        viewModelScope.launch {
            _registerState.value = Resource.Loading
            _registerState.value = authRepository.registerUser(
                email = email,
                password = password,
                fullName = fullName,
                phoneNumber = phoneNumber
            )
        }
    }

    fun resetRegisterState() {
        _registerState.value = null
    }
}
