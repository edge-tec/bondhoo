package com.connectly.android.feature.profile.presentation.blocking

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.RestrictedUserDto
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class BlockedUsersViewModel @Inject constructor(
    private val api: ConnectlyApi
) : ViewModel() {

    private val _uiState = MutableStateFlow(BlockedUsersState())
    val uiState: StateFlow<BlockedUsersState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun handleIntent(intent: BlockedUsersIntent) {
        when (intent) {
            BlockedUsersIntent.LoadData -> loadData()
            is BlockedUsersIntent.SelectTab -> _uiState.update { it.copy(selectedTab = intent.tab) }
            is BlockedUsersIntent.SearchQueryChanged -> _uiState.update { it.copy(searchQuery = intent.query) }
            is BlockedUsersIntent.RequestAction -> _uiState.update {
                it.copy(pendingActionUser = intent.user, pendingActionType = intent.type)
            }
            BlockedUsersIntent.DismissActionDialog -> _uiState.update {
                it.copy(pendingActionUser = null, pendingActionType = null)
            }
            BlockedUsersIntent.ExecuteConfirmedAction -> executeConfirmedAction()
            BlockedUsersIntent.DismissFeedback -> _uiState.update {
                it.copy(successMessage = null, errorMessage = null)
            }
        }
    }

    fun loadData() {
        _uiState.update { it.copy(isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            try {
                val response = api.getBlockedAndRestrictedUsers()
                if (response.isSuccessful && response.body()?.data != null) {
                    val data = response.body()!!.data!!
                    _uiState.update {
                        it.copy(
                            blockedUsers = data.blocked_users,
                            restrictedUsers = data.restricted_users,
                            mutedUsers = data.muted_users,
                            isLoading = false
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            errorMessage = response.body()?.message ?: "Failed to load restriction list."
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isLoading = false,
                        errorMessage = e.localizedMessage ?: "Network error occurred."
                    )
                }
            }
        }
    }

    private fun executeConfirmedAction() {
        val user = _uiState.value.pendingActionUser ?: return
        val action = _uiState.value.pendingActionType ?: return

        _uiState.update { it.copy(isActionInProgress = true) }

        viewModelScope.launch {
            try {
                val response = when (action) {
                    RestrictionActionType.UNBLOCK -> api.unblockUserInSettings(user.id)
                    RestrictionActionType.UNRESTRICT -> api.unrestrictUser(user.id)
                    RestrictionActionType.UNMUTE -> api.unmuteUser(user.id)
                }

                if (response.isSuccessful) {
                    // Optimistic update of local state
                    _uiState.update { state ->
                        when (action) {
                            RestrictionActionType.UNBLOCK -> state.copy(
                                blockedUsers = state.blockedUsers.filter { it.id != user.id },
                                isActionInProgress = false,
                                pendingActionUser = null,
                                pendingActionType = null,
                                successMessage = "${user.name} has been unblocked."
                            )
                            RestrictionActionType.UNRESTRICT -> state.copy(
                                restrictedUsers = state.restrictedUsers.filter { it.id != user.id },
                                isActionInProgress = false,
                                pendingActionUser = null,
                                pendingActionType = null,
                                successMessage = "${user.name} is no longer restricted."
                            )
                            RestrictionActionType.UNMUTE -> state.copy(
                                mutedUsers = state.mutedUsers.filter { it.id != user.id },
                                isActionInProgress = false,
                                pendingActionUser = null,
                                pendingActionType = null,
                                successMessage = "${user.name} has been unmuted."
                            )
                        }
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            isActionInProgress = false,
                            errorMessage = "Action failed. Please try again."
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isActionInProgress = false,
                        errorMessage = e.localizedMessage ?: "Failed to complete action."
                    )
                }
            }
        }
    }
}

