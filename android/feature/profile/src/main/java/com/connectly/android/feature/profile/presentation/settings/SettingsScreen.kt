package com.connectly.android.feature.profile.presentation.settings

import android.content.Context
import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.theme.*

private data class SettingItemDef(
    val id: String,
    val title: String,
    val subtitle: String,
    val icon: ImageVector,
    val category: String,
    val value: String? = null,
    val onClick: () -> Unit
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SettingsScreen(
    onNavigateBack: () -> Unit,
    onNavigateToProfile: () -> Unit,
    onNavigateToPrivacy: () -> Unit,
    onLogoutSuccess: () -> Unit,
    onNavigateToEditProfile: () -> Unit = onNavigateToProfile,
    onNavigateToNotificationSettings: () -> Unit = {},
    onNavigateToAppearance: () -> Unit = {},
    onNavigateToLanguage: () -> Unit = {},
    onNavigateToMessagingCalls: () -> Unit = {},
    onNavigateToFeedPreferences: () -> Unit = {},
    onNavigateToBlockedUsers: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: SettingsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val context = LocalContext.current
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(Unit) {
        viewModel.calculateCacheSize(context)
    }

    LaunchedEffect(state.isLoggedOut) {
        if (state.isLoggedOut) {
            onLogoutSuccess()
        }
    }

    LaunchedEffect(state.statusMessage) {
        state.statusMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.dismissMessages()
        }
    }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.dismissMessages()
        }
    }

    // List of all setting items with metadata for categorization and search
    val allSettings = remember(state) {
        listOf(
            SettingItemDef(
                id = "account",
                title = "Account Settings",
                subtitle = "Personal information, email, phone, verification",
                icon = Icons.Default.Person,
                category = "ACCOUNT & SECURITY",
                onClick = onNavigateToEditProfile
            ),
            SettingItemDef(
                id = "privacy",
                title = "Privacy & Security",
                subtitle = "Post audience, online status, 2FA, sessions",
                icon = Icons.Default.Lock,
                category = "ACCOUNT & SECURITY",
                onClick = onNavigateToPrivacy
            ),
            SettingItemDef(
                id = "blocking",
                title = "Blocked & Restricted Users",
                subtitle = "Manage blocked and restricted accounts",
                icon = Icons.Default.Block,
                category = "ACCOUNT & SECURITY",
                value = if (state.blockedCount > 0) "${state.blockedCount} blocked" else "None",
                onClick = onNavigateToBlockedUsers
            ),
            SettingItemDef(
                id = "notifications",
                title = "Notifications",
                subtitle = "Push alerts, in-app sounds, quiet hours",
                icon = Icons.Default.Notifications,
                category = "PREFERENCES",
                value = "Active",
                onClick = onNavigateToNotificationSettings
            ),
            SettingItemDef(
                id = "appearance",
                title = "Appearance & Theme",
                subtitle = "Customize app theme and styling",
                icon = Icons.Default.Palette,
                category = "PREFERENCES",
                value = state.selectedTheme,
                onClick = onNavigateToAppearance
            ),
            SettingItemDef(
                id = "language",
                title = "Language & Region",
                subtitle = "Choose your preferred display language",
                icon = Icons.Default.Language,
                category = "PREFERENCES",
                value = state.selectedLanguage,
                onClick = onNavigateToLanguage
            ),
            SettingItemDef(
                id = "feed",
                title = "Feed & Content Preferences",
                subtitle = "Video autoplay, sensitive content controls",
                icon = Icons.Default.DynamicFeed,
                category = "PREFERENCES",
                onClick = onNavigateToFeedPreferences
            ),
            SettingItemDef(
                id = "messaging",
                title = "Messaging & Calls",
                subtitle = "Active status, message privacy, call permissions",
                icon = Icons.AutoMirrored.Filled.Chat,
                category = "PREFERENCES",
                value = state.whoCanMessage,
                onClick = onNavigateToMessagingCalls
            ),
            SettingItemDef(
                id = "storage",
                title = "Data & Storage",
                subtitle = "Cache size, data saver, media download",
                icon = Icons.Default.Storage,
                category = "DATA & ACCESSIBILITY",
                value = state.cacheSizeFormatted,
                onClick = { viewModel.openDialog(SettingsDialogType.DATA_STORAGE) }
            ),
            SettingItemDef(
                id = "accessibility",
                title = "Accessibility",
                subtitle = "Larger text, high contrast, reduce motion",
                icon = Icons.Default.Accessibility,
                category = "DATA & ACCESSIBILITY",
                onClick = { viewModel.openDialog(SettingsDialogType.ACCESSIBILITY) }
            ),
            SettingItemDef(
                id = "help",
                title = "Help & Support",
                subtitle = "Help center, report a problem, contact support",
                icon = Icons.AutoMirrored.Filled.Help,
                category = "SUPPORT & ABOUT",
                onClick = { viewModel.openDialog(SettingsDialogType.HELP_SUPPORT) }
            ),
            SettingItemDef(
                id = "about",
                title = "About Connectly",
                subtitle = "Version, terms of service, privacy policy",
                icon = Icons.Default.Info,
                category = "SUPPORT & ABOUT",
                value = "v1.0.0",
                onClick = { viewModel.openDialog(SettingsDialogType.ABOUT_CONNECTLY) }
            )
        )
    }

    // Filtered items based on live search query
    val filteredSettings = remember(state.searchQuery, allSettings) {
        if (state.searchQuery.isBlank()) {
            allSettings
        } else {
            allSettings.filter {
                it.title.contains(state.searchQuery, ignoreCase = true) ||
                it.subtitle.contains(state.searchQuery, ignoreCase = true) ||
                it.category.contains(state.searchQuery, ignoreCase = true)
            }
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = {
                    if (state.isSearchActive) {
                        OutlinedTextField(
                            value = state.searchQuery,
                            onValueChange = { viewModel.onSearchQueryChanged(it) },
                            placeholder = { Text("Search settings...", fontSize = 14.sp) },
                            singleLine = true,
                            modifier = Modifier.fillMaxWidth().height(48.dp),
                            shape = RoundedCornerShape(24.dp),
                            colors = OutlinedTextFieldDefaults.colors(
                                focusedBorderColor = ConnectlyBlue,
                                unfocusedBorderColor = BorderLight
                            )
                        )
                    } else {
                        Text(
                            text = "Settings",
                            fontWeight = FontWeight.Bold,
                            fontSize = 20.sp,
                            color = TextPrimaryLight
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { viewModel.toggleSearchActive(!state.isSearchActive) }) {
                        Icon(
                            imageVector = if (state.isSearchActive) Icons.Default.Close else Icons.Default.Search,
                            contentDescription = if (state.isSearchActive) "Close Search" else "Search Settings"
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(BackgroundLight),
            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 12.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            // 1. Profile Summary Card
            if (!state.isSearchActive) {
                item {
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable(onClick = onNavigateToProfile),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            ConnectlyAvatar(
                                imageUrl = state.userAvatar,
                                name = state.userName,
                                size = 56.dp,
                                isVerified = state.isVerified
                            )

                            Spacer(modifier = Modifier.width(14.dp))

                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = state.userName,
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 17.sp,
                                    color = TextPrimaryLight
                                )
                                Spacer(modifier = Modifier.height(2.dp))
                                Text(
                                    text = "@${state.userUsername}",
                                    fontSize = 13.sp,
                                    color = TextSecondaryLight
                                )
                                if (state.userEmail.isNotBlank()) {
                                    Text(
                                        text = state.userEmail,
                                        fontSize = 11.sp,
                                        color = TextMutedLight
                                    )
                                }
                            }

                            Surface(
                                shape = RoundedCornerShape(12.dp),
                                color = ConnectlyBlue.copy(alpha = 0.08f)
                            ) {
                                Row(
                                    verticalAlignment = Alignment.CenterVertically,
                                    modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp)
                                ) {
                                    Text(
                                        text = "View",
                                        color = ConnectlyBlue,
                                        fontSize = 12.sp,
                                        fontWeight = FontWeight.SemiBold
                                    )
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Icon(
                                        imageVector = Icons.AutoMirrored.Filled.ArrowForwardIos,
                                        contentDescription = null,
                                        tint = ConnectlyBlue,
                                        modifier = Modifier.size(11.dp)
                                    )
                                }
                            }
                        }
                    }
                }
            }

            // 2. Settings Items Grouped or Filtered
            if (filteredSettings.isEmpty()) {
                item {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(top = 40.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Icon(
                                imageVector = Icons.Default.SearchOff,
                                contentDescription = null,
                                tint = Color.Gray,
                                modifier = Modifier.size(48.dp)
                            )
                            Spacer(modifier = Modifier.height(12.dp))
                            Text(
                                text = "No settings found",
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp,
                                color = TextPrimaryLight
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "No options matching \"${state.searchQuery}\"",
                                fontSize = 13.sp,
                                color = TextSecondaryLight
                            )
                        }
                    }
                }
            } else {
                val categories = filteredSettings.map { it.category }.distinct()
                categories.forEach { category ->
                    item {
                        Text(
                            text = category,
                            fontSize = 12.sp,
                            fontWeight = FontWeight.Bold,
                            color = TextSecondaryLight,
                            modifier = Modifier.padding(start = 4.dp, top = 8.dp, bottom = 2.dp)
                        )
                    }

                    val itemsInCategory = filteredSettings.filter { it.category == category }
                    items(itemsInCategory.size) { index ->
                        val item = itemsInCategory[index]
                        SettingRowCard(item = item)
                    }
                }
            }

            // 3. Log Out Button Card
            if (!state.isSearchActive) {
                item {
                    Spacer(modifier = Modifier.height(6.dp))
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable(enabled = !state.isLoggingOut) {
                                viewModel.openDialog(SettingsDialogType.LOGOUT_CONFIRMATION)
                            },
                        shape = RoundedCornerShape(14.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(16.dp),
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Box(
                                modifier = Modifier
                                    .size(38.dp)
                                    .clip(CircleShape)
                                    .background(ConnectlyRose.copy(alpha = 0.12f)),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.AutoMirrored.Filled.Logout,
                                    contentDescription = "Log Out",
                                    tint = ConnectlyRose,
                                    modifier = Modifier.size(20.dp)
                                )
                            }

                            Spacer(modifier = Modifier.width(14.dp))

                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = if (state.isLoggingOut) "Logging out..." else "Log Out",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 15.sp,
                                    color = ConnectlyRose
                                )
                                Text(
                                    text = "Sign out from Connectly on this device",
                                    fontSize = 12.sp,
                                    color = TextSecondaryLight
                                )
                            }

                            if (state.isLoggingOut) {
                                CircularProgressIndicator(
                                    modifier = Modifier.size(20.dp),
                                    strokeWidth = 2.dp,
                                    color = ConnectlyRose
                                )
                            }
                        }
                    }
                    Spacer(modifier = Modifier.height(20.dp))
                }
            }
        }
    }

    // Interactive Dialogs for each setting feature
    when (state.activeDialog) {
        SettingsDialogType.LOGOUT_CONFIRMATION -> {
            AlertDialog(
                onDismissRequest = { viewModel.dismissDialog() },
                title = { Text("Log Out from Connectly?") },
                text = { Text("Are you sure you want to log out? You will need your password to log back in.") },
                confirmButton = {
                    Button(
                        onClick = { viewModel.confirmLogout() },
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyRose)
                    ) {
                        Text("Log Out", color = Color.White)
                    }
                },
                dismissButton = {
                    TextButton(onClick = { viewModel.dismissDialog() }) {
                        Text("Cancel")
                    }
                }
            )
        }

        SettingsDialogType.THEME_PICKER -> {
            AlertDialog(
                onDismissRequest = { viewModel.dismissDialog() },
                title = { Text("Appearance & Theme") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        listOf("Light", "Dark", "System Default").forEach { theme ->
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable { viewModel.setTheme(theme) }
                                    .padding(vertical = 8.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                RadioButton(
                                    selected = state.selectedTheme == theme,
                                    onClick = { viewModel.setTheme(theme) }
                                )
                                Spacer(modifier = Modifier.width(8.dp))
                                Text(theme, fontSize = 15.sp)
                            }
                        }
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.dismissDialog() }) {
                        Text("Close")
                    }
                }
            )
        }

        SettingsDialogType.LANGUAGE_PICKER -> {
            AlertDialog(
                onDismissRequest = { viewModel.dismissDialog() },
                title = { Text("Language & Region") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        listOf("English" to "English (US)", "বাংলা" to "বাংলা (বাংলাদেশ)").forEach { (code, label) ->
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable { viewModel.setLanguage(code) }
                                    .padding(vertical = 8.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                RadioButton(
                                    selected = state.selectedLanguage == code,
                                    onClick = { viewModel.setLanguage(code) }
                                )
                                Spacer(modifier = Modifier.width(8.dp))
                                Text(label, fontSize = 15.sp)
                            }
                        }
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.dismissDialog() }) {
                        Text("Close")
                    }
                }
            )
        }

        SettingsDialogType.DATA_STORAGE -> {
            AlertDialog(
                onDismissRequest = { viewModel.dismissDialog() },
                title = { Text("Data & Storage Usage") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(14.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text("Temporary App Cache:", fontSize = 14.sp)
                            Text(state.cacheSizeFormatted, fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                        }

                        Button(
                            onClick = { viewModel.clearAppCache(context) },
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue),
                            modifier = Modifier.fillMaxWidth()
                        ) {
                            Icon(Icons.Default.DeleteSweep, contentDescription = null, modifier = Modifier.size(18.dp))
                            Spacer(modifier = Modifier.width(8.dp))
                            Text("Clear Cache Now")
                        }

                        HorizontalDivider()

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Data Saver", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                Text("Lower media quality to save mobile data", fontSize = 12.sp, color = TextSecondaryLight)
                            }
                            Switch(
                                checked = state.dataSaverEnabled,
                                onCheckedChange = { viewModel.toggleDataSaver() }
                            )
                        }
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.dismissDialog() }) {
                        Text("Done")
                    }
                }
            )
        }

        SettingsDialogType.ACCESSIBILITY -> {
            AlertDialog(
                onDismissRequest = { viewModel.dismissDialog() },
                title = { Text("Accessibility Options") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Larger Text", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                Text("Increase font size for better readability", fontSize = 12.sp, color = TextSecondaryLight)
                            }
                            Switch(
                                checked = state.largeTextEnabled,
                                onCheckedChange = { viewModel.toggleLargeText() }
                            )
                        }

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("High Contrast", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                Text("Enhance text contrast and boundaries", fontSize = 12.sp, color = TextSecondaryLight)
                            }
                            Switch(
                                checked = state.highContrastEnabled,
                                onCheckedChange = { viewModel.toggleHighContrast() }
                            )
                        }

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Reduce Motion", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                Text("Minimize transitions and animations", fontSize = 12.sp, color = TextSecondaryLight)
                            }
                            Switch(
                                checked = state.reduceMotionEnabled,
                                onCheckedChange = { viewModel.toggleReduceMotion() }
                            )
                        }
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.dismissDialog() }) {
                        Text("Done")
                    }
                }
            )
        }

        SettingsDialogType.FEED_PREFERENCES -> {
            AlertDialog(
                onDismissRequest = { viewModel.dismissDialog() },
                title = { Text("Feed & Video Preferences") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Autoplay on Wi-Fi Only", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                Text("Only play videos automatically on Wi-Fi", fontSize = 12.sp, color = TextSecondaryLight)
                            }
                            Switch(
                                checked = state.autoplayOnWifiOnly,
                                onCheckedChange = { viewModel.toggleAutoplayOnWifi() }
                            )
                        }
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.dismissDialog() }) {
                        Text("Done")
                    }
                }
            )
        }

        SettingsDialogType.MESSAGING_CALLS -> {
            AlertDialog(
                onDismissRequest = { viewModel.dismissDialog() },
                title = { Text("Messaging & Calling Privacy") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text("Show Active Status", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                Text("Let friends see when you are online", fontSize = 12.sp, color = TextSecondaryLight)
                            }
                            Switch(
                                checked = state.showOnlineStatus,
                                onCheckedChange = { viewModel.toggleOnlineStatus() }
                            )
                        }

                        HorizontalDivider()

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text("Who Can Message You:", fontSize = 14.sp)
                            Text(state.whoCanMessage, fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                        }
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.dismissDialog() }) {
                        Text("Done")
                    }
                }
            )
        }

        SettingsDialogType.NOTIFICATIONS_INFO -> {
            AlertDialog(
                onDismissRequest = { viewModel.dismissDialog() },
                title = { Text("Notification Preferences") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("All notification channels are actively synchronized with Firebase Cloud Messaging (FCM) on bondhoo.com.", fontSize = 14.sp)
                        Spacer(modifier = Modifier.height(4.dp))
                        Text("• Likes, Comments & Mentions: Enabled", fontSize = 13.sp, color = TextSecondaryLight)
                        Text("• Direct Messages & Calls: Instant High-Priority", fontSize = 13.sp, color = TextSecondaryLight)
                        Text("• Friend Requests & Birthdays: Daily Digest", fontSize = 13.sp, color = TextSecondaryLight)
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.dismissDialog() }) {
                        Text("Got It")
                    }
                }
            )
        }

        SettingsDialogType.BLOCKED_USERS -> {
            AlertDialog(
                onDismissRequest = { viewModel.dismissDialog() },
                title = { Text("Blocked Users") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("You currently have ${state.blockedCount} blocked contacts.", fontSize = 14.sp)
                        Text("Blocked accounts cannot send you messages, view your posts, or find your profile in searches.", fontSize = 13.sp, color = TextSecondaryLight)
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.dismissDialog() }) {
                        Text("Close")
                    }
                }
            )
        }

        SettingsDialogType.HELP_SUPPORT -> {
            AlertDialog(
                onDismissRequest = { viewModel.dismissDialog() },
                title = { Text("Help & Support") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Text("Welcome to Connectly Support!", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                        Text("Need assistance with your account, privacy, or security?", fontSize = 13.sp)
                        Text("• Support Email: support@bondhoo.com", fontWeight = FontWeight.SemiBold, fontSize = 13.sp, color = ConnectlyBlue)
                        Text("• Official Website: https://bondhoo.com", fontSize = 13.sp, color = ConnectlyBlue)
                        Text("• 24/7 Community Safety Desk Active", fontSize = 12.sp, color = TextSecondaryLight)
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.dismissDialog() }) {
                        Text("Close")
                    }
                }
            )
        }

        SettingsDialogType.ABOUT_CONNECTLY -> {
            AlertDialog(
                onDismissRequest = { viewModel.dismissDialog() },
                title = { Text("About Connectly") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Text("Connectly Native Android", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                        Text("Version: 1.0.0 (Release Build 2026.11)", fontSize = 13.sp, color = TextSecondaryLight)
                        Text("Platform: JUGAJUG Enterprise Social Ecosystem", fontSize = 13.sp, color = TextSecondaryLight)
                        Spacer(modifier = Modifier.height(4.dp))
                        Text("Designed & Built with modern Jetpack Compose and Clean Architecture.", fontSize = 12.sp)
                        Text("© 2026 Edge Technologies. All rights reserved.", fontSize = 11.sp, color = TextMutedLight)
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.dismissDialog() }) {
                        Text("Close")
                    }
                }
            )
        }

        null -> {}
    }
}

@Composable
private fun SettingRowCard(item: SettingItemDef) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = item.onClick),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 14.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Box(
                modifier = Modifier
                    .size(38.dp)
                    .clip(CircleShape)
                    .background(ConnectlyBlue.copy(alpha = 0.1f)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = item.icon,
                    contentDescription = item.title,
                    tint = ConnectlyBlue,
                    modifier = Modifier.size(20.dp)
                )
            }

            Spacer(modifier = Modifier.width(14.dp))

            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = item.title,
                    fontWeight = FontWeight.SemiBold,
                    fontSize = 15.sp,
                    color = TextPrimaryLight
                )
                Text(
                    text = item.subtitle,
                    fontSize = 12.sp,
                    color = TextSecondaryLight,
                    maxLines = 1
                )
            }

            if (item.value != null) {
                Text(
                    text = item.value,
                    fontSize = 13.sp,
                    color = TextSecondaryLight,
                    fontWeight = FontWeight.Medium,
                    modifier = Modifier.padding(end = 6.dp)
                )
            }

            Icon(
                imageVector = Icons.AutoMirrored.Filled.ArrowForwardIos,
                contentDescription = null,
                tint = Color.LightGray,
                modifier = Modifier.size(13.dp)
            )
        }
    }
}
