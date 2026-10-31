package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
data class Group(
    val id: Long,
    val name: String,
    val slug: String = "",
    val description: String? = null,
    val coverImage: String? = null,
    val avatarImage: String? = null,
    val membersCount: Int = 0,
    val formattedMembersCount: String = "${membersCount} members",
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
    val startTime: Long = System.currentTimeMillis(),
    val endTime: Long? = null,
    val dateDay: String = "15",
    val dateMonth: String = "NOV",
    val timeFormatted: String = "Sat, 6:00 PM - 11:00 PM",
    val location: String = "Dhaka Arena, Dhaka",
    val interestedCount: Int = 0,
    val goingCount: Int = 0,
    val myRsvp: RsvpStatus = RsvpStatus.NONE
)

enum class RsvpStatus(val value: String) {
    NONE("none"),
    INTERESTED("interested"),
    GOING("going"),
    NOT_GOING("not_going");

    companion object {
        fun from(value: String): RsvpStatus = entries.find { it.value.equals(value, ignoreCase = true) } ?: NONE
    }
}

@Serializable
data class Page(
    val id: Long,
    val name: String,
    val username: String? = null,
    val category: String = "Business",
    val bio: String? = null,
    val avatarUrl: String? = null,
    val coverUrl: String? = null,
    val followersCount: Int = 0,
    val isFollowing: Boolean = false,
    val isAdmin: Boolean = false,
    val isVerified: Boolean = false
)

@Serializable
data class LiveStream(
    val id: Long,
    val title: String,
    val streamer: User,
    val playbackUrl: String? = null,
    val viewerCount: Int = 0,
    val formattedViewerCount: String = "${viewerCount}",
    val isLive: Boolean = true,
    val startedAt: Long = System.currentTimeMillis(),
    val durationSeconds: Int = 0
)

@Serializable
data class LiveComment(
    val id: Long,
    val streamId: Long,
    val user: User,
    val comment: String,
    val timestamp: Long = System.currentTimeMillis()
)

@Serializable
data class LiveReaction(
    val id: Long,
    val type: String = "heart",
    val user: User
)
