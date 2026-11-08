package com.connectly.android.feature.profile.presentation.friends

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.FriendshipStatus
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.feature.profile.domain.repository.ProfileRepository
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.async
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class FriendsViewModel @Inject constructor(
    private val profileRepository: ProfileRepository,
    private val api: ConnectlyApi
) : ViewModel() {

    private val _uiState = MutableStateFlow(FriendsUiState(isLoading = true))
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
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            try {
                val friendsDeferred = async { api.getFriends(perPage = 50) }
                val requestsDeferred = async { api.getFriendRequests(perPage = 50) }
                val suggestionsDeferred = async { api.getSuggestedFriends(limit = 20) }

                val friendsResp = friendsDeferred.await()
                val requestsResp = requestsDeferred.await()
                val suggestionsResp = suggestionsDeferred.await()

                val friends = if (friendsResp.isSuccessful && friendsResp.body()?.data != null) {
                    friendsResp.body()!!.data.map { dto ->
                        User(
                            id = dto.id,
                            name = dto.name,
                            username = dto.username,
                            avatar = dto.resolvedAvatar,
                            isVerified = dto.is_verified,
                            isOnline = dto.is_online,
                            friendsCount = dto.friends_count,
                            friendshipStatus = FriendshipStatus.FRIENDS
                        )
                    }
                } else emptyList()

                val requests = if (requestsResp.isSuccessful && requestsResp.body()?.data != null) {
                    requestsResp.body()!!.data.mapNotNull { req ->
                        val u = req.user ?: return@mapNotNull null
                        FriendRequestItem(
                            user = User(
                                id = u.id,
                                name = u.name,
                                username = u.username,
                                avatar = u.resolvedAvatar,
                                isVerified = u.is_verified,
                                friendshipStatus = FriendshipStatus.PENDING_RECEIVED
                            ),
                            mutualFriendsCount = u.friends_count,
                            timeAgo = "Recently"
                        )
                    }
                } else emptyList()

                val suggestions = if (suggestionsResp.isSuccessful && suggestionsResp.body()?.data != null) {
                    suggestionsResp.body()!!.data.map { u ->
                        SuggestedFriendItem(
                            user = User(
                                id = u.id,
                                name = u.name,
                                username = u.username,
                                avatar = u.resolvedAvatar,
                                isVerified = u.is_verified,
                                friendsCount = u.friends_count,
                                friendshipStatus = FriendshipStatus.NONE
                            ),
                            mutualFriendsCount = u.friends_count,
                            reason = "Suggested based on mutual network"
                        )
                    }
                } else emptyList()

                _uiState.update {
                    it.copy(
                        friendsList = friends,
                        friendRequests = requests,
                        suggestions = suggestions,
                        isLoading = false
                    )
                }
            } catch (e: Exception) {
                _uiState.update { it.copy(isLoading = false, errorMessage = e.message) }
            }
        }
    }

    private fun acceptFriendRequest(userId: Long) {
        val accepted = _uiState.value.friendRequests.find { it.user.id == userId }
        _uiState.update { state ->
            state.copy(
                friendRequests = state.friendRequests.filterNot { it.user.id == userId },
                friendsList = if (accepted != null) {
                    val newFriend = accepted.user.copy(friendshipStatus = FriendshipStatus.FRIENDS)
                    if (state.friendsList.none { it.id == userId }) state.friendsList + newFriend else state.friendsList
                } else state.friendsList
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
                profileRepository.unfriendUser(userId).collect { }
            } catch (_: Exception) {}
        }
    }
}

