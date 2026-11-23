package com.connectly.android

import android.content.Intent
import android.net.Uri
import android.os.Build
import com.connectly.android.core.model.CallType

/**
 * Parses incoming HTTPS App Links (https://bondhoo.com/...), custom deep links
 * (bondhoo://, connectly://, jugajug://), incoming call notification intents,
 * and Android System Share Sheet intents (ACTION_SEND / ACTION_SEND_MULTIPLE).
 */
sealed interface BondhooDeepLink {
    data class IncomingCall(val callId: Long, val callType: CallType = CallType.AUDIO) : BondhooDeepLink
    data class ShareContent(val text: String? = null, val mediaUris: List<Uri> = emptyList()) : BondhooDeepLink
    data class Chat(val conversationId: Long) : BondhooDeepLink
    data class Profile(val identifier: String) : BondhooDeepLink
    data class LiveStream(val streamId: Long) : BondhooDeepLink
    data object Marketplace : BondhooDeepLink
    data object Groups : BondhooDeepLink
    data object Pages : BondhooDeepLink
    data object Events : BondhooDeepLink
    data object Notifications : BondhooDeepLink
    data object Jobs : BondhooDeepLink
    data object Learning : BondhooDeepLink
    data object Search : BondhooDeepLink
    data object Settings : BondhooDeepLink
    data object CreatePost : BondhooDeepLink
}

object DeepLinkRouter {

    private const val EXTRA_CALL_ID = "extra_call_id"

    fun parseIntent(intent: Intent?): BondhooDeepLink? {
        if (intent == null) return null

        val callId = intent.getLongExtra(EXTRA_CALL_ID, 0L)
        if (callId > 0L) {
            return BondhooDeepLink.IncomingCall(callId = callId)
        }

        // Handle Android System Share Sheet (Share text, links, images, videos into Bondhoo)
        when (intent.action) {
            Intent.ACTION_SEND -> {
                val sharedText = intent.getStringExtra(Intent.EXTRA_TEXT)?.takeIf { it.isNotBlank() }
                val streamUri: Uri? = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                    intent.getParcelableExtra(Intent.EXTRA_STREAM, Uri::class.java)
                } else {
                    @Suppress("DEPRECATION")
                    intent.getParcelableExtra(Intent.EXTRA_STREAM)
                }
                val uris = listOfNotNull(streamUri)
                if (sharedText != null || uris.isNotEmpty()) {
                    return BondhooDeepLink.ShareContent(text = sharedText, mediaUris = uris)
                }
            }
            Intent.ACTION_SEND_MULTIPLE -> {
                val sharedText = intent.getStringExtra(Intent.EXTRA_TEXT)?.takeIf { it.isNotBlank() }
                val uris: List<Uri> = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                    intent.getParcelableArrayListExtra(Intent.EXTRA_STREAM, Uri::class.java).orEmpty()
                } else {
                    @Suppress("DEPRECATION")
                    intent.getParcelableArrayListExtra<Uri>(Intent.EXTRA_STREAM).orEmpty()
                }
                if (sharedText != null || uris.isNotEmpty()) {
                    return BondhooDeepLink.ShareContent(text = sharedText, mediaUris = uris)
                }
            }
        }

        val uri = intent.data ?: return null
        return parseUri(uri)
    }

    fun parseUri(uri: Uri): BondhooDeepLink? {
        val scheme = uri.scheme?.lowercase().orEmpty()
        val host = uri.host?.lowercase().orEmpty()
        val segments = uri.pathSegments.filter { it.isNotBlank() }

        // Handle custom schemes: bondhoo://messenger/chat/123, connectly://notifications, etc.
        if (scheme in setOf("bondhoo", "connectly", "jugajug")) {
            return when (host) {
                "messenger", "messages", "chat" -> {
                    val id = segments.lastOrNull()?.toLongOrNull()
                    if (id != null && id > 0L) BondhooDeepLink.Chat(id) else null
                }
                "profile", "u", "user" -> {
                    segments.firstOrNull()?.let { BondhooDeepLink.Profile(it) }
                }
                "live" -> {
                    BondhooDeepLink.LiveStream(segments.firstOrNull()?.toLongOrNull() ?: 1L)
                }
                "marketplace" -> BondhooDeepLink.Marketplace
                "groups" -> BondhooDeepLink.Groups
                "pages" -> BondhooDeepLink.Pages
                "events" -> BondhooDeepLink.Events
                "notifications" -> BondhooDeepLink.Notifications
                "jobs" -> BondhooDeepLink.Jobs
                "learning" -> BondhooDeepLink.Learning
                "search" -> BondhooDeepLink.Search
                "settings" -> BondhooDeepLink.Settings
                "create-post", "post" -> BondhooDeepLink.CreatePost
                else -> null
            }
        }

        // Handle verified HTTPS App Links: https://bondhoo.com/...
        if ((scheme == "https" || scheme == "http") && (host == "bondhoo.com" || host == "www.bondhoo.com")) {
            val first = segments.firstOrNull()?.lowercase() ?: return null
            val second = segments.getOrNull(1)
            return when (first) {
                "messages", "messenger", "chat" -> {
                    val id = second?.toLongOrNull()
                    if (id != null && id > 0L) BondhooDeepLink.Chat(id) else null
                }
                "profile", "u", "user" -> {
                    second?.takeIf { it.isNotBlank() }?.let { BondhooDeepLink.Profile(it) }
                }
                "live" -> {
                    BondhooDeepLink.LiveStream(second?.toLongOrNull() ?: 1L)
                }
                "marketplace" -> BondhooDeepLink.Marketplace
                "groups" -> BondhooDeepLink.Groups
                "pages" -> BondhooDeepLink.Pages
                "events" -> BondhooDeepLink.Events
                "notifications" -> BondhooDeepLink.Notifications
                "jobs" -> BondhooDeepLink.Jobs
                "learning" -> BondhooDeepLink.Learning
                "search" -> BondhooDeepLink.Search
                "settings" -> BondhooDeepLink.Settings
                "create-post", "post" -> BondhooDeepLink.CreatePost
                else -> null
            }
        }

        return null
    }
}
