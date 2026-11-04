package com.connectly.android.feature.learning.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.compose.composable
import com.connectly.android.feature.learning.presentation.learning.LearningHomeScreen

const val LEARNING_ROUTE = "learning_route"

fun NavController.navigateToLearning(navOptions: NavOptions? = null) {
    navigate(LEARNING_ROUTE, navOptions)
}

fun NavGraphBuilder.learningScreen(
    onNavigateBack: () -> Unit,
    onNavigateToCourse: (String) -> Unit = {},
    onNavigateToLesson: (Long) -> Unit = {}
) {
    composable(route = LEARNING_ROUTE) {
        LearningHomeScreen(
            onNavigateBack = onNavigateBack,
            onNavigateToCourse = onNavigateToCourse,
            onNavigateToLesson = onNavigateToLesson
        )
    }
}

