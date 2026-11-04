package com.connectly.android.feature.profile.presentation.menu

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowForwardIos
import androidx.compose.material.icons.automirrored.filled.Chat
import androidx.compose.material.icons.automirrored.filled.Help
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.theme.*
import com.connectly.android.feature.profile.presentation.settings.SettingsViewModel

private data class MenuItemDef(
    val id: String,
    val title: String,
    val subtitle: String,
    val icon: ImageVector,
    val iconTint: Color,
    val badge: String? = null,
    val isLiveBadge: Boolean = false,
    val onClick: () -> Unit
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MenuScreen(
    onNavigateToProfile: () -> Unit,
    onNavigateToMessages: () -> Unit,
    onNavigateToNotifications: () -> Unit,
    onNavigateToReels: () -> Unit,
    onNavigateToGroups: () -> Unit,
    onNavigateToPages: () -> Unit,
    onNavigateToLive: () -> Unit,
    onNavigateToEvents: () -> Unit,
    onNavigateToSettings: () -> Unit,
    onNavigateToHelpSupport: () -> Unit,
    onNavigateToSearch: () -> Unit,
    onLogoutSuccess: () -> Unit,
    onNavigateToMarketplace: () -> Unit = {},
    onNavigateToSaved: () -> Unit = {},
    onNavigateToMemories: () -> Unit = {},
    onNavigateToJobs: () -> Unit = {},
    onNavigateToLearning: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: SettingsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    var showLogoutDialog by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        viewModel.loadSettingsOverview()
    }

    LaunchedEffect(state.isLoggedOut) {
        if (state.isLoggedOut) {
            onLogoutSuccess()
        }
    }

    val primaryMenuItems = remember {
        listOf(
            MenuItemDef(
                id = "messages",
                title = "Messages",
                subtitle = "Chats & voice calls",
                icon = Icons.AutoMirrored.Filled.Chat,
                iconTint = Color(0xFF38BDF8),
                onClick = onNavigateToMessages
            ),
            MenuItemDef(
                id = "notifications",
                title = "Notifications",
                subtitle = "Alerts & updates",
                icon = Icons.Default.Notifications,
                iconTint = Color(0xFFF59E0B),
                onClick = onNavigateToNotifications
            ),
            MenuItemDef(
                id = "reels",
                title = "Reels",
                subtitle = "Short video feed",
                icon = Icons.Default.PlayCircle,
                iconTint = Color(0xFFC084FC),
                badge = "New",
                onClick = onNavigateToReels
            ),
            MenuItemDef(
                id = "groups",
                title = "Groups",
                subtitle = "Find your community",
                icon = Icons.Default.Groups,
                iconTint = Color(0xFF818CF8),
                onClick = onNavigateToGroups
            ),
            MenuItemDef(
                id = "pages",
                title = "Pages",
                subtitle = "Official spaces & creators",
                icon = Icons.Default.Flag,
                iconTint = Color(0xFFFB923C),
                onClick = onNavigateToPages
            ),
            MenuItemDef(
                id = "marketplace",
                title = "Marketplace",
                subtitle = "Buy and sell locally",
                icon = Icons.Default.ShoppingBag,
                iconTint = Color(0xFF34D399),
                onClick = onNavigateToMarketplace
            ),
            MenuItemDef(
                id = "live",
                title = "Live Studio",
                subtitle = "Real-time broadcasting",
                icon = Icons.Default.Videocam,
                iconTint = Color(0xFFFB7185),
                badge = "Live",
                isLiveBadge = true,
                onClick = onNavigateToLive
            ),
            MenuItemDef(
                id = "saved",
                title = "Saved Posts",
                subtitle = "Bookmarked items",
                icon = Icons.Default.Bookmark,
                iconTint = Color(0xFF2DD4BF),
                onClick = onNavigateToSaved
            ),
            MenuItemDef(
                id = "events",
                title = "Events",
                subtitle = "Discover things to do",
                icon = Icons.Default.Event,
                iconTint = Color(0xFFA78BFA),
                onClick = onNavigateToEvents
            ),
            MenuItemDef(
                id = "memories",
                title = "Memories",
                subtitle = "On this day in the past",
                icon = Icons.Default.History,
                iconTint = Color(0xFF22D3EE),
                onClick = onNavigateToMemories
            ),
            MenuItemDef(
                id = "jobs",
                title = "Jobs & Careers",
                subtitle = "Hiring & recruitment ATS",
                icon = Icons.Default.Work,
                iconTint = Color(0xFF0284C7),
                badge = "New",
                onClick = onNavigateToJobs
            ),
            MenuItemDef(
                id = "learning",
                title = "Learning & Courses",
                subtitle = "Skills, certifications & LMS",
                icon = androidx.compose.material.icons.Icons.Default.School,
                iconTint = Color(0xFF6366F1),
                badge = "New",
                onClick = onNavigateToLearning
            )
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Menu",
                        fontSize = 24.sp,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                },
                actions = {
                    IconButton(onClick = onNavigateToSearch) {
                        Icon(
                            imageVector = Icons.Default.Search,
                            contentDescription = "Search",
                            tint = MaterialTheme.colorScheme.onSurface
                        )
                    }
                    IconButton(onClick = onNavigateToSettings) {
                        Icon(
                            imageVector = Icons.Default.Settings,
                            contentDescription = "Settings",
                            tint = MaterialTheme.colorScheme.onSurface
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        },
        modifier = modifier
    ) { padding ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .background(MaterialTheme.colorScheme.background),
            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 12.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            if (state.isLoading) {
                item {
                    LinearProgressIndicator(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clip(RoundedCornerShape(2.dp)),
                        color = ConnectlyBlue
                    )
                }
            }

            // 1. User Profile Card
            item {
                Card(
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable(onClick = onNavigateToProfile)
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(16.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(14.dp)
                    ) {
                        Box {
                            ConnectlyAvatar(
                                imageUrl = state.userAvatar,
                                name = state.userName,
                                size = 56.dp,
                                isOnline = true,
                                isVerified = state.isVerified
                            )
                        }

                        Column(modifier = Modifier.weight(1f)) {
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(4.dp)
                            ) {
                                Text(
                                    text = state.userName,
                                    fontSize = 17.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = MaterialTheme.colorScheme.onSurface,
                                    maxLines = 1,
                                    overflow = TextOverflow.Ellipsis
                                )
                                if (state.isVerified) {
                                    Icon(
                                        imageVector = Icons.Default.CheckCircle,
                                        contentDescription = "Verified",
                                        tint = ConnectlyBlue,
                                        modifier = Modifier.size(16.dp)
                                    )
                                }
                            }
                            Text(
                                text = "@${state.userUsername}",
                                fontSize = 13.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                                maxLines = 1,
                                overflow = TextOverflow.Ellipsis
                            )
                            Spacer(modifier = Modifier.height(2.dp))
                            Row(
                                verticalAlignment = Alignment.CenterVertically,
                                horizontalArrangement = Arrangement.spacedBy(4.dp)
                            ) {
                                Text(
                                    text = "View profile",
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.SemiBold,
                                    color = ConnectlyBlue
                                )
                                Icon(
                                    imageVector = Icons.AutoMirrored.Filled.ArrowForwardIos,
                                    contentDescription = null,
                                    tint = ConnectlyBlue,
                                    modifier = Modifier.size(10.dp)
                                )
                            }
                        }
                    }
                }
            }

            // 2. Main 10 Services (2-Column Grid of Cards)
            item {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    primaryMenuItems.chunked(2).forEach { rowItems ->
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            rowItems.forEach { item ->
                                Card(
                                    shape = RoundedCornerShape(14.dp),
                                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                                    elevation = CardDefaults.cardElevation(defaultElevation = 0.5.dp),
                                    modifier = Modifier
                                        .weight(1f)
                                        .clickable(onClick = item.onClick)
                                ) {
                                    Column(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(14.dp)
                                    ) {
                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.SpaceBetween,
                                            verticalAlignment = Alignment.CenterVertically
                                        ) {
                                            Box(
                                                modifier = Modifier
                                                    .size(40.dp)
                                                    .clip(RoundedCornerShape(10.dp))
                                                    .background(item.iconTint.copy(alpha = 0.12f)),
                                                contentAlignment = Alignment.Center
                                            ) {
                                                Icon(
                                                    imageVector = item.icon,
                                                    contentDescription = item.title,
                                                    tint = item.iconTint,
                                                    modifier = Modifier.size(22.dp)
                                                )
                                            }

                                            // Badge pill
                                            if (item.badge != null) {
                                                Surface(
                                                    shape = RoundedCornerShape(12.dp),
                                                    color = if (item.isLiveBadge) Color(0xFFE11D48) else Color(0xFF7C3AED)
                                                ) {
                                                    Row(
                                                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                                        verticalAlignment = Alignment.CenterVertically,
                                                        horizontalArrangement = Arrangement.spacedBy(3.dp)
                                                    ) {
                                                        if (item.isLiveBadge) {
                                                            Box(
                                                                modifier = Modifier
                                                                    .size(5.dp)
                                                                    .clip(CircleShape)
                                                                    .background(Color.White)
                                                            )
                                                        }
                                                        Text(
                                                            text = item.badge,
                                                            fontSize = 10.sp,
                                                            fontWeight = FontWeight.ExtraBold,
                                                            color = Color.White
                                                        )
                                                    }
                                                }
                                            }
                                        }

                                        Spacer(modifier = Modifier.height(10.dp))

                                        Text(
                                            text = item.title,
                                            fontSize = 14.sp,
                                            fontWeight = FontWeight.Bold,
                                            color = MaterialTheme.colorScheme.onSurface,
                                            maxLines = 1,
                                            overflow = TextOverflow.Ellipsis
                                        )
                                        Text(
                                            text = item.subtitle,
                                            fontSize = 11.sp,
                                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                                            maxLines = 1,
                                            overflow = TextOverflow.Ellipsis
                                        )
                                    }
                                }
                            }

                            // If row has only 1 item, fill space
                            if (rowItems.size == 1) {
                                Spacer(modifier = Modifier.weight(1f))
                            }
                        }
                    }
                }
            }

            // 3. SETTINGS & SAFETY Section
            item {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text(
                        text = "SETTINGS & SAFETY",
                        fontSize = 12.sp,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        modifier = Modifier.padding(start = 4.dp, top = 6.dp)
                    )

                    Card(
                        shape = RoundedCornerShape(14.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 0.5.dp),
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable(onClick = onNavigateToSettings)
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(14.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(14.dp)
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(38.dp)
                                    .clip(RoundedCornerShape(10.dp))
                                    .background(MaterialTheme.colorScheme.surfaceVariant),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.Settings,
                                    contentDescription = "Settings & Privacy",
                                    tint = MaterialTheme.colorScheme.onSurfaceVariant,
                                    modifier = Modifier.size(20.dp)
                                )
                            }
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = "Settings & Privacy",
                                    fontSize = 14.sp,
                                    fontWeight = FontWeight.SemiBold,
                                    color = MaterialTheme.colorScheme.onSurface
                                )
                                Text(
                                    text = "Account, privacy, security, passwords",
                                    fontSize = 12.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                            Icon(
                                imageVector = Icons.AutoMirrored.Filled.ArrowForwardIos,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f),
                                modifier = Modifier.size(14.dp)
                            )
                        }
                    }

                    Card(
                        shape = RoundedCornerShape(14.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 0.5.dp),
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable(onClick = onNavigateToHelpSupport)
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(14.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.spacedBy(14.dp)
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(38.dp)
                                    .clip(RoundedCornerShape(10.dp))
                                    .background(MaterialTheme.colorScheme.surfaceVariant),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.AutoMirrored.Filled.Help,
                                    contentDescription = "Help & Guidelines",
                                    tint = MaterialTheme.colorScheme.onSurfaceVariant,
                                    modifier = Modifier.size(20.dp)
                                )
                            }
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = "Help & Guidelines",
                                    fontSize = 14.sp,
                                    fontWeight = FontWeight.SemiBold,
                                    color = MaterialTheme.colorScheme.onSurface
                                )
                                Text(
                                    text = "Community guidelines, terms & safety",
                                    fontSize = 12.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                            Icon(
                                imageVector = Icons.AutoMirrored.Filled.ArrowForwardIos,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.6f),
                                modifier = Modifier.size(14.dp)
                            )
                        }
                    }
                }
            }

            // 4. Log Out Button (Red-styled, exactly matching reference design)
            item {
                Spacer(modifier = Modifier.height(4.dp))
                Card(
                    shape = RoundedCornerShape(14.dp),
                    colors = CardDefaults.cardColors(containerColor = Color(0xFFFEE2E2)),
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable { showLogoutDialog = true }
                ) {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 14.dp),
                        horizontalArrangement = Arrangement.Center,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.Logout,
                            contentDescription = "Log Out",
                            tint = Color(0xFFDC2626),
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(
                            text = "Log Out",
                            fontSize = 15.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color(0xFFDC2626)
                        )
                    }
                }
                Spacer(modifier = Modifier.height(16.dp))
            }
        }
    }

    // Confirmation Logout Dialog
    if (showLogoutDialog) {
        AlertDialog(
            onDismissRequest = { showLogoutDialog = false },
            title = {
                Text(
                    text = "Log Out of Connectly?",
                    fontWeight = FontWeight.Bold
                )
            },
            text = {
                Text("Are you sure you want to log out of your account on this device?")
            },
            confirmButton = {
                Button(
                    onClick = {
                        showLogoutDialog = false
                        viewModel.confirmLogout()
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFFDC2626))
                ) {
                    Text("Log Out", color = Color.White)
                }
            },
            dismissButton = {
                TextButton(onClick = { showLogoutDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }
}
