package com.connectly.android.feature.profile.presentation.memories

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.Schedule
import androidx.compose.material.icons.filled.Share
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.MemoryItemDto
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MemoriesScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    api: ConnectlyApi? = null
) {
    var isLoading by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    var memories by remember { mutableStateOf<List<MemoryItemDto>>(emptyList()) }
    val scope = rememberCoroutineScope()

    fun loadMemories() {
        if (api == null) return
        isLoading = true
        errorMessage = null
        scope.launch {
            try {
                val response = api.getMemories()
                if (response.isSuccessful && response.body() != null) {
                    memories = response.body()!!.data
                } else {
                    errorMessage = "Failed to load memories"
                }
            } catch (e: Exception) {
                errorMessage = e.localizedMessage ?: "Network error"
            } finally {
                isLoading = false
            }
        }
    }

    LaunchedEffect(Unit) {
        loadMemories()
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Memories",
                        fontWeight = FontWeight.Bold,
                        fontSize = 20.sp
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
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .background(MaterialTheme.colorScheme.background)
        ) {
            // Memories Hero Banner
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
                    .clip(RoundedCornerShape(16.dp))
                    .background(
                        Brush.horizontalGradient(
                            listOf(Color(0xFF6366F1), Color(0xFF8B5CF6))
                        )
                    )
                    .padding(18.dp)
            ) {
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(14.dp)
                ) {
                    Box(
                        modifier = Modifier
                            .size(46.dp)
                            .clip(RoundedCornerShape(12.dp))
                            .background(Color.White.copy(alpha = 0.2f)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.History,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(26.dp)
                        )
                    }
                    Column {
                        Text(
                            text = "On This Day",
                            fontSize = 17.sp,
                            fontWeight = FontWeight.Bold,
                            color = Color.White
                        )
                        Text(
                            text = "Look back on memories from this date in the past.",
                            fontSize = 12.sp,
                            color = Color.White.copy(alpha = 0.85f)
                        )
                    }
                }
            }

            Box(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 16.dp),
                contentAlignment = Alignment.Center
            ) {
                when {
                    isLoading -> {
                        CircularProgressIndicator(color = ConnectlyBlue)
                    }
                    errorMessage != null && memories.isEmpty() -> {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Text(
                                text = errorMessage ?: "Error loading memories",
                                color = MaterialTheme.colorScheme.error,
                                fontSize = 14.sp
                            )
                            Button(onClick = { loadMemories() }) {
                                Text("Retry")
                            }
                        }
                    }
                    memories.isEmpty() -> {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Icon(
                                imageVector = Icons.Default.Schedule,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.onSurfaceVariant,
                                modifier = Modifier.size(56.dp)
                            )
                            Text(
                                text = "No memories for today",
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                            Text(
                                text = "Check back tomorrow to see moments from past years!",
                                fontSize = 13.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                    else -> {
                        LazyColumn(
                            verticalArrangement = Arrangement.spacedBy(12.dp),
                            contentPadding = PaddingValues(vertical = 8.dp),
                            modifier = Modifier.fillMaxSize()
                        ) {
                            items(memories, key = { it.id }) { memory ->
                                Card(
                                    shape = RoundedCornerShape(14.dp),
                                    colors = CardDefaults.cardColors(
                                        containerColor = MaterialTheme.colorScheme.surface
                                    ),
                                    elevation = CardDefaults.cardElevation(defaultElevation = 0.5.dp),
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Column(modifier = Modifier.padding(14.dp)) {
                                        Row(
                                            verticalAlignment = Alignment.CenterVertically,
                                            horizontalArrangement = Arrangement.spacedBy(10.dp)
                                        ) {
                                            ConnectlyAvatar(
                                                imageUrl = memory.author_avatar,
                                                name = memory.author_name ?: "Me",
                                                size = 40.dp
                                            )
                                            Column(modifier = Modifier.weight(1f)) {
                                                Text(
                                                    text = memory.years_ago_label,
                                                    fontWeight = FontWeight.Bold,
                                                    fontSize = 14.sp,
                                                    color = ConnectlyBlue
                                                )
                                                Text(
                                                    text = memory.created_at_formatted ?: "",
                                                    fontSize = 12.sp,
                                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                                )
                                            }
                                        }

                                        if (memory.content.isNotBlank()) {
                                            Spacer(modifier = Modifier.height(10.dp))
                                            Text(
                                                text = memory.content,
                                                fontSize = 14.sp,
                                                color = MaterialTheme.colorScheme.onSurface
                                            )
                                        }

                                        Spacer(modifier = Modifier.height(10.dp))
                                        Divider(color = MaterialTheme.colorScheme.surfaceVariant)
                                        Spacer(modifier = Modifier.height(6.dp))

                                        Row(
                                            modifier = Modifier.fillMaxWidth(),
                                            horizontalArrangement = Arrangement.End
                                        ) {
                                            TextButton(onClick = { /* Share memory */ }) {
                                                Icon(
                                                    imageVector = Icons.Default.Share,
                                                    contentDescription = null,
                                                    modifier = Modifier.size(16.dp)
                                                )
                                                Spacer(modifier = Modifier.width(6.dp))
                                                Text("Share Memory", fontSize = 12.sp)
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

