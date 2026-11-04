package com.connectly.android.feature.notifications.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.compose.composable
import com.connectly.android.feature.notifications.presentation.notifications.NotificationsScreen
import com.connectly.android.feature.notifications.presentation.search.SearchScreen

const val NOTIFICATIONS_ROUTE = "notifications_route"
const val SEARCH_ROUTE = "search_route"

fun NavController.navigateToNotifications(navOptions: NavOptions? = null) {
    navigate(NOTIFICATIONS_ROUTE, navOptions)
}

fun NavController.navigateToSearch(navOptions: NavOptions? = null) {
    navigate(SEARCH_ROUTE, navOptions)
}

fun NavGraphBuilder.notificationsGraph(
    navController: NavController
) {
    composable(NOTIFICATIONS_ROUTE) {
        NotificationsScreen(
            onNavigateBack = { navController.popBackStack() },
            onNavigateToSettings = { navController.navigate("settings_route") },
            onNotificationClick = { notification ->
                val target = notification.targetUrl
                if (!target.isNullOrBlank()) {
                    when {
                        target.startsWith("/groups") -> navController.navigate("groups_route")
                        target.startsWith("/pages") -> navController.navigate("pages_route")
                        target.startsWith("/messages") -> navController.navigate("messenger_route")
                        target.startsWith("/profile") -> navController.navigate("profile_route")
                        target.startsWith("/live") -> navController.navigate("live_route/1")
                        target.startsWith("/events") -> navController.navigate("events_route")
                        target.startsWith("/settings") -> navController.navigate("settings_route")
                        else -> {
                            when (notification.type) {
                                com.connectly.android.core.model.NotificationType.FRIEND_REQUEST,
                                com.connectly.android.core.model.NotificationType.FOLLOWER -> navController.navigate("profile_route")
                                com.connectly.android.core.model.NotificationType.GROUP_INVITE -> navController.navigate("groups_route")
                                com.connectly.android.core.model.NotificationType.LIVE_ALERT -> navController.navigate("live_route/1")
                                com.connectly.android.core.model.NotificationType.EVENT_REMINDER -> navController.navigate("events_route")
                                com.connectly.android.core.model.NotificationType.MESSENGER,
                                com.connectly.android.core.model.NotificationType.CALL -> navController.navigate("messenger_route")
                                else -> {}
                            }
                        }
                    }
                } else {
                    when (notification.type) {
                        com.connectly.android.core.model.NotificationType.FRIEND_REQUEST,
                        com.connectly.android.core.model.NotificationType.FOLLOWER -> navController.navigate("profile_route")
                        com.connectly.android.core.model.NotificationType.GROUP_INVITE -> navController.navigate("groups_route")
                        com.connectly.android.core.model.NotificationType.LIVE_ALERT -> navController.navigate("live_route/1")
                        com.connectly.android.core.model.NotificationType.EVENT_REMINDER -> navController.navigate("events_route")
                        com.connectly.android.core.model.NotificationType.MESSENGER,
                        com.connectly.android.core.model.NotificationType.CALL -> navController.navigate("messenger_route")
                        else -> {}
                    }
                }
            }
        )
    }

    composable(SEARCH_ROUTE) {
        SearchScreen(
            onNavigateBack = { navController.popBackStack() },
            onUserClick = { navController.navigate("profile_route") },
            onGroupClick = { navController.navigate("groups_route") },
            onPageClick = { navController.navigate("pages_route") },
            onEventClick = { navController.navigate("events_route") },
            onPostClick = { navController.navigate("feed_route") }
        )
    }
}
