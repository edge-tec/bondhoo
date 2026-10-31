package com.connectly.android.feature.events.presentation.events

import com.connectly.android.core.model.Event
import com.connectly.android.core.model.RsvpStatus

data class EventsState(
    val selectedTab: String = "discover", // "discover", "my_events", "past"
    val events: List<Event> = emptyList(),
    val isLoading: Boolean = false,
    val errorMessage: String? = null
)

sealed interface EventsIntent {
    data class SelectTab(val tab: String) : EventsIntent
    data class ToggleRsvp(val eventId: Long, val targetStatus: RsvpStatus) : EventsIntent
    data object Refresh : EventsIntent
}

