package com.connectly.android.feature.groups.navigation

import androidx.navigation.NavController
import androidx.navigation.NavGraphBuilder
import androidx.navigation.NavOptions
import androidx.navigation.NavType
import androidx.navigation.compose.composable
import androidx.navigation.navArgument
import com.connectly.android.feature.groups.presentation.content.GroupAnnouncementsScreen
import com.connectly.android.feature.groups.presentation.content.GroupContentModerationScreen
import com.connectly.android.feature.groups.presentation.content.GroupPendingPostsScreen
import com.connectly.android.feature.groups.presentation.content.GroupPostComposerScreen
import com.connectly.android.feature.groups.presentation.content.GroupPostDetailsScreen
import com.connectly.android.feature.groups.presentation.dashboard.GroupDashboardScreen
import com.connectly.android.feature.groups.presentation.groups.GroupsScreen
import com.connectly.android.feature.groups.presentation.members.GroupMembersScreen
import com.connectly.android.feature.groups.presentation.pages.PagesScreen
import com.connectly.android.feature.groups.presentation.pages.analytics.PageAnalyticsScreen
import com.connectly.android.feature.groups.presentation.pages.content.PageContentManagerScreen
import com.connectly.android.feature.groups.presentation.pages.create.PageCreationWizardScreen
import com.connectly.android.feature.groups.presentation.pages.dashboard.PageDashboardScreen
import com.connectly.android.feature.groups.presentation.pages.inbox.PageInboxScreen
import com.connectly.android.feature.groups.presentation.pages.monetization.PageMonetizationScreen
import com.connectly.android.feature.groups.presentation.pages.profile.PageProfileScreen
import com.connectly.android.feature.groups.presentation.pages.roles.PageTeamRolesScreen
import com.connectly.android.feature.groups.presentation.pages.settings.PageSettingsScreen
import com.connectly.android.feature.groups.presentation.profile.GroupProfileScreen
import com.connectly.android.feature.groups.presentation.roles.GroupRolesScreen

const val GROUPS_ROUTE = "groups_route"
const val PAGES_ROUTE = "pages_route"
const val GROUP_PROFILE_ROUTE = "group_profile_route/{slugOrId}"
const val GROUP_DASHBOARD_ROUTE = "group_dashboard_route/{groupId}"
const val GROUP_ROLES_ROUTE = "group_roles_route/{groupId}"
const val GROUP_MEMBERS_ROUTE = "group_members_route/{groupId}"
const val GROUP_POST_COMPOSER_ROUTE = "group_post_composer_route/{groupId}"
const val GROUP_POST_DETAILS_ROUTE = "group_post_details_route/{groupId}/{postId}"
const val GROUP_PENDING_POSTS_ROUTE = "group_pending_posts_route/{groupId}"
const val GROUP_ANNOUNCEMENTS_ROUTE = "group_announcements_route/{groupId}"
const val GROUP_CONTENT_MODERATION_ROUTE = "group_content_moderation_route/{groupId}"

const val PAGE_PROFILE_ROUTE = "page_profile_route/{pageId}"
const val PAGE_CREATE_ROUTE = "page_create_route"
const val PAGE_DASHBOARD_ROUTE = "page_dashboard_route/{pageId}"
const val PAGE_ANALYTICS_ROUTE = "page_analytics_route/{pageId}"
const val PAGE_CONTENT_ROUTE = "page_content_route/{pageId}"
const val PAGE_ROLES_ROUTE = "page_roles_route/{pageId}"
const val PAGE_INBOX_ROUTE = "page_inbox_route/{pageId}"
const val PAGE_SETTINGS_ROUTE = "page_settings_route/{pageId}"
const val PAGE_MONETIZATION_ROUTE = "page_monetization_route/{pageId}"

fun NavController.navigateToGroups(navOptions: NavOptions? = null) {
    navigate(GROUPS_ROUTE, navOptions)
}

fun NavController.navigateToPages(navOptions: NavOptions? = null) {
    navigate(PAGES_ROUTE, navOptions)
}

fun NavController.navigateToGroupProfile(slugOrId: String, navOptions: NavOptions? = null) {
    navigate("group_profile_route/$slugOrId", navOptions)
}

fun NavController.navigateToGroupDashboard(groupId: Long, navOptions: NavOptions? = null) {
    navigate("group_dashboard_route/$groupId", navOptions)
}

fun NavController.navigateToGroupRoles(groupId: Long, navOptions: NavOptions? = null) {
    navigate("group_roles_route/$groupId", navOptions)
}

fun NavController.navigateToGroupMembers(groupId: Long, navOptions: NavOptions? = null) {
    navigate("group_members_route/$groupId", navOptions)
}

fun NavController.navigateToGroupPostComposer(groupId: Long, navOptions: NavOptions? = null) {
    navigate("group_post_composer_route/$groupId", navOptions)
}

fun NavController.navigateToGroupPostDetails(groupId: Long, postId: Long, navOptions: NavOptions? = null) {
    navigate("group_post_details_route/$groupId/$postId", navOptions)
}

fun NavController.navigateToGroupPendingPosts(groupId: Long, navOptions: NavOptions? = null) {
    navigate("group_pending_posts_route/$groupId", navOptions)
}

fun NavController.navigateToGroupAnnouncements(groupId: Long, navOptions: NavOptions? = null) {
    navigate("group_announcements_route/$groupId", navOptions)
}

fun NavController.navigateToGroupContentModeration(groupId: Long, navOptions: NavOptions? = null) {
    navigate("group_content_moderation_route/$groupId", navOptions)
}

fun NavController.navigateToPageProfile(pageId: Long, navOptions: NavOptions? = null) {
    navigate("page_profile_route/$pageId", navOptions)
}

fun NavController.navigateToPageCreate(navOptions: NavOptions? = null) {
    navigate(PAGE_CREATE_ROUTE, navOptions)
}

fun NavController.navigateToPageDashboard(pageId: Long, navOptions: NavOptions? = null) {
    navigate("page_dashboard_route/$pageId", navOptions)
}

fun NavController.navigateToPageAnalytics(pageId: Long, navOptions: NavOptions? = null) {
    navigate("page_analytics_route/$pageId", navOptions)
}

fun NavController.navigateToPageContentManager(pageId: Long, navOptions: NavOptions? = null) {
    navigate("page_content_route/$pageId", navOptions)
}

fun NavController.navigateToPageRoles(pageId: Long, navOptions: NavOptions? = null) {
    navigate("page_roles_route/$pageId", navOptions)
}

fun NavController.navigateToPageInbox(pageId: Long, navOptions: NavOptions? = null) {
    navigate("page_inbox_route/$pageId", navOptions)
}

fun NavController.navigateToPageSettings(pageId: Long, navOptions: NavOptions? = null) {
    navigate("page_settings_route/$pageId", navOptions)
}

fun NavController.navigateToPageMonetization(pageId: Long, navOptions: NavOptions? = null) {
    navigate("page_monetization_route/$pageId", navOptions)
}

fun NavGraphBuilder.groupsGraph(
    navController: NavController
) {
    composable(GROUPS_ROUTE) {
        GroupsScreen(
            onNavigateToGroupDetails = { groupId ->
                navController.navigateToGroupProfile(groupId.toString())
            },
            onCreateGroup = { }
        )
    }

    composable(
        route = GROUP_PROFILE_ROUTE,
        arguments = listOf(navArgument("slugOrId") { type = NavType.StringType })
    ) { backStackEntry ->
        val slugOrId = backStackEntry.arguments?.getString("slugOrId") ?: ""
        GroupProfileScreen(
            slugOrId = slugOrId,
            onNavigateBack = { navController.popBackStack() },
            onNavigateToDashboard = { groupId ->
                navController.navigateToGroupDashboard(groupId)
            }
        )
    }

    composable(
        route = GROUP_DASHBOARD_ROUTE,
        arguments = listOf(navArgument("groupId") { type = NavType.LongType })
    ) { backStackEntry ->
        val groupId = backStackEntry.arguments?.getLong("groupId") ?: 0L
        GroupDashboardScreen(
            groupId = groupId,
            onNavigateBack = { navController.popBackStack() },
            onQuickActionClick = { actionId, _ ->
                if (actionId == "manage_admins" || actionId == "manage_roles") {
                    navController.navigateToGroupRoles(groupId)
                } else if (actionId == "manage_members") {
                    navController.navigateToGroupMembers(groupId)
                } else if (actionId == "create_post") {
                    navController.navigateToGroupPostComposer(groupId)
                } else if (actionId == "create_announcement") {
                    navController.navigateToGroupAnnouncements(groupId)
                } else if (actionId == "review_requests" || actionId == "pending_posts") {
                    navController.navigateToGroupPendingPosts(groupId)
                } else if (actionId == "view_reports" || actionId == "moderation") {
                    navController.navigateToGroupContentModeration(groupId)
                }
            }
        )
    }

    composable(
        route = GROUP_MEMBERS_ROUTE,
        arguments = listOf(navArgument("groupId") { type = NavType.LongType })
    ) { backStackEntry ->
        val groupId = backStackEntry.arguments?.getLong("groupId") ?: 0L
        GroupMembersScreen(
            groupId = groupId,
            onNavigateBack = { navController.popBackStack() },
            onNavigateToUserProfile = { /* user profile */ }
        )
    }

    composable(
        route = GROUP_ROLES_ROUTE,
        arguments = listOf(navArgument("groupId") { type = NavType.LongType })
    ) { backStackEntry ->
        val groupId = backStackEntry.arguments?.getLong("groupId") ?: 0L
        GroupRolesScreen(
            groupId = groupId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(
        route = GROUP_POST_COMPOSER_ROUTE,
        arguments = listOf(navArgument("groupId") { type = NavType.LongType })
    ) { backStackEntry ->
        val groupId = backStackEntry.arguments?.getLong("groupId") ?: 0L
        GroupPostComposerScreen(
            groupId = groupId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(
        route = GROUP_POST_DETAILS_ROUTE,
        arguments = listOf(
            navArgument("groupId") { type = NavType.LongType },
            navArgument("postId") { type = NavType.LongType }
        )
    ) { backStackEntry ->
        val groupId = backStackEntry.arguments?.getLong("groupId") ?: 0L
        val postId = backStackEntry.arguments?.getLong("postId") ?: 0L
        GroupPostDetailsScreen(
            groupId = groupId,
            postId = postId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(
        route = GROUP_PENDING_POSTS_ROUTE,
        arguments = listOf(navArgument("groupId") { type = NavType.LongType })
    ) { backStackEntry ->
        val groupId = backStackEntry.arguments?.getLong("groupId") ?: 0L
        GroupPendingPostsScreen(
            groupId = groupId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(
        route = GROUP_ANNOUNCEMENTS_ROUTE,
        arguments = listOf(navArgument("groupId") { type = NavType.LongType })
    ) { backStackEntry ->
        val groupId = backStackEntry.arguments?.getLong("groupId") ?: 0L
        GroupAnnouncementsScreen(
            groupId = groupId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(
        route = GROUP_CONTENT_MODERATION_ROUTE,
        arguments = listOf(navArgument("groupId") { type = NavType.LongType })
    ) { backStackEntry ->
        val groupId = backStackEntry.arguments?.getLong("groupId") ?: 0L
        GroupContentModerationScreen(
            groupId = groupId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(PAGES_ROUTE) {
        PagesScreen(
            onNavigateBack = { navController.popBackStack() },
            onPageClick = { pageId -> navController.navigateToPageProfile(pageId) },
            onCreatePage = { navController.navigateToPageCreate() }
        )
    }

    composable(PAGE_CREATE_ROUTE) {
        PageCreationWizardScreen(
            onNavigateBack = { navController.popBackStack() },
            onPageCreated = { newPageId ->
                navController.popBackStack()
                navController.navigateToPageProfile(newPageId)
            }
        )
    }

    composable(
        route = PAGE_PROFILE_ROUTE,
        arguments = listOf(navArgument("pageId") { type = NavType.StringType })
    ) { backStackEntry ->
        val pageId = backStackEntry.arguments?.getString("pageId") ?: ""
        PageProfileScreen(
            pageIdOrSlug = pageId,
            onNavigateBack = { navController.popBackStack() },
            onNavigateToDashboard = { id -> navController.navigateToPageDashboard(id) },
            onNavigateToInbox = { id -> navController.navigateToPageInbox(id) }
        )
    }

    composable(
        route = PAGE_DASHBOARD_ROUTE,
        arguments = listOf(navArgument("pageId") { type = NavType.LongType })
    ) { backStackEntry ->
        val pageId = backStackEntry.arguments?.getLong("pageId") ?: 0L
        PageDashboardScreen(
            pageId = pageId,
            onNavigateBack = { navController.popBackStack() },
            onNavigateToAnalytics = { id -> navController.navigateToPageAnalytics(id) },
            onNavigateToContentManager = { id -> navController.navigateToPageContentManager(id) },
            onNavigateToRoles = { id -> navController.navigateToPageRoles(id) },
            onNavigateToInbox = { id -> navController.navigateToPageInbox(id) },
            onNavigateToSettings = { id -> navController.navigateToPageSettings(id) },
            onNavigateToMonetization = { id -> navController.navigateToPageMonetization(id) }
        )
    }

    composable(
        route = PAGE_ANALYTICS_ROUTE,
        arguments = listOf(navArgument("pageId") { type = NavType.LongType })
    ) { backStackEntry ->
        val pageId = backStackEntry.arguments?.getLong("pageId") ?: 0L
        PageAnalyticsScreen(
            pageId = pageId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(
        route = PAGE_CONTENT_ROUTE,
        arguments = listOf(navArgument("pageId") { type = NavType.LongType })
    ) { backStackEntry ->
        val pageId = backStackEntry.arguments?.getLong("pageId") ?: 0L
        PageContentManagerScreen(
            pageId = pageId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(
        route = PAGE_ROLES_ROUTE,
        arguments = listOf(navArgument("pageId") { type = NavType.LongType })
    ) { backStackEntry ->
        val pageId = backStackEntry.arguments?.getLong("pageId") ?: 0L
        PageTeamRolesScreen(
            pageId = pageId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(
        route = PAGE_INBOX_ROUTE,
        arguments = listOf(navArgument("pageId") { type = NavType.LongType })
    ) { backStackEntry ->
        val pageId = backStackEntry.arguments?.getLong("pageId") ?: 0L
        PageInboxScreen(
            pageId = pageId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(
        route = PAGE_SETTINGS_ROUTE,
        arguments = listOf(navArgument("pageId") { type = NavType.LongType })
    ) { backStackEntry ->
        val pageId = backStackEntry.arguments?.getLong("pageId") ?: 0L
        PageSettingsScreen(
            pageId = pageId,
            onNavigateBack = { navController.popBackStack() }
        )
    }

    composable(
        route = PAGE_MONETIZATION_ROUTE,
        arguments = listOf(navArgument("pageId") { type = NavType.LongType })
    ) { backStackEntry ->
        val pageId = backStackEntry.arguments?.getLong("pageId") ?: 0L
        PageMonetizationScreen(
            pageId = pageId,
            onNavigateBack = { navController.popBackStack() }
        )
    }
}
