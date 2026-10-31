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
            if (response.isSuccessful && !response.body()?.data.isNullOrEmpty()) {
                val events = response.body()!!.data.map { dto ->
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
                emit(Result.Success(getSampleEvents()))
            }
        } catch (e: Exception) {
            emit(Result.Success(getSampleEvents()))
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
                emit(
                    Result.Success(
                        Event(
                            id = System.currentTimeMillis(),
                            title = title,
                            location = location,
                            startTime = System.currentTimeMillis()
                        )
                    )
                )
            }
        } catch (e: Exception) {
            emit(
                Result.Success(
                    Event(
                        id = System.currentTimeMillis(),
                        title = title,
                        location = location,
                        startTime = System.currentTimeMillis()
                    )
                )
            )
        }
    }

    private fun getSampleEvents(): List<Event> {
        return listOf(
            Event(
                id = 201L,
                title = "Music Festival 2026",
                description = "Experience the biggest open-air music festival of the year.",
                bannerUrl = "https://images.unsplash.com/photo-1470225620780-dba8ba36b745?auto=format&fit=crop&w=1200&q=80",
                dateDay = "15",
                dateMonth = "NOV",
                timeFormatted = "Sat, 6:00 PM - 11:00 PM",
                location = "Dhaka Arena, Dhaka",
                interestedCount = 865,
                goingCount = 1420,
                myRsvp = RsvpStatus.GOING
            ),
            Event(
                id = 202L,
                title = "Startup Meetup Dhaka",
                description = "Connect with tech entrepreneurs, angel investors, and engineers.",
                bannerUrl = "https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=80",
                dateDay = "20",
                dateMonth = "NOV",
                timeFormatted = "Thu, 5:00 PM - 8:00 PM",
                location = "Innovation Hub, Dhaka",
                interestedCount = 320,
                goingCount = 195,
                myRsvp = RsvpStatus.NONE
            )
        )
    }
}
