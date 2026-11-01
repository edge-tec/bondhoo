package com.connectly.android.core.designsystem.component

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.core.Spring
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.spring
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.scaleIn
import androidx.compose.animation.scaleOut
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.interaction.collectIsPressedAsState
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.scale
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.core.designsystem.theme.*

enum class ConnectlyReaction(
    val emoji: String,
    val label: String,
    val color: Color
) {
    LIKE("👍", "Like", ReactionLikeBlue),
    LOVE("❤️", "Love", ReactionLoveRed),
    HAHA("😆", "Haha", ReactionHahaYellow),
    WOW("😮", "Wow", ReactionWowYellow),
    SAD("😢", "Sad", ReactionSadPurple),
    ANGRY("😡", "Angry", ReactionAngryOrange)
}

@Composable
fun ConnectlyReactionBar(
    isVisible: Boolean,
    onReactionSelect: (ConnectlyReaction) -> Unit,
    onDismiss: () -> Unit,
    modifier: Modifier = Modifier
) {
    AnimatedVisibility(
        visible = isVisible,
        enter = fadeIn() + scaleIn(spring(dampingRatio = Spring.DampingRatioMediumBouncy)),
        exit = fadeOut() + scaleOut(),
        modifier = modifier
    ) {
        Surface(
            shape = RoundedCornerShape(32.dp),
            color = MaterialTheme.colorScheme.surface,
            tonalElevation = 8.dp,
            shadowElevation = 8.dp,
            modifier = Modifier
                .border(1.dp, BorderLight, RoundedCornerShape(32.dp))
        ) {
            Row(
                modifier = Modifier
                    .padding(horizontal = 12.dp, vertical = 6.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalAlignment = Alignment.CenterVertically
            ) {
                ConnectlyReaction.entries.forEach { reaction ->
                    val interactionSource = remember { MutableInteractionSource() }
                    val isPressed by interactionSource.collectIsPressedAsState()
                    val scale by animateFloatAsState(
                        targetValue = if (isPressed) 1.35f else 1.0f,
                        label = "reaction_scale"
                    )

                    Box(
                        modifier = Modifier
                            .size(36.dp)
                            .scale(scale)
                            .clip(CircleShape)
                            .clickable(
                                interactionSource = interactionSource,
                                indication = ripple(bounded = false, radius = 20.dp)
                            ) {
                                onReactionSelect(reaction)
                                onDismiss()
                            },
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            text = reaction.emoji,
                            fontSize = 24.sp
                        )
                    }
                }
            }
        }
    }
}

