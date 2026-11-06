package com.connectly.android.feature.jobs.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.compose.composable
import com.connectly.android.feature.jobs.presentation.jobs.JobsHomeScreen

const val JOBS_ROUTE = "jobs_route"

fun NavController.navigateToJobs(navOptions: NavOptions? = null) {
    navigate(JOBS_ROUTE, navOptions)
}

fun NavGraphBuilder.jobsScreen(
    onNavigateBack: () -> Unit,
    onJoinVideoCall: (Long) -> Unit = {}
) {
    composable(route = JOBS_ROUTE) {
        JobsHomeScreen(
            onNavigateBack = onNavigateBack,
            onJoinVideoCall = onJoinVideoCall
        )
    }
}

