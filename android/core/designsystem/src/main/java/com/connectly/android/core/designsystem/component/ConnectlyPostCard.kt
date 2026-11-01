package com.connectly.android.core.designsystem.component

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Comment
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.filled.MoreVert
import androidx.compose.material.icons.filled.Public
import androidx.compose.material.icons.filled.ThumbUp
import androidx.compose.material.icons.outlined.ThumbUp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.*

@Composable
fun ConnectlyPostCard(
    authorName: String,
    authorAvatarUrl: String?,
    authorVerified: Boolean = false,
    timestamp: String,
    privacyIcon: String = "🌐",
    content: String,
    mediaUrls: List<String> = emptyList(),
    likesCount: Int = 0,
    commentsCount: Int = 0,
    sharesCount: Int = 0,
    userReaction: ConnectlyReaction? = null,
    onReactionSelect: (ConnectlyReaction) -> Unit = {},
    onCommentClick: () -> Unit = {},
    onShareClick: () -> Unit = {},
    onAuthorClick: () -> Unit = {},
    onMoreOptionsClick: () -> Unit = {},
    onPhotoClick: (Int) -> Unit = {},
    modifier: Modifier = Modifier
) {
    var isReactionBarVisible by remember { mutableStateOf(false) }

    Card(
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp),
        modifier = modifier
            .fillMaxWidth()
            .border(1.dp, BorderLight, RoundedCornerShape(16.dp))
    ) {
        Column(modifier = Modifier.padding(vertical = 12.dp)) {
            // Header: Author Avatar, Name, Timestamp & Privacy, 3-dots
            Row(
                verticalAlignment = Alignment.CenterVertically,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp)
            ) {
                ConnectlyAvatar(
                    imageUrl = authorAvatarUrl,
                    name = authorName,
                    size = 42.dp,
                    isVerified = authorVerified,
                    modifier = Modifier.clickable(onClick = onAuthorClick)
                )

                Spacer(modifier = Modifier.width(10.dp))

                Column(
                    modifier = Modifier
                        .weight(1f)
                        .clickable(onClick = onAuthorClick)
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            text = authorName,
                            fontWeight = FontWeight.Bold,
                            fontSize = 15.sp,
                            color = TextPrimaryLight
                        )
                    }

                    Row(
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(4.dp)
                    ) {
                        Text(
                            text = timestamp,
                            fontSize = 12.sp,
                            color = TextSecondaryLight
                        )
                        Text(
                            text = "•",
                            fontSize = 12.sp,
                            color = TextSecondaryLight
                        )
                        Text(
                            text = privacyIcon,
                            fontSize = 11.sp,
                            color = TextSecondaryLight
                        )
                    }
                }

                IconButton(onClick = onMoreOptionsClick) {
                    Icon(
                        imageVector = Icons.Default.MoreVert,
                        contentDescription = "Post Options",
                        tint = TextSecondaryLight
                    )
                }
            }

            // Post Text Content
            if (content.isNotBlank()) {
                Text(
                    text = content,
                    style = MaterialTheme.typography.bodyLarge,
                    color = TextPrimaryLight,
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 16.dp, vertical = 8.dp)
                )
            }

            // Media Gallery (Single, Dual, Multi-Photo)
            if (mediaUrls.isNotEmpty()) {
                PostPhotoGallery(
                    mediaUrls = mediaUrls,
                    onPhotoClick = onPhotoClick,
                    modifier = Modifier.padding(top = 4.dp, bottom = 8.dp)
                )
            }

            // Reaction Bar Overlay
            Box(modifier = Modifier.fillMaxWidth()) {
                if (isReactionBarVisible) {
                    ConnectlyReactionBar(
                        isVisible = isReactionBarVisible,
                        onReactionSelect = { reaction ->
                            onReactionSelect(reaction)
                            isReactionBarVisible = false
                        },
                        onDismiss = { isReactionBarVisible = false },
                        modifier = Modifier
                            .padding(start = 16.dp, bottom = 4.dp)
                            .align(Alignment.BottomStart)
                    )
                }
            }

            // Reaction & Comments Stats Row
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 4.dp)
            ) {
                // Left: Reaction icons and count
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(4.dp)
                ) {
                    if (likesCount > 0) {
                        Text(
                            text = "❤️👍😮",
                            fontSize = 13.sp
                        )
                        Text(
                            text = if (likesCount >= 1000) String.format("%.1fK", likesCount / 1000.0) else "$likesCount",
                            fontSize = 13.sp,
                            color = TextSecondaryLight,
                            fontWeight = FontWeight.Medium
                        )
                    }
                }

                // Right: Comments & shares
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    if (commentsCount > 0) {
                        Text(
                            text = "$commentsCount comments",
                            fontSize = 13.sp,
                            color = TextSecondaryLight
                        )
                    }
                    if (sharesCount > 0) {
                        Text(
                            text = "$sharesCount shares",
                            fontSize = 13.sp,
                            color = TextSecondaryLight
                        )
                    }
                }
            }

            HorizontalDivider(
                color = BorderLight,
                thickness = 0.8.dp,
                modifier = Modifier.padding(horizontal = 16.dp, vertical = 6.dp)
            )

            // Action Buttons: Like, Comment, Share
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 8.dp),
                horizontalArrangement = Arrangement.SpaceEvenly
            ) {
                // Like Button
                TextButton(
                    onClick = {
                        if (userReaction == null) {
                            onReactionSelect(ConnectlyReaction.LIKE)
                        } else {
                            isReactionBarVisible = !isReactionBarVisible
                        }
                    },
                    modifier = Modifier.weight(1f)
                ) {
                    val iconColor = userReaction?.color ?: TextSecondaryLight
                    Text(
                        text = userReaction?.emoji ?: "👍",
                        fontSize = 16.sp
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = userReaction?.label ?: "Like",
                        color = iconColor,
                        fontWeight = if (userReaction != null) FontWeight.Bold else FontWeight.SemiBold,
                        fontSize = 13.sp
                    )
                }

                // Comment Button
                TextButton(
                    onClick = onCommentClick,
                    modifier = Modifier.weight(1f)
                ) {
                    Icon(
                        imageVector = Icons.AutoMirrored.Filled.Comment,
                        contentDescription = "Comment",
                        tint = TextSecondaryLight,
                        modifier = Modifier.size(18.dp)
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = "Comment",
                        color = TextSecondaryLight,
                        fontWeight = FontWeight.SemiBold,
                        fontSize = 13.sp
                    )
                }

                // Share Button
                TextButton(
                    onClick = onShareClick,
                    modifier = Modifier.weight(1f)
                ) {
                    Icon(
                        imageVector = Icons.AutoMirrored.Filled.Send,
                        contentDescription = "Share",
                        tint = TextSecondaryLight,
                        modifier = Modifier.size(18.dp)
                    )
                    Spacer(modifier = Modifier.width(6.dp))
                    Text(
                        text = "Share",
                        color = TextSecondaryLight,
                        fontWeight = FontWeight.SemiBold,
                        fontSize = 13.sp
                    )
                }
            }
        }
    }
}

@Composable
fun PostPhotoGallery(
    mediaUrls: List<String>,
    onPhotoClick: (Int) -> Unit,
    modifier: Modifier = Modifier
) {
    when (mediaUrls.size) {
        1 -> {
            AsyncImage(
                model = mediaUrls[0],
                contentDescription = "Post photo",
                contentScale = ContentScale.Crop,
                modifier = modifier
                    .fillMaxWidth()
                    .height(280.dp)
                    .clip(RoundedCornerShape(8.dp))
                    .clickable { onPhotoClick(0) }
            )
        }
        2 -> {
            Row(
                modifier = modifier
                    .fillMaxWidth()
                    .height(220.dp),
                horizontalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                AsyncImage(
                    model = mediaUrls[0],
                    contentDescription = null,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier
                        .weight(1f)
                        .fillMaxHeight()
                        .clip(RoundedCornerShape(topStart = 8.dp, bottomStart = 8.dp))
                        .clickable { onPhotoClick(0) }
                )
                AsyncImage(
                    model = mediaUrls[1],
                    contentDescription = null,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier
                        .weight(1f)
                        .fillMaxHeight()
                        .clip(RoundedCornerShape(topEnd = 8.dp, bottomEnd = 8.dp))
                        .clickable { onPhotoClick(1) }
                )
            }
        }
        else -> {
            // Multi-image layout (3 or 4+)
            Column(
                modifier = modifier
                    .fillMaxWidth()
                    .height(280.dp),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                AsyncImage(
                    model = mediaUrls[0],
                    contentDescription = null,
                    contentScale = ContentScale.Crop,
                    modifier = Modifier
                        .fillMaxWidth()
                        .weight(1.2f)
                        .clip(RoundedCornerShape(topStart = 8.dp, topEnd = 8.dp))
                        .clickable { onPhotoClick(0) }
                )
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .weight(1f),
                    horizontalArrangement = Arrangement.spacedBy(4.dp)
                ) {
                    AsyncImage(
                        model = mediaUrls[1],
                        contentDescription = null,
                        contentScale = ContentScale.Crop,
                        modifier = Modifier
                            .weight(1f)
                            .fillMaxHeight()
                            .clip(RoundedCornerShape(bottomStart = 8.dp))
                            .clickable { onPhotoClick(1) }
                    )
                    Box(
                        modifier = Modifier
                            .weight(1f)
                            .fillMaxHeight()
                            .clip(RoundedCornerShape(bottomEnd = 8.dp))
                            .clickable { onPhotoClick(2) }
                    ) {
                        AsyncImage(
                            model = mediaUrls[2],
                            contentDescription = null,
                            contentScale = ContentScale.Crop,
                            modifier = Modifier.fillMaxSize()
                        )
                        if (mediaUrls.size > 3) {
                            Box(
                                modifier = Modifier
                                    .fillMaxSize()
                                    .background(Color.Black.copy(alpha = 0.5f)),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    text = "+${mediaUrls.size - 3}",
                                    color = Color.White,
                                    fontSize = 20.sp,
                                    fontWeight = FontWeight.Bold
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

