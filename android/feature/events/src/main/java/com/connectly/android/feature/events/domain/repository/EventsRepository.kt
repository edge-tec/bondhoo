package com.connectly.android.feature.events.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Event
import com.connectly.android.core.model.RsvpStatus
import kotlinx.coroutines.flow.Flow

interface EventsRepository {
    fun getEvents(filter: String, page: Int = 1): Flow<Result<List<Event>>>
    fun getEventDetails(eventId: Long): Flow<Result<Event>>
    fun rsvpEvent(eventId: Long, status: RsvpStatus): Flow<Result<RsvpStatus>>
    fun createEvent(title: String, location: String, startTime: String): Flow<Result<Event>>
}

