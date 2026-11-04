package com.connectly.android.feature.groups.presentation.content

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.PendingActions
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
fun GroupPendingPostsScreen(
    groupId: Long,
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    viewModel: GroupContentViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()

    LaunchedEffect(groupId) {
        viewModel.handleIntent(GroupContentIntent.LoadPendingPosts(groupId))
    }

    // Rejection reason dialog
    state.rejectDialogPostId?.let { postId ->
        var reason by remember { mutableStateOf("Does not meet community guidelines") }
        AlertDialog(
            onDismissRequest = { viewModel.handleIntent(GroupContentIntent.DismissDialogs) },
            title = { Text("Reject Post") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    Text("Provide a reason for the author so they know why their post was rejected:")
                    OutlinedTextField(
                        value = reason,
                        onValueChange = { reason = it },
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = { viewModel.handleIntent(GroupContentIntent.RejectPendingPost(postId, reason)) },
                    colors = ButtonDefaults.buttonColors(containerColor = MaterialTheme.colorScheme.error)
                ) {
                    Text("Confirm Rejection")
                }
            },
            dismissButton = {
                TextButton(onClick = { viewModel.handleIntent(GroupContentIntent.DismissDialogs) }) {
                    Text("Cancel")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        Text("Pending Posts Queue", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                        Text("${state.pendingPosts.size} posts waiting for review", fontSize = 12.sp, color = TextSecondaryLight)
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
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
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Notification message
            state.actionMessage?.let { msg ->
                item {
                    Card(colors = CardDefaults.cardColors(containerColor = Color(0xFFDCFCE7))) {
                        Row(modifier = Modifier.padding(12.dp), verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Outlined.CheckCircle, contentDescription = null, tint = Color(0xFF16A34A))
                            Spacer(Modifier.width(8.dp))
                            Text(msg, color = Color(0xFF166534), fontSize = 13.sp)
                        }
                    }
                }
            }

            if (state.pendingPosts.isEmpty()) {
                item {
                    Column(
                        modifier = Modifier.fillMaxWidth().padding(48.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Icon(Icons.Outlined.PendingActions, contentDescription = null, modifier = Modifier.size(56.dp), tint = TextSecondaryLight)
                        Spacer(Modifier.height(12.dp))
                        Text("Queue is empty", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                        Text("All member posts have been reviewed.", fontSize = 13.sp, color = TextSecondaryLight)
                    }
                }
            } else {
                items(state.pendingPosts, key = { it.id }) { post ->
                    PendingPostCard(
                        post = post,
                        onApprove = { viewModel.handleIntent(GroupContentIntent.ApprovePendingPost(post.id)) },
                        onReject = { viewModel.handleIntent(GroupContentIntent.OpenRejectDialog(post.id)) }
                    )
                }
            }
        }
    }
}

@Composable
private fun PendingPostCard(
    post: PostDto,
    onApprove: () -> Unit,
    onReject: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Box(
                    modifier = Modifier
                        .size(40.dp)
                        .clip(CircleShape)
                        .background(ConnectlyBlue.copy(alpha = 0.1f)),
                    contentAlignment = Alignment.Center
                ) {
                    Text(post.resolvedAuthor.name.take(1).uppercase(), fontWeight = FontWeight.Bold, color = ConnectlyBlue)
                }
                Spacer(Modifier.width(10.dp))
                Column {
                    Text(post.resolvedAuthor.name, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                    Text("@${post.resolvedAuthor.username}", fontSize = 11.sp, color = TextSecondaryLight)
                }
            }

            if (!post.content.isNullOrBlank()) {
                Text(post.content.orEmpty(), fontSize = 14.sp, color = TextPrimaryLight)
            }

            HorizontalDivider()

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.End,
                verticalAlignment = Alignment.CenterVertically
            ) {
                OutlinedButton(
                    onClick = onReject,
                    colors = ButtonDefaults.outlinedButtonColors(contentColor = MaterialTheme.colorScheme.error)
                ) {
                    Icon(Icons.Default.Close, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(4.dp))
                    Text("Reject")
                }
                Spacer(Modifier.width(8.dp))
                Button(
                    onClick = onApprove,
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                ) {
                    Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(16.dp))
                    Spacer(Modifier.width(4.dp))
                    Text("Approve")
                }
            }
        }
    }
}

