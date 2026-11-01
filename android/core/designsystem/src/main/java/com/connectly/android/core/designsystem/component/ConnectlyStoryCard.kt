package com.connectly.android.core.designsystem.component

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.*

@Composable
fun ConnectlyStoryCard(
    userName: String,
    avatarUrl: String?,
    isAddStory: Boolean = false,
    hasUnseenStory: Boolean = true,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    avatarSize: Dp = 68.dp
) {
    Column(
        horizontalAlignment = Alignment.CenterHorizontally,
        modifier = modifier
            .width(76.dp)
            .clickable(onClick = onClick)
            .padding(vertical = 4.dp)
    ) {
        Box(
            modifier = Modifier.size(avatarSize),
            contentAlignment = Alignment.Center
        ) {
            if (isAddStory) {
                // User Avatar with '+' Badge
                Box(
                    modifier = Modifier
                        .size(avatarSize)
                        .clip(CircleShape)
                        .border(2.dp, BorderLight, CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    if (!avatarUrl.isNullOrBlank()) {
                        AsyncImage(
                            model = avatarUrl,
                            contentDescription = "Your Avatar",
                            contentScale = ContentScale.Crop,
                            modifier = Modifier.fillMaxSize()
                        )
                    } else {
                        Box(
                            modifier = Modifier
                                .fillMaxSize()
                                .background(SurfaceSubtle),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = "You",
                                fontWeight = FontWeight.SemiBold,
                                color = TextSecondaryLight,
                                fontSize = 14.sp
                            )
                        }
                    }
                }

                // Plus Badge
                Box(
                    modifier = Modifier
                        .size(24.dp)
                        .align(Alignment.BottomEnd)
                        .clip(CircleShape)
                        .background(ConnectlyElectricBlue)
                        .border(2.dp, MaterialTheme.colorScheme.surface, CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = Icons.Default.Add,
                        contentDescription = "Add Story",
                        tint = Color.White,
                        modifier = Modifier.size(16.dp)
                    )
                }
            } else {
                // Friend Story Ring
                val ringModifier = if (hasUnseenStory) {
                    Modifier.border(2.5.dp, ConnectlyStoryRingGradient, CircleShape)
                } else {
                    Modifier.border(1.5.dp, BorderLight, CircleShape)
                }

                Box(
                    modifier = Modifier
                        .size(avatarSize)
                        .clip(CircleShape)
                        .then(ringModifier)
                        .padding(3.dp)
                        .clip(CircleShape),
                    contentAlignment = Alignment.Center
                ) {
                    if (!avatarUrl.isNullOrBlank()) {
                        AsyncImage(
                            model = avatarUrl,
                            contentDescription = userName,
                            contentScale = ContentScale.Crop,
                            modifier = Modifier.fillMaxSize()
                        )
                    } else {
                        Box(
                            modifier = Modifier
                                .fillMaxSize()
                                .background(ConnectlyPrimaryGradient),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(
                                text = userName.take(1).uppercase(),
                                color = Color.White,
                                fontWeight = FontWeight.Bold,
                                fontSize = 18.sp
                            )
                        }
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(6.dp))

        Text(
            text = if (isAddStory) "Add Story" else userName,
            style = MaterialTheme.typography.bodySmall,
            fontWeight = if (hasUnseenStory || isAddStory) FontWeight.SemiBold else FontWeight.Normal,
            color = TextPrimaryLight,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
            textAlign = TextAlign.Center
        )
    }
}

