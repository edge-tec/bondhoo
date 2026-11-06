package com.connectly.android.feature.jobs

import com.connectly.android.core.model.*
import com.connectly.android.feature.jobs.domain.usecase.*
import com.connectly.android.feature.jobs.presentation.jobs.*
import io.mockk.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.*
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class JobsViewModelTest {

    private val testDispatcher = StandardTestDispatcher()

    private val searchJobsUseCase: SearchJobsUseCase = mockk()
    private val getJobDetailsUseCase: GetJobDetailsUseCase = mockk()
    private val createJobUseCase: CreateJobUseCase = mockk()
    private val applyForJobUseCase: ApplyForJobUseCase = mockk()
    private val getMyApplicationsUseCase: GetMyApplicationsUseCase = mockk()
    private val saveJobUseCase: SaveJobUseCase = mockk()
    private val unsaveJobUseCase: UnsaveJobUseCase = mockk()
    private val getSavedJobsUseCase: GetSavedJobsUseCase = mockk()
    private val getCandidateProfileUseCase: GetCandidateProfileUseCase = mockk()
    private val getCandidateResumesUseCase: GetCandidateResumesUseCase = mockk()
    private val getCompaniesUseCase: GetCompaniesUseCase = mockk()
    private val getCompanyDashboardUseCase: GetCompanyDashboardUseCase = mockk()
    private val getAtsPipelineUseCase: GetAtsPipelineUseCase = mockk()
    private val updateAtsStageUseCase: UpdateAtsStageUseCase = mockk()
    private val getInterviewsUseCase: GetInterviewsUseCase = mockk()
    private val scheduleInterviewUseCase: ScheduleInterviewUseCase = mockk()
    private val respondInterviewUseCase: RespondInterviewUseCase = mockk()
    private val deleteJobUseCase: DeleteJobUseCase = mockk()

    private lateinit var viewModel: JobsViewModel

    private val sampleJob = Job(
        id = 1L,
        title = "Senior Android Engineer",
        slug = "senior-android-engineer",
        companyName = "Connectly Tech",
        location = "Dhaka, Bangladesh",
        workplaceType = "HYBRID",
        employmentType = "FULL_TIME",
        salaryFormatted = "\$3,000 - \$4,500/mo",
        isSaved = false
    )

    private val sampleCompany = CompanyItem(
        id = 10L,
        name = "Connectly Tech",
        slug = "connectly-tech",
        industry = "Technology",
        isVerified = true
    )

    private val sampleProfile = CandidateProfileItem(
        id = 5L,
        userId = 101L,
        professionalTitle = "Staff Software Architect",
        professionalSummary = "Building scalable distributed and mobile systems.",
        currentPosition = "Lead Architect",
        experienceYears = 10
    )

    private val sampleApplication = JobApplicationItem(
        id = 100L,
        jobPostingId = 1L,
        userId = 101L,
        fullName = "Mizanur Rahman",
        email = "mm923310@gmail.com",
        phone = "+8801700000000",
        jobTitle = "Senior Android Engineer",
        companyName = "Connectly Tech",
        status = "applied",
        appliedAtFormatted = "2026-11-04"
    )

    private val sampleInterview = InterviewItem(
        id = 50L,
        applicationId = 100L,
        candidateName = "Mizanur Rahman",
        jobTitle = "Senior Android Engineer",
        interviewType = "video",
        scheduledAtFormatted = "2026-11-10 14:00:00",
        status = "scheduled"
    )

    private val sampleAtsColumn = AtsColumn(
        stageKey = "applied",
        title = "Applied",
        color = "#0284c7",
        count = 1,
        candidates = listOf(
            AtsCandidateItem(
                id = 100L,
                jobId = 1L,
                jobTitle = "Senior Android Engineer",
                candidateName = "Mizanur Rahman",
                candidateEmail = "mm923310@gmail.com"
            )
        )
    )

    @Before
    fun setUp() {
        Dispatchers.setMain(testDispatcher)

        every { searchJobsUseCase(any(), any(), any(), any(), any(), any(), any(), any()) } returns
            flowOf(Result.success(listOf(sampleJob)))
        every { getCandidateProfileUseCase() } returns flowOf(Result.success(sampleProfile))
        every { getCandidateResumesUseCase() } returns flowOf(Result.success(emptyList()))
        every { getCompaniesUseCase(mineOnly = true) } returns flowOf(Result.success(listOf(sampleCompany)))
        every { getCompanyDashboardUseCase(sampleCompany.id) } returns flowOf(Result.success(EmployerMetrics(totalJobs = 5, totalApplications = 42)))
        every { getAtsPipelineUseCase(sampleCompany.id, any()) } returns flowOf(Result.success(listOf(sampleAtsColumn)))

        every { getInterviewsUseCase() } returns flowOf(Result.success(listOf(sampleInterview)))
        every { deleteJobUseCase(any()) } returns flowOf(Result.success(Unit))

        viewModel = JobsViewModel(
            searchJobsUseCase,
            getJobDetailsUseCase,
            createJobUseCase,
            applyForJobUseCase,
            getMyApplicationsUseCase,
            saveJobUseCase,
            unsaveJobUseCase,
            getSavedJobsUseCase,
            getCandidateProfileUseCase,
            getCandidateResumesUseCase,
            getCompaniesUseCase,
            getCompanyDashboardUseCase,
            getAtsPipelineUseCase,
            updateAtsStageUseCase,
            getInterviewsUseCase,
            scheduleInterviewUseCase,
            respondInterviewUseCase,
            deleteJobUseCase
        )
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun testInitialLoad_populatesJobsProfileAndCompanies() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals(1, state.jobs.size)
        assertEquals("Senior Android Engineer", state.jobs.first().title)
        assertNotNull(state.candidateProfile)
        assertEquals("Staff Software Architect", state.candidateProfile?.professionalTitle)
        assertEquals(1, state.myCompanies.size)
        assertEquals("Connectly Tech", state.selectedCompany?.name)
    }

    @Test
    fun testSearchJobs_updatesStateWithResults() = runTest {
        val filteredJob = sampleJob.copy(id = 2L, title = "Staff Backend Engineer")
        every { searchJobsUseCase(query = "Backend", workplaceType = null, employmentType = null) } returns
            flowOf(Result.success(listOf(filteredJob)))

        viewModel.onEvent(JobsUiEvent.UpdateSearchQuery("Backend"))
        viewModel.onEvent(JobsUiEvent.Search)
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals("Backend", state.searchQuery)
        assertEquals(1, state.jobs.size)
        assertEquals("Staff Backend Engineer", state.jobs.first().title)
    }

    @Test
    fun testFilterWorkplaceAndEmployment_triggersFilteredSearch() = runTest {
        every { searchJobsUseCase(query = null, workplaceType = "REMOTE", employmentType = "FULL_TIME") } returns
            flowOf(Result.success(listOf(sampleJob.copy(workplaceType = "REMOTE"))))

        viewModel.onEvent(JobsUiEvent.FilterWorkplace("REMOTE"))
        viewModel.onEvent(JobsUiEvent.FilterEmployment("FULL_TIME"))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals("REMOTE", state.selectedWorkplaceType)
        assertEquals("FULL_TIME", state.selectedEmploymentType)
        assertEquals("REMOTE", state.jobs.first().workplaceType)
    }

    @Test
    fun testToggleSaveJob_togglesSavedState() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        every { saveJobUseCase(sampleJob.id) } returns flowOf(Result.success(Unit))

        viewModel.onEvent(JobsUiEvent.ToggleSaveJob(sampleJob))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertTrue(state.jobs.first().isSaved)
        assertEquals("Job saved successfully!", state.successMessage)
    }

    @Test
    fun testSubmitApplication_successfulSubmission() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        every { applyForJobUseCase(eq(1L), any()) } returns flowOf(Result.success(sampleApplication))
        every { getMyApplicationsUseCase() } returns flowOf(Result.success(listOf(sampleApplication)))

        viewModel.onEvent(
            JobsUiEvent.SubmitApplication(
                jobId = 1L,
                fullName = "Mizanur Rahman",
                email = "mm923310@gmail.com",
                phone = "+8801700000000",
                resumeId = null,
                coverLetter = "Experienced lead architect ready to scale.",
                expectedSalary = 4500
            )
        )
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertFalse(state.showApplyDialog)
        assertTrue(state.successMessage?.contains("submitted successfully") == true)
        assertEquals(1, state.applications.size)
    }

    @Test
    fun testSetTabApplications_loadsApplications() = runTest {
        every { getMyApplicationsUseCase() } returns flowOf(Result.success(listOf(sampleApplication)))

        viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.APPLICATIONS))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals(JobsTab.APPLICATIONS, state.currentTab)
        assertEquals(1, state.applications.size)
        assertEquals("Senior Android Engineer", state.applications.first().jobTitle)
    }

    @Test
    fun testSetTabSaved_loadsSavedJobs() = runTest {
        val saved = sampleJob.copy(isSaved = true)
        every { getSavedJobsUseCase() } returns flowOf(Result.success(listOf(saved)))

        viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.SAVED))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals(JobsTab.SAVED, state.currentTab)
        assertEquals(1, state.savedJobs.size)
        assertTrue(state.savedJobs.first().isSaved)
    }

    @Test
    fun testSetTabInterviews_loadsInterviews() = runTest {
        every { getInterviewsUseCase() } returns flowOf(Result.success(listOf(sampleInterview)))

        viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.INTERVIEWS))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals(JobsTab.INTERVIEWS, state.currentTab)
        assertEquals(1, state.interviews.size)
        assertEquals("video", state.interviews.first().interviewType)
    }

    @Test
    fun testSetTabEmployer_loadsDashboardAndAts() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()

        viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.EMPLOYER))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals(JobsTab.EMPLOYER, state.currentTab)
        assertEquals(5, state.employerMetrics.totalJobs)
        assertEquals(1, state.atsColumns.size)
        assertEquals("applied", state.atsColumns.first().stageKey)
    }

    @Test
    fun testMoveCandidateStage_updatesStageAndRefreshesPipeline() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        every { updateAtsStageUseCase(100L, "interview", any()) } returns flowOf(Result.success(sampleApplication.copy(status = "interview")))

        viewModel.onEvent(JobsUiEvent.MoveCandidateStage(100L, "interview"))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals("Candidate moved to interview", state.successMessage)
        verify { updateAtsStageUseCase(100L, "interview", any()) }
    }

    @Test
    fun testRespondInterview_acceptUpdatesStatus() = runTest {
        every { respondInterviewUseCase(50L, "accept") } returns flowOf(Result.success(sampleInterview.copy(status = "confirmed")))
        every { getInterviewsUseCase() } returns flowOf(Result.success(listOf(sampleInterview.copy(status = "confirmed"))))

        viewModel.onEvent(JobsUiEvent.RespondInterview(50L, accept = true))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertEquals("Interview confirmed!", state.successMessage)
    }

    @Test
    fun testCreateJob_successUpdatesStateAndRefreshesJobs() = runTest {
        val created = sampleJob.copy(id = 99L, title = "Principal Software Architect")
        val params = mapOf("title" to "Principal Software Architect", "company_id" to 10L)
        every { createJobUseCase(params) } returns flowOf(Result.success(created))

        viewModel.onEvent(JobsUiEvent.CreateJob(params))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertFalse(state.showCreateJobDialog)
        assertTrue(state.successMessage?.contains("Job posted successfully") == true)
    }

    @Test
    fun testOpenCreateJobDialog_togglesState() = runTest {
        viewModel.onEvent(JobsUiEvent.OpenCreateJobDialog(true))
        assertTrue(viewModel.uiState.value.showCreateJobDialog)

        viewModel.onEvent(JobsUiEvent.OpenCreateJobDialog(false))
        assertFalse(viewModel.uiState.value.showCreateJobDialog)
    }

    @Test
    fun testDeleteJob_successRemovesJobFromList() = runTest {
        viewModel.onEvent(JobsUiEvent.DeleteJob(sampleJob.id))
        testDispatcher.scheduler.advanceUntilIdle()

        val state = viewModel.uiState.value
        assertFalse(state.jobs.any { it.id == sampleJob.id })
        assertEquals("Job deleted successfully.", state.successMessage)
    }

    @Test
    fun testClearMessages_clearsErrorAndSuccess() = runTest {
        viewModel.onEvent(JobsUiEvent.ClearMessages)
        val state = viewModel.uiState.value
        assertNull(state.errorMessage)
        assertNull(state.successMessage)
    }
}
