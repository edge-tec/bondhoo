package com.connectly.android.feature.auth.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.User
import kotlinx.coroutines.flow.Flow

interface AuthRepository {
    fun login(login: String, password: String): Flow<Result<User>>
    fun register(
        name: String,
        email: String,
        phone: String,
        birthDate: String,
        gender: String,
        password: String,
        passwordConfirmation: String
    ): Flow<Result<User>>
    fun verify2Fa(code: String, sessionToken: String): Flow<Result<User>>
    fun sendPhoneOtp(phone: String): Flow<Result<String>>
    fun verifyPhoneOtp(phone: String, otp: String): Flow<Result<User>>
    fun verifyEmailOtp(email: String, otp: String): Flow<Result<User>>
    fun resendEmailOtp(email: String): Flow<Result<String>>
    fun forgotPassword(email: String): Flow<Result<String>>
    fun logout(): Flow<Result<Unit>>
    fun isUserLoggedIn(): Boolean
    fun getSavedUserId(): Long
}
