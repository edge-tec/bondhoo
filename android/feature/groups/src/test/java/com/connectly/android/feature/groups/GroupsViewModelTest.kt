package com.connectly.android.feature.groups

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Group
import com.connectly.android.feature.groups.domain.usecase.GetFeaturedGroupUseCase
import com.connectly.android.feature.groups.domain.usecase.GetGroupsUseCase
import com.connectly.android.feature.groups.domain.usecase.ToggleJoinGroupUseCase
import com.connectly.android.feature.groups.presentation.groups.GroupsIntent
import com.connectly.android.feature.groups.presentation.groups.GroupsViewModel
import io.mockk.every
import io.mockk.mockk
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class GroupsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getGroupsUseCase: GetGroupsUseCase = mockk()
    private val getFeaturedGroupUseCase: GetFeaturedGroupUseCase = mockk()
    private val toggleJoinGroupUseCase: ToggleJoinGroupUseCase = mockk()

    private lateinit var viewModel: GroupsViewModel

    private val featuredGroup = Group(
        id = 1001L,
        name = "Travel Lovers BD",
        slug = "travel-lovers-bd",
        membersCount = 248000,
        formattedMembersCount = "248K members",
        isMember = false
    )

    private val popularGroup = Group(
        id = 101L,
        name = "Tech World",
        slug = "tech-world",
        membersCount = 156000,
        formattedMembersCount = "156K members",
        isMember = false
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getFeaturedGroupUseCase() } returns flowOf(Result.Success(featuredGroup))
        every { getGroupsUseCase("discover", 1) } returns flowOf(
            Result.Loading,
            Result.Success(listOf(popularGroup))
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when viewmodel initialized, loads featured and popular groups`() = runTest {
        viewModel = GroupsViewModel(getGroupsUseCase, getFeaturedGroupUseCase, toggleJoinGroupUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals("Travel Lovers BD", state.featuredGroup?.name)
            assertEquals(1, state.popularGroups.size)
            assertEquals("Tech World", state.popularGroups.first().name)
        }
    }

    @Test
    fun `when toggle join dispatched, updates membership state optimistically`() = runTest {
        every { toggleJoinGroupUseCase(101L, false) } returns flowOf(Result.Success(true))

        viewModel = GroupsViewModel(getGroupsUseCase, getFeaturedGroupUseCase, toggleJoinGroupUseCase)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(GroupsIntent.ToggleJoin(popularGroup))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertTrue(state.popularGroups.first().isMember)
        }
    }
}

