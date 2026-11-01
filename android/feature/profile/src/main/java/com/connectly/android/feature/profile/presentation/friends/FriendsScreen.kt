package com.connectly.android.feature.profile.presentation.friends

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Chat
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material.icons.filled.PersonAdd
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.component.ConnectlyEmptyState
import com.connectly.android.core.designsystem.theme.*
import com.connectly.android.core.model.User

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun FriendsScreen(
    onNavigateToUserProfile: (Long) -> Unit = {},
    onNavigateToChat: (Long) -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: FriendsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Friends",
                        fontWeight = FontWeight.Bold,
                        fontSize = 24.sp,
                        color = TextPrimaryLight
                    )
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        modifier = modifier
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .background(BackgroundLight)
        ) {
            // Search Bar
            OutlinedTextField(
                value = state.searchQuery,
                onValueChange = { viewModel.handleIntent(FriendsIntent.SearchQueryChanged(it)) },
                placeholder = { Text("Search friends...", color = TextMutedLight, fontSize = 14.sp) },
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = TextSecondaryLight) },
                trailingIcon = {
                    if (state.searchQuery.isNotEmpty()) {
                        IconButton(onClick = { viewModel.handleIntent(FriendsIntent.SearchQueryChanged("")) }) {
                            Icon(Icons.Default.Clear, contentDescription = "Clear", tint = TextSecondaryLight)
                        }
                    }
                },
                singleLine = true,
                shape = RoundedCornerShape(20.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedContainerColor = MaterialTheme.colorScheme.surface,
                    unfocusedContainerColor = MaterialTheme.colorScheme.surface,
                    focusedBorderColor = ConnectlyElectricBlue,
                    unfocusedBorderColor = BorderLight
                ),
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp)
                    .height(48.dp)
            )

            // Segmented Filter Pills
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 6.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                FriendFilterPill(
                    label = "Requests",
                    badge = if (state.friendRequests.isNotEmpty()) state.friendRequests.size.toString() else null,
                    isSelected = state.selectedTab == "requests",
                    onClick = { viewModel.handleIntent(FriendsIntent.SelectTab("requests")) }
                )

                FriendFilterPill(
                    label = "Your Friends",
                    badge = if (state.friendsList.isNotEmpty()) state.friendsList.size.toString() else null,
                    isSelected = state.selectedTab == "friends",
                    onClick = { viewModel.handleIntent(FriendsIntent.SelectTab("friends")) }
                )

                FriendFilterPill(
                    label = "Suggestions",
                    isSelected = state.selectedTab == "suggestions",
                    onClick = { viewModel.handleIntent(FriendsIntent.SelectTab("suggestions")) }
                )
            }

            Spacer(modifier = Modifier.height(4.dp))

            // Tab Content
            when (state.selectedTab) {
                "requests" -> {
                    if (state.friendRequests.isEmpty()) {
                        ConnectlyEmptyState(
                            title = "No Pending Requests",
                            description = "When someone sends you a friend request, you'll see it here."
                        )
                    } else {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize(),
                            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            items(state.friendRequests, key = { it.user.id }) { item ->
                                FriendRequestCard(
                                    item = item,
                                    onConfirm = { viewModel.handleIntent(FriendsIntent.AcceptRequest(item.user.id)) },
                                    onDelete = { viewModel.handleIntent(FriendsIntent.DeleteRequest(item.user.id)) },
                                    onProfileClick = { onNavigateToUserProfile(item.user.id) }
                                )
                            }
                        }
                    }
                }

                "friends" -> {
                    val filteredFriends = if (state.searchQuery.isBlank()) {
                        state.friendsList
                    } else {
                        state.friendsList.filter { it.name.contains(state.searchQuery, ignoreCase = true) }
                    }

                    if (filteredFriends.isEmpty()) {
                        ConnectlyEmptyState(
                            title = "No Friends Found",
                            description = if (state.searchQuery.isBlank()) "You haven't connected with any friends yet." else "No friends matching \"${state.searchQuery}\""
                        )
                    } else {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize(),
                            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            items(filteredFriends, key = { it.id }) { friend ->
                                FriendItemCard(
                                    user = friend,
                                    onProfileClick = { onNavigateToUserProfile(friend.id) },
                                    onMessageClick = { onNavigateToChat(friend.id) },
                                    onUnfriendClick = { viewModel.handleIntent(FriendsIntent.Unfriend(friend.id)) }
                                )
                            }
                        }
                    }
                }

                "suggestions" -> {
                    if (state.suggestions.isEmpty()) {
                        ConnectlyEmptyState(
                            title = "No Suggestions Right Now",
                            description = "Check back later for new friend suggestions."
                        )
                    } else {
                        LazyColumn(
                            modifier = Modifier.fillMaxSize(),
                            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 8.dp),
                            verticalArrangement = Arrangement.spacedBy(10.dp)
                        ) {
                            items(state.suggestions, key = { it.user.id }) { item ->
                                SuggestedFriendCard(
                                    item = item,
                                    onAdd = { viewModel.handleIntent(FriendsIntent.AddFriend(item.user.id)) },
                                    onRemove = { viewModel.handleIntent(FriendsIntent.RemoveSuggestion(item.user.id)) },
                                    onProfileClick = { onNavigateToUserProfile(item.user.id) }
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun FriendFilterPill(
    label: String,
    badge: String? = null,
    isSelected: Boolean,
    onClick: () -> Unit
) {
    Surface(
        onClick = onClick,
        shape = RoundedCornerShape(20.dp),
        color = if (isSelected) ConnectlyElectricBlue else SurfaceLight,
        tonalElevation = if (isSelected) 2.dp else 0.dp,
        modifier = Modifier.height(36.dp)
    ) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            modifier = Modifier.padding(horizontal = 14.dp)
        ) {
            Text(
                text = label,
                color = if (isSelected) Color.White else TextPrimaryLight,
                fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
                fontSize = 13.sp
            )
            if (badge != null) {
                Spacer(modifier = Modifier.width(6.dp))
                Box(
                    modifier = Modifier
                        .size(18.dp)
                        .clip(CircleShape)
                        .background(if (isSelected) Color.White else ConnectlyElectricBlue),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = badge,
                        color = if (isSelected) ConnectlyElectricBlue else Color.White,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold
                    )
                }
            }
        }
    }
}

@Composable
private fun FriendRequestCard(
    item: FriendRequestItem,
    onConfirm: () -> Unit,
    onDelete: () -> Unit,
    onProfileClick: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(14.dp),
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
                imageUrl = item.user.avatar,
                name = item.user.name,
                size = 56.dp,
                isVerified = item.user.isVerified,
                modifier = Modifier.clickable(onClick = onProfileClick)
            )

            Spacer(modifier = Modifier.width(12.dp))

            Column(modifier = Modifier.weight(1f)) {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = item.user.name,
                        fontWeight = FontWeight.Bold,
                        fontSize = 15.sp,
                        color = TextPrimaryLight
                    )
                    Text(
                        text = item.timeAgo,
                        fontSize = 12.sp,
                        color = TextSecondaryLight
                    )
                }

                if (item.mutualFriendsCount > 0) {
                    Text(
                        text = "${item.mutualFriendsCount} mutual friends",
                        fontSize = 12.sp,
                        color = TextSecondaryLight
                    )
                }

                Spacer(modifier = Modifier.height(8.dp))

                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Button(
                        onClick = onConfirm,
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue),
                        shape = RoundedCornerShape(8.dp),
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 6.dp),
                        modifier = Modifier.height(34.dp)
                    ) {
                        Text("Confirm", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                    }

                    Button(
                        onClick = onDelete,
                        colors = ButtonDefaults.buttonColors(
                            containerColor = SurfaceSubtle,
                            contentColor = TextPrimaryLight
                        ),
                        shape = RoundedCornerShape(8.dp),
                        contentPadding = PaddingValues(horizontal = 16.dp, vertical = 6.dp),
                        modifier = Modifier.height(34.dp)
                    ) {
                        Text("Delete", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                    }
                }
            }
        }
    }
}

@Composable
private fun SuggestedFriendCard(
    item: SuggestedFriendItem,
    onAdd: () -> Unit,
    onRemove: () -> Unit,
    onProfileClick: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(14.dp),
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
                imageUrl = item.user.avatar,
                name = item.user.name,
                size = 56.dp,
                isVerified = item.user.isVerified,
                modifier = Modifier.clickable(onClick = onProfileClick)
            )

            Spacer(modifier = Modifier.width(12.dp))

            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = item.user.name,
                    fontWeight = FontWeight.Bold,
                    fontSize = 15.sp,
                    color = TextPrimaryLight
                )

                Text(
                    text = item.reason,
                    fontSize = 12.sp,
                    color = TextSecondaryLight
                )

                Spacer(modifier = Modifier.height(8.dp))

                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    Button(
                        onClick = onAdd,
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyElectricBlue),
                        shape = RoundedCornerShape(8.dp),
                        contentPadding = PaddingValues(horizontal = 14.dp, vertical = 6.dp),
                        modifier = Modifier.height(34.dp)
                    ) {
                        Icon(Icons.Default.PersonAdd, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(6.dp))
                        Text("Add Friend", fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                    }

                    Button(
                        onClick = onRemove,
                        colors = ButtonDefaults.buttonColors(
                            containerColor = SurfaceSubtle,
                            contentColor = TextSecondaryLight
                        ),
                        shape = RoundedCornerShape(8.dp),
                        contentPadding = PaddingValues(horizontal = 12.dp, vertical = 6.dp),
                        modifier = Modifier.height(34.dp)
                    ) {
                        Text("Remove", fontSize = 12.sp)
                    }
                }
            }
        }
    }
}

@Composable
private fun FriendItemCard(
    user: User,
    onProfileClick: () -> Unit,
    onMessageClick: () -> Unit,
    onUnfriendClick: () -> Unit
) {
    Card(
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onProfileClick)
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
                size = 48.dp,
                isOnline = user.isOnline,
                isVerified = user.isVerified
            )

            Spacer(modifier = Modifier.width(12.dp))

            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = user.name,
                    fontWeight = FontWeight.Bold,
                    fontSize = 15.sp,
                    color = TextPrimaryLight
                )
                Text(
                    text = if (user.isOnline) "Online" else "@${user.username}",
                    fontSize = 12.sp,
                    color = if (user.isOnline) ConnectlyGreen else TextSecondaryLight
                )
            }

            IconButton(onClick = onMessageClick) {
                Icon(
                    imageVector = Icons.AutoMirrored.Filled.Chat,
                    contentDescription = "Message",
                    tint = ConnectlyElectricBlue,
                    modifier = Modifier.size(20.dp)
                )
            }

            IconButton(onClick = onUnfriendClick) {
                Icon(
                    imageVector = Icons.Default.MoreVert,
                    contentDescription = "Options",
                    tint = TextSecondaryLight,
                    modifier = Modifier.size(20.dp)
                )
            }
        }
    }
}
