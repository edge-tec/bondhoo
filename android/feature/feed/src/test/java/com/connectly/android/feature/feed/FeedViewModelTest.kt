package com.connectly.android.feature.feed

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ReactionResponse
import com.connectly.android.feature.feed.domain.repository.PaginatedFeed
import com.connectly.android.feature.feed.domain.usecase.*
import com.connectly.android.feature.feed.presentation.feed.FeedIntent
import com.connectly.android.feature.feed.presentation.feed.FeedViewModel
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
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class FeedViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getFeedUseCase: GetFeedUseCase = mockk()
    private val reactToPostUseCase: ReactToPostUseCase = mockk()
    private val toggleSavePostUseCase: ToggleSavePostUseCase = mockk()
    private val sharePostUseCase: SharePostUseCase = mockk()
    private val deletePostUseCase: DeletePostUseCase = mockk()
    private val reportPostUseCase: ReportPostUseCase = mockk()
    private val getCommentsUseCase: GetCommentsUseCase = mockk()
    private val addCommentUseCase: AddCommentUseCase = mockk()
    private val reactToCommentUseCase: ReactToCommentUseCase = mockk()
    private val getSuggestedFriendsUseCase: GetSuggestedFriendsUseCase = mockk()

    private lateinit var viewModel: FeedViewModel

    private val testPost = Post(
        id = 101L,
        author = User(id = 12L, name = "Afrin Akter", username = "afrin_akter"),
        content = "Life is better when you're surrounded by good people. ❤️ #friendship",
        reactionsCount = 2400,
        commentsCount = 248,
        sharesCount = 35,
        userReaction = null
    )

    private val testPost2 = Post(
        id = 102L,
        author = User(id = 14L, name = "Rahim Ahmed", username = "rahim_ahmed"),
        content = "Padma bridge evening view! 🌅",
        reactionsCount = 500,
        commentsCount = 45,
        sharesCount = 10,
        userReaction = null
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getFeedUseCase(tab = "for_you", cursor = null, perPage = 15) } returns flowOf(
            Result.Loading,
            Result.Success(PaginatedFeed(posts = listOf(testPost), nextCursor = "cursor_p1", hasMore = true))
        )
        every { getSuggestedFriendsUseCase() } returns flowOf(
            Result.Success(listOf(User(id = 20L, name = "Jamal Uddin", username = "jamal_u")))
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    private fun createViewModel(): FeedViewModel {
        return FeedViewModel(
            getFeedUseCase,
            reactToPostUseCase,
            toggleSavePostUseCase,
            sharePostUseCase,
            deletePostUseCase,
            reportPostUseCase,
            getCommentsUseCase,
            addCommentUseCase,
            reactToCommentUseCase,
            getSuggestedFriendsUseCase
        )
    }

    @Test
    fun `when viewmodel initialized, feed posts and suggested friends are loaded successfully`() = runTest {
        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(1, state.posts.size)
            assertEquals("Afrin Akter", state.posts.first().author.name)
            assertEquals(2400, state.posts.first().reactionsCount)
            assertEquals("cursor_p1", state.nextCursor)
            assertEquals(true, state.hasMore)
            assertEquals(1, state.suggestedFriends.size)
            assertEquals("Jamal Uddin", state.suggestedFriends.first().name)
        }
    }

    @Test
    fun `when post reacted, optimistic update increments count and confirms from server`() = runTest {
        every { reactToPostUseCase(101L, "love") } returns flowOf(
            Result.Success(ReactionResponse(reacted = true, type = "love", reactions_count = 2401))
        )

        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(FeedIntent.ReactToPost(101L, "love"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            val post = state.posts.first()
            assertEquals("love", post.userReaction)
            assertEquals(2401, post.reactionsCount)
        }
    }

    @Test
    fun `when reactToPost fails, state rolls back to previous reaction and count`() = runTest {
        every { reactToPostUseCase(101L, "love") } returns flowOf(
            Result.Error(Exception("Network timeout"))
        )

        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(FeedIntent.ReactToPost(101L, "love"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            val post = state.posts.first()
            assertNull(post.userReaction)
            assertEquals(2400, post.reactionsCount)
            assertNotNull(state.errorMessage)
        }
    }

    @Test
    fun `when loadMorePosts dispatched, appends new posts and updates cursor`() = runTest {
        every { getFeedUseCase(tab = "for_you", cursor = "cursor_p1", perPage = 15) } returns flowOf(
            Result.Success(PaginatedFeed(posts = listOf(testPost2), nextCursor = null, hasMore = false))
        )

        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(FeedIntent.LoadMorePosts)
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(2, state.posts.size)
            assertEquals(101L, state.posts[0].id)
            assertEquals(102L, state.posts[1].id)
            assertNull(state.nextCursor)
            assertEquals(false, state.hasMore)
        }
    }

    @Test
    fun `when comment added with parentId, comments count increments and reply placed`() = runTest {
        val newReply = Comment(
            id = 502L,
            postId = 101L,
            parentId = 201L,
            author = User(id = 1L, name = "You", username = "you"),
            body = "Thank you so much!"
        )
        every { addCommentUseCase(101L, "Thank you so much!", 201L) } returns flowOf(
            Result.Success(newReply)
        )

        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(FeedIntent.AddComment(101L, "Thank you so much!", 201L))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(249, state.posts.first().commentsCount)
            assertEquals(1, state.activeComments.size)
            assertEquals(201L, state.activeComments.first().parentId)
            assertEquals("Thank you so much!", state.activeComments.first().body)
        }
    }

    @Test
    fun `when post reported, submitReport triggers success message`() = runTest {
        every { reportPostUseCase(101L, "spam", "Excessive unsolicited links") } returns flowOf(
            Result.Success(Unit)
        )

        viewModel = createViewModel()
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(FeedIntent.OpenReportDialog(101L))
        assertEquals(101L, viewModel.uiState.value.reportingPostId)

        viewModel.handleIntent(FeedIntent.SubmitReport(101L, "spam", "Excessive unsolicited links"))
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertNull(state.reportingPostId)
            assertNotNull(state.reportSuccessMessage)
        }
    }
}
