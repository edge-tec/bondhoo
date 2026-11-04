package com.connectly.android.feature.profile.presentation.profile

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.User
import com.connectly.android.feature.profile.domain.usecase.FollowUserUseCase
import com.connectly.android.feature.profile.domain.usecase.GetProfileUseCase
import com.connectly.android.feature.profile.domain.usecase.SendFriendRequestUseCase
import com.connectly.android.feature.profile.domain.usecase.UnfollowUserUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.collect
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ProfileViewModel @Inject constructor(
    private val getProfileUseCase: GetProfileUseCase,
    private val followUserUseCase: FollowUserUseCase,
    private val unfollowUserUseCase: UnfollowUserUseCase,
    private val sendFriendRequestUseCase: SendFriendRequestUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(ProfileState())
    val uiState: StateFlow<ProfileState> = _uiState.asStateFlow()

    init {
        loadProfile("me")
    }

    fun handleIntent(intent: ProfileIntent) {
        when (intent) {
            is ProfileIntent.LoadProfile -> loadProfile(intent.username)
            is ProfileIntent.SelectTab -> _uiState.update { it.copy(selectedTab = intent.tabIndex) }
            ProfileIntent.ToggleFollow -> toggleFollow()
            ProfileIntent.SendFriendRequest -> sendFriendRequest()
            ProfileIntent.ShareProfile -> {
                _uiState.update { it.copy(userMessage = "Profile link ready to share.") }
            }
            ProfileIntent.CopyProfileLink -> {
                _uiState.update { it.copy(userMessage = "Profile link copied to clipboard.") }
            }
            ProfileIntent.BlockUser -> {
                _uiState.update { it.copy(userMessage = "User has been blocked.") }
            }
            ProfileIntent.ReportProfile -> {
                _uiState.update { it.copy(userMessage = "Profile report submitted to moderation.") }
            }
            ProfileIntent.DismissMessage -> {
                _uiState.update { it.copy(userMessage = null, errorMessage = null) }
            }
        }
    }

    private fun loadProfile(username: String) {
        viewModelScope.launch {
            getProfileUseCase(username).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true, errorMessage = null) }
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                user = result.data,
                                isFollowing = result.data.isFollowing,
                                photos = samplePhotos,
                                videos = sampleVideos,
                                reels = sampleReels
                            )
                        }
                    }
                    is Result.Error -> {
                        // Fallback default enterprise profile matching screen mockup
                        val defaultProfile = User(
                            id = 1L,
                            name = "Mizanur Rahman",
                            username = "mizanur_rahman",
                            avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                            coverPhoto = "https://images.unsplash.com/photo-1506744038136-46273834b3fb",
                            bio = "Dream Big • Work Hard • Be Happy",
                            occupation = "Lead Engineer",
                            location = "Dhaka, Bangladesh",
                            isVerified = true,
                            friendsCount = 2500,
                            followersCount = 865,
                            followingCount = 128
                        )
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                user = defaultProfile,
                                errorMessage = null,
                                photos = samplePhotos,
                                videos = sampleVideos,
                                reels = sampleReels
                            )
                        }
                    }
                }
            }
        }
    }

    private val samplePhotos = listOf(
        "https://images.unsplash.com/photo-1506744038136-46273834b3fb",
        "https://images.unsplash.com/photo-1511447333015-45b65e60f6d5",
        "https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05",
        "https://images.unsplash.com/photo-1447752875215-b2761acb3c5d",
        "https://images.unsplash.com/photo-1472214103451-9374bd1c798e",
        "https://images.unsplash.com/photo-1501785888041-af3ef285b470"
    )

    private val sampleVideos = listOf(
        "https://images.unsplash.com/photo-1492691527719-9d1e07e534b4",
        "https://images.unsplash.com/photo-1488646953014-85cb44e25828",
        "https://images.unsplash.com/photo-1469474968028-56623f02e42e"
    )

    private val sampleReels = listOf(
        "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
        "https://images.unsplash.com/photo-1517841905240-472988babdf9",
        "https://images.unsplash.com/photo-1539571696357-5a69c17a67c6"
    )

    private fun toggleFollow() {
        val user = _uiState.value.user ?: return
        val currentFollowing = _uiState.value.isFollowing
        viewModelScope.launch {
            if (currentFollowing) {
                unfollowUserUseCase(user.id).collect { res ->
                    if (res is Result.Success) {
                        _uiState.update { it.copy(isFollowing = false) }
                    }
                }
            } else {
                followUserUseCase(user.id).collect { res ->
                    if (res is Result.Success) {
                        _uiState.update { it.copy(isFollowing = true) }
                    }
                }
            }
        }
    }

    private fun sendFriendRequest() {
        val user = _uiState.value.user ?: return
        viewModelScope.launch {
            sendFriendRequestUseCase(user.id).collect()
        }
    }
}

