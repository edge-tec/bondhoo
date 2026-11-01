package com.connectly.android

import com.connectly.android.core.designsystem.component.BottomBarDestination
import com.connectly.android.core.designsystem.component.ConnectlyReaction
import com.connectly.android.feature.auth.navigation.LOGIN_ROUTE
import com.connectly.android.feature.auth.navigation.ONBOARDING_ROUTE
import com.connectly.android.feature.auth.navigation.REGISTER_ROUTE
import com.connectly.android.feature.auth.navigation.SPLASH_ROUTE
import com.connectly.android.feature.groups.navigation.GROUPS_ROUTE
import com.connectly.android.feature.groups.navigation.PAGES_ROUTE
import com.connectly.android.feature.profile.navigation.FRIENDS_ROUTE
import com.connectly.android.feature.profile.navigation.PROFILE_ROUTE
import com.connectly.android.feature.profile.navigation.SETTINGS_ROUTE
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Test

class ConnectlyPhase2UiArchitectureTest {

    @Test
    fun verify_all_auth_and_onboarding_routes() {
        assertEquals("auth_splash", SPLASH_ROUTE)
        assertEquals("auth_onboarding", ONBOARDING_ROUTE)
        assertEquals("auth_login", LOGIN_ROUTE)
        assertEquals("auth_register", REGISTER_ROUTE)
    }

    @Test
    fun verify_main_screens_routes() {
        assertEquals("main_home", MAIN_HOME_ROUTE)
        assertEquals("profile_route", PROFILE_ROUTE)
        assertEquals("friends_route", FRIENDS_ROUTE)
        assertEquals("settings_route", SETTINGS_ROUTE)
        assertEquals("groups_route", GROUPS_ROUTE)
        assertEquals("pages_route", PAGES_ROUTE)
    }

    @Test
    fun verify_bottom_navigation_specifications() {
        val tabs = BottomBarDestination.entries
        assertEquals(6, tabs.size)
        assertNotNull(tabs.find { it == BottomBarDestination.HOME })
        assertNotNull(tabs.find { it == BottomBarDestination.FRIENDS })
        assertNotNull(tabs.find { it == BottomBarDestination.CREATE })
        assertNotNull(tabs.find { it == BottomBarDestination.REELS })
        assertNotNull(tabs.find { it == BottomBarDestination.MESSENGER })
        assertNotNull(tabs.find { it == BottomBarDestination.MENU })
    }

    @Test
    fun verify_social_reactions_mapping() {
        assertEquals("👍", ConnectlyReaction.LIKE.emoji)
        assertEquals("❤️", ConnectlyReaction.LOVE.emoji)
        assertEquals("😆", ConnectlyReaction.HAHA.emoji)
        assertEquals("😮", ConnectlyReaction.WOW.emoji)
        assertEquals("😢", ConnectlyReaction.SAD.emoji)
        assertEquals("😡", ConnectlyReaction.ANGRY.emoji)
    }
}

