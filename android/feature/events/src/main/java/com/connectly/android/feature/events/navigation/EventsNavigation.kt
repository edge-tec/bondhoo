package com.connectly.android.feature.events.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.compose.composable
import com.connectly.android.feature.events.presentation.events.EventsScreen

const val EVENTS_ROUTE = "events_route"

fun NavController.navigateToEvents(navOptions: NavOptions? = null) {
    navigate(EVENTS_ROUTE, navOptions)
}

fun NavGraphBuilder.eventsGraph(
    navController: NavController
) {
    composable(EVENTS_ROUTE) {
        EventsScreen(
            onNavigateToEventDetails = { /* event details */ },
            onCreateEvent = { /* create event */ }
        )
    }
}

