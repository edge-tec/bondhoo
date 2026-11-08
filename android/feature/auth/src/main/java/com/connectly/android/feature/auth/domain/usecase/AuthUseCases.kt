package com.connectly.android.feature.auth.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.User
import com.connectly.android.feature.auth.domain.repository.AuthRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

class LoginUseCase @Inject constructor(
    private val repository: AuthRepository
) {
    operator fun invoke(login: String, password: String): Flow<Result<User>> {
        return repository.login(login.trim(), password)
    }
}

class RegisterUseCase @Inject constructor(
    private val repository: AuthRepository
) {
    operator fun invoke(
        name: String,
        email: String,
        phone: String,
        birthDate: String,
        gender: String,
        password: String,
        passwordConfirmation: String
    ): Flow<Result<User>> {
        return repository.register(
            name.trim(),
            email.trim(),
            phone.trim(),
            birthDate.trim(),
            gender.trim(),
            password,
            passwordConfirmation
        )
    }
}

class SendPhoneOtpUseCase @Inject constructor(
    private val repository: AuthRepository
) {
    operator fun invoke(phone: String): Flow<Result<String>> {
        return repository.sendPhoneOtp(phone.trim())
    }
}

class VerifyPhoneOtpUseCase @Inject constructor(
    private val repository: AuthRepository
) {
    operator fun invoke(phone: String, otp: String): Flow<Result<User>> {
        return repository.verifyPhoneOtp(phone.trim(), otp.trim())
    }
}

class Verify2FaUseCase @Inject constructor(
    private val repository: AuthRepository
) {
    operator fun invoke(code: String, sessionToken: String): Flow<Result<User>> {
        return repository.verify2Fa(code.trim(), sessionToken)
    }
}

class LogoutUseCase @Inject constructor(
    private val repository: AuthRepository
) {
    operator fun invoke(): Flow<Result<Unit>> {
        return repository.logout()
    }
}

class CheckAuthStatusUseCase @Inject constructor(
    private val repository: AuthRepository
) {
    operator fun invoke(): Boolean {
        return repository.isUserLoggedIn()
    }
}

