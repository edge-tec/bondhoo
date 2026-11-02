package com.connectly.android.feature.profile.presentation.blocking

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen
import com.connectly.android.core.designsystem.theme.ConnectlyRose
import com.connectly.android.core.network.api.RestrictedUserDto

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun BlockedUsersScreen(
    onNavigateBack: () -> Unit,
    onNavigateToUserProfile: (Long) -> Unit,
    modifier: Modifier = Modifier,
    viewModel: BlockedUsersViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Blocked & Restricted",
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
        modifier = modifier
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
                .background(MaterialTheme.colorScheme.background)
        ) {
            // Search Input
            OutlinedTextField(
                value = state.searchQuery,
                onValueChange = { viewModel.handleIntent(BlockedUsersIntent.SearchQueryChanged(it)) },
                placeholder = { Text("Search users by name or username...") },
                leadingIcon = {
                    Icon(
                        imageVector = Icons.Default.Search,
                        contentDescription = "Search",
                        tint = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                },
                trailingIcon = {
                    if (state.searchQuery.isNotEmpty()) {
                        IconButton(onClick = { viewModel.handleIntent(BlockedUsersIntent.SearchQueryChanged("")) }) {
                            Icon(imageVector = Icons.Default.Close, contentDescription = "Clear")
                        }
                    }
                },
                singleLine = true,
                shape = RoundedCornerShape(12.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = ConnectlyBlue,
                    unfocusedBorderColor = MaterialTheme.colorScheme.outlineVariant,
                    focusedContainerColor = MaterialTheme.colorScheme.surface,
                    unfocusedContainerColor = MaterialTheme.colorScheme.surface
                ),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp)
            )

            // Category Tabs
            PrimaryTabRow(
                selectedTabIndex = state.selectedTab.ordinal,
                containerColor = MaterialTheme.colorScheme.surface,
                contentColor = ConnectlyBlue
            ) {
                Tab(
                    selected = state.selectedTab == BlockedTab.BLOCKED,
                    onClick = { viewModel.handleIntent(BlockedUsersIntent.SelectTab(BlockedTab.BLOCKED)) },
                    text = {
                        Text(
                            text = "Blocked (${state.blockedUsers.size})",
                            fontWeight = if (state.selectedTab == BlockedTab.BLOCKED) FontWeight.Bold else FontWeight.Medium
                        )
                    }
                )
                Tab(
                    selected = state.selectedTab == BlockedTab.RESTRICTED,
                    onClick = { viewModel.handleIntent(BlockedUsersIntent.SelectTab(BlockedTab.RESTRICTED)) },
                    text = {
                        Text(
                            text = "Restricted (${state.restrictedUsers.size})",
                            fontWeight = if (state.selectedTab == BlockedTab.RESTRICTED) FontWeight.Bold else FontWeight.Medium
                        )
                    }
                )
                Tab(
                    selected = state.selectedTab == BlockedTab.MUTED,
                    onClick = { viewModel.handleIntent(BlockedUsersIntent.SelectTab(BlockedTab.MUTED)) },
                    text = {
                        Text(
                            text = "Muted (${state.mutedUsers.size})",
                            fontWeight = if (state.selectedTab == BlockedTab.MUTED) FontWeight.Bold else FontWeight.Medium
                        )
                    }
                )
            }

            // Feedback Banners
            state.successMessage?.let { msg ->
                Card(
                    colors = CardDefaults.cardColors(containerColor = ConnectlyGreen.copy(alpha = 0.12f)),
                    shape = RoundedCornerShape(10.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 8.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Icon(imageVector = Icons.Default.CheckCircle, contentDescription = null, tint = ConnectlyGreen)
                        Text(
                            text = msg,
                            color = ConnectlyGreen,
                            style = MaterialTheme.typography.bodyMedium,
                            fontWeight = FontWeight.Medium,
                            modifier = Modifier.weight(1f)
                        )
                        IconButton(
                            onClick = { viewModel.handleIntent(BlockedUsersIntent.DismissFeedback) },
                            modifier = Modifier.size(24.dp)
                        ) {
                            Icon(imageVector = Icons.Default.Close, contentDescription = null, tint = ConnectlyGreen, modifier = Modifier.size(16.dp))
                        }
                    }
                }
            }

            state.errorMessage?.let { err ->
                Card(
                    colors = CardDefaults.cardColors(containerColor = ConnectlyRose.copy(alpha = 0.12f)),
                    shape = RoundedCornerShape(10.dp),
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 8.dp)
                ) {
                    Row(
                        modifier = Modifier.padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Icon(imageVector = Icons.Default.Error, contentDescription = null, tint = ConnectlyRose)
                        Text(
                            text = err,
                            color = ConnectlyRose,
                            style = MaterialTheme.typography.bodyMedium,
                            fontWeight = FontWeight.Medium,
                            modifier = Modifier.weight(1f)
                        )
                        IconButton(
                            onClick = { viewModel.handleIntent(BlockedUsersIntent.DismissFeedback) },
                            modifier = Modifier.size(24.dp)
                        ) {
                            Icon(imageVector = Icons.Default.Close, contentDescription = null, tint = ConnectlyRose, modifier = Modifier.size(16.dp))
                        }
                    }
                }
            }

            // Body Content
            if (state.isLoading) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(32.dp),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator(color = ConnectlyBlue)
                }
            } else if (state.filteredList.isEmpty()) {
                EmptyStateView(tab = state.selectedTab, isSearching = state.searchQuery.isNotEmpty())
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(horizontal = 16.dp, vertical = 10.dp),
                    verticalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    items(state.filteredList, key = { it.id }) { user ->
                        RestrictedUserCard(
                            user = user,
                            tab = state.selectedTab,
                            onViewProfile = { onNavigateToUserProfile(user.id) },
                            onAction = {
                                val actionType = when (state.selectedTab) {
                                    BlockedTab.BLOCKED -> RestrictionActionType.UNBLOCK
                                    BlockedTab.RESTRICTED -> RestrictionActionType.UNRESTRICT
                                    BlockedTab.MUTED -> RestrictionActionType.UNMUTE
                                }
                                viewModel.handleIntent(BlockedUsersIntent.RequestAction(user, actionType))
                            }
                        )
                    }
                }
            }
        }
    }

    // Confirmation Dialog
    state.pendingActionUser?.let { user ->
        val action = state.pendingActionType ?: RestrictionActionType.UNBLOCK
        val title = when (action) {
            RestrictionActionType.UNBLOCK -> "Unblock ${user.name}?"
            RestrictionActionType.UNRESTRICT -> "Unrestrict ${user.name}?"
            RestrictionActionType.UNMUTE -> "Unmute ${user.name}?"
        }
        val description = when (action) {
            RestrictionActionType.UNBLOCK -> "Once unblocked, ${user.name} will be able to view your profile, see your public posts, and send you messages or friend requests."
            RestrictionActionType.UNRESTRICT -> "${user.name} will again be able to see when you're online and when you've read their messages."
            RestrictionActionType.UNMUTE -> "You will resume receiving alerts, stories and notifications from ${user.name}."
        }
        val confirmButtonText = when (action) {
            RestrictionActionType.UNBLOCK -> "Unblock"
            RestrictionActionType.UNRESTRICT -> "Unrestrict"
            RestrictionActionType.UNMUTE -> "Unmute"
        }

        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(BlockedUsersIntent.DismissActionDialog) },
            title = { Text(title, fontWeight = FontWeight.Bold) },
            text = { Text(description, style = MaterialTheme.typography.bodyMedium) },
            confirmButton = {
                Button(
                    onClick = { viewModel.handleIntent(BlockedUsersIntent.ExecuteConfirmedAction) },
                    enabled = !state.isActionInProgress,
                    colors = ButtonDefaults.buttonColors(
                        containerColor = if (action == RestrictionActionType.UNBLOCK) ConnectlyRose else ConnectlyBlue
                    )
                ) {
                    Text(confirmButtonText, color = Color.White)
                }
            },
            dismissButton = {
                TextButton(
                    onClick = { viewModel.handleIntent(BlockedUsersIntent.DismissActionDialog) },
                    enabled = !state.isActionInProgress
                ) {
                    Text("Cancel")
                }
            }
        )
    }
}

@Composable
private fun RestrictedUserCard(
    user: RestrictedUserDto,
    tab: BlockedTab,
    onViewProfile: () -> Unit,
    onAction: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = Modifier.fillMaxWidth()
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            ConnectlyAvatar(
                imageUrl = user.avatar,
                name = user.name,
                isVerified = user.is_verified,
                size = 46.dp
            )

            Spacer(modifier = Modifier.width(12.dp))

            Column(
                modifier = Modifier
                    .weight(1f)
                    .clickable(onClick = onViewProfile)
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Text(
                        text = user.name,
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.Bold,
                        color = MaterialTheme.colorScheme.onSurface
                    )
                    if (user.is_verified) {
                        Spacer(modifier = Modifier.width(4.dp))
                        Icon(
                            imageVector = Icons.Default.CheckCircle,
                            contentDescription = "Verified",
                            tint = ConnectlyBlue,
                            modifier = Modifier.size(14.dp)
                        )
                    }
                }
                Text(
                    text = "@${user.username}",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }

            Spacer(modifier = Modifier.width(8.dp))

            OutlinedButton(
                onClick = onAction,
                shape = RoundedCornerShape(8.dp),
                contentPadding = PaddingValues(horizontal = 12.dp, vertical = 6.dp),
                colors = ButtonDefaults.outlinedButtonColors(
                    contentColor = if (tab == BlockedTab.BLOCKED) ConnectlyRose else ConnectlyBlue
                )
            ) {
                Text(
                    text = when (tab) {
                        BlockedTab.BLOCKED -> "Unblock"
                        BlockedTab.RESTRICTED -> "Unrestrict"
                        BlockedTab.MUTED -> "Unmute"
                    },
                    style = MaterialTheme.typography.labelMedium,
                    fontWeight = FontWeight.Bold
                )
            }
        }
    }
}

@Composable
private fun EmptyStateView(
    tab: BlockedTab,
    isSearching: Boolean
) {
    Box(
        modifier = Modifier
            .fillMaxSize()
            .padding(32.dp),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(10.dp)
        ) {
            Icon(
                imageVector = when {
                    isSearching -> Icons.Default.SearchOff
                    tab == BlockedTab.BLOCKED -> Icons.Default.Block
                    tab == BlockedTab.RESTRICTED -> Icons.Default.PersonOff
                    else -> Icons.Default.VolumeOff
                },
                contentDescription = null,
                tint = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.5f),
                modifier = Modifier.size(54.dp)
            )

            Text(
                text = if (isSearching) {
                    "No matching users found"
                } else {
                    when (tab) {
                        BlockedTab.BLOCKED -> "No Blocked Users"
                        BlockedTab.RESTRICTED -> "No Restricted Friends"
                        BlockedTab.MUTED -> "No Muted Friends"
                    }
                },
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.Bold,
                color = MaterialTheme.colorScheme.onSurface
            )

            Text(
                text = if (isSearching) {
                    "Try checking your spelling or search for another name."
                } else {
                    when (tab) {
                        BlockedTab.BLOCKED -> "You haven't blocked anyone yet. Blocked people cannot view your profile, posts or message you."
                        BlockedTab.RESTRICTED -> "Restricted friends cannot see your active online presence or read receipts."
                        BlockedTab.MUTED -> "Muted friends won't appear in your story tray or generate audible notification rings."
                    }
                },
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = androidx.compose.ui.text.style.TextAlign.Center
            )
        }
    }
}

