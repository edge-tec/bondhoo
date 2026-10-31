package com.connectly.android.feature.live.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.NavType
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import com.connectly.android.feature.live.presentation.player.LivePlayerScreen

const val LIVE_ROUTE = "live_route"

fun NavController.navigateToLive(streamId: Long = 1L, navOptions: NavOptions? = null) {
    navigate("$LIVE_ROUTE/$streamId", navOptions)
}

fun NavGraphBuilder.liveGraph(
    navController: NavController
) {
    composable(
        route = "$LIVE_ROUTE/{streamId}",
        arguments = listOf(navArgument("streamId") { type = NavType.LongType; defaultValue = 1L })
    ) { backStackEntry ->
        val streamId = backStackEntry.arguments?.getLong("streamId") ?: 1L
        LivePlayerScreen(
            streamId = streamId,
            onNavigateBack = { navController.popBackStack() }
        )
    }
}

