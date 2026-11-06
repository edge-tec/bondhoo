package com.connectly.android.feature.groups.presentation.pages.roles

import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.AssignPageRoleRequest
import com.connectly.android.core.network.api.PageRoleDto
import com.connectly.android.feature.groups.domain.usecase.AssignPageRoleUseCase
import com.connectly.android.feature.groups.domain.usecase.GetPageRolesUseCase
import com.connectly.android.feature.groups.domain.usecase.RemovePageRoleUseCase
import com.connectly.android.feature.groups.presentation.util.getLongOrNull
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class PageTeamRolesUiState(
    val pageId: Long = 0L,
    val isLoading: Boolean = false,
    val roles: List<PageRoleDto> = emptyList(),
    val errorMessage: String? = null,
    val isAssigning: Boolean = false
)

@HiltViewModel
class PageTeamRolesViewModel @Inject constructor(
    private val getPageRolesUseCase: GetPageRolesUseCase,
    private val assignPageRoleUseCase: AssignPageRoleUseCase,
    private val removePageRoleUseCase: RemovePageRoleUseCase,
    savedStateHandle: SavedStateHandle
) : ViewModel() {

    private val _uiState = MutableStateFlow(PageTeamRolesUiState())
    val uiState: StateFlow<PageTeamRolesUiState> = _uiState.asStateFlow()

    init {
        val pageId = savedStateHandle.getLongOrNull("pageId") ?: 0L
        if (pageId > 0L) {
            _uiState.update { it.copy(pageId = pageId) }
            loadRoles(pageId)
        }
    }

    fun loadRoles(pageId: Long) {
        _uiState.update { it.copy(pageId = pageId, isLoading = true, errorMessage = null) }
        viewModelScope.launch {
            getPageRolesUseCase(pageId).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, roles = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    fun assignRole(userId: Long, role: String) {
        val pageId = _uiState.value.pageId
        if (pageId <= 0L || userId <= 0L) return

        _uiState.update { it.copy(isAssigning = true) }
        viewModelScope.launch {
            val request = AssignPageRoleRequest(user_id = userId, role = role)
            assignPageRoleUseCase(pageId, request).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isAssigning = true) }
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isAssigning = false,
                                roles = it.roles.filter { r -> r.user_id != userId } + result.data
                            )
                        }
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isAssigning = false, errorMessage = result.exception.message) }
                    }
                }
            }
        }
    }

    fun removeRole(roleId: Long) {
        val pageId = _uiState.value.pageId
        if (pageId <= 0L) return

        viewModelScope.launch {
            removePageRoleUseCase(pageId, roleId).collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(roles = it.roles.filter { r -> r.id != roleId }) }
                }
            }
        }
    }
}

