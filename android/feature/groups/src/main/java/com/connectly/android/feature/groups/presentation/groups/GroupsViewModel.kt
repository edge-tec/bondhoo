package com.connectly.android.feature.groups.presentation.groups

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Group
import com.connectly.android.feature.groups.domain.usecase.GetFeaturedGroupUseCase
import com.connectly.android.feature.groups.domain.usecase.GetGroupsUseCase
import com.connectly.android.feature.groups.domain.usecase.ToggleJoinGroupUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class GroupsViewModel @Inject constructor(
    private val getGroupsUseCase: GetGroupsUseCase,
    private val getFeaturedGroupUseCase: GetFeaturedGroupUseCase,
    private val toggleJoinGroupUseCase: ToggleJoinGroupUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(GroupsState())
    val uiState: StateFlow<GroupsState> = _uiState.asStateFlow()

    init {
        loadData()
    }

    fun handleIntent(intent: GroupsIntent) {
        when (intent) {
            is GroupsIntent.SelectTab -> {
                _uiState.update { it.copy(selectedTab = intent.tab) }
                loadGroups(intent.tab)
            }
            is GroupsIntent.ToggleJoin -> toggleJoin(intent.group)
            GroupsIntent.Refresh -> loadData()
        }
    }

    private fun loadData() {
        loadFeatured()
        loadGroups(_uiState.value.selectedTab)
    }

    private fun loadFeatured() {
        viewModelScope.launch {
            getFeaturedGroupUseCase().collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(featuredGroup = result.data) }
                }
            }
        }
    }

    private fun loadGroups(tab: String) {
        viewModelScope.launch {
            getGroupsUseCase(tab).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, popularGroups = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    private fun toggleJoin(group: Group) {
        val newJoined = !group.isMember
        // Optimistic update
        _uiState.update { state ->
            if (state.featuredGroup?.id == group.id) {
                state.copy(featuredGroup = state.featuredGroup.copy(isMember = newJoined))
            } else {
                state.copy(
                    popularGroups = state.popularGroups.map {
                        if (it.id == group.id) it.copy(isMember = newJoined) else it
                    }
                )
            }
        }

        viewModelScope.launch {
            toggleJoinGroupUseCase(group.id, group.isMember).collect()
        }
    }
}

