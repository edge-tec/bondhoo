package com.connectly.android.feature.feed.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.compose.composable
import com.connectly.android.feature.feed.presentation.create.CreatePostScreen
import com.connectly.android.feature.feed.presentation.feed.FeedScreen

const val FEED_ROUTE = "feed_route"
const val CREATE_POST_ROUTE = "create_post_route"

fun NavController.navigateToFeed(navOptions: NavOptions? = null) {
    navigate(FEED_ROUTE, navOptions)
}

fun NavController.navigateToCreatePost(navOptions: NavOptions? = null) {
    navigate(CREATE_POST_ROUTE, navOptions)
}

fun NavGraphBuilder.feedGraph(
    navController: NavController,
    onNavigateToStoryViewer: (Long) -> Unit,
    onNavigateToCreateStory: () -> Unit,
    onNavigateToProfile: () -> Unit
) {
    composable(FEED_ROUTE) {
        FeedScreen(
            onNavigateToCreatePost = { navController.navigateToCreatePost() },
            onNavigateToStoryViewer = onNavigateToStoryViewer,
            onNavigateToCreateStory = onNavigateToCreateStory,
            onNavigateToProfile = onNavigateToProfile
        )
    }

    composable(CREATE_POST_ROUTE) {
        CreatePostScreen(
            onNavigateBack = { navController.popBackStack() }
        )
    }
}
