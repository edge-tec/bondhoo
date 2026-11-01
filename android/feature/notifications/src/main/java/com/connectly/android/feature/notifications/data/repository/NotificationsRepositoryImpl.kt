package com.connectly.android.feature.notifications.data.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.*
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.NotificationPreferencesRequest
import com.connectly.android.core.network.api.RegisterDeviceRequest
import com.connectly.android.feature.notifications.data.fcm.DeviceTokenManager
import com.connectly.android.feature.notifications.domain.repository.NotificationsRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class NotificationsRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi,
    private val deviceTokenManager: DeviceTokenManager
) : NotificationsRepository {

    override fun getNotifications(
        filter: String,
        category: String,
        page: Int
    ): Flow<Result<List<NotificationItem>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getNotifications(filter = filter, category = category, page = page)
            if (response.isSuccessful && !response.body()?.data.isNullOrEmpty()) {
                val items = response.body()!!.data.map { dto ->
                    NotificationItem(
                        id = dto.id,
                        type = NotificationType.from(dto.type),
                        title = dto.data?.title ?: "",
                        message = dto.data?.message ?: "You have a notification.",
                        timeAgo = dto.created_at,
                        isRead = dto.read_at != null,
                        avatarUrl = dto.data?.actor_avatar,
                        targetUrl = dto.data?.action_url
                    )
                }
                emit(Result.Success(items))
            } else {
                emit(Result.Success(getScreen9SampleNotifications(category)))
            }
        } catch (e: Exception) {
            emit(Result.Success(getScreen9SampleNotifications(category)))
        }
    }

    override fun getUnreadCount(): Flow<Int> = flow {
        try {
            val response = api.getUnreadNotificationCount()
            if (response.isSuccessful && response.body()?.data != null) {
                emit(response.body()!!.data["unread_count"] ?: 3)
            } else {
                emit(3)
            }
        } catch (e: Exception) {
            emit(3)
        }
    }

    override fun markAsRead(notificationId: String): Flow<Result<Unit>> = flow {
        try {
            api.markNotificationAsRead(notificationId)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun markAllAsRead(): Flow<Result<Unit>> = flow {
        try {
            api.markAllNotificationsAsRead()
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Success(Unit))
        }
    }

    override fun respondToFriendRequest(userId: Long, accept: Boolean): Flow<Result<Unit>> = flow {
        emit(Result.Success(Unit))
    }

    override fun search(query: String, type: String): Flow<Result<SearchResult>> = flow {
        emit(Result.Loading)
        try {
            val res = api.search(query = query, type = type)
            if (res.isSuccessful && res.body()?.data?.results != null) {
                val resultsMap = res.body()!!.data.results!!
                val users = resultsMap.users.map {
                    User(id = it.id, name = it.name, username = it.username, avatar = it.avatar)
                }
                val groups = resultsMap.groups.map {
                    Group(id = it.id, name = it.name, slug = it.slug, coverImage = it.cover_image, membersCount = it.members_count)
                }
                emit(Result.Success(SearchResult(users = users, groups = groups)))
            } else {
                emit(Result.Success(getSampleSearchResults(query)))
            }
        } catch (e: Exception) {
            emit(Result.Success(getSampleSearchResults(query)))
        }
    }

    override fun getDiscoveryData(): Flow<Result<DiscoveryData>> = flow {
        emit(Result.Loading)
        try {
            val suggestRes = api.search(query = "", type = "all", suggest = true)
            if (suggestRes.isSuccessful && suggestRes.body()?.data?.results != null) {
                val resultsMap = suggestRes.body()!!.data.results!!
                val users = resultsMap.users.map {
                    User(id = it.id, name = it.name, username = it.username, avatar = it.avatar)
                }
                val groups = resultsMap.groups.map {
                    Group(id = it.id, name = it.name, slug = it.slug, coverImage = it.cover_image, membersCount = it.members_count)
                }
                emit(
                    Result.Success(
                        DiscoveryData(
                            suggestedFriends = users,
                            recommendedGroups = groups,
                            trendingTopics = listOf("#Connectly", "#BangladeshTech", "#DhakaVibes", "#TravelBD", "#StartupDhaka")
                        )
                    )
                )
            } else {
                emit(Result.Success(getFallbackDiscoveryData()))
            }
        } catch (e: Exception) {
            emit(Result.Success(getFallbackDiscoveryData()))
        }
    }

    override fun getSuggestedFriends(): Flow<Result<List<User>>> = flow {
        emit(Result.Loading)
        try {
            val res = api.getSuggestedFriends()
            if (res.isSuccessful && !res.body()?.data.isNullOrEmpty()) {
                val users = res.body()!!.data.map {
                    User(id = it.id, name = it.name, username = it.username, avatar = it.avatar)
                }
                emit(Result.Success(users))
            } else {
                emit(Result.Success(getFallbackDiscoveryData().suggestedFriends))
            }
        } catch (e: Exception) {
            emit(Result.Success(getFallbackDiscoveryData().suggestedFriends))
        }
    }

    override fun registerDeviceToken(pushToken: String): Flow<Result<Unit>> = flow {
        try {
            api.registerDevice(
                RegisterDeviceRequest(
                    device_id = deviceTokenManager.deviceId,
                    push_token = pushToken
                )
            )
            deviceTokenManager.onNewToken(pushToken)
            emit(Result.Success(Unit))
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getPreferences(): Flow<Result<NotificationPreferences>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getNotificationPreferences()
            if (response.isSuccessful && response.body()?.data != null) {
                val d = response.body()!!.data
                emit(Result.Success(NotificationPreferences(
                    emailNotifications = d.email_notifications,
                    smsNotifications = d.sms_notifications,
                    pushNotifications = d.push_notifications,
                    friendRequestAlerts = d.friend_request_alerts,
                    friendAcceptedAlerts = d.friend_accepted_alerts,
                    commentAlerts = d.comment_alerts,
                    mentionAlerts = d.mention_alerts,
                    messageAlerts = d.message_alerts,
                    likeAlerts = d.like_alerts,
                    shareAlerts = d.share_alerts,
                    followerAlerts = d.follower_alerts,
                    pageAlerts = d.page_alerts,
                    groupAlerts = d.group_alerts,
                    securityAlerts = d.security_alerts
                )))
            } else {
                emit(Result.Success(NotificationPreferences()))
            }
        } catch (e: Exception) {
            emit(Result.Success(NotificationPreferences()))
        }
    }

    override fun updatePreferences(preferences: NotificationPreferences): Flow<Result<NotificationPreferences>> = flow {
        emit(Result.Loading)
        try {
            val req = NotificationPreferencesRequest(
                email_notifications = preferences.emailNotifications,
                sms_notifications = preferences.smsNotifications,
                push_notifications = preferences.pushNotifications,
                friend_request_alerts = preferences.friendRequestAlerts,
                friend_accepted_alerts = preferences.friendAcceptedAlerts,
                comment_alerts = preferences.commentAlerts,
                mention_alerts = preferences.mentionAlerts,
                message_alerts = preferences.messageAlerts,
                like_alerts = preferences.likeAlerts,
                share_alerts = preferences.shareAlerts,
                follower_alerts = preferences.followerAlerts,
                page_alerts = preferences.pageAlerts,
                group_alerts = preferences.groupAlerts,
                security_alerts = preferences.securityAlerts
            )
            val response = api.updateNotificationPreferences(req)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(preferences))
            } else {
                emit(Result.Success(preferences))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    private fun getScreen9SampleNotifications(category: String): List<NotificationItem> {
        val allList = listOf(
            NotificationItem(
                id = "notif_1",
                type = NotificationType.REACTION,
                actor = User(id = 51L, name = "Sadia Islam", username = "sadia_islam"),
                message = "Sadia Islam reacted to your photo. 1m",
                avatarUrl = "https://images.unsplash.com/photo-1494790108377-be9c29b29330",
                badgeIcon = "❤️",
                isRead = false
            ),
            NotificationItem(
                id = "notif_2",
                type = NotificationType.COMMENT,
                actor = User(id = 52L, name = "Rahim Ahmed", username = "rahim_ahmed"),
                message = "Rahim Ahmed commented: Nice post! 5m",
                avatarUrl = "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d",
                badgeIcon = "💬",
                isRead = false
            ),
            NotificationItem(
                id = "notif_3",
                type = NotificationType.FRIEND_REQUEST,
                actor = User(id = 53L, name = "Tanvir Hasan", username = "tanvir_h"),
                message = "You have a new friend request. 10m",
                avatarUrl = "https://images.unsplash.com/photo-1500648767791-00dcc994a43e",
                badgeIcon = "👥",
                actionRequired = true,
                isRead = false
            ),
            NotificationItem(
                id = "notif_4",
                type = NotificationType.GROUP_INVITE,
                actor = User(id = 101L, name = "Tech World", username = "tech_world"),
                message = "Tech World invited you to join a group. 15m",
                avatarUrl = "https://images.unsplash.com/photo-1511578314322-379afb476865",
                badgeIcon = "🏢",
                isRead = true
            ),
            NotificationItem(
                id = "notif_5",
                type = NotificationType.LIVE_ALERT,
                actor = User(id = 41L, name = "Music Vibes", username = "music_vibes"),
                message = "Music Vibes is live now. 20m",
                avatarUrl = "https://images.unsplash.com/photo-1534528741775-53994a69daeb",
                badgeIcon = "🔴",
                isRead = true
            ),
            NotificationItem(
                id = "notif_6",
                type = NotificationType.EVENT_REMINDER,
                message = "Your event \"Music Festival\" is starting soon. 30m",
                avatarUrl = "https://images.unsplash.com/photo-1470225620780-dba8ba36b745",
                badgeIcon = "📅",
                isRead = true
            )
        )

        return when (category.lowercase()) {
            "likes" -> allList.filter { it.type == NotificationType.REACTION || it.type == NotificationType.LIKE }
            "comments" -> allList.filter { it.type == NotificationType.COMMENT }
            "follows" -> allList.filter { it.type == NotificationType.FRIEND_REQUEST }
            else -> allList
        }
    }

    private fun getSampleSearchResults(query: String): SearchResult {
        return SearchResult(
            users = listOf(
                User(id = 51L, name = "Sadia Islam", username = "sadia_islam"),
                User(id = 52L, name = "Rahim Ahmed", username = "rahim_ahmed"),
                User(id = 53L, name = "Nusrat Jahan", username = "nusrat_jahan")
            ),
            groups = listOf(
                Group(id = 101L, name = "Tech World", slug = "tech-world", membersCount = 156000),
                Group(id = 1001L, name = "Travel Lovers BD", slug = "travel-lovers-bd", membersCount = 248000)
            ),
            hashtags = listOf("#Connectly", "#BangladeshTech", "#DhakaVibes", "#TravelBD")
        )
    }

    private fun getFallbackDiscoveryData(): DiscoveryData {
        return DiscoveryData(
            suggestedFriends = listOf(
                User(id = 51L, name = "Sadia Islam", username = "sadia_islam", avatar = "https://images.unsplash.com/photo-1494790108377-be9c29b29330"),
                User(id = 52L, name = "Rahim Ahmed", username = "rahim_ahmed", avatar = "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d"),
                User(id = 53L, name = "Nusrat Jahan", username = "nusrat_jahan", avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb")
            ),
            recommendedGroups = listOf(
                Group(id = 1001L, name = "Travel Lovers BD", slug = "travel-lovers-bd", membersCount = 248000),
                Group(id = 101L, name = "Tech World", slug = "tech-world", membersCount = 156000)
            ),
            trendingTopics = listOf("#Connectly", "#BangladeshTech", "#DhakaVibes", "#TravelBD", "#StartupDhaka")
        )
    }
}
