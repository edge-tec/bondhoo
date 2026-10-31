package com.connectly.android.core.network

import com.connectly.android.core.datastore.TokenStorageManager
import okhttp3.Authenticator
import okhttp3.Request
import okhttp3.Response
import okhttp3.Route
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class TokenAuthenticator @Inject constructor(
    private val tokenStorageManager: TokenStorageManager
) : Authenticator {

    override fun authenticate(route: Route?, response: Response): Request? {
        // Prevent infinite loops if retry fails
        if (responseCount(response) >= 3) {
            tokenStorageManager.clearAll()
            return null
        }

        // On 401 unauthorized, clear session tokens
        tokenStorageManager.clearAll()
        return null
    }

    private fun responseCount(response: Response): Int {
        var result = 1
        var prior = response.priorResponse
        while (prior != null) {
            result++
            prior = prior.priorResponse
        }
        return result
    }
}

