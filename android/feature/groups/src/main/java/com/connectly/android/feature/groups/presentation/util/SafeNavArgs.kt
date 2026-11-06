package com.connectly.android.feature.groups.presentation.util

import androidx.lifecycle.SavedStateHandle

/**
 * Safe navigation argument extraction to prevent ClassCastException
 * when arguments are passed as either Long, Int, or String in SavedStateHandle.
 */
fun SavedStateHandle.getLongOrNull(vararg keys: String): Long? {
    for (key in keys) {
        when (val raw = get<Any>(key)) {
            is Long -> return raw
            is Int -> return raw.toLong()
            is String -> {
                val parsed = raw.toLongOrNull()
                if (parsed != null) return parsed
            }
            is Number -> return raw.toLong()
        }
    }
    return null
}

fun SavedStateHandle.getStringOrNull(vararg keys: String): String? {
    for (key in keys) {
        when (val raw = get<Any>(key)) {
            is String -> if (raw.isNotBlank()) return raw
            is Long -> return raw.toString()
            is Int -> return raw.toString()
            is Number -> return raw.toString()
        }
    }
    return null
}

