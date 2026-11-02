package com.connectly.android.core.designsystem.theme

import android.content.Context
import androidx.appcompat.app.AppCompatDelegate
import androidx.compose.ui.graphics.Color
import androidx.core.os.LocaleListCompat
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import java.util.Locale
import javax.inject.Inject
import javax.inject.Singleton

data class AccentColorOption(
    val id: String,
    val name: String,
    val color: Color,
    val hex: String
)

val AvailableAccents = listOf(
    AccentColorOption("blue", "Connectly Blue", ConnectlyBlue, "#1877F2"),
    AccentColorOption("indigo", "Electric Indigo", ConnectlyIndigo, "#4F46E5"),
    AccentColorOption("purple", "Royal Purple", ConnectlyPurple, "#7C3AED"),
    AccentColorOption("green", "Emerald Green", ConnectlyGreen, "#10B981"),
    AccentColorOption("rose", "Crimson Rose", ConnectlyRose, "#E11D48"),
    AccentColorOption("amber", "Warm Amber", ConnectlyAmber, "#F59E0B")
)

@Singleton
class ThemeManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    private val prefs = context.getSharedPreferences("connectly_appearance_prefs", Context.MODE_PRIVATE)

    private val _themeMode = MutableStateFlow(prefs.getString("theme_mode", "system") ?: "system")
    val themeMode: StateFlow<String> = _themeMode.asStateFlow()

    private val _accentId = MutableStateFlow(prefs.getString("accent_id", "blue") ?: "blue")
    val accentId: StateFlow<String> = _accentId.asStateFlow()

    private val _fontScale = MutableStateFlow(prefs.getFloat("font_scale", 1.0f))
    val fontScale: StateFlow<Float> = _fontScale.asStateFlow()

    private val _compactDisplay = MutableStateFlow(prefs.getBoolean("compact_display", false))
    val compactDisplay: StateFlow<Boolean> = _compactDisplay.asStateFlow()

    private val _reduceAnimations = MutableStateFlow(prefs.getBoolean("reduce_animations", false))
    val reduceAnimations: StateFlow<Boolean> = _reduceAnimations.asStateFlow()

    private val _language = MutableStateFlow(prefs.getString("language_code", "en") ?: "en")
    val language: StateFlow<String> = _language.asStateFlow()

    init {
        // Apply saved locale on startup
        applyLocale(_language.value, persist = false)
    }

    fun setThemeMode(mode: String) {
        _themeMode.value = mode
        prefs.edit().putString("theme_mode", mode).apply()
    }

    fun setAccentId(id: String) {
        _accentId.value = id
        prefs.edit().putString("accent_id", id).apply()
    }

    fun setFontScale(scale: Float) {
        _fontScale.value = scale
        prefs.edit().putFloat("font_scale", scale).apply()
    }

    fun setCompactDisplay(compact: Boolean) {
        _compactDisplay.value = compact
        prefs.edit().putBoolean("compact_display", compact).apply()
    }

    fun setReduceAnimations(reduce: Boolean) {
        _reduceAnimations.value = reduce
        prefs.edit().putBoolean("reduce_animations", reduce).apply()
    }

    fun setLanguage(langCode: String) {
        _language.value = langCode
        prefs.edit().putString("language_code", langCode).apply()
        applyLocale(langCode, persist = true)
    }

    fun getCurrentAccent(): AccentColorOption {
        return AvailableAccents.firstOrNull { it.id == _accentId.value } ?: AvailableAccents.first()
    }

    private fun applyLocale(langCode: String, persist: Boolean) {
        try {
            val locale = Locale(langCode)
            Locale.setDefault(locale)
            val appLocale = LocaleListCompat.forLanguageTags(langCode)
            AppCompatDelegate.setApplicationLocales(appLocale)

            val config = context.resources.configuration
            config.setLocale(locale)
            context.createConfigurationContext(config)
        } catch (_: Exception) {
        }
    }
}
