package com.connectly.android.feature.profile.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.compose.composable
import com.connectly.android.feature.profile.presentation.edit.EditProfileScreen
import com.connectly.android.feature.profile.presentation.privacy.PrivacySettingsScreen
import com.connectly.android.feature.profile.presentation.profile.ProfileScreen
import com.connectly.android.feature.profile.presentation.settings.SettingsScreen

const val PROFILE_ROUTE = "profile_route"
const val EDIT_PROFILE_ROUTE = "edit_profile_route"
const val PRIVACY_SETTINGS_ROUTE = "privacy_settings_route"
const val SETTINGS_ROUTE = "settings_route"

fun NavController.navigateToProfile(navOptions: NavOptions? = null) {
    navigate(PROFILE_ROUTE, navOptions)
}

fun NavController.navigateToEditProfile(navOptions: NavOptions? = null) {
    navigate(EDIT_PROFILE_ROUTE, navOptions)
}

fun NavController.navigateToPrivacySettings(navOptions: NavOptions? = null) {
    navigate(PRIVACY_SETTINGS_ROUTE, navOptions)
}

fun NavController.navigateToSettings(navOptions: NavOptions? = null) {
    navigate(SETTINGS_ROUTE, navOptions)
}

fun NavGraphBuilder.profileGraph(
    navController: NavController,
    onLogout: () -> Unit
) {
    composable(PROFILE_ROUTE) {
        ProfileScreen(
            onNavigateBack = { navController.popBackStack() },
            onNavigateToEditProfile = { navController.navigateToEditProfile() },
            onNavigateToSettings = { navController.navigateToSettings() }
        )
    }

    composable(EDIT_PROFILE_ROUTE) {
        EditProfileScreen(
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(PRIVACY_SETTINGS_ROUTE) {
        PrivacySettingsScreen(
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(SETTINGS_ROUTE) {
        SettingsScreen(
            onNavigateBack = { navController.popBackStack() },
            onNavigateToProfile = { navController.navigateToProfile() },
            onNavigateToPrivacy = { navController.navigateToPrivacySettings() },
            onLogoutSuccess = onLogout
        )
    }
}
