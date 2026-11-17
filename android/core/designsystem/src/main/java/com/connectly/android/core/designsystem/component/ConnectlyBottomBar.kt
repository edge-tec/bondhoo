package com.connectly.android.core.designsystem.component

import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Chat
import androidx.compose.material.icons.automirrored.outlined.Chat
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material.icons.filled.OndemandVideo
import androidx.compose.material.icons.filled.People
import androidx.compose.material.icons.outlined.Home
import androidx.compose.material.icons.outlined.Menu
import androidx.compose.material.icons.outlined.OndemandVideo
import androidx.compose.material.icons.outlined.PeopleOutline
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.core.designsystem.theme.ConnectlyBlue

enum class BottomBarDestination(
    val title: String,
    val selectedIcon: ImageVector,
    val unselectedIcon: ImageVector
) {
    HOME("Home", Icons.Filled.Home, Icons.Outlined.Home),
    FRIENDS("Friends", Icons.Filled.People, Icons.Outlined.PeopleOutline),
    REELS("Videos", Icons.Filled.OndemandVideo, Icons.Outlined.OndemandVideo),
    MESSENGER("Messenger", Icons.AutoMirrored.Filled.Chat, Icons.AutoMirrored.Outlined.Chat),
    MENU("Menu", Icons.Filled.Menu, Icons.Outlined.Menu);

    val icon: ImageVector get() = selectedIcon
}

@Composable
fun ConnectlyBottomNavigation(
    selectedDestination: BottomBarDestination,
    onDestinationClick: (BottomBarDestination) -> Unit,
    modifier: Modifier = Modifier
) {
    ConnectlyBottomBar(
        selectedDestination = selectedDestination,
        onDestinationClick = onDestinationClick,
        modifier = modifier
    )
}

@Composable
fun ConnectlyBottomBar(
    selectedDestination: BottomBarDestination,
    onDestinationClick: (BottomBarDestination) -> Unit,
    modifier: Modifier = Modifier
) {
    val unselectedColor = MaterialTheme.colorScheme.onSurface.copy(alpha = 0.65f)

    NavigationBar(
        containerColor = MaterialTheme.colorScheme.surface,
        tonalElevation = 6.dp,
        modifier = modifier
            .fillMaxWidth()
            .height(66.dp)
    ) {
        BottomBarDestination.entries.forEach { destination ->
            val isSelected = destination == selectedDestination

            NavigationBarItem(
                selected = isSelected,
                onClick = { onDestinationClick(destination) },
                icon = {
                    Icon(
                        imageVector = if (isSelected) destination.selectedIcon else destination.unselectedIcon,
                        contentDescription = destination.title,
                        modifier = Modifier.size(24.dp)
                    )
                },
                label = {
                    Text(
                        text = destination.title,
                        fontSize = 11.sp,
                        fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                        maxLines = 1,
                        softWrap = false,
                        overflow = TextOverflow.Ellipsis
                    )
                },
                alwaysShowLabel = true,
                colors = NavigationBarItemDefaults.colors(
                    selectedIconColor = ConnectlyBlue,
                    selectedTextColor = ConnectlyBlue,
                    unselectedIconColor = unselectedColor,
                    unselectedTextColor = unselectedColor,
                    indicatorColor = Color.Transparent
                )
            )
        }
    }
}
