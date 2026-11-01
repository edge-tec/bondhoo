package com.connectly.android

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.connectly.android.core.designsystem.component.BottomBarDestination
import com.connectly.android.core.designsystem.component.ConnectlyBottomBar
import com.connectly.android.core.designsystem.component.ConnectlyTopBar
import com.connectly.android.core.designsystem.theme.ConnectlyTheme
import com.connectly.android.feature.auth.navigation.LOGIN_ROUTE
import com.connectly.android.feature.auth.navigation.SPLASH_ROUTE
import com.connectly.android.feature.auth.navigation.authGraph
import com.connectly.android.feature.auth.navigation.navigateToLogin
import com.connectly.android.core.model.CallType
import com.connectly.android.feature.calling.navigation.callingGraph
import com.connectly.android.feature.calling.navigation.navigateToCall
import com.connectly.android.feature.feed.navigation.feedGraph
import com.connectly.android.feature.feed.navigation.navigateToCreatePost
import com.connectly.android.feature.feed.presentation.feed.FeedScreen
import com.connectly.android.feature.messenger.navigation.messengerGraph
import com.connectly.android.feature.messenger.navigation.navigateToChat
import com.connectly.android.feature.messenger.presentation.conversations.ConversationListScreen
import com.connectly.android.feature.events.navigation.eventsGraph
import com.connectly.android.feature.groups.navigation.groupsGraph
import com.connectly.android.feature.groups.presentation.groups.GroupsScreen
import com.connectly.android.feature.live.navigation.liveGraph
import com.connectly.android.feature.live.navigation.navigateToLive
import com.connectly.android.feature.notifications.navigation.navigateToNotifications
import com.connectly.android.feature.notifications.navigation.navigateToSearch
import com.connectly.android.feature.notifications.navigation.notificationsGraph
import com.connectly.android.feature.profile.navigation.navigateToPrivacySettings
import com.connectly.android.feature.profile.navigation.navigateToProfile
import com.connectly.android.feature.profile.navigation.profileGraph
import com.connectly.android.feature.profile.presentation.friends.FriendsScreen
import com.connectly.android.feature.profile.presentation.settings.SettingsScreen
import com.connectly.android.feature.reels.navigation.reelsGraph
import com.connectly.android.feature.reels.presentation.player.ReelsScreen
import com.connectly.android.feature.stories.navigation.navigateToCreateStory
import com.connectly.android.feature.stories.navigation.navigateToStoryViewer
import com.connectly.android.feature.stories.navigation.storiesGraph
import dagger.hilt.android.AndroidEntryPoint

const val MAIN_HOME_ROUTE = "main_home"

@AndroidEntryPoint
class MainActivity : ComponentActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        setContent {
            ConnectlyTheme {
                val rootNavController = rememberNavController()

                NavHost(
                    navController = rootNavController,
                    startDestination = SPLASH_ROUTE
                ) {
                    authGraph(
                        onNavigateToHome = {
                            rootNavController.navigate(MAIN_HOME_ROUTE) {
                                popUpTo(0) { inclusive = true }
                            }
                        },
                        navController = rootNavController
                    )

                    profileGraph(
                        navController = rootNavController,
                        onLogout = {
                            rootNavController.navigate(LOGIN_ROUTE) {
                                popUpTo(0) { inclusive = true }
                            }
                        }
                    )

                    feedGraph(
                        navController = rootNavController,
                        onNavigateToStoryViewer = { storyId ->
                            rootNavController.navigateToStoryViewer(storyId)
                        },
                        onNavigateToCreateStory = {
                            rootNavController.navigateToCreateStory()
                        },
                        onNavigateToProfile = {
                            rootNavController.navigateToProfile()
                        }
                    )

                    storiesGraph(
                        navController = rootNavController
                    )

                    reelsGraph(
                        navController = rootNavController,
                        onNavigateToCreateReel = { rootNavController.navigateToCreateStory() }
                    )

                    messengerGraph(
                        navController = rootNavController,
                        onStartAudioCall = { receiverId ->
                            rootNavController.navigateToCall(receiverId, isIncoming = false, callType = CallType.AUDIO)
                        },
                        onStartVideoCall = { receiverId ->
                            rootNavController.navigateToCall(receiverId, isIncoming = false, callType = CallType.VIDEO)
                        }
                    )

                    callingGraph(
                        navController = rootNavController
                    )

                    groupsGraph(
                        navController = rootNavController
                    )

                    eventsGraph(
                        navController = rootNavController
                    )

                    liveGraph(
                        navController = rootNavController
                    )

                    notificationsGraph(
                        navController = rootNavController
                    )

                    composable(MAIN_HOME_ROUTE) {
                        var selectedTab by remember { mutableStateOf(BottomBarDestination.HOME) }

                        Scaffold(
                            bottomBar = {
                                ConnectlyBottomBar(
                                    selectedDestination = selectedTab,
                                    onDestinationClick = { destination ->
                                        if (destination == BottomBarDestination.CREATE) {
                                            rootNavController.navigateToCreatePost()
                                        } else {
                                            selectedTab = destination
                                        }
                                    }
                                )
                            }
                        ) { innerPadding ->
                            when (selectedTab) {
                                BottomBarDestination.HOME -> {
                                    FeedScreen(
                                        onNavigateToCreatePost = { rootNavController.navigateToCreatePost() },
                                        onNavigateToStoryViewer = { storyId -> rootNavController.navigateToStoryViewer(storyId) },
                                        onNavigateToCreateStory = { rootNavController.navigateToCreateStory() },
                                        onNavigateToProfile = { rootNavController.navigateToProfile() },
                                        onNavigateToSearch = { rootNavController.navigateToSearch() },
                                        onNavigateToNotifications = { rootNavController.navigateToNotifications() },
                                        modifier = Modifier.padding(innerPadding)
                                    )
                                }
                                BottomBarDestination.FRIENDS -> {
                                    FriendsScreen(
                                        onNavigateToUserProfile = { rootNavController.navigateToProfile() },
                                        onNavigateToChat = { conversationId -> rootNavController.navigateToChat(conversationId) },
                                        modifier = Modifier.padding(innerPadding)
                                    )
                                }
                                BottomBarDestination.REELS -> {
                                    ReelsScreen(
                                        onNavigateToCreateReel = { rootNavController.navigateToCreateStory() },
                                        modifier = Modifier.padding(innerPadding)
                                    )
                                }
                                BottomBarDestination.MESSENGER -> {
                                    ConversationListScreen(
                                        onNavigateToChat = { conversationId ->
                                            rootNavController.navigateToChat(conversationId)
                                        },
                                        onNavigateBack = { selectedTab = BottomBarDestination.HOME },
                                        modifier = Modifier.padding(innerPadding)
                                    )
                                }
                                BottomBarDestination.MENU -> {
                                    SettingsScreen(
                                        onNavigateBack = { selectedTab = BottomBarDestination.HOME },
                                        onNavigateToProfile = { rootNavController.navigateToProfile() },
                                        onNavigateToPrivacy = { rootNavController.navigateToPrivacySettings() },
                                        onLogoutSuccess = {
                                            rootNavController.navigate(LOGIN_ROUTE) {
                                                popUpTo(0) { inclusive = true }
                                            }
                                        },
                                        modifier = Modifier.padding(innerPadding)
                                    )
                                }
                                else -> {
                                    FeedScreen(
                                        onNavigateToCreatePost = { rootNavController.navigateToCreatePost() },
                                        onNavigateToStoryViewer = { storyId -> rootNavController.navigateToStoryViewer(storyId) },
                                        onNavigateToCreateStory = { rootNavController.navigateToCreateStory() },
                                        onNavigateToProfile = { rootNavController.navigateToProfile() },
                                        modifier = Modifier.padding(innerPadding)
                                    )
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
