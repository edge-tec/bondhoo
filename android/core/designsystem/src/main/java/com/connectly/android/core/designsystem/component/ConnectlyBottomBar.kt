package com.connectly.android.core.designsystem.component

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import com.connectly.android.core.designsystem.theme.ConnectlyBlue

enum class BottomBarDestination(
    val title: String,
    val icon: ImageVector
) {
    HOME("Home", Icons.Default.Home),
    FRIENDS("Friends", Icons.Default.People),
    CREATE("Create", Icons.Default.Add),
    REELS("Videos", Icons.Default.PlayCircle),
    MESSENGER("Messenger", Icons.Default.Chat),
    MENU("Menu", Icons.Default.Menu)
}

@Composable
fun ConnectlyBottomBar(
    selectedDestination: BottomBarDestination,
    onDestinationClick: (BottomBarDestination) -> Unit,
    modifier: Modifier = Modifier
) {
    NavigationBar(
        containerColor = MaterialTheme.colorScheme.surface,
        tonalElevation = 8.dp,
        modifier = modifier
    ) {
        BottomBarDestination.entries.forEach { destination ->
            val isSelected = destination == selectedDestination

            if (destination == BottomBarDestination.CREATE) {
                // Elevated '+' Create action button
                NavigationBarItem(
                    selected = false,
                    onClick = { onDestinationClick(destination) },
                    icon = {
                        Box(
                            modifier = Modifier
                                .size(40.dp)
                                .clip(CircleShape)
                                .background(ConnectlyBlue),
                            contentAlignment = Alignment.Center
                        ) {
                            Icon(
                                imageVector = destination.icon,
                                contentDescription = destination.title,
                                tint = Color.White,
                                modifier = Modifier.size(24.dp)
                            )
                        }
                    },
                    colors = NavigationBarItemDefaults.colors(
                        indicatorColor = Color.Transparent
                    )
                )
            } else {
                NavigationBarItem(
                    selected = isSelected,
                    onClick = { onDestinationClick(destination) },
                    icon = {
                        Icon(
                            imageVector = destination.icon,
                            contentDescription = destination.title
                        )
                    },
                    label = {
                        Text(
                            text = destination.title,
                            style = MaterialTheme.typography.labelSmall
                        )
                    },
                    colors = NavigationBarItemDefaults.colors(
                        selectedIconColor = ConnectlyBlue,
                        selectedTextColor = ConnectlyBlue,
                        unselectedIconColor = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
                        unselectedTextColor = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.6f),
                        indicatorColor = Color.Transparent
                    )
                )
            }
        }
    }
}

