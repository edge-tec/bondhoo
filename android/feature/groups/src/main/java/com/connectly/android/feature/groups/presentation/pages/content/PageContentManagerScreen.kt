package com.connectly.android.feature.groups.presentation.pages.content

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material.icons.outlined.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

data class ContentItem(
    val id: Long,
    val title: String,
    val type: String, // "Post", "Reels", "Video", "Story", "Live"
    val status: String, // "Published", "Scheduled", "Draft"
    val date: String,
    val reach: Int = 0
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun PageContentManagerScreen(
    pageId: Long,
    onNavigateBack: () -> Unit = {},
    modifier: Modifier = Modifier
) {
    var selectedTypeFilter by remember { mutableStateOf("All") }
    var showCreatePostDialog by remember { mutableStateOf(false) }
    var postText by remember { mutableStateOf("") }

    val sampleContent = remember {
        mutableStateListOf(
            ContentItem(1, "New Tech Gadget Launch in Bangladesh", "Post", "Published", "2 hours ago", 14200),
            ContentItem(2, "Behind the scenes video tour", "Reels", "Published", "Yesterday", 28900),
            ContentItem(3, "Weekly Q&A and Tech Trends", "Post", "Scheduled", "Tomorrow, 6:00 PM", 0),
            ContentItem(4, "Product launch announcement draft", "Post", "Draft", "Saved today", 0)
        )
    }

    if (showCreatePostDialog) {
        AlertDialog(
            onDismissRequest = { showCreatePostDialog = false },
            title = { Text("Publish Page Post", fontWeight = FontWeight.Bold) },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    OutlinedTextField(
                        value = postText,
                        onValueChange = { postText = it },
                        label = { Text("Post content / announcement...") },
                        maxLines = 5,
                        modifier = Modifier.fillMaxWidth()
                    )
                }
            },
            confirmButton = {
                Button(
                    onClick = {
                        if (postText.isNotBlank()) {
                            sampleContent.add(
                                0,
                                ContentItem(System.currentTimeMillis(), postText, "Post", "Published", "Just now", 0)
                            )
                            postText = ""
                            showCreatePostDialog = false
                        }
                    }
                ) {
                    Text("Publish Now")
                }
            },
            dismissButton = {
                TextButton(onClick = { showCreatePostDialog = false }) {
                    Text("Cancel")
                }
            }
        )
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Page Content Hub", fontWeight = FontWeight.Bold) },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Back")
                    }
                }
            )
        },
        floatingActionButton = {
            FloatingActionButton(
                onClick = { showCreatePostDialog = true },
                containerColor = MaterialTheme.colorScheme.primary
            ) {
                Icon(Icons.Default.Add, contentDescription = "Create Content")
            }
        },
        modifier = modifier
    ) { paddingValues ->
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(horizontal = 16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Type Filter Row
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    listOf("All", "Posts", "Reels", "Videos", "Scheduled", "Drafts").forEach { filter ->
                        FilterChip(
                            selected = selectedTypeFilter == filter,
                            onClick = { selectedTypeFilter = filter },
                            label = { Text(filter, fontSize = 12.sp) }
                        )
                    }
                }
            }

            // Content List Items
            items(sampleContent.size) { idx ->
                val item = sampleContent[idx]
                Card(
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier.padding(14.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column(modifier = Modifier.weight(1f)) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Surface(
                                    color = when (item.status) {
                                        "Published" -> Color(0xFFDCFCE7)
                                        "Scheduled" -> Color(0xFFFEF3C7)
                                        else -> Color(0xFFF1F5F9)
                                    },
                                    shape = RoundedCornerShape(4.dp)
                                ) {
                                    Text(
                                        text = item.status.uppercase(),
                                        fontSize = 10.sp,
                                        fontWeight = FontWeight.Bold,
                                        color = when (item.status) {
                                            "Published" -> Color(0xFF166534)
                                            "Scheduled" -> Color(0xFFB45309)
                                            else -> Color(0xFF475569)
                                        },
                                        modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp)
                                    )
                                }
                                Spacer(modifier = Modifier.width(8.dp))
                                Text(item.type, fontSize = 11.sp, color = MaterialTheme.colorScheme.primary, fontWeight = FontWeight.SemiBold)
                            }
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(item.title, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                            Spacer(modifier = Modifier.height(2.dp))
                            Text("${item.date} • ${item.reach} reach", fontSize = 11.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }

                        IconButton(onClick = { }) {
                            Icon(Icons.Default.MoreVert, contentDescription = "Options")
                        }
                    }
                }
            }
        }
    }
}
