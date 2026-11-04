package com.connectly.android.core.model

import kotlinx.serialization.Serializable

@Serializable
enum class NotificationType(val value: String) {
    LIKE("like"),
    REACTION("reaction"),
    COMMENT("comment"),
    FRIEND_REQUEST("friend_request"),
    FOLLOWER("follower"),
    GROUP_INVITE("group_invite"),
    LIVE_ALERT("live_alert"),
    EVENT_REMINDER("event_reminder"),
    MESSENGER("messenger"),
    CALL("call"),
    SECURITY("security"),
    SYSTEM("system");

    companion object {
        fun from(value: String): NotificationType = entries.find { it.value.equals(value, ignoreCase = true) } ?: SYSTEM
    }
}

@Serializable
data class NotificationItem(
    val id: String,
    val type: NotificationType,
    val actor: User? = null,
    val title: String = "",
    val message: String,
    val timeAgo: String = "Just now",
    val isRead: Boolean = false,
    val avatarUrl: String? = null,
    val targetUrl: String? = null,
    val badgeIcon: String? = null,
    val actionRequired: Boolean = false
)

@Serializable
data class SearchResult(
    val users: List<User> = emptyList(),
    val posts: List<Post> = emptyList(),
    val groups: List<Group> = emptyList(),
    val pages: List<Page> = emptyList(),
    val events: List<Event> = emptyList(),
    val hashtags: List<String> = emptyList()
)

@Serializable
data class DiscoveryData(
    val suggestedFriends: List<User> = emptyList(),
    val recommendedGroups: List<Group> = emptyList(),
    val trendingTopics: List<String> = emptyList(),
    val recommendedPosts: List<Post> = emptyList()
)

@Serializable
data class NotificationPreferences(
    val emailNotifications: Boolean = true,
    val smsNotifications: Boolean = false,
    val pushNotifications: Boolean = true,
    val friendRequestAlerts: Boolean = true,
    val friendAcceptedAlerts: Boolean = true,
    val commentAlerts: Boolean = true,
    val mentionAlerts: Boolean = true,
    val messageAlerts: Boolean = true,
    val likeAlerts: Boolean = true,
    val shareAlerts: Boolean = true,
    val followerAlerts: Boolean = true,
    val pageAlerts: Boolean = true,
    val groupAlerts: Boolean = true,
    val securityAlerts: Boolean = true
)

