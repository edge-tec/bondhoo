package com.connectly.android.feature.profile.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.compose.composable
import com.connectly.android.core.designsystem.theme.ThemeManager
import com.connectly.android.feature.profile.presentation.appearance.AppearanceSettingsScreen
import com.connectly.android.feature.profile.presentation.edit.EditProfileScreen
import com.connectly.android.feature.profile.presentation.feedpreferences.FeedPreferencesScreen
import com.connectly.android.feature.profile.presentation.friends.FriendsScreen
import com.connectly.android.feature.profile.presentation.language.LanguageSettingsScreen
import com.connectly.android.feature.profile.presentation.messaging.MessagingCallSettingsScreen
import com.connectly.android.feature.profile.presentation.notifications.NotificationSettingsScreen
import com.connectly.android.feature.profile.presentation.privacy.PrivacySettingsScreen
import com.connectly.android.feature.profile.presentation.profile.ProfileScreen
import com.connectly.android.feature.profile.presentation.settings.SettingsScreen

const val PROFILE_ROUTE = "profile_route"
const val EDIT_PROFILE_ROUTE = "edit_profile_route"
const val PRIVACY_SETTINGS_ROUTE = "privacy_settings_route"
const val NOTIFICATION_SETTINGS_ROUTE = "notification_settings_route"
const val APPEARANCE_SETTINGS_ROUTE = "appearance_settings_route"
const val LANGUAGE_SETTINGS_ROUTE = "language_settings_route"
const val MESSAGING_CALL_SETTINGS_ROUTE = "messaging_call_settings_route"
const val FEED_PREFERENCES_ROUTE = "feed_preferences_route"
const val SETTINGS_ROUTE = "settings_route"
const val FRIENDS_ROUTE = "friends_route"

fun NavController.navigateToProfile(navOptions: NavOptions? = null) {
    navigate(PROFILE_ROUTE, navOptions)
}

fun NavController.navigateToFriends(navOptions: NavOptions? = null) {
    navigate(FRIENDS_ROUTE, navOptions)
}

fun NavController.navigateToEditProfile(navOptions: NavOptions? = null) {
    navigate(EDIT_PROFILE_ROUTE, navOptions)
}

fun NavController.navigateToPrivacySettings(navOptions: NavOptions? = null) {
    navigate(PRIVACY_SETTINGS_ROUTE, navOptions)
}

fun NavController.navigateToNotificationSettings(navOptions: NavOptions? = null) {
    navigate(NOTIFICATION_SETTINGS_ROUTE, navOptions)
}

fun NavController.navigateToAppearanceSettings(navOptions: NavOptions? = null) {
    navigate(APPEARANCE_SETTINGS_ROUTE, navOptions)
}

fun NavController.navigateToLanguageSettings(navOptions: NavOptions? = null) {
    navigate(LANGUAGE_SETTINGS_ROUTE, navOptions)
}

fun NavController.navigateToMessagingCallSettings(navOptions: NavOptions? = null) {
    navigate(MESSAGING_CALL_SETTINGS_ROUTE, navOptions)
}

fun NavController.navigateToFeedPreferences(navOptions: NavOptions? = null) {
    navigate(FEED_PREFERENCES_ROUTE, navOptions)
}

fun NavController.navigateToSettings(navOptions: NavOptions? = null) {
    navigate(SETTINGS_ROUTE, navOptions)
}

fun NavGraphBuilder.profileGraph(
    navController: NavController,
    themeManager: ThemeManager,
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
            onNavigateBack = { navController.popBackStack() },
            onAccountTerminated = onLogout
        )
    }

    composable(PRIVACY_SETTINGS_ROUTE) {
        PrivacySettingsScreen(
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(NOTIFICATION_SETTINGS_ROUTE) {
        NotificationSettingsScreen(
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(APPEARANCE_SETTINGS_ROUTE) {
        AppearanceSettingsScreen(
            themeManager = themeManager,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(LANGUAGE_SETTINGS_ROUTE) {
        LanguageSettingsScreen(
            themeManager = themeManager,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(MESSAGING_CALL_SETTINGS_ROUTE) {
        MessagingCallSettingsScreen(
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(FEED_PREFERENCES_ROUTE) {
        FeedPreferencesScreen(
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(SETTINGS_ROUTE) {
        SettingsScreen(
            onNavigateBack = { navController.popBackStack() },
            onNavigateToProfile = { navController.navigateToProfile() },
            onNavigateToEditProfile = { navController.navigateToEditProfile() },
            onNavigateToPrivacy = { navController.navigateToPrivacySettings() },
            onNavigateToNotificationSettings = { navController.navigateToNotificationSettings() },
            onNavigateToAppearance = { navController.navigateToAppearanceSettings() },
            onNavigateToLanguage = { navController.navigateToLanguageSettings() },
            onNavigateToMessagingCalls = { navController.navigateToMessagingCallSettings() },
            onNavigateToFeedPreferences = { navController.navigateToFeedPreferences() },
            onLogoutSuccess = onLogout
        )
    }

    composable(FRIENDS_ROUTE) {
        FriendsScreen(
            onNavigateToUserProfile = { navController.navigateToProfile() }
        )
    }
}

