package com.connectly.android.feature.notifications.presentation.search

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.DiscoveryData
import com.connectly.android.core.model.SearchResult
import com.connectly.android.feature.notifications.domain.usecase.ClearSearchHistoryUseCase
import com.connectly.android.feature.notifications.domain.usecase.DeleteSearchHistoryItemUseCase
import com.connectly.android.feature.notifications.domain.usecase.GetDiscoveryDataUseCase
import com.connectly.android.feature.notifications.domain.usecase.GetSearchHistoryUseCase
import com.connectly.android.feature.notifications.domain.usecase.SearchUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class SearchUiState(
    val query: String = "",
    val selectedFilter: String = "All",
    val searchResults: SearchResult = SearchResult(),
    val discoveryData: DiscoveryData = DiscoveryData(),
    val recentSearches: List<String> = listOf("Sadia Islam", "#MusicFestival", "Travel Lovers BD", "Tech World"),
    val isLoading: Boolean = false,
    val errorMessage: String? = null
)

sealed interface SearchIntent {
    data class QueryChanged(val query: String) : SearchIntent
    data class FilterSelected(val filter: String) : SearchIntent
    data class RemoveRecentItem(val item: String) : SearchIntent
    data object ClearRecentSearches : SearchIntent
    data object RefreshDiscovery : SearchIntent
}

@HiltViewModel
class SearchViewModel @Inject constructor(
    private val searchUseCase: SearchUseCase,
    private val getDiscoveryDataUseCase: GetDiscoveryDataUseCase,
    private val getSearchHistoryUseCase: GetSearchHistoryUseCase,
    private val deleteSearchHistoryItemUseCase: DeleteSearchHistoryItemUseCase,
    private val clearSearchHistoryUseCase: ClearSearchHistoryUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(SearchUiState())
    val uiState: StateFlow<SearchUiState> = _uiState.asStateFlow()

    private var searchJob: Job? = null

    init {
        loadDiscovery()
        loadSearchHistory()
    }

    fun handleIntent(intent: SearchIntent) {
        when (intent) {
            is SearchIntent.QueryChanged -> onQueryChanged(intent.query)
            is SearchIntent.FilterSelected -> onFilterSelected(intent.filter)
            is SearchIntent.RemoveRecentItem -> {
                viewModelScope.launch {
                    deleteSearchHistoryItemUseCase(intent.item).collect { result ->
                        if (result is Result.Success) {
                            _uiState.update { it.copy(recentSearches = result.data) }
                        }
                    }
                }
            }
            SearchIntent.ClearRecentSearches -> {
                viewModelScope.launch {
                    clearSearchHistoryUseCase().collect { result ->
                        if (result is Result.Success) {
                            _uiState.update { it.copy(recentSearches = emptyList()) }
                        }
                    }
                }
            }
            SearchIntent.RefreshDiscovery -> {
                loadDiscovery()
                loadSearchHistory()
            }
        }
    }

    private fun loadDiscovery() {
        viewModelScope.launch {
            getDiscoveryDataUseCase().collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(discoveryData = result.data) }
                }
            }
        }
    }

    private fun loadSearchHistory() {
        viewModelScope.launch {
            getSearchHistoryUseCase().collect { result ->
                if (result is Result.Success) {
                    _uiState.update { it.copy(recentSearches = result.data) }
                }
            }
        }
    }

    private fun onQueryChanged(newQuery: String) {
        _uiState.update { it.copy(query = newQuery) }
        searchJob?.cancel()

        if (newQuery.isBlank()) {
            _uiState.update { it.copy(searchResults = SearchResult(), isLoading = false) }
            return
        }

        searchJob = viewModelScope.launch {
            delay(300) // Debounce
            _uiState.update { it.copy(isLoading = true) }
            searchUseCase(newQuery, _uiState.value.selectedFilter.lowercase()).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, searchResults = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    private fun onFilterSelected(filter: String) {
        _uiState.update { it.copy(selectedFilter = filter) }
        if (_uiState.value.query.isNotBlank()) {
            onQueryChanged(_uiState.value.query)
        }
    }
}

