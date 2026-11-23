package com.connectly.android

import android.content.Context
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * Observes real-time device network connectivity via Android ConnectivityManager
 * so Bondhoo can display an instant Offline / Back Online banner across the app.
 */
class NetworkConnectivityMonitor(context: Context) {

    private val connectivityManager =
        context.applicationContext.getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager

    private val _isOnline = MutableStateFlow(checkCurrentlyOnline())
    val isOnline: StateFlow<Boolean> = _isOnline.asStateFlow()

    private var registered = false

    private val networkCallback = object : ConnectivityManager.NetworkCallback() {
        override fun onAvailable(network: Network) {
            _isOnline.value = true
        }

        override fun onCapabilitiesChanged(network: Network, capabilities: NetworkCapabilities) {
            val hasInternet = capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET)
            val validated = capabilities.hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED)
            _isOnline.value = hasInternet && validated
        }

        override fun onLost(network: Network) {
            _isOnline.value = checkCurrentlyOnline()
        }

        override fun onUnavailable() {
            _isOnline.value = false
        }
    }

    fun start() {
        if (registered) return
        runCatching {
            connectivityManager.registerDefaultNetworkCallback(networkCallback)
            registered = true
            _isOnline.value = checkCurrentlyOnline()
        }
    }

    fun stop() {
        if (!registered) return
        runCatching {
            connectivityManager.unregisterNetworkCallback(networkCallback)
        }
        registered = false
    }

    private fun checkCurrentlyOnline(): Boolean {
        return runCatching {
            val active = connectivityManager.activeNetwork ?: return false
            val caps = connectivityManager.getNetworkCapabilities(active) ?: return false
            caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET) &&
                caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED)
        }.getOrDefault(true)
    }
}
