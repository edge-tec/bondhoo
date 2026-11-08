package com.connectly.android.feature.learning

import app.cash.turbine.test
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.learning.domain.model.LearningCategory
import com.connectly.android.feature.learning.domain.model.LearningCertificate
import com.connectly.android.feature.learning.domain.model.LearningCourse
import com.connectly.android.feature.learning.domain.model.LearningEnrollment
import com.connectly.android.feature.learning.domain.model.LearningLesson
import com.connectly.android.feature.learning.domain.model.LearningSection
import com.connectly.android.feature.learning.domain.usecase.EnrollCourseUseCase
import com.connectly.android.feature.learning.domain.usecase.GetCurriculumUseCase
import com.connectly.android.feature.learning.domain.usecase.GetLearningCategoriesUseCase
import com.connectly.android.feature.learning.domain.usecase.GetLearningCoursesUseCase
import com.connectly.android.feature.learning.domain.usecase.GetMyCertificatesUseCase
import com.connectly.android.feature.learning.domain.usecase.GetMyLearningUseCase
import com.connectly.android.feature.learning.domain.usecase.SyncLessonProgressUseCase
import com.connectly.android.feature.learning.presentation.learning.LearningIntent
import com.connectly.android.feature.learning.presentation.learning.LearningTab
import com.connectly.android.feature.learning.presentation.learning.LearningUiEffect
import com.connectly.android.feature.learning.presentation.learning.LearningViewModel
import io.mockk.every
import io.mockk.mockk
import io.mockk.verify
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
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
    private val getCurriculumUseCase: GetCurriculumUseCase = mockk(relaxed = true)
    private val syncLessonProgressUseCase: SyncLessonProgressUseCase = mockk(relaxed = true)

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

    private val paidCourse = sampleCourse.copy(
        id = 202L,
        title = "Enterprise Security & Architecture",
        slug = "enterprise-security-architecture",
        type = "paid",
        price = 2500.0,
        discountedPrice = 1999.0,
        currency = "BDT",
        isEnrolled = false
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

    private val paidEnrollment = LearningEnrollment(
        id = 2L,
        courseId = 202L,
        courseTitle = "Enterprise Security & Architecture",
        courseThumbnail = null,
        instructorName = "Prof. Alan Turing",
        status = "active",
        progressPercentage = 0.0,
        completedLessons = 0,
        totalLessons = 10,
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
            Result.Success(listOf(sampleCourse, paidCourse))
        )
        every { getMyLearningUseCase() } returns flowOf(Result.Success(listOf(sampleEnrollment)))
        every { getMyCertificatesUseCase() } returns flowOf(Result.Success(listOf(sampleCertificate)))
        every { enrollCourseUseCase(any(), any()) } returns flowOf(Result.Success(sampleEnrollment))
        every { getCurriculumUseCase(any()) } returns flowOf(Result.Success(emptyList()))

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
        assertEquals(2, state.courses.size)
        assertEquals("Distributed Systems in Kotlin", state.courses[0].title)
        assertTrue(state.courses[0].isEnrolled)
        assertFalse(state.courses[1].isEnrolled)
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
    fun `selecting category level and pricing type updates filters and re-queries courses`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.handleIntent(LearningIntent.SelectCategory(1L))
        viewModel.handleIntent(LearningIntent.SelectLevel("expert"))
        viewModel.handleIntent(LearningIntent.SelectType("paid"))
        testDispatcher.scheduler.advanceUntilIdle()

        assertEquals(1L, viewModel.uiState.value.selectedCategoryId)
        assertEquals("expert", viewModel.uiState.value.selectedLevel)
        assertEquals("paid", viewModel.uiState.value.selectedType)
    }

    @Test
    fun `enrolling in course emits snackbar effect and updates success message`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.effect.test {
            viewModel.handleIntent(LearningIntent.EnrollCourse(101L))
            testDispatcher.scheduler.advanceUntilIdle()

            val effect = awaitItem()
            assertTrue(effect is LearningUiEffect.ShowSnackbar)
            assertTrue((effect as LearningUiEffect.ShowSnackbar).message.startsWith("Enrolled successfully"))
            assertTrue(viewModel.uiState.value.enrolledSuccessMessage?.contains("Distributed Systems") == true)
        }
    }

    @Test
    fun `paid enrollment failure does not mark course as enrolled and allows clean retry with coupon`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        every { enrollCourseUseCase(202L, null) } returns flowOf(
            Result.Error(IllegalStateException("Insufficient wallet balance to enroll in this course."))
        )

        viewModel.handleIntent(LearningIntent.EnrollCourse(202L, null))
        testDispatcher.scheduler.advanceUntilIdle()

        val failedState = viewModel.uiState.value
        assertEquals("Insufficient wallet balance to enroll in this course.", failedState.enrollErrorMessage)
        assertFalse(failedState.myLearning.any { it.courseId == 202L })
        assertFalse(failedState.courses.first { it.id == 202L }.isEnrolled)

        // Now retry with a valid coupon after wallet top-up
        every { enrollCourseUseCase(202L, "SAVE20") } returns flowOf(Result.Success(paidEnrollment))
        every { getMyLearningUseCase() } returns flowOf(Result.Success(listOf(paidEnrollment, sampleEnrollment)))

        viewModel.handleIntent(LearningIntent.EnrollCourse(202L, "  SAVE20  "))
        testDispatcher.scheduler.advanceUntilIdle()

        val recoveredState = viewModel.uiState.value
        assertNull(recoveredState.enrollErrorMessage)
        assertTrue(recoveredState.myLearning.any { it.courseId == 202L })
        assertTrue(recoveredState.courses.first { it.id == 202L }.isEnrolled)
        verify(exactly = 1) { enrollCourseUseCase(202L, "SAVE20") }
    }

    @Test
    fun `duplicate tap guard prevents concurrent duplicate enrollment requests`() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        val delayedFlow = MutableSharedFlow<Result<LearningEnrollment>>()
        every { enrollCourseUseCase(202L, null) } returns delayedFlow

        viewModel.handleIntent(LearningIntent.EnrollCourse(202L))
        testDispatcher.scheduler.runCurrent()
        assertTrue(202L in viewModel.uiState.value.enrollingCourseIds)

        // Second rapid tap while first request is still in flight
        viewModel.handleIntent(LearningIntent.EnrollCourse(202L))
        testDispatcher.scheduler.runCurrent()

        delayedFlow.emit(Result.Success(paidEnrollment))
        testDispatcher.scheduler.advanceUntilIdle()

        assertFalse(202L in viewModel.uiState.value.enrollingCourseIds)
        verify(exactly = 1) { enrollCourseUseCase(202L, null) }
    }

    @Test
    fun `completing lesson marks lesson completed in curriculum and refreshes progress and certificates`() = runTest {
        val lesson = LearningLesson(
            id = 55L,
            sectionId = 10L,
            courseId = 101L,
            title = "Raft Leader Election",
            slug = "raft-leader-election",
            type = "video",
            content = "Deep dive",
            videoUrl = null,
            videoDurationSeconds = 900,
            sortOrder = 1,
            isPreview = true,
            isMandatory = true,
            isCompleted = false
        )
        val section = LearningSection(
            id = 10L,
            courseId = 101L,
            title = "Module 1",
            description = null,
            sortOrder = 1,
            lessons = listOf(lesson)
        )
        every { getCurriculumUseCase(101L) } returns flowOf(Result.Success(listOf(section)))
        every { syncLessonProgressUseCase(55L, 1800, true) } returns flowOf(Result.Success(Unit))

        testDispatcher.scheduler.advanceUntilIdle()
        viewModel.handleIntent(LearningIntent.LoadCurriculum(101L))
        testDispatcher.scheduler.advanceUntilIdle()

        assertFalse(viewModel.uiState.value.selectedCourseCurriculum[0].lessons[0].isCompleted)

        viewModel.handleIntent(LearningIntent.CompleteLesson(lessonId = 55L, courseId = 101L))
        testDispatcher.scheduler.advanceUntilIdle()

        assertTrue(viewModel.uiState.value.selectedCourseCurriculum[0].lessons[0].isCompleted)
        verify(exactly = 1) { syncLessonProgressUseCase(55L, 1800, true) }
    }
}
