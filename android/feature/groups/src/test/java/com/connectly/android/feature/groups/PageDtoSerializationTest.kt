package com.connectly.android.feature.groups

import com.connectly.android.core.network.api.*
import kotlinx.serialization.json.Json
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

class PageDtoSerializationTest {

    private val json = Json {
        ignoreUnknownKeys = true
        isLenient = true
        coerceInputValues = true
        encodeDefaults = true
    }

    @Test
    fun `deserializes page with null social_links and null business_hours`() {
        val payload = """
            {
                "id": 1,
                "name": "Acme Tech",
                "category": "Technology",
                "social_links": null,
                "business_hours": null
            }
        """.trimIndent()

        val dto = json.decodeFromString<PageDto>(payload)
        assertEquals(1L, dto.id)
        assertEquals("Acme Tech", dto.name)
        assertTrue(dto.resolvedSocialLinks.isEmpty())
        assertNull(dto.business_hours)
    }

    @Test
    fun `deserializes page with empty array social_links`() {
        val payload = """
            {
                "id": 2,
                "name": "Design Studio",
                "category": "Design",
                "social_links": []
            }
        """.trimIndent()

        val dto = json.decodeFromString<PageDto>(payload)
        assertEquals(2L, dto.id)
        assertTrue(dto.resolvedSocialLinks.isEmpty())
    }

    @Test
    fun `deserializes page with list of string social_links`() {
        val payload = """
            {
                "id": 3,
                "name": "Global Media",
                "category": "Media",
                "social_links": ["https://twitter.com/global", "https://linkedin.com/global"]
            }
        """.trimIndent()

        val dto = json.decodeFromString<PageDto>(payload)
        assertEquals(2, dto.resolvedSocialLinks.size)
        assertEquals("https://twitter.com/global", dto.resolvedSocialLinks[0].url)
        assertEquals("https://linkedin.com/global", dto.resolvedSocialLinks[1].url)
    }

    @Test
    fun `deserializes page with structured list of object social_links`() {
        val payload = """
            {
                "id": 4,
                "name": "Enterprise BD",
                "category": "Corporate",
                "social_links": [
                    {"platform": "facebook", "url": "https://facebook.com/enterprise", "is_visible": true},
                    {"platform": "github", "url": "https://github.com/enterprise", "is_visible": false}
                ]
            }
        """.trimIndent()

        val dto = json.decodeFromString<PageDto>(payload)
        assertEquals(2, dto.resolvedSocialLinks.size)
        assertEquals("facebook", dto.resolvedSocialLinks[0].platform)
        assertEquals("https://facebook.com/enterprise", dto.resolvedSocialLinks[0].url)
        assertTrue(dto.resolvedSocialLinks[0].is_visible)
        assertEquals("github", dto.resolvedSocialLinks[1].platform)
    }

    @Test
    fun `deserializes page with map of key value social_links`() {
        val payload = """
            {
                "id": 5,
                "name": "StartUp Hive",
                "category": "Startup",
                "social_links": {
                    "facebook": "https://facebook.com/startuphive",
                    "instagram": "https://instagram.com/startuphive"
                }
            }
        """.trimIndent()

        val dto = json.decodeFromString<PageDto>(payload)
        assertEquals(2, dto.resolvedSocialLinks.size)
        val fb = dto.resolvedSocialLinks.find { it.platform == "facebook" }
        assertNotNull(fb)
        assertEquals("https://facebook.com/startuphive", fb?.url)
    }

    @Test
    fun `deserializes page with structured object business_hours`() {
        val payload = """
            {
                "id": 6,
                "name": "Cafe Mocha",
                "category": "Restaurant",
                "business_hours": {
                    "monday": {"mode": "open", "open": "09:00", "close": "22:00"},
                    "friday": {"mode": "closed"}
                }
            }
        """.trimIndent()

        val dto = json.decodeFromString<PageDto>(payload)
        assertNotNull(dto.business_hours)
        assertEquals("open", dto.business_hours?.monday?.mode)
        assertEquals("09:00", dto.business_hours?.monday?.open)
        assertEquals("22:00", dto.business_hours?.monday?.close)
        assertEquals("closed", dto.business_hours?.friday?.mode)
    }

    @Test
    fun `deserializes page with string summary business_hours`() {
        val payload = """
            {
                "id": 7,
                "name": "24 Seven Mart",
                "category": "Retail",
                "business_hours": "Open 24 hours daily"
            }
        """.trimIndent()

        val dto = json.decodeFromString<PageDto>(payload)
        assertNotNull(dto.business_hours)
        assertEquals("Open 24 hours daily", dto.business_hours?.raw_summary)
    }

    @Test
    fun `deserializes page follow response DTO`() {
        val payload = """
            {
                "page_id": 10,
                "is_following": true,
                "followers_count": 142
            }
        """.trimIndent()

        val dto = json.decodeFromString<PageFollowResponseDto>(payload)
        assertEquals(10L, dto.page_id)
        assertTrue(dto.is_following)
        assertEquals(142, dto.followers_count)
    }

    @Test
    fun `deserializes group join and action response DTOs`() {
        val joinPayload = """
            {
                "group_id": 88,
                "status": "joined",
                "members_count": 501
            }
        """.trimIndent()

        val joinDto = json.decodeFromString<GroupJoinResponseDto>(joinPayload)
        assertEquals(88L, joinDto.group_id)
        assertEquals("joined", joinDto.status)
        assertEquals(501, joinDto.members_count)

        val actionPayload = """
            {
                "success": true,
                "message": "Left the group successfully."
            }
        """.trimIndent()

        val actionDto = json.decodeFromString<GroupActionResponseDto>(actionPayload)
        assertTrue(actionDto.success)
        assertEquals("Left the group successfully.", actionDto.message)
    }

    @Test
    fun `deserializes page analytics detail DTO with backend nested summary and string engagement rate`() {
        val payload = """
            {
                "page_id": 1,
                "slug": "techpulse",
                "period_days": 7,
                "summary": {
                    "total_followers": 1540,
                    "new_followers": 85,
                    "unfollowers": 3,
                    "net_follower_growth": 82,
                    "total_posts": 45,
                    "engagement_rate": "5.75%",
                    "impressions": 12800,
                    "reach": 9400,
                    "reactions": 540,
                    "comments": 120,
                    "shares": 45
                },
                "top_content": [
                    {
                        "id": 101,
                        "content": "Exciting updates coming soon!",
                        "type": "post",
                        "likes_count": 240,
                        "reach": 4200
                    }
                ]
            }
        """.trimIndent()

        val dto = json.decodeFromString<PageAnalyticsDetailDto>(payload)
        assertEquals(1L, dto.page_id)
        assertNotNull(dto.summary)
        assertEquals(1540, dto.resolvedFollowers)
        assertEquals(9400, dto.resolvedReach)
        assertEquals(12800, dto.resolvedImpressions)
        assertEquals(5.75, dto.resolvedEngagementRate, 0.001)
        assertEquals(540, dto.resolvedReactions)
        assertEquals(1, dto.top_content.size)
        assertEquals(101L, dto.top_content.first().id)
    }

    @Test
    fun `deserializes page monetization DTO`() {
        val payload = """
            {
                "is_eligible": true,
                "stars_enabled": true,
                "in_stream_ads_enabled": false,
                "subscriptions_enabled": true,
                "current_followers": 12500,
                "required_followers": 10000,
                "current_watch_minutes": 650000,
                "required_watch_minutes": 600000,
                "payout_status": "Active",
                "estimated_earnings": 142.50
            }
        """.trimIndent()

        val dto = json.decodeFromString<PageMonetizationStatusDto>(payload)
        assertTrue(dto.is_eligible)
        assertTrue(dto.stars_enabled)
        assertEquals(12500, dto.current_followers)
        assertEquals(142.50, dto.estimated_earnings, 0.01)
    }
}

