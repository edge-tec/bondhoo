package com.connectly.android.core.common.network

import javax.inject.Qualifier
import kotlin.annotation.AnnotationRetention.RUNTIME

@Qualifier
@Retention(RUNTIME)
annotation class Dispatcher(val connectlyDispatcher: ConnectlyDispatchers)

enum class ConnectlyDispatchers {
    Default,
    IO,
    Main
}

