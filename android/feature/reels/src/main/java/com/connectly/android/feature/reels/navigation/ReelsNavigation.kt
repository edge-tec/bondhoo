package com.connectly.android.feature.reels.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.compose.composable
import com.connectly.android.feature.reels.presentation.create.CreateReelScreen
import com.connectly.android.feature.reels.presentation.player.ReelsScreen

const val REELS_ROUTE = "reels_route"
const val CREATE_REEL_ROUTE = "create_reel_route"

fun NavController.navigateToReels(navOptions: NavOptions? = null) {
    navigate(REELS_ROUTE, navOptions)
}

fun NavController.navigateToCreateReel(navOptions: NavOptions? = null) {
    navigate(CREATE_REEL_ROUTE, navOptions)
}

fun NavGraphBuilder.reelsGraph(
    navController: NavController,
    onNavigateToCreateReel: () -> Unit
) {
    composable(REELS_ROUTE) {
        ReelsScreen(
            onNavigateToCreateReel = onNavigateToCreateReel
        )
    }

    composable(CREATE_REEL_ROUTE) {
        CreateReelScreen(
            onNavigateBack = { navController.popBackStack() }
        )
    }
}
