package com.connectly.android.feature.profile.presentation.friends

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.FriendshipStatus
import com.connectly.android.core.model.User
import com.connectly.android.feature.profile.domain.repository.ProfileRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class FriendsViewModel @Inject constructor(
    private val profileRepository: ProfileRepository
) : ViewModel() {

    private val _uiState = MutableStateFlow(createInitialState())
    val uiState: StateFlow<FriendsUiState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun handleIntent(intent: FriendsIntent) {
        when (intent) {
            is FriendsIntent.SelectTab -> _uiState.update { it.copy(selectedTab = intent.tab) }
            is FriendsIntent.SearchQueryChanged -> _uiState.update { it.copy(searchQuery = intent.query) }
            is FriendsIntent.AcceptRequest -> acceptFriendRequest(intent.userId)
            is FriendsIntent.DeleteRequest -> rejectFriendRequest(intent.userId)
            is FriendsIntent.AddFriend -> sendFriendRequest(intent.userId)
            is FriendsIntent.RemoveSuggestion -> removeSuggestion(intent.userId)
            is FriendsIntent.Unfriend -> unfriendUser(intent.userId)
            FriendsIntent.Refresh -> loadData()
        }
    }

    private fun loadData() {
        _uiState.update { it.copy(isLoading = false) }
    }

    private fun acceptFriendRequest(userId: Long) {
        val accepted = _uiState.value.friendRequests.find { it.user.id == userId }
        _uiState.update { state ->
            state.copy(
                friendRequests = state.friendRequests.filterNot { it.user.id == userId },
                friendsList = if (accepted != null) state.friendsList + accepted.user else state.friendsList
            )
        }
        viewModelScope.launch {
            try {
                profileRepository.acceptFriendRequest(userId).collect { }
            } catch (_: Exception) {}
        }
    }

    private fun rejectFriendRequest(userId: Long) {
        _uiState.update { state ->
            state.copy(
                friendRequests = state.friendRequests.filterNot { it.user.id == userId }
            )
        }
        viewModelScope.launch {
            try {
                profileRepository.rejectFriendRequest(userId).collect { }
            } catch (_: Exception) {}
        }
    }

    private fun sendFriendRequest(userId: Long) {
        _uiState.update { state ->
            state.copy(
                suggestions = state.suggestions.filterNot { it.user.id == userId }
            )
        }
        viewModelScope.launch {
            try {
                profileRepository.sendFriendRequest(userId).collect { }
            } catch (_: Exception) {}
        }
    }

    private fun removeSuggestion(userId: Long) {
        _uiState.update { state ->
            state.copy(suggestions = state.suggestions.filterNot { it.user.id == userId })
        }
    }

    private fun unfriendUser(userId: Long) {
        _uiState.update { state ->
            state.copy(friendsList = state.friendsList.filterNot { it.id == userId })
        }
        viewModelScope.launch {
            try {
                profileRepository.unfollowUser(userId).collect { }
            } catch (_: Exception) {}
        }
    }

    private fun createInitialState(): FriendsUiState {
        val requests = listOf(
            FriendRequestItem(
                user = User(
                    id = 101L,
                    name = "Tania Akter",
                    username = "tania_akter",
                    avatar = "https://images.unsplash.com/photo-1544005313-94ddf0286df2",
                    isVerified = true,
                    friendshipStatus = FriendshipStatus.PENDING_RECEIVED
                ),
                mutualFriendsCount = 18,
                timeAgo = "10m"
            ),
            FriendRequestItem(
                user = User(
                    id = 102L,
                    name = "Farhan Kabir",
                    username = "farhan_k",
                    avatar = "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d",
                    friendshipStatus = FriendshipStatus.PENDING_RECEIVED
                ),
                mutualFriendsCount = 6,
                timeAgo = "2h"
            ),
            FriendRequestItem(
                user = User(
                    id = 103L,
                    name = "Nusrat Jahan",
                    username = "nusrat_j",
                    avatar = "https://images.unsplash.com/photo-1517841905240-472988babdf9",
                    friendshipStatus = FriendshipStatus.PENDING_RECEIVED
                ),
                mutualFriendsCount = 24,
                timeAgo = "1d"
            )
        )

        val friends = listOf(
            User(id = 201L, name = "Sadia Islam", username = "sadia_islam", avatar = "https://images.unsplash.com/photo-1494790108377-be9c29b29330", isOnline = true, isVerified = true, friendshipStatus = FriendshipStatus.FRIENDS),
            User(id = 202L, name = "Rahim Ahmed", username = "rahim_ahmed", avatar = "https://images.unsplash.com/photo-1500648767791-00dcc994a43e", isOnline = true, friendshipStatus = FriendshipStatus.FRIENDS),
            User(id = 203L, name = "Afrin Akter", username = "afrin_akter", avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb", isOnline = false, friendshipStatus = FriendshipStatus.FRIENDS),
            User(id = 204L, name = "Rahat Khan", username = "rahat_k", avatar = "https://images.unsplash.com/photo-1539571696357-5a69c17a67c6", isOnline = false, friendshipStatus = FriendshipStatus.FRIENDS)
        )

        val suggestions = listOf(
            SuggestedFriendItem(
                user = User(id = 301L, name = "Imran Hasan", username = "imran_h", avatar = "https://images.unsplash.com/photo-1522075469751-3a6694fb2f61", isVerified = true),
                mutualFriendsCount = 12,
                reason = "Followed by Sadia Islam"
            ),
            SuggestedFriendItem(
                user = User(id = 302L, name = "Mahmudur Rahman", username = "mahmud_r", avatar = "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d"),
                mutualFriendsCount = 9,
                reason = "Lives in Dhaka"
            ),
            SuggestedFriendItem(
                user = User(id = 303L, name = "Sabrina Yasmin", username = "sabrina_y", avatar = "https://images.unsplash.com/photo-1524504388940-b1c1722653e1"),
                mutualFriendsCount = 15,
                reason = "Member of Tech World BD"
            )
        )

        return FriendsUiState(
            selectedTab = "requests",
            friendRequests = requests,
            friendsList = friends,
            suggestions = suggestions,
            isLoading = false
        )
    }
}

