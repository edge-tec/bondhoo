package com.connectly.android.feature.events.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Event
import com.connectly.android.core.model.RsvpStatus
import com.connectly.android.feature.events.domain.repository.EventsRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

class GetEventsUseCase @Inject constructor(
    private val repository: EventsRepository
) {
    operator fun invoke(filter: String, page: Int = 1): Flow<Result<List<Event>>> {
        return repository.getEvents(filter, page)
    }
}

class RsvpEventUseCase @Inject constructor(
    private val repository: EventsRepository
) {
    operator fun invoke(eventId: Long, status: RsvpStatus): Flow<Result<RsvpStatus>> {
        return repository.rsvpEvent(eventId, status)
    }
}

class CreateEventUseCase @Inject constructor(
    private val repository: EventsRepository
) {
    operator fun invoke(title: String, location: String, startTime: String): Flow<Result<Event>> {
        return repository.createEvent(title, location, startTime)
    }
}

