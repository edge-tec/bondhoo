package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
data class Group(
    val id: Long,
    val name: String,
    val slug: String,
    val description: String? = null,
    val coverImage: String? = null,
    val avatarImage: String? = null,
    val membersCount: Int = 0,
    val isMember: Boolean = false,
    val isAdmin: Boolean = false,
    val isPrivate: Boolean = false,
    val category: String? = null
)

@Serializable
data class Event(
    val id: Long,
    val title: String,
    val description: String? = null,
    val bannerUrl: String? = null,
    val startTime: Long,
    val endTime: Long? = null,
    val location: String,
    val interestedCount: Int = 0,
    val goingCount: Int = 0,
    val myRsvp: RsvpStatus = RsvpStatus.NONE
)

enum class RsvpStatus(val value: String) {
    NONE("none"),
    INTERESTED("interested"),
    GOING("going"),
    NOT_GOING("not_going")
}
