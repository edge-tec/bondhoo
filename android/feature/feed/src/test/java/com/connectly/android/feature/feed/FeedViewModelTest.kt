package com.connectly.android.feature.feed

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Comment
import com.connectly.android.core.model.Post
import com.connectly.android.core.model.User
import com.connectly.android.core.network.api.ReactionResponse
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
    private val getCommentsUseCase: GetCommentsUseCase = mockk()
    private val addCommentUseCase: AddCommentUseCase = mockk()
    private val reactToCommentUseCase: ReactToCommentUseCase = mockk()

    private lateinit var viewModel: FeedViewModel

    private val testPost = Post(
        id = 101L,
        author = User(id = 12L, name = "Afrin Akter", username = "afrin_akter"),
        content = "Life is better when you're surrounded by good people. ❤️",
        reactionsCount = 2400,
        commentsCount = 248,
        sharesCount = 35,
        userReaction = null
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)
        every { getFeedUseCase("for_you", null, 15) } returns flowOf(
            Result.Loading,
            Result.Success(listOf(testPost))
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `when viewmodel initialized, feed posts are loaded successfully`() = runTest {
        viewModel = FeedViewModel(
            getFeedUseCase,
            reactToPostUseCase,
            toggleSavePostUseCase,
            sharePostUseCase,
            deletePostUseCase,
            getCommentsUseCase,
            addCommentUseCase,
            reactToCommentUseCase
        )

        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(1, state.posts.size)
            assertEquals("Afrin Akter", state.posts.first().author.name)
            assertEquals(2400, state.posts.first().reactionsCount)
        }
    }

    @Test
    fun `when post reacted, optimistic update increments reaction count`() = runTest {
        every { reactToPostUseCase(101L, "love") } returns flowOf(
            Result.Success(ReactionResponse(user_reaction = "love", reactions_count = 2401))
        )

        viewModel = FeedViewModel(
            getFeedUseCase,
            reactToPostUseCase,
            toggleSavePostUseCase,
            sharePostUseCase,
            deletePostUseCase,
            getCommentsUseCase,
            addCommentUseCase,
            reactToCommentUseCase
        )
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(FeedIntent.ReactToPost(101L, "love"))

        viewModel.uiState.test {
            val state = awaitItem()
            val post = state.posts.first()
            assertEquals("love", post.userReaction)
            assertEquals(2401, post.reactionsCount)
        }
    }

    @Test
    fun `when comment added, comments count increments and comment prepended`() = runTest {
        val newComment = Comment(
            id = 501L,
            postId = 101L,
            author = User(id = 1L, name = "You", username = "you"),
            body = "Great post!"
        )
        every { addCommentUseCase(101L, "Great post!", null) } returns flowOf(
            Result.Success(newComment)
        )

        viewModel = FeedViewModel(
            getFeedUseCase,
            reactToPostUseCase,
            toggleSavePostUseCase,
            sharePostUseCase,
            deletePostUseCase,
            getCommentsUseCase,
            addCommentUseCase,
            reactToCommentUseCase
        )
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(FeedIntent.AddComment(101L, "Great post!"))

        viewModel.uiState.test {
            val state = awaitItem()
            assertEquals(249, state.posts.first().commentsCount)
            assertEquals(1, state.activeComments.size)
            assertEquals("Great post!", state.activeComments.first().body)
        }
    }
}
