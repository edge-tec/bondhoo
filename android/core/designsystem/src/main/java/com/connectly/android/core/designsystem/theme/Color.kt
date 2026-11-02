package com.connectly.android.core.designsystem.theme

import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color

// Connectly Brand Colors & Electric Blue Accents
val ConnectlyBlue = Color(0xFF1877F2)
val ConnectlyElectricBlue = Color(0xFF2563EB)
val ConnectlyBlueDark = Color(0xFF1D4ED8)
val ConnectlyIndigo = Color(0xFF4F46E5)
val ConnectlyPurple = Color(0xFF7C3AED)
val ConnectlyViolet = Color(0xFF8B5CF6)
val ConnectlyRose = Color(0xFFE11D48)
val ConnectlyRed = Color(0xFFEF4444)
val ConnectlyGreen = Color(0xFF10B981)
val ConnectlyAmber = Color(0xFFF59E0B)

// Reactions Palette
val ReactionLikeBlue = Color(0xFF1877F2)
val ReactionLoveRed = Color(0xFFE11D48)
val ReactionHahaYellow = Color(0xFFF59E0B)
val ReactionWowYellow = Color(0xFFFBBF24)
val ReactionSadPurple = Color(0xFF6366F1)
val ReactionAngryOrange = Color(0xFFEA580C)

// Gradients (Indigo, Violet, Electric Blue)
val ConnectlyPrimaryGradient = Brush.horizontalGradient(
    listOf(ConnectlyElectricBlue, ConnectlyPurple)
)

val ConnectlyHeroGradient = Brush.linearGradient(
    listOf(ConnectlyIndigo, ConnectlyViolet)
)

val ConnectlyStoryRingGradient = Brush.sweepGradient(
    listOf(
        Color(0xFFF59E0B),
        Color(0xFFE11D48),
        Color(0xFF8B5CF6),
        Color(0xFF2563EB),
        Color(0xFFF59E0B)
    )
)

// Neutral & Background Colors (Light Theme - Premium White)
val BackgroundLight = Color(0xFFF8FAFC)
val SurfaceLight = Color(0xFFFFFFFF)
val SurfaceSubtle = Color(0xFFF1F5F9)
val TextPrimaryLight = Color(0xFF0F172A)
val TextSecondaryLight = Color(0xFF64748B)
val TextMutedLight = Color(0xFF94A3B8)
val BorderLight = Color(0xFFE2E8F0)

// Neutral & Background Colors (Dark Theme)
val BackgroundDark = Color(0xFF0F172A)
val SurfaceDark = Color(0xFF1E293B)
val SurfaceDarkSubtle = Color(0xFF334155)
val TextPrimaryDark = Color(0xFFF8FAFC)
val TextSecondaryDark = Color(0xFF94A3B8)
val BorderDark = Color(0xFF334155)
