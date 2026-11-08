package com.connectly.android.core.designsystem

import com.connectly.android.core.designsystem.component.BottomBarDestination
import com.connectly.android.core.designsystem.component.ConnectlyReaction
import com.connectly.android.core.designsystem.component.EmojiCategory
import com.connectly.android.core.designsystem.theme.*
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertTrue
import org.junit.Test

class ConnectlyDesignSystemTest {

    @Test
    fun verify_brand_and_accent_colors() {
        assertNotNull(ConnectlyBlue)
        assertNotNull(ConnectlyElectricBlue)
        assertNotNull(ConnectlyIndigo)
        assertNotNull(ConnectlyPurple)
        assertNotNull(ConnectlyViolet)
        assertNotNull(ConnectlyRose)
        assertNotNull(ConnectlyGreen)
        assertNotNull(ConnectlyAmber)
    }

    @Test
    fun verify_brand_gradients() {
        assertNotNull(ConnectlyPrimaryGradient)
        assertNotNull(ConnectlyHeroGradient)
        assertNotNull(ConnectlyStoryRingGradient)
    }

    @Test
    fun verify_six_social_reactions() {
        val reactions = ConnectlyReaction.entries
        assertEquals(6, reactions.size)

        assertEquals("👍", ConnectlyReaction.LIKE.emoji)
        assertEquals("❤️", ConnectlyReaction.LOVE.emoji)
        assertEquals("😆", ConnectlyReaction.HAHA.emoji)
        assertEquals("😮", ConnectlyReaction.WOW.emoji)
        assertEquals("😢", ConnectlyReaction.SAD.emoji)
        assertEquals("😡", ConnectlyReaction.ANGRY.emoji)
    }

    @Test
    fun verify_bottom_navigation_destinations() {
        val destinations = BottomBarDestination.entries
        assertEquals(6, destinations.size)

        assertEquals("Home", BottomBarDestination.HOME.title)
        assertEquals("Friends", BottomBarDestination.FRIENDS.title)
        assertEquals("Create", BottomBarDestination.CREATE.title)
        assertEquals("Videos", BottomBarDestination.REELS.title)
        assertEquals("Messenger", BottomBarDestination.MESSENGER.title)
        assertEquals("Menu", BottomBarDestination.MENU.title)
    }

    @Test
    fun verify_emoji_categories_and_presets() {
        val categories = EmojiCategory.entries
        assertTrue(categories.size >= 7)

        val smileys = EmojiCategory.SMILEYS
        assertEquals("Smileys", smileys.label)
        assertTrue(smileys.emojis.contains("😀"))
        assertTrue(smileys.emojis.contains("😂"))
        assertTrue(smileys.emojis.contains("😍"))

        val gestures = EmojiCategory.GESTURES
        assertTrue(gestures.emojis.contains("👍"))
        assertTrue(gestures.emojis.contains("👏"))
        assertTrue(gestures.emojis.contains("🙏"))

        val love = EmojiCategory.LOVE
        assertTrue(love.emojis.contains("❤️"))

        val party = EmojiCategory.CELEBRATION
        assertTrue(party.emojis.contains("🔥"))
        assertTrue(party.emojis.contains("🎉"))

        val food = EmojiCategory.FOOD
        assertTrue(food.emojis.contains("🍕"))

        val sports = EmojiCategory.ACTIVITIES
        assertTrue(sports.emojis.contains("⚽"))

        val objects = EmojiCategory.OBJECTS
        assertTrue(objects.emojis.contains("💡"))
    }
}

