package com.connectly.android.feature.profile.presentation.privacy

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
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
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.*
import com.connectly.android.core.network.api.UserSessionDto

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PrivacySettingsScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: PrivacySettingsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

    // Dialog state for password change
    var currentPass by remember { mutableStateOf("") }
    var newPass by remember { mutableStateOf("") }
    var confirmPass by remember { mutableStateOf("") }

    LaunchedEffect(state.successMessage) {
        state.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(PrivacySettingsIntent.DismissMessages)
        }
    }

    LaunchedEffect(state.errorMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.handleIntent(PrivacySettingsIntent.DismissMessages)
        }
    }

    Scaffold(
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = { Text("Privacy & Security", fontWeight = FontWeight.Bold, fontSize = 18.sp) },
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
                        TextButton(onClick = { viewModel.handleIntent(PrivacySettingsIntent.SaveAllSettings) }) {
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
                // SECTION 1: PROFILE PRIVACY
                // ==========================================
                item {
                    SectionHeader(title = "PROFILE PRIVACY", icon = Icons.Default.Person)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            // Profile Visibility
                            PrivacyOptionSelector(
                                title = "Account Privacy",
                                subtitle = "Public accounts are visible to everyone. Private accounts only share to approved friends.",
                                currentValue = state.profileVisibility,
                                options = listOf(
                                    "public" to "Public",
                                    "friends" to "Friends Only",
                                    "private" to "Private"
                                ),
                                onSelect = { viewModel.handleIntent(PrivacySettingsIntent.ProfileVisibilityChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Profile Lock
                            SwitchSettingRow(
                                title = "Lock Profile",
                                subtitle = "Only confirmed friends can view full-size photos, posts, and details on your timeline.",
                                checked = state.isProfileLocked,
                                onCheckedChange = { viewModel.handleIntent(PrivacySettingsIntent.ProfileLockedToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Avatar Guard
                            SwitchSettingRow(
                                title = "Profile Picture Guard",
                                subtitle = "Prevents downloading, copying, or screenshotting your avatar photo.",
                                checked = state.hasAvatarGuard,
                                onCheckedChange = { viewModel.handleIntent(PrivacySettingsIntent.AvatarGuardToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Who Can See Posts
                            PrivacyOptionSelector(
                                title = "Who can see your posts",
                                subtitle = "Audience for your future posts and timeline updates.",
                                currentValue = state.whoCanSeePosts,
                                options = listOf(
                                    "public" to "Public",
                                    "friends" to "Friends Only",
                                    "only_me" to "Only Me"
                                ),
                                onSelect = { viewModel.handleIntent(PrivacySettingsIntent.WhoCanSeePostsChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Who Can See Friends
                            PrivacyOptionSelector(
                                title = "Who can see your friends list",
                                subtitle = "Who can view the complete list of your friends.",
                                currentValue = state.whoCanSeeFriends,
                                options = listOf(
                                    "public" to "Public",
                                    "friends" to "Friends Only",
                                    "only_me" to "Only Me"
                                ),
                                onSelect = { viewModel.handleIntent(PrivacySettingsIntent.WhoCanSeeFriendsChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Who Can Send Friend Requests
                            PrivacyOptionSelector(
                                title = "Who can send you friend requests",
                                subtitle = "Control who is allowed to send friendship invitations.",
                                currentValue = state.whoCanSendFriendRequests,
                                options = listOf(
                                    "everyone" to "Everyone",
                                    "friends_of_friends" to "Friends of Friends"
                                ),
                                onSelect = { viewModel.handleIntent(PrivacySettingsIntent.WhoCanSendFriendRequestsChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Who Can Follow
                            PrivacyOptionSelector(
                                title = "Who can follow you",
                                subtitle = "People who follow you see your public posts in their feed.",
                                currentValue = state.whoCanFollow,
                                options = listOf(
                                    "everyone" to "Everyone",
                                    "friends" to "Friends Only"
                                ),
                                onSelect = { viewModel.handleIntent(PrivacySettingsIntent.WhoCanFollowChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Find By Email
                            PrivacyOptionSelector(
                                title = "Who can find you by email",
                                subtitle = "Allow users to locate your account using your email address.",
                                currentValue = state.findByEmail,
                                options = listOf(
                                    "everyone" to "Everyone",
                                    "friends" to "Friends",
                                    "only_me" to "No One"
                                ),
                                onSelect = { viewModel.handleIntent(PrivacySettingsIntent.FindByEmailChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Find By Phone
                            PrivacyOptionSelector(
                                title = "Who can find you by phone number",
                                subtitle = "Allow people with your phone number to discover your account.",
                                currentValue = state.findByPhone,
                                options = listOf(
                                    "everyone" to "Everyone",
                                    "friends" to "Friends",
                                    "only_me" to "No One"
                                ),
                                onSelect = { viewModel.handleIntent(PrivacySettingsIntent.FindByPhoneChanged(it)) }
                            )
                        }
                    }
                }

                // ==========================================
                // SECTION 2: CONTENT & INTERACTIONS
                // ==========================================
                item {
                    SectionHeader(title = "CONTENT & INTERACTIONS", icon = Icons.Default.Share)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            // Story Audience
                            PrivacyOptionSelector(
                                title = "Story Visibility",
                                subtitle = "Choose who can see the 24-hour stories you publish.",
                                currentValue = state.storyVisibility,
                                options = listOf(
                                    "public" to "Public",
                                    "friends" to "Friends Only"
                                ),
                                onSelect = { viewModel.handleIntent(PrivacySettingsIntent.StoryVisibilityChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Who Can Message
                            PrivacyOptionSelector(
                                title = "Who can message you",
                                subtitle = "Control who can initiate direct chat conversations with you.",
                                currentValue = state.whoCanMessage,
                                options = listOf(
                                    "everyone" to "Everyone",
                                    "friends" to "Friends Only"
                                ),
                                onSelect = { viewModel.handleIntent(PrivacySettingsIntent.WhoCanMessageChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Allow Tagging
                            SwitchSettingRow(
                                title = "Allow Tagging",
                                subtitle = "Let other users tag you in their posts, photos, and stories.",
                                checked = state.allowTagging,
                                onCheckedChange = { viewModel.handleIntent(PrivacySettingsIntent.AllowTaggingToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Search Engine Indexing
                            SwitchSettingRow(
                                title = "Search Engine Indexing",
                                subtitle = "Allow search engines (Google, Bing) to link directly to your public profile.",
                                checked = state.searchEngineIndexing,
                                onCheckedChange = { viewModel.handleIntent(PrivacySettingsIntent.SearchEngineIndexingToggled(it)) }
                            )
                        }
                    }
                }

                // ==========================================
                // SECTION 3: ONLINE PRIVACY
                // ==========================================
                item {
                    SectionHeader(title = "ONLINE PRIVACY", icon = Icons.Default.Visibility)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            // Online Status
                            SwitchSettingRow(
                                title = "Show Online Status",
                                subtitle = "Shows a green active badge when you are active on Connectly.",
                                checked = state.showOnlineStatus,
                                onCheckedChange = { viewModel.handleIntent(PrivacySettingsIntent.ShowOnlineStatusToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Last Seen
                            PrivacyOptionSelector(
                                title = "Last Seen",
                                subtitle = "Controls who can see timestamp when you were last online.",
                                currentValue = state.showLastSeen,
                                options = listOf(
                                    "everyone" to "Everyone",
                                    "friends" to "Friends Only",
                                    "nobody" to "Nobody"
                                ),
                                onSelect = { viewModel.handleIntent(PrivacySettingsIntent.ShowLastSeenChanged(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Read Receipts
                            SwitchSettingRow(
                                title = "Read Receipts",
                                subtitle = "Allow friends to see when you've read their direct messages.",
                                checked = state.readReceiptsEnabled,
                                onCheckedChange = { viewModel.handleIntent(PrivacySettingsIntent.ReadReceiptsToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Typing Indicators
                            SwitchSettingRow(
                                title = "Typing Indicators",
                                subtitle = "Show 'Typing...' animation in conversations while composing a reply.",
                                checked = state.typingIndicatorsEnabled,
                                onCheckedChange = { viewModel.handleIntent(PrivacySettingsIntent.TypingIndicatorsToggled(it)) }
                            )
                        }
                    }
                }

                // ==========================================
                // SECTION 4: SECURITY & SESSIONS
                // ==========================================
                item {
                    SectionHeader(title = "SECURITY & SESSIONS", icon = Icons.Default.Security)
                }

                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
                    ) {
                        Column(modifier = Modifier.padding(horizontal = 16.dp, vertical = 8.dp)) {
                            // Change Password Action
                            Row(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .clickable { viewModel.handleIntent(PrivacySettingsIntent.OpenPasswordDialog(true)) }
                                    .padding(vertical = 12.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(36.dp)
                                        .clip(CircleShape)
                                        .background(ConnectlyBlue.copy(alpha = 0.12f)),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Icon(Icons.Default.VpnKey, contentDescription = null, tint = ConnectlyBlue, modifier = Modifier.size(20.dp))
                                }
                                Spacer(modifier = Modifier.width(12.dp))
                                Column(modifier = Modifier.weight(1f)) {
                                    Text("Change Password", fontWeight = FontWeight.SemiBold, fontSize = 15.sp, color = TextPrimaryLight)
                                    Text("Update your login password regularly", fontSize = 12.sp, color = TextSecondaryLight)
                                }
                                Icon(Icons.Default.ChevronRight, contentDescription = null, tint = TextMutedLight)
                            }

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Two-Factor Authentication
                            SwitchSettingRow(
                                title = "Two-Factor Authentication (2FA)",
                                subtitle = "Adds extra security by requiring a verification code when logging in.",
                                checked = state.twoFactorEnabled,
                                onCheckedChange = { viewModel.handleIntent(PrivacySettingsIntent.TwoFactorToggled(it)) }
                            )

                            HorizontalDivider(color = BorderLight, modifier = Modifier.padding(vertical = 8.dp))

                            // Login Alerts
                            SwitchSettingRow(
                                title = "Login Alerts",
                                subtitle = "Receive alerts whenever a new login occurs from an unrecognized device or browser.",
                                checked = state.loginAlertsEnabled,
                                onCheckedChange = { viewModel.handleIntent(PrivacySettingsIntent.LoginAlertsToggled(it)) }
                            )
                        }
                    }
                }

                // ==========================================
                // SECTION 5: ACTIVE SESSIONS & DEVICES
                // ==========================================
                item {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        SectionHeader(title = "ACTIVE SESSIONS & DEVICES", icon = Icons.Default.Devices)
                        if (state.sessions.size > 1) {
                            TextButton(onClick = { viewModel.handleIntent(PrivacySettingsIntent.ConfirmLogoutOthers) }) {
                                Text("Log Out Others", fontSize = 12.sp, color = ConnectlyRed, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }

                if (state.isSessionsLoading && state.sessions.isEmpty()) {
                    item {
                        Box(modifier = Modifier.fillMaxWidth().padding(16.dp), contentAlignment = Alignment.Center) {
                            CircularProgressIndicator(modifier = Modifier.size(24.dp), color = ConnectlyBlue)
                        }
                    }
                } else if (state.sessions.isEmpty()) {
                    item {
                        Card(
                            modifier = Modifier.fillMaxWidth(),
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
                                Icon(Icons.Default.Smartphone, contentDescription = null, tint = ConnectlyGreen)
                                Spacer(modifier = Modifier.width(12.dp))
                                Column {
                                    Text("This Device (Active Now)", fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = TextPrimaryLight)
                                    Text("Android Native App • Current Session", fontSize = 12.sp, color = TextSecondaryLight)
                                }
                            }
                        }
                    }
                } else {
                    items(state.sessions) { session ->
                        SessionItemCard(
                            session = session,
                            onRevokeClick = { viewModel.handleIntent(PrivacySettingsIntent.ConfirmRevokeSession(session)) }
                        )
                    }
                }

                item {
                    Spacer(modifier = Modifier.height(30.dp))
                }
            }
        }
    }

    // ==========================================
    // DIALOGS
    // ==========================================

    // 1. Password Change Dialog
    if (state.showPasswordChangeDialog) {
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(PrivacySettingsIntent.OpenPasswordDialog(false)) },
            title = { Text("Change Password", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text("Enter your current password and choose a new secure password.", fontSize = 13.sp, color = TextSecondaryLight)
                    OutlinedTextField(
                        value = currentPass,
                        onValueChange = { currentPass = it },
                        label = { Text("Current Password") },
                        visualTransformation = PasswordVisualTransformation(),
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )
                    OutlinedTextField(
                        value = newPass,
                        onValueChange = { newPass = it },
                        label = { Text("New Password (min 8 chars)") },
                        visualTransformation = PasswordVisualTransformation(),
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )
                    OutlinedTextField(
                        value = confirmPass,
                        onValueChange = { confirmPass = it },
                        label = { Text("Confirm New Password") },
                        visualTransformation = PasswordVisualTransformation(),
                        modifier = Modifier.fillMaxWidth(),
                        singleLine = true
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        viewModel.handleIntent(PrivacySettingsIntent.ChangePassword(currentPass, newPass, confirmPass))
                        currentPass = ""
                        newPass = ""
                        confirmPass = ""
                    },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                ) {
                    Text("Update Password", color = Color.White)
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(PrivacySettingsIntent.OpenPasswordDialog(false)) }) {
                    Text("Cancel", color = TextSecondaryLight)
                }
            }
        )
    }

    // 2. Revoke Single Session Dialog
    state.sessionToRevoke?.let { session ->
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(PrivacySettingsIntent.DismissRevokeDialog) },
            title = { Text("Log Out Device?", fontWeight = FontWeight.Bold) },
            text = {
                Text(
                    "Are you sure you want to terminate the active session on '${session.device_name ?: session.os ?: "this device"}'? This device will be signed out immediately.",
                    fontSize = 14.sp,
                    color = TextSecondaryLight
                )
            },
            confirmButton = {
                Button(
                    onClick = { viewModel.handleIntent(PrivacySettingsIntent.ExecuteRevokeSession) },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyRed)
                ) {
                    Text("Log Out Device", color = Color.White)
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(PrivacySettingsIntent.DismissRevokeDialog) }) {
                    Text("Cancel", color = TextSecondaryLight)
                }
            }
        )
    }

    // 3. Logout All Other Devices Dialog
    if (state.showLogoutOthersDialog) {
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(PrivacySettingsIntent.DismissLogoutOthersDialog) },
            title = { Text("Log Out from Other Devices?", fontWeight = FontWeight.Bold) },
            text = {
                Text(
                    "This will invalidate all sessions and sign you out of Connectly on all other phones, tablets, and web browsers. Your current session will remain active.",
                    fontSize = 14.sp,
                    color = TextSecondaryLight
                )
            },
            confirmButton = {
                Button(
                    onClick = { viewModel.handleIntent(PrivacySettingsIntent.ExecuteLogoutOthers) },
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyRed)
                ) {
                    Text("Log Out All Others", color = Color.White)
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(PrivacySettingsIntent.DismissLogoutOthersDialog) }) {
                    Text("Cancel", color = TextSecondaryLight)
                }
            }
        )
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
private fun SwitchSettingRow(
    title: String,
    subtitle: String,
    checked: Boolean,
    onCheckedChange: (Boolean) -> Unit
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { onCheckedChange(!checked) }
            .padding(vertical = 10.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Column(modifier = Modifier.weight(1f)) {
            Text(text = title, fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = TextPrimaryLight)
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
private fun PrivacyOptionSelector(
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

@Composable
private fun SessionItemCard(
    session: UserSessionDto,
    onRevokeClick: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(14.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Box(
                modifier = Modifier
                    .size(40.dp)
                    .clip(CircleShape)
                    .background(if (session.is_current) ConnectlyGreen.copy(alpha = 0.12f) else BorderLight),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = when {
                        session.device_name?.contains("Android", ignoreCase = true) == true -> Icons.Default.Smartphone
                        session.os?.contains("Mac", ignoreCase = true) == true || session.os?.contains("Windows", ignoreCase = true) == true -> Icons.Default.Computer
                        else -> Icons.Default.Devices
                    },
                    contentDescription = null,
                    tint = if (session.is_current) ConnectlyGreen else TextSecondaryLight,
                    modifier = Modifier.size(20.dp)
                )
            }

            Spacer(modifier = Modifier.width(12.dp))

            Column(modifier = Modifier.weight(1f)) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(
                        text = session.device_name ?: session.os ?: "Device",
                        fontWeight = FontWeight.SemiBold,
                        fontSize = 14.sp,
                        color = TextPrimaryLight
                    )
                    if (session.is_current) {
                        Spacer(modifier = Modifier.width(6.dp))
                        Surface(
                            shape = RoundedCornerShape(4.dp),
                            color = ConnectlyGreen.copy(alpha = 0.15f)
                        ) {
                            Text(
                                text = "This Device",
                                color = ConnectlyGreen,
                                fontSize = 10.sp,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                            )
                        }
                    }
                }
                Spacer(modifier = Modifier.height(2.dp))
                val locationText = listOfNotNull(session.browser, session.location, session.ip_address).joinToString(" • ")
                Text(
                    text = if (locationText.isNotBlank()) locationText else "Active session",
                    fontSize = 11.sp,
                    color = TextSecondaryLight
                )
            }

            if (!session.is_current) {
                IconButton(onClick = onRevokeClick) {
                    Icon(Icons.Default.DeleteOutline, contentDescription = "Revoke Session", tint = ConnectlyRed)
                }
            }
        }
    }
}
