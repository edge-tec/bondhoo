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
import com.connectly.android.feature.auth.navigation.authGraph
import com.connectly.android.feature.auth.navigation.navigateToLogin
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
                    startDestination = LOGIN_ROUTE
                ) {
                    authGraph(
                        onNavigateToHome = {
                            rootNavController.navigate(MAIN_HOME_ROUTE) {
                                popUpTo(LOGIN_ROUTE) { inclusive = true }
                            }
                        },
                        navController = rootNavController
                    )

                    composable(MAIN_HOME_ROUTE) {
                        var selectedTab by remember { mutableStateOf(BottomBarDestination.HOME) }

                        Scaffold(
                            topBar = {
                                ConnectlyTopBar(
                                    title = "Connectly",
                                    unreadNotificationsCount = 3,
                                    onSearchClick = { /* Navigate to Search */ },
                                    onNotificationsClick = { /* Navigate to Notifications */ },
                                    onProfileClick = {
                                        rootNavController.navigateToLogin()
                                    }
                                )
                            },
                            bottomBar = {
                                ConnectlyBottomBar(
                                    selectedDestination = selectedTab,
                                    onDestinationClick = { destination ->
                                        selectedTab = destination
                                    }
                                )
                            }
                        ) { innerPadding ->
                            Box(
                                modifier = Modifier
                                    .fillMaxSize()
                                    .padding(innerPadding)
                                    .background(MaterialTheme.colorScheme.background),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = "Connectly — ${selectedTab.title} Screen",
                                    style = MaterialTheme.typography.titleMedium,
                                    color = MaterialTheme.colorScheme.onBackground
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}
