package com.tenantpro.app.ui.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.tenantpro.app.data.model.AuthResponse
import com.tenantpro.app.data.repository.AuthRepository
import com.tenantpro.app.utils.Resource
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharingStarted
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.stateIn
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val authRepository: AuthRepository
) : ViewModel() {

    // Tracks whether the initial token-check is still running (for splash screen).
    private val _isCheckingToken = MutableStateFlow(true)
    val isCheckingToken: StateFlow<Boolean> = _isCheckingToken.asStateFlow()

    // Emits true once a valid JWT is found in DataStore.
    val isLoggedIn: StateFlow<Boolean> = authRepository.isLoggedIn
        .stateIn(
            scope = viewModelScope,
            started = SharingStarted.WhileSubscribed(5_000),
            initialValue = false
        )

    private val _loginState = MutableStateFlow<Resource<AuthResponse>?>(null)
    val loginState: StateFlow<Resource<AuthResponse>?> = _loginState.asStateFlow()

    init {
        // Mark token check as done once the first emission arrives
        viewModelScope.launch {
            authRepository.isLoggedIn.collect { _isCheckingToken.value = false }
        }
    }

    fun login(email: String, password: String) {
        viewModelScope.launch {
            _loginState.value = Resource.Loading
            _loginState.value = authRepository.loginWithEmailPassword(email, password)
        }
    }

    suspend fun hasSavedSession(): Boolean = authRepository.isLoggedIn.first()

    suspend fun logout() = authRepository.logout()

    fun resetLoginState() { _loginState.value = null }
}
