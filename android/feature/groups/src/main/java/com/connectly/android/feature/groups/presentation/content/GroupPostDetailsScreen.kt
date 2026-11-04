package com.connectly.android.feature.groups.presentation.content

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material.icons.filled.ThumbUp
import androidx.compose.material.icons.outlined.*
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
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.TextPrimaryLight
import com.connectly.android.core.designsystem.theme.TextSecondaryLight
import com.connectly.android.core.network.api.PostDto

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun GroupPostDetailsScreen(
    groupId: Long,
    postId: Long,
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: GroupContentViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    var commentText by remember { mutableStateOf("") }
    var menuExpanded by remember { mutableStateOf(false) }

    LaunchedEffect(postId) {
        viewModel.handleIntent(GroupContentIntent.LoadPostDetails(groupId, postId))
    }

    val post = state.selectedPostDetails

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Post Discussion", fontWeight = FontWeight.Bold, fontSize = 18.sp) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = { menuExpanded = true }) {
                        Icon(Icons.Default.MoreVert, contentDescription = "Options")
                    }
                    DropdownMenu(
                        expanded = menuExpanded,
                        onDismissRequest = { menuExpanded = false }
                    ) {
                        DropdownMenuItem(
                            text = { Text("Pin / Unpin Post") },
                            leadingIcon = { Icon(Icons.Outlined.PushPin, contentDescription = null) },
                            onClick = {
                                menuExpanded = false
                                viewModel.handleIntent(GroupContentIntent.TogglePinPost(postId))
                            }
                        )
                        DropdownMenuItem(
                            text = { Text("Report Post") },
                            leadingIcon = { Icon(Icons.Outlined.Flag, contentDescription = null) },
                            onClick = {
                                menuExpanded = false
                                viewModel.handleIntent(GroupContentIntent.ReportPost(postId, "spam", "Reported by member"))
                            }
                        )
                        HorizontalDivider()
                        DropdownMenuItem(
                            text = { Text("Delete Post", color = MaterialTheme.colorScheme.error) },
                            leadingIcon = { Icon(Icons.Outlined.Delete, contentDescription = null, tint = MaterialTheme.colorScheme.error) },
                            onClick = {
                                menuExpanded = false
                                viewModel.handleIntent(GroupContentIntent.DeletePost(postId))
                                onNavigateBack()
                            }
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
            )
        },
        bottomBar = {
            Surface(
                tonalElevation = 6.dp,
                color = MaterialTheme.colorScheme.surface,
                modifier = Modifier.fillMaxWidth()
            ) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 12.dp, vertical = 8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    OutlinedTextField(
                        value = commentText,
                        onValueChange = { commentText = it },
                        placeholder = { Text("Write a comment or reply...") },
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(24.dp)
                    )
                    Spacer(Modifier.width(8.dp))
                    IconButton(
                        onClick = {
                            if (commentText.isNotBlank()) {
                                // Add comment
                                commentText = ""
                            }
                        },
                        enabled = commentText.isNotBlank()
                    ) {
                        Icon(Icons.AutoMirrored.Filled.Send, contentDescription = "Send", tint = ConnectlyBlue)
                    }
                }
            }
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            if (post == null) {
                item {
                    Box(modifier = Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = ConnectlyBlue)
                    }
                }
            } else {
                // Post Card
                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                    ) {
                        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                            // Author Header
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Box(
                                    modifier = Modifier
                                        .size(44.dp)
                                        .clip(CircleShape)
                                        .background(ConnectlyBlue.copy(alpha = 0.1f)),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Text(post.resolvedAuthor.name.take(1).uppercase(), fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                                }
                                Spacer(Modifier.width(10.dp))
                                Column {
                                    Text(post.resolvedAuthor.name, fontWeight = FontWeight.Bold, fontSize = 15.sp)
                                    Text("@${post.resolvedAuthor.username} • ${post.created_at ?: "Just now"}", fontSize = 12.sp, color = TextSecondaryLight)
                                }
                            }

                            // Content
                            if (!post.content.isNullOrBlank()) {
                                Text(post.content.orEmpty(), fontSize = 15.sp, color = TextPrimaryLight)
                            }

                            // Counters & React bar
                            HorizontalDivider()

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Row(
                                    modifier = Modifier.clickable {
                                        viewModel.handleIntent(GroupContentIntent.ReactPost(post.id, "like"))
                                    },
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Icon(
                                        Icons.Default.ThumbUp,
                                        contentDescription = "Like",
                                        tint = if (post.resolvedUserReaction != null) ConnectlyBlue else TextSecondaryLight,
                                        modifier = Modifier.size(18.dp)
                                    )
                                    Spacer(Modifier.width(6.dp))
                                    Text("${post.resolvedReactionsCount} Likes", fontSize = 13.sp)
                                }

                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Outlined.Chat, contentDescription = "Comments", tint = TextSecondaryLight, modifier = Modifier.size(18.dp))
                                    Spacer(Modifier.width(6.dp))
                                    Text("${post.resolvedCommentsCount} Comments", fontSize = 13.sp)
                                }
                            }
                        }
                    }
                }

                item {
                    Text("Comments & Discussion", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                }

                // Empty notice for comments
                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.3f))
                    ) {
                        Text(
                            "Join the conversation! Leave a thought or reply above.",
                            modifier = Modifier.padding(16.dp),
                            color = TextSecondaryLight,
                            fontSize = 13.sp
                        )
                    }
                }
            }
        }
    }
}

