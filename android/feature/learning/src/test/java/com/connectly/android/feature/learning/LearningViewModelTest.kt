package com.connectly.android.feature.learning

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.learning.domain.model.LearningCategory
import com.connectly.android.feature.learning.domain.model.LearningCertificate
import com.connectly.android.feature.learning.domain.model.LearningCourse
import com.connectly.android.feature.learning.domain.model.LearningEnrollment
import com.connectly.android.feature.learning.domain.usecase.EnrollCourseUseCase
import com.connectly.android.feature.learning.domain.usecase.GetLearningCategoriesUseCase
import com.connectly.android.feature.learning.domain.usecase.GetLearningCoursesUseCase
import com.connectly.android.feature.learning.domain.usecase.GetMyCertificatesUseCase
import com.connectly.android.feature.learning.domain.usecase.GetMyLearningUseCase
import com.connectly.android.feature.learning.presentation.learning.LearningIntent
import com.connectly.android.feature.learning.presentation.learning.LearningTab
import com.connectly.android.feature.learning.presentation.learning.LearningUiEffect
import com.connectly.android.feature.learning.presentation.learning.LearningViewModel
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
class LearningViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val getCategoriesUseCase: GetLearningCategoriesUseCase = mockk()
    private val getCoursesUseCase: GetLearningCoursesUseCase = mockk()
    private val enrollCourseUseCase: EnrollCourseUseCase = mockk()
    private val getMyLearningUseCase: GetMyLearningUseCase = mockk()
    private val getMyCertificatesUseCase: GetMyCertificatesUseCase = mockk()
    private val getCurriculumUseCase: com.connectly.android.feature.learning.domain.usecase.GetCurriculumUseCase = mockk(relaxed = true)
    private val syncLessonProgressUseCase: com.connectly.android.feature.learning.domain.usecase.SyncLessonProgressUseCase = mockk(relaxed = true)

    private lateinit var viewModel: LearningViewModel

    private val sampleCategory = LearningCategory(
        id = 1L,
        name = "Computer Science",
        slug = "computer-science",
        icon = "code",
        description = "Tech and engineering",
        coursesCount = 12
    )

    private val sampleCourse = LearningCourse(
        id = 101L,
        instructorId = 5L,
        categoryId = 1L,
        title = "Distributed Systems in Kotlin",
        slug = "distributed-systems-kotlin",
        subtitle = "Consensus and concurrency",
        description = "Full production guide",
        level = "expert",
        language = "en",
        thumbnailUrl = "https://images.unsplash.com/photo-1518770660439",
        promotionalVideoUrl = null,
        type = "free",
        price = 0.0,
        discountedPrice = null,
        currency = "USD",
        status = "published",
        durationMinutes = 180,
        totalSections = 6,
        totalLessons = 24,
        enrollmentsCount = 520,
        ratingAvg = 4.95,
        ratingCount = 88,
        isFeatured = true,
        instructorName = "Prof. Alan Turing",
        categoryName = "Computer Science"
    )

    private val sampleEnrollment = LearningEnrollment(
        id = 1L,
        courseId = 101L,
        courseTitle = "Distributed Systems in Kotlin",
        courseThumbnail = null,
        instructorName = "Prof. Alan Turing",
        status = "active",
        progressPercentage = 80.0,
        completedLessons = 19,
        totalLessons = 24,
        isCompleted = false
    )

    private val sampleCertificate = LearningCertificate(
        id = 1L,
        certificateCode = "CERT-2026-JUGAJUG-ABCD",
        courseTitle = "Distributed Systems in Kotlin",
        studentName = "Ada Lovelace",
        instructorName = "Prof. Alan Turing",
        issuedAt = "2026-11-04T12:00:00Z",
        pdfUrl = null,
        qrCodeUrl = null,
        isValid = true
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)

        every { getCategoriesUseCase() } returns flowOf(Result.Success(listOf(sampleCategory)))
        every { getCoursesUseCase(any(), any(), any(), any(), any(), any(), any()) } returns flowOf(
            Result.Success(listOf(sampleCourse))
        )
        every { getMyLearningUseCase() } returns flowOf(Result.Success(listOf(sampleEnrollment)))
        every { getMyCertificatesUseCase() } returns flowOf(Result.Success(listOf(sampleCertificate)))
        every { enrollCourseUseCase(any(), any()) } returns flowOf(Result.Success(sampleEnrollment))

        viewModel = LearningViewModel(
            getCategoriesUseCase = getCategoriesUseCase,
            getCoursesUseCase = getCoursesUseCase,
            enrollCourseUseCase = enrollCourseUseCase,
            getMyLearningUseCase = getMyLearningUseCase,
            getMyCertificatesUseCase = getMyCertificatesUseCase,
            getCurriculumUseCase = getCurriculumUseCase,
            syncLessonProgressUseCase = syncLessonProgressUseCase
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun `initialization loads categories and courses into state`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals(1, state.categories.size)
        assertEquals("Computer Science", state.categories[0].name)
        assertEquals(1, state.courses.size)
        assertEquals("Distributed Systems in Kotlin", state.courses[0].title)
        assertEquals(1, state.myLearning.size)
        assertEquals(1, state.certificates.size)
    }

    @Test
    fun `selecting tab updates activeTab in state`() = runTest {
        viewModel.handleIntent(LearningIntent.SelectTab(LearningTab.MY_LEARNING))
        testDispatcher.scheduler.advanceUntilIdle()

        assertEquals(LearningTab.MY_LEARNING, viewModel.uiState.value.activeTab)

        viewModel.handleIntent(LearningIntent.SelectTab(LearningTab.CERTIFICATES))
        testDispatcher.scheduler.advanceUntilIdle()

        assertEquals(LearningTab.CERTIFICATES, viewModel.uiState.value.activeTab)
    }

    @Test
    fun `selecting category updates state and re-queries courses`() = runTest {
        viewModel.handleIntent(LearningIntent.SelectCategory(1L))
        testDispatcher.scheduler.advanceUntilIdle()

        assertEquals(1L, viewModel.uiState.value.selectedCategoryId)
    }

    @Test
    fun `enrolling in course emits snackbar effect and updates success message`() = runTest {
        viewModel.effect.test {
            viewModel.handleIntent(LearningIntent.EnrollCourse(101L))
            testDispatcher.scheduler.advanceUntilIdle()

            val effect = awaitItem()
            assertTrue(effect is LearningUiEffect.ShowSnackbar)
            assertTrue((effect as LearningUiEffect.ShowSnackbar).message.startsWith("Enrolled successfully"))
            assertTrue(viewModel.uiState.value.enrolledSuccessMessage?.contains("Distributed Systems") == true)
        }
    }
}

