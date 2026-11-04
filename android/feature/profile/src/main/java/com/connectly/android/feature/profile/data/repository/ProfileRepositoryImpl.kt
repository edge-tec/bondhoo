package com.connectly.android.feature.profile.data.repository

import com.connectly.android.core.common.network.ConnectlyDispatchers
import com.connectly.android.core.common.network.Dispatcher
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.database.dao.UserDao
import com.connectly.android.core.database.entity.UserEntity
import com.connectly.android.core.datastore.TokenStorageManager
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.UpdatePrivacySettingsRequest
import com.connectly.android.core.network.api.UpdateProfileRequest
import com.connectly.android.feature.profile.domain.repository.ProfileRepository
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.flowOn
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import java.io.File
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class ProfileRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi,
    private val userDao: UserDao,
    private val tokenStorageManager: TokenStorageManager,
    @Dispatcher(ConnectlyDispatchers.IO) private val ioDispatcher: CoroutineDispatcher
) : ProfileRepository {

    override fun getUserProfile(username: String): Flow<Result<User>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getMe()
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val user = User(
                    id = dto.id,
                    name = dto.name,
                    username = dto.username,
                    email = dto.email,
                    avatar = dto.avatar,
                    coverPhoto = "https://images.unsplash.com/photo-1506744038136-46273834b3fb",
                    bio = dto.bio ?: "Dream Big • Work Hard • Be Happy",
                    occupation = "Software Engineer",
                    location = "Dhaka, Bangladesh",
                    isVerified = dto.is_verified,
                    friendsCount = if (dto.friends_count > 0) dto.friends_count else 2540,
                    followersCount = if (dto.followers_count > 0) dto.followers_count else 865,
                    followingCount = if (dto.following_count > 0) dto.following_count else 128
                )
                // Cache user in Room for offline profile view
                userDao.insertUser(
                    UserEntity(
                        id = user.id,
                        name = user.name,
                        username = user.username,
                        email = user.email,
                        avatar = user.avatar,
                        coverPhoto = user.coverPhoto,
                        bio = user.bio,
                        isVerified = user.isVerified,
                        friendsCount = user.friendsCount,
                        followersCount = user.followersCount,
                        followingCount = user.followingCount
                    )
                )
                emit(Result.Success(user))
            } else {
                emit(Result.Error(Exception("Failed to fetch user profile.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun updateProfile(
        bio: String,
        occupation: String,
        location: String
    ): Flow<Result<User>> = flow {
        emit(Result.Loading)
        try {
            val response = api.updateProfile(
                UpdateProfileRequest(
                    bio = bio,
                    occupation = occupation,
                    location = location
                )
            )
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data
                val user = User(
                    id = dto.id,
                    name = dto.name,
                    username = dto.username,
                    email = dto.email,
                    avatar = dto.avatar,
                    bio = bio,
                    occupation = occupation,
                    location = location,
                    isVerified = dto.is_verified,
                    friendsCount = dto.friends_count,
                    followersCount = dto.followers_count,
                    followingCount = dto.following_count
                )
                userDao.insertUser(
                    UserEntity(
                        id = user.id,
                        name = user.name,
                        username = user.username,
                        email = user.email,
                        avatar = user.avatar,
                        bio = user.bio,
                        isVerified = user.isVerified,
                        friendsCount = user.friendsCount,
                        followersCount = user.followersCount,
                        followingCount = user.followingCount
                    )
                )
                emit(Result.Success(user))
            } else {
                emit(Result.Error(Exception("Profile update failed.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun uploadAvatar(file: File): Flow<Result<String>> = flow {
        emit(Result.Loading)
        try {
            val requestFile = file.asRequestBody("image/*".toMediaTypeOrNull())
            val body = MultipartBody.Part.createFormData("file", file.name, requestFile)
            val response = api.uploadAvatar(body)
            if (response.isSuccessful && response.body() != null) {
                emit(Result.Success(response.body()!!.data.url ?: ""))
            } else {
                emit(Result.Error(Exception("Failed to upload avatar.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun uploadCover(file: File): Flow<Result<String>> = flow {
        emit(Result.Loading)
        try {
            val requestFile = file.asRequestBody("image/*".toMediaTypeOrNull())
            val body = MultipartBody.Part.createFormData("file", file.name, requestFile)
            val response = api.uploadCover(body)
            if (response.isSuccessful && response.body() != null) {
                emit(Result.Success(response.body()!!.data.url ?: ""))
            } else {
                emit(Result.Error(Exception("Failed to upload cover photo.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun sendFriendRequest(userId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.sendFriendRequest(userId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to send friend request.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun acceptFriendRequest(userId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.acceptFriendRequest(userId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to accept friend request.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun rejectFriendRequest(userId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.rejectFriendRequest(userId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to reject friend request.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun unfriendUser(userId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.unfriendUser(userId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to remove friend.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun followUser(userId: Long): Flow<Result<Boolean>> = flow {
        emit(Result.Loading)
        try {
            val response = api.followUser(userId)
            if (response.isSuccessful) {
                emit(Result.Success(true))
            } else {
                emit(Result.Error(Exception("Failed to follow user.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun unfollowUser(userId: Long): Flow<Result<Boolean>> = flow {
        emit(Result.Loading)
        try {
            val response = api.unfollowUser(userId)
            if (response.isSuccessful) {
                emit(Result.Success(false))
            } else {
                emit(Result.Error(Exception("Failed to unfollow user.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun blockUser(userId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.blockUser(userId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to block user.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun unblockUser(userId: Long): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.unblockUser(userId)
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to unblock user.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)

    override fun updatePrivacySettings(visibility: String): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.updatePrivacySettings(
                UpdatePrivacySettingsRequest(
                    profile_visibility = visibility,
                    avatar_privacy = "public",
                    bio_privacy = visibility
                )
            )
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Failed to update privacy settings.")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }.flowOn(ioDispatcher)
}
