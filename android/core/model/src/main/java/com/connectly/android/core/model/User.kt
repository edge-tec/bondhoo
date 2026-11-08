package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
data class User(
    val id: Long,
    val name: String,
    val username: String,
    val email: String? = null,
    val avatar: String? = null,
    val coverPhoto: String? = null,
    val bio: String? = null,
    val occupation: String? = null,
    val location: String? = null,
    val isVerified: Boolean = false,
    val friendsCount: Int = 0,
    val followersCount: Int = 0,
    val followingCount: Int = 0,
    val isOnline: Boolean = false,
    val isFollowing: Boolean = false,
    val friendshipStatus: FriendshipStatus = FriendshipStatus.NONE,
    val requiresEmailVerification: Boolean = false,
    val fallbackOtp: String? = null,
    val emailSent: Boolean = true,
    val emailError: String? = null
)

enum class FriendshipStatus {
    NONE,
    PENDING_SENT,
    PENDING_RECEIVED,
    FRIENDS,
    BLOCKED
}

