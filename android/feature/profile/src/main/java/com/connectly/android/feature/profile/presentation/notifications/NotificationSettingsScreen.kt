package com.connectly.android.feature.profile.presentation.notifications

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NotificationSettingsScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: NotificationSettingsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(state.successMessage) {
        state.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(NotificationSettingsIntent.DismissMessages)
        }
    }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(NotificationSettingsIntent.DismissMessages)
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { Text("Notifications", fontWeight = FontWeight.Bold, fontSize = 18.sp) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    if (state.isSaving) {
                        CircularProgressIndicator(
                            modifier = Modifier
                                .size(24.dp)
                                .padding(end = 16.dp),
                            color = ConnectlyBlue,
                            strokeWidth = 2.dp
                        )
                    } else {
                        TextButton(onClick = { viewModel.handleIntent(NotificationSettingsIntent.SaveAllSettings) }) {
                            Text("Save", fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                        }
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        modifier = modifier
    ) { paddingValues ->
        if (state.isLoading) {
            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues),
                contentAlignment = Alignment.Center
            ) {
                CircularProgressIndicator(color = ConnectlyBlue)
            }
        } else {
            LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(paddingValues)
                    .background(BackgroundLight),
                contentPadding = PaddingValues(horizontal = 16.dp, vertical = 14.dp),
                verticalArrangement = Arrangement.spacedBy(14.dp)
            ) {
                // ==========================================
                // SECTION 1: MASTER CONTROLS & QUIET HOURS
                // ==========================================
                item {
                    SectionHeader(title = "GENERAL CONTROLS & DND", icon = Icons.Default.Tune)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            // Master Toggle
                            SwitchRow(
                                title = "Allow Notifications",
                                subtitle = "Master switch for all push and device notifications.",
                                checked = state.masterNotificationsEnabled,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.MasterToggled(it)) },
                                highlight = true
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Do Not Disturb
                            SwitchRow(
                                title = "Do Not Disturb (DND)",
                                subtitle = "Temporarily silence all alerts without changing individual preferences.",
                                checked = state.doNotDisturb,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.DoNotDisturbToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Quiet Hours
                            SwitchRow(
                                title = "Quiet Hours",
                                subtitle = if (state.quietHoursEnabled) "Mutes notifications between ${state.quietHoursStart} and ${state.quietHoursEnd}." else "Automatically mute alerts during your sleeping hours.",
                                checked = state.quietHoursEnabled,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.QuietHoursToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Sound & Vibration
                            SwitchRow(
                                title = "Notification Sounds",
                                subtitle = "Play audible chime on incoming alerts and messages.",
                                checked = state.soundEnabled,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.SoundToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Vibration",
                                subtitle = "Haptic feedback on alerts when device is in ring or vibrate mode.",
                                checked = state.vibrationEnabled,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.VibrationToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Lock Screen Privacy
                            OptionSelector(
                                title = "Lock Screen Privacy",
                                subtitle = "How alerts are presented on your Android device lock screen.",
                                currentValue = state.lockScreenPrivacy,
                                options = listOf(
                                    "show_all" to "Show All Content",
                                    "hide_sensitive" to "Hide Sensitive Content",
                                    "dont_show" to "Don't Show Notifications"
                                ),
                                onSelect = { viewModel.handleIntent(NotificationSettingsIntent.LockScreenPrivacyChanged(it)) }
                            )
                        }
                    }
                }

                // ==========================================
                // SECTION 2: NOTIFICATION CHANNELS
                // ==========================================
                item {
                    SectionHeader(title = "NOTIFICATION CHANNELS", icon = Icons.Default.Campaign)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            // Push
                            SwitchRow(
                                title = "Push Notifications",
                                subtitle = "Direct delivery to your device notification tray via FCM.",
                                checked = state.pushNotifications,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.PushToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // In-App
                            SwitchRow(
                                title = "In-App Notifications",
                                subtitle = "Banners and badges that appear while browsing Connectly.",
                                checked = state.inAppNotifications,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.InAppToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Email
                            SwitchRow(
                                title = "Email Notifications",
                                subtitle = "Periodic summaries and account activity sent to your email.",
                                checked = state.emailNotifications,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.EmailToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // SMS
                            SwitchRow(
                                title = "SMS Text Alerts",
                                subtitle = "Important notifications delivered via SMS text messages.",
                                checked = state.smsNotifications,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.SmsToggled(it)) }
                            )
                        }
                    }
                }

                // ==========================================
                // SECTION 3: SOCIAL INTERACTIONS
                // ==========================================
                item {
                    SectionHeader(title = "SOCIAL INTERACTIONS", icon = Icons.Default.ThumbUp)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            SwitchRow(
                                title = "Likes and Reactions",
                                subtitle = "When someone reacts to your posts, comments, or media.",
                                checked = state.likeAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.LikeAlertsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Comments and Replies",
                                subtitle = "When someone comments on your post or replies to your comment.",
                                checked = state.commentAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.CommentAlertsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Friend Requests",
                                subtitle = "When someone sends you a new friend request.",
                                checked = state.friendRequestAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.FriendRequestAlertsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Friend Suggestions & Accepted",
                                subtitle = "When a friend request is accepted or new suggestions are available.",
                                checked = state.friendAcceptedAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.FriendAcceptedAlertsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "New Followers",
                                subtitle = "When another person begins following your profile.",
                                checked = state.followerAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.FollowerAlertsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Direct Messages",
                                subtitle = "New messages and chat conversation alerts.",
                                checked = state.messageAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.MessageAlertsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Mentions and Tags",
                                subtitle = "When you are mentioned in a post, comment, or tagged in a photo.",
                                checked = state.mentionAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.MentionAlertsToggled(it)) }
                            )
                        }
                    }
                }

                // ==========================================
                // SECTION 4: COMMUNITY & BROADCASTS
                // ==========================================
                item {
                    SectionHeader(title = "COMMUNITY & BROADCASTS", icon = Icons.Default.Groups)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            SwitchRow(
                                title = "Group Activity",
                                subtitle = "Posts, announcements, and polls from your joined groups.",
                                checked = state.groupAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.GroupAlertsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Page Activity",
                                subtitle = "Updates from pages and public figures you follow.",
                                checked = state.pageAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.PageAlertsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Events",
                                subtitle = "Invitations and calendar reminders for upcoming events.",
                                checked = state.eventAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.EventAlertsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Live Videos",
                                subtitle = "Alerts when creators or friends you follow start live streaming.",
                                checked = state.liveAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.LiveAlertsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Stories & Reels",
                                subtitle = "New stories and viral reels from your favorite creators.",
                                checked = state.storyAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.StoryAlertsToggled(it)) }
                            )
                        }
                    }
                }

                // ==========================================
                // SECTION 5: SYSTEM & SECURITY
                // ==========================================
                item {
                    SectionHeader(title = "SYSTEM & SECURITY ALERTS", icon = Icons.Default.Security)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            // Security Alerts
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(vertical = 10.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Column(modifier = Modifier.weight(1f)) {
                                    Row(verticalAlignment = Alignment.CenterVertically) {
                                        Text(
                                            text = "Security Alerts",
                                            fontWeight = FontWeight.SemiBold,
                                            fontSize = 14.sp,
                                            color = TextPrimaryLight
                                        )
                                        Spacer(modifier = Modifier.width(6.dp))
                                        Surface(
                                            shape = RoundedCornerShape(4.dp),
                                            color = ConnectlyGreen.copy(alpha = 0.15f)
                                        ) {
                                            Text(
                                                text = "Required",
                                                color = ConnectlyGreen,
                                                fontSize = 10.sp,
                                                fontWeight = FontWeight.Bold,
                                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                            )
                                        }
                                    }
                                    Spacer(modifier = Modifier.height(2.dp))
                                    Text(
                                        text = "Critical warnings about unrecognized logins, 2FA attempts, and password changes. Cannot be silenced by marketing preferences.",
                                        fontSize = 12.sp,
                                        color = TextSecondaryLight,
                                        lineHeight = 16.sp
                                    )
                                }
                                Spacer(modifier = Modifier.width(12.dp))
                                Switch(
                                    checked = state.securityAlerts,
                                    onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.SecurityAlertsToggled(it)) },
                                    colors = SwitchDefaults.colors(
                                        checkedThumbColor = Color.White,
                                        checkedTrackColor = ConnectlyBlue
                                    )
                                )
                            }

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Product Updates",
                                subtitle = "News about new features and major app enhancements.",
                                checked = state.productAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.ProductAlertsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            SwitchRow(
                                title = "Marketing & Promotional",
                                subtitle = "Promotions, surveys, and special partner announcements.",
                                checked = state.marketingAlerts,
                                onCheckedChange = { viewModel.handleIntent(NotificationSettingsIntent.MarketingAlertsToggled(it)) }
                            )
                        }
                    }
                }

                item {
                    Spacer(modifier = Modifier.height(30.dp))
                }
            }
        }
    }
}

@Composable
private fun SectionHeader(title: String, icon: ImageVector) {
    Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.padding(horizontal = 4.dp, vertical = 2.dp)) {
        Icon(icon, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(16.dp))
        Spacer(modifier = Modifier.width(6.dp))
        Text(
            text = title,
            fontWeight = FontWeight.Bold,
            fontSize = 12.sp,
            color = TextSecondaryLight,
            letterSpacing = 0.5.sp
        )
    }
}

@Composable
private fun SwitchRow(
    title: String,
    subtitle: String,
    checked: Boolean,
    onCheckedChange: (Boolean) -> Unit,
    highlight: Boolean = false
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onCheckedChange(!checked) }
            .padding(vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = title,
                fontWeight = if (highlight) FontWeight.Bold else FontWeight.SemiBold,
                fontSize = if (highlight) 15.sp else 14.sp,
                color = if (highlight) ConnectlyBlue else TextPrimaryLight
            )
            Spacer(modifier = Modifier.height(2.dp))
            Text(text = subtitle, fontSize = 12.sp, color = TextSecondaryLight, lineHeight = 16.sp)
        }
        Spacer(modifier = Modifier.width(12.dp))
        Switch(
            checked = checked,
            onCheckedChange = onCheckedChange,
            colors = SwitchDefaults.colors(
                checkedThumbColor = Color.White,
                checkedTrackColor = ConnectlyBlue
            )
        )
    }
}

@Composable
private fun OptionSelector(
    title: String,
    subtitle: String,
    currentValue: String,
    options: List<Pair<String, String>>,
    onSelect: (String) -> Unit
) {
    var expanded by remember { mutableStateOf(false) }
    val displayLabel = options.firstOrNull { it.first == currentValue }?.second ?: currentValue

    Column(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { expanded = !expanded }
            .padding(vertical = 10.dp)
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text(text = title, fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = TextPrimaryLight)
                Spacer(modifier = Modifier.height(2.dp))
                Text(text = subtitle, fontSize = 12.sp, color = TextSecondaryLight, lineHeight = 16.sp)
            }
            Spacer(modifier = Modifier.width(8.dp))
            Surface(
                shape = RoundedCornerShape(8.dp),
                color = ConnectlyBlue.copy(alpha = 0.10f)
            ) {
                Row(
                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(text = displayLabel, fontSize = 12.sp, fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                    Spacer(modifier = Modifier.width(4.dp))
                    Icon(
                        imageVector = if (expanded) Icons.Default.KeyboardArrowUp else Icons.Default.KeyboardArrowDown,
                        contentDescription = null,
                        tint = ConnectlyBlue,
                        modifier = Modifier.size(16.dp)
                    )
                }
            }
        }

        if (expanded) {
            Spacer(modifier = Modifier.height(8.dp))
            Surface(
                shape = RoundedCornerShape(12.dp),
                color = BackgroundLight,
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(modifier = Modifier.padding(8.dp)) {
                    options.forEach { (key, label) ->
                        val isSelected = key == currentValue
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .clip(RoundedCornerShape(8.dp))
                                .background(if (isSelected) ConnectlyBlue.copy(alpha = 0.15f) else Color.Transparent)
                                .clickable {
                                    onSelect(key)
                                    expanded = false
                                }
                                .padding(horizontal = 12.dp, vertical = 8.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            RadioButton(
                                selected = isSelected,
                                onClick = {
                                    onSelect(key)
                                    expanded = false
                                },
                                colors = RadioButtonDefaults.colors(selectedColor = ConnectlyBlue)
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Text(
                                text = label,
                                fontSize = 13.sp,
                                fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal,
                                color = if (isSelected) ConnectlyBlue else TextPrimaryLight
                            )
                        }
                    }
                }
            }
        }
    }
}

