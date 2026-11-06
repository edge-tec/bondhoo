package com.connectly.android.feature.feed.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.NavType
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import com.connectly.android.feature.feed.presentation.create.CreatePostScreen
import com.connectly.android.feature.feed.presentation.feed.FeedScreen

const val FEED_ROUTE = "feed_route"
const val CREATE_POST_ROUTE = "create_post_route"

fun NavController.navigateToFeed(navOptions: NavOptions? = null) {
    navigate(FEED_ROUTE, navOptions)
}

fun NavController.navigateToCreatePost(initialAction: String? = null, navOptions: NavOptions? = null) {
    val route = if (!initialAction.isNullOrBlank()) {
        "$CREATE_POST_ROUTE?initialAction=$initialAction"
    } else {
        CREATE_POST_ROUTE
    }
    navigate(route, navOptions)
}

fun NavGraphBuilder.feedGraph(
    navController: NavController,
    onNavigateToStoryViewer: (Long) -> Unit,
    onNavigateToCreateStory: () -> Unit,
    onNavigateToProfile: () -> Unit,
    onNavigateToLive: (Long) -> Unit = {}
) {
    composable(FEED_ROUTE) {
        FeedScreen(
            onNavigateToCreatePost = { action -> navController.navigateToCreatePost(action) },
            onNavigateToStoryViewer = onNavigateToStoryViewer,
            onNavigateToCreateStory = onNavigateToCreateStory,
            onNavigateToProfile = onNavigateToProfile,
            onNavigateToLive = onNavigateToLive
        )
    }

    composable(
        route = "$CREATE_POST_ROUTE?initialAction={initialAction}",
        arguments = listOf(
            navArgument("initialAction") {
                type = NavType.StringType
                nullable = true
                defaultValue = null
            }
        )
    ) { backStackEntry ->
        val initialAction = backStackEntry.arguments?.getString("initialAction")
        CreatePostScreen(
            initialAction = initialAction,
            onNavigateBack = { navController.popBackStack() },
            onNavigateToLive = onNavigateToLive
        )
    }
}

