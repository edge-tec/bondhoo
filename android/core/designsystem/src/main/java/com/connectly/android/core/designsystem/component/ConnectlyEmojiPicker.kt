package com.connectly.android.core.designsystem.component

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Backspace
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.core.designsystem.theme.ConnectlyBlue

enum class EmojiCategory(val label: String, val icon: String, val emojis: List<String>) {
    SMILEYS(
        label = "Smileys",
        icon = "😀",
        emojis = listOf(
            "😀", "😃", "😄", "😁", "😆", "😅", "😂", "🤣", "🥲", "🥹",
            "😊", "😇", "🙂", "🙃", "😉", "😌", "😍", "🥰", "😘", "😗",
            "😙", "😚", "😋", "😛", "😝", "😜", "🤪", "🤨", "🧐", "🤓",
            "😎", "🥸", "🤩", "🥳", "😏", "😒", "😞", "😔", "😟", "😕",
            "🙁", "☹️", "😣", "😖", "😫", "😩", "🥺", "😢", "😭", "😮‍💨",
            "😤", "😠", "😡", "🤬", "🤯", "😳", "🥵", "🥶", "😱", "😨",
            "😰", "😥", "😓", "🤗", "🤔", "🫣", "🤭", "🫢", "🫡", "🤫",
            "🫠", "🤥", "😶", "😐", "😑", "😬", "🫨", "😯", "😦", "😧",
            "😮", "😲", "🥱", "😴", "🤤", "😪", "😵", "😵‍💫", "🤐", "🥴",
            "🤢", "🤮", "🤧", "😷", "🤒", "🤕", "🤑", "🤠", "😈", "👿",
            "🤡", "💩", "👻", "💀", "👽", "🤖"
        )
    ),
    GESTURES(
        label = "Gestures",
        icon = "👍",
        emojis = listOf(
            "👍", "👎", "👌", "🤌", "🤏", "✌️", "🤞", "🫰", "🤟", "🤘",
            "🤙", "👈", "👉", "👆", "🖕", "👇", "☝️", "🫵", "✋", "🤚",
            "🖐️", "🖖", "👋", "🤝", "🙏", "👏", "🙌", "👐", "🤲", "🤜",
            "🤛", "✊", "👊", "💪", "💅", "🤳", "🫶", "🙇", "🙋", "💁"
        )
    ),
    LOVE(
        label = "Love",
        icon = "❤️",
        emojis = listOf(
            "❤️", "🩷", "🧡", "💛", "💚", "💙", "🩵", "💜", "🖤", "🩶",
            "🤍", "🤎", "💔", "❤️‍🔥", "❤️‍🩹", "❣️", "💕", "💞", "💓", "💗",
            "💖", "💘", "💝", "💟", "💌", "💋", "💐", "🌹", "🥀", "🌺",
            "🌸", "🌼", "🌻", "🌷", "🪷", "🪻"
        )
    ),
    CELEBRATION(
        label = "Party",
        icon = "🎉",
        emojis = listOf(
            "🔥", "✨", "🌟", "⭐", "💫", "💥", "💯", "🎉", "🎊", "🎈",
            "🎂", "🎁", "🏆", "🥇", "🥈", "🥉", "👑", "💎", "🎯", "🎲",
            "🎮", "🕹️", "🎨", "🎭", "🎪", "🎤", "🎧", "🎼", "🎵", "🎶",
            "📢", "📣", "🔔", "🪅", "🥳", "🥂"
        )
    ),
    FOOD(
        label = "Food",
        icon = "🍕",
        emojis = listOf(
            "☕", "🍵", "🧋", "🥤", "🍺", "🍻", "🥂", "🍷", "🍾", "🍕",
            "🍔", "🍟", "🌭", "🍿", "🍩", "🍪", "🎂", "🍰", "🧁", "🍫",
            "🍬", "🍭", "🍦", "🍧", "🍨", "🍓", "🍒", "🍎", "🍉", "🍇",
            "🍌", "🥑", "🥐", "🧀", "🥞", "🧇", "🌮", "🌯", "🍣", "🍜"
        )
    ),
    ACTIVITIES(
        label = "Sports",
        icon = "⚽",
        emojis = listOf(
            "⚽", "🏀", "🏈", "⚾", "🥎", "🎾", "🏐", "🏉", "🥏", "🎱",
            "🏓", "🏸", "🏒", "🥊", "🥋", "🚴", "🏃", "🧘", "🏄", "🏊",
            "🧗", "🚗", "🏎️", "🏍️", "🛵", "🚲", "✈️", "🚀", "🏖️", "🏝️",
            "🏕️", "🌍", "⛺", "⛰️", "🏔️", "🌅", "🌆"
        )
    ),
    OBJECTS(
        label = "Objects",
        icon = "💡",
        emojis = listOf(
            "💡", "📱", "💻", "📷", "📸", "📹", "🔍", "🔎", "🔑", "🔒",
            "🔓", "💰", "💵", "💳", "📦", "✉️", "📝", "📌", "📍", "⏰",
            "⏳", "☀️", "🌙", "⭐", "🌈", "⚡", "❄️", "💧", "🌊", "✝️",
            "☪️", "🕉️", "✡️", "🕊️", "🌍", "🔮", "🧿"
        )
    )
}

/**
 * Quick emoji bar displayed above comment input for 1-tap reactions.
 */
@Composable
fun ConnectlyQuickEmojiRow(
    onEmojiSelected: (String) -> Unit,
    modifier: Modifier = Modifier,
    quickEmojis: List<String> = listOf("❤️", "🙌", "🔥", "👏", "😢", "😍", "😮", "😂", "👍", "🎉", "💯", "✨")
) {
    LazyRow(
        modifier = modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        horizontalArrangement = Arrangement.spacedBy(8.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        items(quickEmojis) { emoji ->
            Box(
                modifier = Modifier
                    .clip(CircleShape)
                    .background(Color(0xFFF1F5F9))
                    .clickable { onEmojiSelected(emoji) }
                    .padding(horizontal = 8.dp, vertical = 4.dp),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = emoji,
                    fontSize = 18.sp,
                    textAlign = TextAlign.Center
                )
            }
        }
    }
}

/**
 * Complete, rich emoji keyboard / picker supporting full categories and instant insertion.
 */
@Composable
fun ConnectlyEmojiPicker(
    onEmojiSelected: (String) -> Unit,
    modifier: Modifier = Modifier,
    onBackspace: (() -> Unit)? = null,
    onClose: (() -> Unit)? = null
) {
    var selectedCategory by remember { mutableStateOf(EmojiCategory.SMILEYS) }
    var searchQuery by remember { mutableStateOf("") }

    val displayedEmojis = remember(selectedCategory, searchQuery) {
        if (searchQuery.isBlank()) {
            selectedCategory.emojis
        } else {
            // Flatten all categories and filter
            val query = searchQuery.trim().lowercase()
            EmojiCategory.entries
                .flatMap { it.emojis }
                .distinct()
                .filter { emoji ->
                    // Match category label or common emoji associations
                    selectedCategory.label.lowercase().contains(query) ||
                            EmojiCategory.entries.any { cat ->
                                cat.label.lowercase().contains(query) && cat.emojis.contains(emoji)
                            }
                }
                .ifEmpty { selectedCategory.emojis }
        }
    }

    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(16.dp),
        color = MaterialTheme.colorScheme.surface,
        tonalElevation = 6.dp,
        shadowElevation = 4.dp
    ) {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(8.dp)
        ) {
            // Category Selector Tabs
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(6.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                EmojiCategory.entries.forEach { category ->
                    val isSelected = category == selectedCategory
                    FilterChip(
                        selected = isSelected,
                        onClick = {
                            selectedCategory = category
                            searchQuery = ""
                        },
                        label = {
                            Text(
                                text = "${category.icon} ${category.label}",
                                fontSize = 12.sp,
                                fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Normal
                            )
                        },
                        colors = FilterChipDefaults.filterChipColors(
                            selectedContainerColor = ConnectlyBlue.copy(alpha = 0.15f),
                            selectedLabelColor = ConnectlyBlue
                        )
                    )
                }
            }

            Spacer(modifier = Modifier.height(4.dp))

            // Emoji Grid
            LazyVerticalGrid(
                columns = GridCells.Fixed(7),
                modifier = Modifier
                    .weight(1f)
                    .fillMaxWidth(),
                contentPadding = PaddingValues(4.dp),
                horizontalArrangement = Arrangement.spacedBy(4.dp),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                items(displayedEmojis) { emoji ->
                    Box(
                        modifier = Modifier
                            .aspectRatio(1f)
                            .clip(RoundedCornerShape(8.dp))
                            .clickable { onEmojiSelected(emoji) },
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            text = emoji,
                            fontSize = 24.sp,
                            textAlign = TextAlign.Center
                        )
                    }
                }
            }

            // Bottom Action Bar (Close & Backspace)
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 4.dp, vertical = 2.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                if (onClose != null) {
                    TextButton(onClick = onClose) {
                        Icon(
                            imageVector = Icons.Default.Close,
                            contentDescription = "Close",
                            tint = Color.Gray,
                            modifier = Modifier.size(18.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text("Close", color = Color.Gray, fontSize = 12.sp)
                    }
                } else {
                    Spacer(modifier = Modifier.width(1.dp))
                }

                if (onBackspace != null) {
                    IconButton(
                        onClick = onBackspace,
                        modifier = Modifier
                            .clip(CircleShape)
                            .background(Color(0xFFF1F5F9))
                            .size(36.dp)
                    ) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.Backspace,
                            contentDescription = "Backspace",
                            tint = Color.DarkGray,
                            modifier = Modifier.size(18.dp)
                        )
                    }
                }
            }
        }
    }
}

