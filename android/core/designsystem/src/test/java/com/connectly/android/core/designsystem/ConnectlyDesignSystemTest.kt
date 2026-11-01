package com.connectly.android.core.designsystem

import com.connectly.android.core.designsystem.component.BottomBarDestination
import com.connectly.android.core.designsystem.component.ConnectlyReaction
import com.connectly.android.core.designsystem.theme.*
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
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
}

