package com.connectly.android.feature.profile.presentation.feedpreferences

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
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
import com.connectly.android.core.designsystem.component.ConnectlyButton
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FeedPreferencesScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: FeedPreferencesViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val scrollState = rememberScrollState()

    var showClearHiddenDialog by remember { mutableStateOf(false) }
    var showClearSnoozeDialog by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Feed & Content Preferences",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Back"
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        },
        bottomBar = {
            Surface(
                color = MaterialTheme.colorScheme.surface,
                tonalElevation = 8.dp,
                shadowElevation = 8.dp
            ) {
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 12.dp)
                ) {
                    ConnectlyButton(
                        text = if (state.isSaving) "Saving Changes..." else "Save Preferences",
                        onClick = { viewModel.handleIntent(FeedPreferencesIntent.SaveAllPreferences) },
                        enabled = !state.isSaving,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            }
        },
        modifier = modifier
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
                .background(MaterialTheme.colorScheme.background)
                .verticalScroll(scrollState)
                .padding(horizontal = 16.dp, vertical = 12.dp),
            verticalArrangement = Arrangement.spacedBy(20.dp)
        ) {
            // Success Feedback
            state.successMessage?.let { msg ->
                Card(
                    colors = CardDefaults.cardColors(containerColor = ConnectlyGreen.copy(alpha = 0.12f)),
                    shape = RoundedCornerShape(12.dp),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier.padding(14.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(10.dp)
                    ) {
                        Icon(
                            imageVector = Icons.Default.CheckCircle,
                            contentDescription = null,
                            tint = ConnectlyGreen
                        )
                        Text(
                            text = msg,
                            color = ConnectlyGreen,
                            style = MaterialTheme.typography.bodyMedium,
                            fontWeight = FontWeight.Medium,
                            modifier = Modifier.weight(1f)
                        )
                        IconButton(
                            onClick = { viewModel.handleIntent(FeedPreferencesIntent.DismissFeedback) },
                            modifier = Modifier.size(24.dp)
                        ) {
                            Icon(
                                imageVector = Icons.Default.Close,
                                contentDescription = "Dismiss",
                                tint = ConnectlyGreen,
                                modifier = Modifier.size(16.dp)
                            )
                        }
                    }
                }
            }

            // Section 1: Feed & Post Delivery
            SectionCard(
                title = "Feed & Content Delivery",
                subtitle = "Manage who sees your updates and filter feed content"
            ) {
                Text(
                    text = "Default Post Audience",
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Text(
                    text = "This will be the default audience selected when you create new posts.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Spacer(modifier = Modifier.height(4.dp))

                AudienceOption(
                    title = "Public",
                    description = "Anyone on or off Connectly can see your posts",
                    icon = Icons.Default.Public,
                    selected = state.defaultPostAudience == "public",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.DefaultPostAudienceChanged("public")) }
                )
                AudienceOption(
                    title = "Friends",
                    description = "Only your confirmed friends can see your posts",
                    icon = Icons.Default.People,
                    selected = state.defaultPostAudience == "friends",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.DefaultPostAudienceChanged("friends")) }
                )
                AudienceOption(
                    title = "Only Me",
                    description = "Private posts visible only to you",
                    icon = Icons.Default.Lock,
                    selected = state.defaultPostAudience == "only_me",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.DefaultPostAudienceChanged("only_me")) }
                )

                HorizontalDivider(modifier = Modifier.padding(vertical = 12.dp))

                Text(
                    text = "Sensitive Content Control",
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Text(
                    text = "Control how much sensitive or graphic content is displayed in your feed.",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
                Spacer(modifier = Modifier.height(6.dp))

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    SensitiveLevelChip(
                        label = "Standard",
                        description = "Warning label",
                        selected = state.sensitiveContentFilter == "standard",
                        onClick = { viewModel.handleIntent(FeedPreferencesIntent.SensitiveContentFilterChanged("standard")) },
                        modifier = Modifier.weight(1f)
                    )
                    SensitiveLevelChip(
                        label = "Less",
                        description = "Strict filter",
                        selected = state.sensitiveContentFilter == "less",
                        onClick = { viewModel.handleIntent(FeedPreferencesIntent.SensitiveContentFilterChanged("less")) },
                        modifier = Modifier.weight(1f)
                    )
                    SensitiveLevelChip(
                        label = "More",
                        description = "Minimal filter",
                        selected = state.sensitiveContentFilter == "more",
                        onClick = { viewModel.handleIntent(FeedPreferencesIntent.SensitiveContentFilterChanged("more")) },
                        modifier = Modifier.weight(1f)
                    )
                }

                HorizontalDivider(modifier = Modifier.padding(vertical = 12.dp))

                // Hidden & Snoozed Management
                PreferenceSummaryRow(
                    title = "Favorites",
                    subtitle = "Prioritize posts from these accounts at top of feed",
                    value = "${state.favoritesCount} accounts",
                    actionText = null,
                    onAction = {}
                )
                PreferenceSummaryRow(
                    title = "Snoozed Accounts",
                    subtitle = "Accounts paused from appearing in feed for 30 days",
                    value = "${state.snoozedAccountsCount} accounts",
                    actionText = if (state.snoozedAccountsCount > 0) "Reset" else null,
                    onAction = { showClearSnoozeDialog = true }
                )
                PreferenceSummaryRow(
                    title = "Hidden Posts",
                    subtitle = "Individual posts you asked Connectly to hide",
                    value = "${state.hiddenPostsCount} posts",
                    actionText = if (state.hiddenPostsCount > 0) "Reset" else null,
                    onAction = { showClearHiddenDialog = true }
                )
            }

            // Section 2: Video & Reels Playback
            SectionCard(
                title = "Video & Reels Preferences",
                subtitle = "Manage video streaming quality and data usage"
            ) {
                Text(
                    text = "Autoplay Videos",
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Spacer(modifier = Modifier.height(4.dp))

                RadioPreferenceItem(
                    title = "On Mobile Data and Wi-Fi",
                    subtitle = "Videos start playing automatically on any connection",
                    selected = state.videoAutoplay == "always",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.VideoAutoplayChanged("always")) }
                )
                RadioPreferenceItem(
                    title = "On Wi-Fi Only",
                    subtitle = "Never autoplay on cellular data to preserve bandwidth",
                    selected = state.videoAutoplay == "wifi_only",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.VideoAutoplayChanged("wifi_only")) }
                )
                RadioPreferenceItem(
                    title = "Never Autoplay Videos",
                    subtitle = "Tap to play videos manually",
                    selected = state.videoAutoplay == "never",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.VideoAutoplayChanged("never")) }
                )

                HorizontalDivider(modifier = Modifier.padding(vertical = 12.dp))

                Text(
                    text = "Video Playback Quality",
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Spacer(modifier = Modifier.height(4.dp))

                RadioPreferenceItem(
                    title = "Auto (Recommended)",
                    subtitle = "Adjusts automatically based on network speed",
                    selected = state.videoQuality == "auto",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.VideoQualityChanged("auto")) }
                )
                RadioPreferenceItem(
                    title = "High Quality (1080p)",
                    subtitle = "Streams in highest available resolution",
                    selected = state.videoQuality == "high",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.VideoQualityChanged("high")) }
                )
                RadioPreferenceItem(
                    title = "Data Saver Quality",
                    subtitle = "Uses lowest resolution to save cellular data",
                    selected = state.videoQuality == "data_saver",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.VideoQualityChanged("data_saver")) }
                )

                HorizontalDivider(modifier = Modifier.padding(vertical = 12.dp))

                // Toggles
                SwitchRow(
                    title = "Data Saver Mode",
                    subtitle = "Reduces video bandwidth by up to 40% and disables video pre-fetching",
                    checked = state.dataSaverEnabled,
                    onCheckedChange = { viewModel.handleIntent(FeedPreferencesIntent.DataSaverToggled(it)) }
                )

                SwitchRow(
                    title = "Reels Autoplay Next",
                    subtitle = "Automatically scrolls to the next reel when playback ends",
                    checked = state.reelsAutoplayNext,
                    onCheckedChange = { viewModel.handleIntent(FeedPreferencesIntent.ReelsAutoplayNextToggled(it)) }
                )
            }

            // Section 3: Stories & Ephemeral Content
            SectionCard(
                title = "Stories Preferences",
                subtitle = "Manage 24-hour ephemeral stories visibility and replies"
            ) {
                Text(
                    text = "Who Can See Your Stories",
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Spacer(modifier = Modifier.height(4.dp))

                RadioPreferenceItem(
                    title = "Friends Only (Recommended)",
                    subtitle = "Visible to your confirmed Connectly friends",
                    selected = state.storyVisibility == "friends",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.StoryVisibilityChanged("friends")) }
                )
                RadioPreferenceItem(
                    title = "Public",
                    subtitle = "Visible to anyone with your profile link",
                    selected = state.storyVisibility == "public",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.StoryVisibilityChanged("public")) }
                )

                HorizontalDivider(modifier = Modifier.padding(vertical = 12.dp))

                Text(
                    text = "Story Reply Permissions",
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )
                Spacer(modifier = Modifier.height(4.dp))

                RadioPreferenceItem(
                    title = "Everyone",
                    subtitle = "Anyone who can view your story can send replies",
                    selected = state.storyReplyPermission == "everyone",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.StoryReplyPermissionChanged("everyone")) }
                )
                RadioPreferenceItem(
                    title = "Friends Only",
                    subtitle = "Only friends can reply with messages and reactions",
                    selected = state.storyReplyPermission == "friends",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.StoryReplyPermissionChanged("friends")) }
                )
                RadioPreferenceItem(
                    title = "Off",
                    subtitle = "Disable direct replies and reaction replies on stories",
                    selected = state.storyReplyPermission == "off",
                    onSelect = { viewModel.handleIntent(FeedPreferencesIntent.StoryReplyPermissionChanged("off")) }
                )

                HorizontalDivider(modifier = Modifier.padding(vertical = 12.dp))

                SwitchRow(
                    title = "Save Stories to Archive",
                    subtitle = "Automatically preserve your expired stories in your private archive",
                    checked = state.saveStoriesToArchive,
                    onCheckedChange = { viewModel.handleIntent(FeedPreferencesIntent.SaveStoriesToArchiveToggled(it)) }
                )
            }

            Spacer(modifier = Modifier.height(20.dp))
        }
    }

    // Confirmation Dialogs
    if (showClearHiddenDialog) {
        AlertDialog(
            onDismissRequest = { showClearHiddenDialog = false },
            title = { Text("Reset Hidden Posts?") },
            text = { Text("This will unhide all previously hidden posts, allowing them to appear in your feed according to the standard ranking.") },
            confirmButton = {
                TextButton(
                    onClick = {
                        viewModel.handleIntent(FeedPreferencesIntent.ClearHiddenPosts)
                        showClearHiddenDialog = false
                    }
                ) {
                    Text("Reset", color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
                }
            },
            dismissButton = {
                TextButton(onClick = { showClearHiddenDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    if (showClearSnoozeDialog) {
        AlertDialog(
            onDismissRequest = { showClearSnoozeDialog = false },
            title = { Text("Reset Snoozed Accounts?") },
            text = { Text("This will remove all 30-day snooze timers and resume showing posts from previously snoozed accounts.") },
            confirmButton = {
                TextButton(
                    onClick = {
                        viewModel.handleIntent(FeedPreferencesIntent.ClearSnoozedAccounts)
                        showClearSnoozeDialog = false
                    }
                ) {
                    Text("Reset", color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.Bold)
                }
            },
            dismissButton = {
                TextButton(onClick = { showClearSnoozeDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }
}

@Composable
private fun SectionCard(
    title: String,
    subtitle: String,
    content: @Composable ColumnScope.() -> Unit
) {
    Card(
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth()
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            Text(
                text = title,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )
            Text(
                text = subtitle,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Spacer(modifier = Modifier.height(14.dp))
            content()
        }
    }
}

@Composable
private fun AudienceOption(
    title: String,
    description: String,
    icon: ImageVector,
    selected: Boolean,
    onSelect: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(12.dp))
            .clickable(onClick = onSelect)
            .background(
                if (selected) ConnectlyBlue.copy(alpha = 0.08f) else Color.Transparent
            )
            .border(
                width = if (selected) 1.5.dp else 0.dp,
                color = if (selected) ConnectlyBlue else Color.Transparent,
                shape = RoundedCornerShape(12.dp)
            )
            .padding(12.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(
            imageVector = icon,
            contentDescription = null,
            tint = if (selected) ConnectlyBlue else MaterialTheme.colorScheme.onSurfaceVariant,
            modifier = Modifier.size(24.dp)
        )
        Spacer(modifier = Modifier.width(12.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = title,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = if (selected) FontWeight.Bold else FontWeight.Medium,
                color = if (selected) ConnectlyBlue else MaterialTheme.colorScheme.onSurface
            )
            Text(
                text = description,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        RadioButton(
            selected = selected,
            onClick = onSelect,
            colors = RadioButtonDefaults.colors(selectedColor = ConnectlyBlue)
        )
    }
}

@Composable
private fun RadioPreferenceItem(
    title: String,
    subtitle: String,
    selected: Boolean,
    onSelect: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(10.dp))
            .clickable(onClick = onSelect)
            .padding(vertical = 8.dp, horizontal = 4.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = title,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = if (selected) FontWeight.SemiBold else FontWeight.Normal,
                color = MaterialTheme.colorScheme.onSurface
            )
            Text(
                text = subtitle,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        RadioButton(
            selected = selected,
            onClick = onSelect,
            colors = RadioButtonDefaults.colors(selectedColor = ConnectlyBlue)
        )
    }
}

@Composable
private fun SensitiveLevelChip(
    label: String,
    description: String,
    selected: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Box(
        modifier = modifier
            .clip(RoundedCornerShape(10.dp))
            .clickable(onClick = onClick)
            .background(
                if (selected) ConnectlyBlue else MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
            )
            .padding(vertical = 10.dp, horizontal = 8.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            Text(
                text = label,
                style = MaterialTheme.typography.bodySmall,
                fontWeight = FontWeight.Bold,
                color = if (selected) Color.White else MaterialTheme.colorScheme.onSurface
            )
            Text(
                text = description,
                style = MaterialTheme.typography.labelSmall,
                fontSize = 10.sp,
                color = if (selected) Color.White.copy(alpha = 0.85f) else MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
    }
}

@Composable
private fun SwitchRow(
    title: String,
    subtitle: String,
    checked: Boolean,
    onCheckedChange: (Boolean) -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = title,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.Medium,
                color = MaterialTheme.colorScheme.onSurface
            )
            Text(
                text = subtitle,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        Spacer(modifier = Modifier.width(12.dp))
        Switch(
            checked = checked,
            onCheckedChange = onCheckedChange,
            colors = SwitchDefaults.colors(checkedThumbColor = ConnectlyBlue)
        )
    }
}

@Composable
private fun PreferenceSummaryRow(
    title: String,
    subtitle: String,
    value: String,
    actionText: String?,
    onAction: () -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(
                text = title,
                style = MaterialTheme.typography.bodyMedium,
                fontWeight = FontWeight.Medium,
                color = MaterialTheme.colorScheme.onSurface
            )
            Text(
                text = subtitle,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
        }
        Spacer(modifier = Modifier.width(8.dp))
        Text(
            text = value,
            style = MaterialTheme.typography.bodySmall,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
        if (actionText != null) {
            Spacer(modifier = Modifier.width(6.dp))
            TextButton(
                onClick = onAction,
                contentPadding = PaddingValues(horizontal = 8.dp, vertical = 0.dp)
            ) {
                Text(
                    text = actionText,
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.primary,
                    fontWeight = FontWeight.Bold
                )
            }
        }
    }
}
