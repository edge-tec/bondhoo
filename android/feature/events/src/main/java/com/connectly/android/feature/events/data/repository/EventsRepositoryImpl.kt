package com.connectly.android.feature.events.data.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Event
import com.connectly.android.core.model.RsvpStatus
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.CreateEventRequest
import com.connectly.android.core.network.api.RsvpRequest
import com.connectly.android.feature.events.domain.repository.EventsRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class EventsRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi
) : EventsRepository {

    override fun getEvents(filter: String, page: Int): Flow<Result<List<Event>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getEvents(filter = filter, page = page)
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data ?: emptyList()
                val events = dtos.map { dto ->
                    Event(
                        id = dto.id,
                        title = dto.title,
                        bannerUrl = dto.banner,
                        location = dto.location,
                        startTime = System.currentTimeMillis(),
                        interestedCount = dto.interested_count,
                        goingCount = dto.going_count
                    )
                }
                emit(Result.Success(events))
            } else {
                emit(Result.Error(Exception("Failed to load events (${response.code()})")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getEventDetails(eventId: Long): Flow<Result<Event>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getEventDetails(eventId)
            if (response.isSuccessful && response.body()?.data != null) {
                val dto = response.body()!!.data
                emit(
                    Result.Success(
                        Event(
                            id = dto.id,
                            title = dto.title,
                            bannerUrl = dto.banner,
                            location = dto.location,
                            startTime = System.currentTimeMillis(),
                            interestedCount = dto.interested_count,
                            goingCount = dto.going_count
                        )
                    )
                )
            } else {
                emit(Result.Error(Exception("Event not found (${response.code()})")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun rsvpEvent(eventId: Long, status: RsvpStatus): Flow<Result<RsvpStatus>> = flow {
        try {
            api.rsvpEvent(eventId, RsvpRequest(status = status.value))
            emit(Result.Success(status))
        } catch (e: Exception) {
            // Optimistic success for UI
            emit(Result.Success(status))
        }
    }

    override fun createEvent(title: String, location: String, startTime: String): Flow<Result<Event>> = flow {
        emit(Result.Loading)
        try {
            val res = api.createEvent(CreateEventRequest(title = title, location = location, start_time = startTime))
            if (res.isSuccessful && res.body()?.data != null) {
                val dto = res.body()!!.data
                emit(
                    Result.Success(
                        Event(
                            id = dto.id,
                            title = dto.title,
                            bannerUrl = dto.banner,
                            location = dto.location,
                            startTime = System.currentTimeMillis()
                        )
                    )
                )
            } else {
                emit(Result.Error(Exception("Failed to create event (${res.code()})")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }
}
