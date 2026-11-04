package com.connectly.android.feature.profile.presentation.saved

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Bookmark
import androidx.compose.material.icons.filled.BookmarkRemove
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.core.designsystem.component.ConnectlyAvatar
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.SavedItemDto
import com.connectly.android.core.network.api.ToggleSaveItemPayload
import kotlinx.coroutines.launch

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SavedPostsScreen(
    onNavigateBack: () -> Unit,
    modifier: Modifier = Modifier,
    api: ConnectlyApi? = null
) {
    var selectedFilter by remember { mutableStateOf("All") }
    var isLoading by remember { mutableStateOf(false) }
    var errorMessage by remember { mutableStateOf<String?>(null) }
    var savedItems by remember { mutableStateOf<List<SavedItemDto>>(emptyList()) }
    val scope = rememberCoroutineScope()

    val filters = listOf("All", "Posts", "Articles", "Reels")

    fun loadSavedItems() {
        if (api == null) return
        isLoading = true
        errorMessage = null
        scope.launch {
            try {
                val collectionQuery = if (selectedFilter == "All") null else selectedFilter
                val response = api.getSavedItems(collection = collectionQuery)
                if (response.isSuccessful && response.body() != null) {
                    savedItems = response.body()!!.data
                } else {
                    errorMessage = "Failed to load saved items"
                }
            } catch (e: Exception) {
                errorMessage = e.localizedMessage ?: "Network error"
            } finally {
                isLoading = false
            }
        }
    }

    LaunchedEffect(selectedFilter) {
        loadSavedItems()
    }

    fun unsaveItem(item: SavedItemDto) {
        if (api == null) return
        scope.launch {
            try {
                api.toggleSavePost(ToggleSaveItemPayload(item_type = item.item_type, item_id = item.item_id))
                savedItems = savedItems.filter { it.id != item.id }
            } catch (_: Exception) {
            }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Saved Posts",
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
            // Filter Tabs
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                filters.forEach { filter ->
                    val isSelected = selectedFilter == filter
                    FilterChip(
                        selected = isSelected,
                        onClick = { selectedFilter = filter },
                        label = { Text(filter, fontSize = 13.sp) },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = ConnectlyBlue.copy(alpha = 0.15f),
                            selectedLabelColor = ConnectlyBlue
                        )
                    )
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
                    errorMessage != null && savedItems.isEmpty() -> {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Text(
                                text = errorMessage ?: "Error loading saved items",
                                color = MaterialTheme.colorScheme.error,
                                fontSize = 14.sp
                            )
                            Button(onClick = { loadSavedItems() }) {
                                Text("Retry")
                            }
                        }
                    }
                    savedItems.isEmpty() -> {
                        Column(
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            Icon(
                                imageVector = Icons.Default.Bookmark,
                                contentDescription = null,
                                tint = MaterialTheme.colorScheme.onSurfaceVariant,
                                modifier = Modifier.size(56.dp)
                            )
                            Text(
                                text = "No saved posts yet",
                                fontWeight = FontWeight.Bold,
                                fontSize = 16.sp,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                            Text(
                                text = "Save posts, articles, and videos to view them later.",
                                fontSize = 13.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }
                    }
                    else -> {
                        LazyColumn(
                            verticalArrangement = Arrangement.spacedBy(10.dp),
                            contentPadding = PaddingValues(vertical = 8.dp),
                            modifier = Modifier.fillMaxSize()
                        ) {
                            items(savedItems, key = { it.id }) { item ->
                                val post = item.post
                                Card(
                                    shape = RoundedCornerShape(12.dp),
                                    colors = CardDefaults.cardColors(
                                        containerColor = MaterialTheme.colorScheme.surface
                                    ),
                                    elevation = CardDefaults.cardElevation(defaultElevation = 0.5.dp),
                                    modifier = Modifier.fillMaxWidth()
                                ) {
                                    Row(
                                        modifier = Modifier
                                            .fillMaxWidth()
                                            .padding(12.dp),
                                        verticalAlignment = Alignment.CenterVertically,
                                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                                    ) {
                                        ConnectlyAvatar(
                                            imageUrl = post?.author_avatar,
                                            name = post?.author_name ?: "Connectly User",
                                            size = 44.dp
                                        )

                                        Column(modifier = Modifier.weight(1f)) {
                                            Text(
                                                text = post?.author_name ?: "Connectly Post",
                                                fontWeight = FontWeight.Bold,
                                                fontSize = 14.sp,
                                                color = MaterialTheme.colorScheme.onSurface
                                            )
                                            if (!post?.content.isNullOrBlank()) {
                                                Text(
                                                    text = post?.content ?: "",
                                                    fontSize = 13.sp,
                                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                                    maxLines = 2,
                                                    overflow = TextOverflow.Ellipsis
                                                )
                                            }
                                            Text(
                                                text = item.collection_name ?: "All Items",
                                                fontSize = 11.sp,
                                                fontWeight = FontWeight.Medium,
                                                color = ConnectlyBlue,
                                                modifier = Modifier.padding(top = 2.dp)
                                            )
                                        }

                                        IconButton(onClick = { unsaveItem(item) }) {
                                            Icon(
                                                imageVector = Icons.Default.BookmarkRemove,
                                                contentDescription = "Remove from Saved",
                                                tint = MaterialTheme.colorScheme.error
                                            )
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

