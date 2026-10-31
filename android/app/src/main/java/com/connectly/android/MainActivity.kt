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
import androidx.compose.ui.unit.dp
import com.connectly.android.core.designsystem.component.BottomBarDestination
import com.connectly.android.core.designsystem.component.ConnectlyBottomBar
import com.connectly.android.core.designsystem.component.ConnectlyTopBar
import com.connectly.android.core.designsystem.theme.ConnectlyTheme
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class MainActivity : ComponentActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        setContent {
            ConnectlyTheme {
                var selectedTab by remember { mutableStateOf(BottomBarDestination.HOME) }

                Scaffold(
                    topBar = {
                        ConnectlyTopBar(
                            title = "Connectly",
                            unreadNotificationsCount = 3,
                            onSearchClick = { /* Navigate to search */ },
                            onNotificationsClick = { /* Navigate to notifications */ },
                            onProfileClick = { /* Navigate to profile */ }
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
