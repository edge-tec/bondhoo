package com.connectly.android.feature.notifications

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.DiscoveryData
import com.connectly.android.core.model.Group
import com.connectly.android.core.model.SearchResult
import com.connectly.android.core.model.User
import com.connectly.android.feature.notifications.domain.usecase.GetDiscoveryDataUseCase
import com.connectly.android.feature.notifications.domain.usecase.SearchUseCase
import com.connectly.android.feature.notifications.presentation.search.SearchIntent
import com.connectly.android.feature.notifications.presentation.search.SearchViewModel
import io.mockk.every
import io.mockk.mockk
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceTimeBy
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class SearchViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val searchUseCase: SearchUseCase = mockk()
    private val getDiscoveryDataUseCase: GetDiscoveryDataUseCase = mockk()

    private lateinit var viewModel: SearchViewModel

    private val sampleUser = User(id = 101L, name = "Sadia Islam", username = "sadia_islam")
    private val sampleGroup = Group(id = "group_1", name = "Dhaka Tech Network")
    private val sampleDiscovery = DiscoveryData(
        suggestedFriends = listOf(sampleUser),
        recommendedGroups = listOf(sampleGroup),
        trendingTopics = listOf("#Connectly2026", "#AndroidDev")
    )
    private val sampleSearchResult = SearchResult(
        users = listOf(sampleUser),
        groups = listOf(sampleGroup)
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getDiscoveryDataUseCase() } returns flowOf(
            Result.Success(sampleDiscovery)
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when viewmodel initialized, discovery data loads successfully`() = runTest {
        viewModel = SearchViewModel(searchUseCase, getDiscoveryDataUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(1, state.discoveryData.suggestedFriends.size)
            assertEquals("Sadia Islam", state.discoveryData.suggestedFriends.first().name)
            assertEquals(1, state.discoveryData.recommendedGroups.size)
            assertTrue(state.discoveryData.trendingTopics.contains("#Connectly2026"))
        }
    }

    @Test
    fun `when query changed, debounces and performs search returning results`() = runTest {
        every { searchUseCase("Sadia", "all") } returns flowOf(
            Result.Loading,
            Result.Success(sampleSearchResult)
        )

        viewModel = SearchViewModel(searchUseCase, getDiscoveryDataUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(SearchIntent.QueryChanged("Sadia"))
        testDispatcher.scheduler.advanceTimeBy(350)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals("Sadia", state.query)
            assertEquals(1, state.searchResults.users.size)
            assertEquals("Sadia Islam", state.searchResults.users.first().name)
        }
    }

    @Test
    fun `when filter selected and query exists, re-triggers search with new filter`() = runTest {
        every { searchUseCase("Sadia", "all") } returns flowOf(Result.Success(sampleSearchResult))
        every { searchUseCase("Sadia", "users") } returns flowOf(Result.Success(sampleSearchResult.copy(groups = emptyList())))

        viewModel = SearchViewModel(searchUseCase, getDiscoveryDataUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(SearchIntent.QueryChanged("Sadia"))
        testDispatcher.scheduler.advanceTimeBy(350)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(SearchIntent.FilterSelected("Users"))
        testDispatcher.scheduler.advanceTimeBy(350)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals("Users", state.selectedFilter)
            assertEquals(1, state.searchResults.users.size)
            assertTrue(state.searchResults.groups.isEmpty())
        }
    }

    @Test
    fun `when clear recent searches or remove item, updates recent searches list`() = runTest {
        viewModel = SearchViewModel(searchUseCase, getDiscoveryDataUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(SearchIntent.RemoveRecentItem("Sadia Islam"))
        viewModel.uiState.test {
            val state = awaitItem()
            assertTrue(!state.recentSearches.contains("Sadia Islam"))
        }

        viewModel.handleIntent(SearchIntent.ClearRecentSearches)
        viewModel.uiState.test {
            val state = awaitItem()
            assertTrue(state.recentSearches.isEmpty())
        }
    }
}
