package com.connectly.android.feature.groups.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.compose.composable
import com.connectly.android.feature.groups.presentation.groups.GroupsScreen

const val GROUPS_ROUTE = "groups_route"

fun NavController.navigateToGroups(navOptions: NavOptions? = null) {
    navigate(GROUPS_ROUTE, navOptions)
}

fun NavGraphBuilder.groupsGraph(
    navController: NavController
) {
    composable(GROUPS_ROUTE) {
        GroupsScreen(
            onNavigateToGroupDetails = { /* navigate to group details */ },
            onCreateGroup = { /* navigate to create group */ }
        )
    }
}

