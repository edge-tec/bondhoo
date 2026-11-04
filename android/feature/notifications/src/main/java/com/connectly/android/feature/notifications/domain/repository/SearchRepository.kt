package com.connectly.android.feature.notifications.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.DiscoveryData
import com.connectly.android.core.model.SearchResult
import com.connectly.android.core.model.User
import kotlinx.coroutines.flow.Flow

/**
 * Dedicated Repository for Global Search & Discovery System.
 * Adheres to Single Responsibility Principle (SRP).
 */
interface SearchRepository {
    fun search(query: String, type: String = "all"): Flow<Result<SearchResult>>
    fun getDiscoveryData(): Flow<Result<DiscoveryData>>
    fun getSuggestedFriends(): Flow<Result<List<User>>>
    fun getSearchHistory(): Flow<Result<List<String>>>
    fun deleteSearchHistoryItem(term: String): Flow<Result<List<String>>>
    fun clearSearchHistory(): Flow<Result<Unit>>
}

