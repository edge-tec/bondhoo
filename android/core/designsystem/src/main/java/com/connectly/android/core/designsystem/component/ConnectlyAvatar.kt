package com.connectly.android.core.designsystem.component

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Check
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen

fun resolveMediaUrl(url: String?): String? {
    if (url.isNullOrBlank()) return null
    val targetHost = "bondhoo.com"
    val sanitized = url.replace("localhost:8000", targetHost)
        .replace("127.0.0.1:8000", targetHost)
        .replace("10.0.2.2:8000", targetHost)
        .replace("192.168.0.184:8000", targetHost)
        .replace("http://bondhoo.com", "https://bondhoo.com")
    return when {
        sanitized.startsWith("https://") -> sanitized
        sanitized.startsWith("http://") -> sanitized.replace("http://", "https://")
        sanitized.startsWith("/") -> "https://$targetHost$sanitized"
        else -> "https://$targetHost/$sanitized"
    }
}

@Composable
fun ConnectlyAvatar(
    imageUrl: String?,
    name: String = "User",
    size: Dp = 40.dp,
    isOnline: Boolean = false,
    isVerified: Boolean = false,
    modifier: Modifier = Modifier
) {
    val resolvedUrl = resolveMediaUrl(imageUrl)

    Box(
        modifier = modifier.size(size),
        contentAlignment = Alignment.Center
    ) {
        // Base layer: Initials Avatar (always rendered as background/placeholder)
        Box(
            modifier = Modifier
                .size(size)
                .clip(CircleShape)
                .background(ConnectlyBlue),
            contentAlignment = Alignment.Center
        ) {
            Text(
                text = name.firstOrNull()?.uppercase() ?: "U",
                color = Color.White,
                fontWeight = FontWeight.Bold,
                fontSize = (size.value * 0.4).sp
            )
        }

        // Overlay: Real image loaded from remote URL
        if (!resolvedUrl.isNullOrBlank()) {
            AsyncImage(
                model = resolvedUrl,
                contentDescription = name,
                contentScale = ContentScale.Crop,
                modifier = Modifier
                    .size(size)
                    .clip(CircleShape)
            )
        }

        // Online presence indicator dot
        if (isOnline) {
            Box(
                modifier = Modifier
                    .size(size * 0.28f)
                    .align(Alignment.BottomEnd)
                    .clip(CircleShape)
                    .background(ConnectlyGreen)
                    .border(2.dp, MaterialTheme.colorScheme.surface, CircleShape)
            )
        }

        // Verified blue badge
        if (isVerified && !isOnline) {
            Box(
                modifier = Modifier
                    .size(size * 0.32f)
                    .align(Alignment.BottomEnd)
                    .clip(CircleShape)
                    .background(ConnectlyBlue)
                    .border(1.5.dp, MaterialTheme.colorScheme.surface, CircleShape),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = Icons.Default.Check,
                    contentDescription = "Verified",
                    tint = Color.White,
                    modifier = Modifier.size(size * 0.2f)
                )
            }
        }
    }
}

