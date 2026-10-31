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
            onNavigateToSettings = { /* settings */ },
            onNotificationClick = { /* click */ }
        )
    }

    composable(SEARCH_ROUTE) {
        SearchScreen(
            onNavigateBack = { navController.popBackStack() }
        )
    }
}
