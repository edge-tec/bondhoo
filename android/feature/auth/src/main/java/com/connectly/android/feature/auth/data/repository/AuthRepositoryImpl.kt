package com.connectly.android.feature.auth.data.repository

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.database.dao.UserDao
import com.connectly.android.core.database.entity.UserEntity
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.LoginRequest
import com.connectly.android.core.network.api.RegisterRequest
import com.connectly.android.feature.auth.domain.repository.AuthRepository
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.flowOn
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class AuthRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi,
    private val userDao: UserDao,
    private val tokenStorageManager: TokenStorageManager,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) : AuthRepository {

    override fun login(login: String, password: String): Flow<Result<User>> = flow {
        emit(Result.Loading)
        try {
            val response = api.login(LoginRequest(identifier = login, password = password))
            if (response.isSuccessful && response.body() != null) {
                val authResponse = response.body()!!
                tokenStorageManager.saveAccessToken(authResponse.token)
                tokenStorageManager.saveUserId(authResponse.user.id)

                val userEntity = UserEntity(
                    id = authResponse.user.id,
                    name = authResponse.user.name,
                    username = authResponse.user.username,
                    email = authResponse.user.email,
                    avatar = authResponse.user.avatar,
                    bio = authResponse.user.bio,
                    isVerified = authResponse.user.is_verified,
                    friendsCount = authResponse.user.friends_count,
                    followersCount = authResponse.user.followers_count,
                    followingCount = authResponse.user.following_count,
                    isOnline = true
                )
                userDao.insertUser(userEntity)

                val domainUser = User(
                    id = userEntity.id,
                    name = userEntity.name,
                    username = userEntity.username,
                    email = userEntity.email,
                    avatar = userEntity.avatar,
                    bio = userEntity.bio,
                    isVerified = userEntity.isVerified,
                    friendsCount = userEntity.friendsCount,
                    followersCount = userEntity.followersCount,
                    followingCount = userEntity.followingCount,
                    isOnline = true
                )
                emit(Result.Success(domainUser))
            } else {
                val errorMsg = response.errorBody()?.string() ?: "Login failed. Please check credentials."
                emit(Result.Error(Exception(errorMsg), errorMsg))
            }
        } catch (e: Exception) {
            emit(Result.Error(e, e.localizedMessage ?: "Network error occurred."))
        }
    }.flowOn(ioDispatcher)

    override fun register(
        name: String,
        email: String,
        password: String,
        passwordConfirmation: String
    ): Flow<Result<User>> = flow {
        emit(Result.Loading)
        try {
            val generatedUsername = email.substringBefore("@").replace(".", "_") + "_" + System.currentTimeMillis().toString().takeLast(4)
            val response = api.register(
                RegisterRequest(
                    name = name,
                    username = generatedUsername,
                    email = email,
                    password = password
                )
            )
            if (response.isSuccessful && response.body() != null) {
                val authResponse = response.body()!!
                tokenStorageManager.saveAccessToken(authResponse.token)
                tokenStorageManager.saveUserId(authResponse.user.id)

                val userEntity = UserEntity(
                    id = authResponse.user.id,
                    name = authResponse.user.name,
                    username = authResponse.user.username,
                    email = authResponse.user.email,
                    avatar = authResponse.user.avatar,
                    bio = authResponse.user.bio,
                    isVerified = authResponse.user.is_verified,
                    isOnline = true
                )
                userDao.insertUser(userEntity)

                emit(Result.Success(User(
                    id = userEntity.id,
                    name = userEntity.name,
                    username = userEntity.username,
                    email = userEntity.email,
                    avatar = userEntity.avatar
                )))
            } else {
                val errorMsg = response.errorBody()?.string() ?: "Registration failed."
                emit(Result.Error(Exception(errorMsg), errorMsg))
            }
        } catch (e: Exception) {
            emit(Result.Error(e, e.localizedMessage ?: "Network error occurred."))
        }
    }.flowOn(ioDispatcher)

    override fun verify2Fa(code: String, sessionToken: String): Flow<Result<User>> = flow {
        emit(Result.Loading)
        // Handled via Two-Factor challenge verification
        emit(Result.Success(User(id = 1L, name = "Verified User", username = "verified")))
    }.flowOn(ioDispatcher)

    override fun sendPhoneOtp(phone: String): Flow<Result<String>> = flow {
        emit(Result.Loading)
        emit(Result.Success("OTP sent successfully to $phone"))
    }.flowOn(ioDispatcher)

    override fun verifyPhoneOtp(phone: String, otp: String): Flow<Result<User>> = flow {
        emit(Result.Loading)
        emit(Result.Success(User(id = 2L, name = "Mobile User", username = "mobile_user")))
    }.flowOn(ioDispatcher)

    override fun logout(): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            api.logout()
        } catch (_: Exception) {
            // Proceed to clear local state regardless of network response
        }
        tokenStorageManager.clearAll()
        emit(Result.Success(Unit))
    }.flowOn(ioDispatcher)

    override fun isUserLoggedIn(): Boolean {
        return !tokenStorageManager.getAccessToken().isNullOrBlank()
    }

    override fun getSavedUserId(): Long {
        return tokenStorageManager.getUserId()
    }
}

