package com.connectly.android.feature.events.presentation.events

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.RsvpStatus
import com.connectly.android.feature.events.domain.usecase.GetEventsUseCase
import com.connectly.android.feature.events.domain.usecase.RsvpEventUseCase
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class EventsViewModel @Inject constructor(
    private val getEventsUseCase: GetEventsUseCase,
    private val rsvpEventUseCase: RsvpEventUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(EventsState())
    val uiState: StateFlow<EventsState> = _uiState.asStateFlow()

    init {
        loadEvents(_uiState.value.selectedTab)
    }

    fun handleIntent(intent: EventsIntent) {
        when (intent) {
            is EventsIntent.SelectTab -> {
                _uiState.update { it.copy(selectedTab = intent.tab) }
                loadEvents(intent.tab)
            }
            is EventsIntent.ToggleRsvp -> toggleRsvp(intent.eventId, intent.targetStatus)
            EventsIntent.Refresh -> loadEvents(_uiState.value.selectedTab)
        }
    }

    private fun loadEvents(tab: String) {
        viewModelScope.launch {
            getEventsUseCase(tab).collect { result ->
                when (result) {
                    is Result.Loading -> _uiState.update { it.copy(isLoading = true) }
                    is Result.Success -> _uiState.update { it.copy(isLoading = false, events = result.data) }
                    is Result.Error -> _uiState.update { it.copy(isLoading = false, errorMessage = result.exception.message) }
                }
            }
        }
    }

    private fun toggleRsvp(eventId: Long, targetStatus: RsvpStatus) {
        // Optimistic update
        _uiState.update { state ->
            state.copy(
                events = state.events.map { ev ->
                    if (ev.id == eventId) {
                        val newRsvp = if (ev.myRsvp == targetStatus) RsvpStatus.NONE else targetStatus
                        ev.copy(myRsvp = newRsvp)
                    } else ev
                }
            )
        }

        viewModelScope.launch {
            rsvpEventUseCase(eventId, targetStatus).collect()
        }
    }
}

