package com.connectly.android.feature.stories.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.NavType
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import com.connectly.android.feature.stories.presentation.create.CreateStoryScreen
import com.connectly.android.feature.stories.presentation.viewer.StoryViewerScreen

const val STORY_VIEWER_ROUTE = "story_viewer_route"
const val CREATE_STORY_ROUTE = "create_story_route"

fun NavController.navigateToStoryViewer(storyId: Long, navOptions: NavOptions? = null) {
    navigate("$STORY_VIEWER_ROUTE/$storyId", navOptions)
}

fun NavController.navigateToCreateStory(navOptions: NavOptions? = null) {
    navigate(CREATE_STORY_ROUTE, navOptions)
}

fun NavGraphBuilder.storiesGraph(
    navController: NavController
) {
    composable(
        route = "$STORY_VIEWER_ROUTE/{storyId}",
        arguments = listOf(navArgument("storyId") { type = NavType.LongType })
    ) { backStackEntry ->
        val storyId = backStackEntry.arguments?.getLong("storyId") ?: 1L
        StoryViewerScreen(
            storyId = storyId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(CREATE_STORY_ROUTE) {
        CreateStoryScreen(
            onNavigateBack = { navController.popBackStack() }
        )
    }
}
