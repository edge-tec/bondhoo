package com.connectly.android.core.designsystem.theme

import android.app.Activity
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.SideEffect
import androidx.compose.runtime.compositionLocalOf
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.toArgb
import androidx.compose.ui.platform.LocalView
import androidx.core.view.WindowCompat

val LocalReduceAnimations = compositionLocalOf { false }
val LocalCompactDisplay = compositionLocalOf { false }
val LocalFontScale = compositionLocalOf { 1.0f }

@Composable
fun ConnectlyTheme(
    themeMode: String = "system",
    accentColor: Color = ConnectlyBlue,
    fontScale: Float = 1.0f,
    reduceAnimations: Boolean = false,
    compactDisplay: Boolean = false,
    darkTheme: Boolean = when (themeMode) {
        "light" -> false
        "dark" -> true
        else -> isSystemInDarkTheme()
    },
    content: @Composable () -> Unit
) {
    val darkColorScheme = darkColorScheme(
        primary = accentColor,
        onPrimary = SurfaceLight,
        secondary = ConnectlyPurple,
        onSecondary = SurfaceLight,
        background = BackgroundDark,
        surface = SurfaceDark,
        onBackground = TextPrimaryDark,
        onSurface = TextPrimaryDark,
        outline = BorderDark
    )

    val lightColorScheme = lightColorScheme(
        primary = accentColor,
        onPrimary = SurfaceLight,
        secondary = ConnectlyPurple,
        onSecondary = SurfaceLight,
        background = BackgroundLight,
        surface = SurfaceLight,
        onBackground = TextPrimaryLight,
        onSurface = TextPrimaryLight,
        outline = BorderLight
    )

    val colorScheme = if (darkTheme) darkColorScheme else lightColorScheme
    val view = LocalView.current

    if (!view.isInEditMode && view.context is Activity) {
        SideEffect {
            val window = (view.context as Activity).window
            window.statusBarColor = colorScheme.surface.toArgb()
            WindowCompat.getInsetsController(window, view).isAppearanceLightStatusBars = !darkTheme
        }
    }

    CompositionLocalProvider(
        LocalReduceAnimations provides reduceAnimations,
        LocalCompactDisplay provides compactDisplay,
        LocalFontScale provides fontScale
    ) {
        MaterialTheme(
            colorScheme = colorScheme,
            typography = Typography,
            content = content
        )
    }
}
