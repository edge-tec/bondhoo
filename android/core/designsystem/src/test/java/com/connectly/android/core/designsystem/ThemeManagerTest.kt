package com.connectly.android.core.designsystem

import android.content.Context
import android.content.SharedPreferences
import android.content.res.Configuration
import android.content.res.Resources
import com.connectly.android.core.designsystem.theme.AvailableAccents
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.designsystem.theme.ConnectlyGreen
import com.connectly.android.core.designsystem.theme.ThemeManager
import io.mockk.*
import org.junit.Assert.*
import org.junit.Before
import org.junit.Test

class ThemeManagerTest {

    private val context: Context = mockk(relaxed = true)
    private val sharedPrefs: SharedPreferences = mockk(relaxed = true)
    private val prefsEditor: SharedPreferences.Editor = mockk(relaxed = true)
    private val resources: Resources = mockk(relaxed = true)
    private val configuration: Configuration = mockk(relaxed = true)

    private lateinit var themeManager: ThemeManager

    @Before
    fun setup() {
        every { context.getSharedPreferences(any(), any()) } returns sharedPrefs
        every { sharedPrefs.edit() } returns prefsEditor
        every { prefsEditor.putString(any(), any()) } returns prefsEditor
        every { prefsEditor.putFloat(any(), any()) } returns prefsEditor
        every { prefsEditor.putBoolean(any(), any()) } returns prefsEditor

        every { context.resources } returns resources
        every { resources.configuration } returns configuration

        themeManager = ThemeManager(context)
    }

    @Test
    fun testDefaultValues() {
        assertEquals("system", themeManager.themeMode.value)
        assertEquals("blue", themeManager.accentId.value)
        assertEquals(1.0f, themeManager.fontScale.value, 0.001f)
        assertFalse(themeManager.compactDisplay.value)
        assertFalse(themeManager.reduceAnimations.value)
        assertEquals("en", themeManager.language.value)
    }

    @Test
    fun testSetThemeMode() {
        themeManager.setThemeMode("dark")
        assertEquals("dark", themeManager.themeMode.value)
        verify { prefsEditor.putString("theme_mode", "dark") }

        themeManager.setThemeMode("light")
        assertEquals("light", themeManager.themeMode.value)
        verify { prefsEditor.putString("theme_mode", "light") }
    }

    @Test
    fun testSetAccentColor() {
        themeManager.setAccentId("green")
        assertEquals("green", themeManager.accentId.value)
        verify { prefsEditor.putString("accent_id", "green") }

        val currentAccent = themeManager.getCurrentAccent()
        assertEquals("Emerald Green", currentAccent.name)
        assertEquals(ConnectlyGreen, currentAccent.color)
    }

    @Test
    fun testFontScale() {
        themeManager.setFontScale(1.15f)
        assertEquals(1.15f, themeManager.fontScale.value, 0.001f)
        verify { prefsEditor.putFloat("font_scale", 1.15f) }
    }

    @Test
    fun testCompactAndAnimationToggles() {
        themeManager.setCompactDisplay(true)
        assertTrue(themeManager.compactDisplay.value)
        verify { prefsEditor.putBoolean("compact_display", true) }

        themeManager.setReduceAnimations(true)
        assertTrue(themeManager.reduceAnimations.value)
        verify { prefsEditor.putBoolean("reduce_animations", true) }
    }

    @Test
    fun testSetLanguage() {
        themeManager.setLanguage("bn")
        assertEquals("bn", themeManager.language.value)
        verify { prefsEditor.putString("language_code", "bn") }
    }

    @Test
    fun testAvailableAccentsList() {
        assertEquals(6, AvailableAccents.size)
        assertTrue(AvailableAccents.any { it.id == "blue" && it.color == ConnectlyBlue })
        assertTrue(AvailableAccents.any { it.id == "green" && it.color == ConnectlyGreen })
    }
}
