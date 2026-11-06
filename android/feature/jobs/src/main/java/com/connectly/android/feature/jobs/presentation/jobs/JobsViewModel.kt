package com.connectly.android.feature.jobs.presentation.jobs

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.model.CompanyItem
import com.connectly.android.core.model.Job
import com.connectly.android.feature.jobs.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class JobsViewModel @Inject constructor(
    private val searchJobsUseCase: SearchJobsUseCase,
    private val getJobDetailsUseCase: GetJobDetailsUseCase,
    private val createJobUseCase: CreateJobUseCase,
    private val applyForJobUseCase: ApplyForJobUseCase,
    private val getMyApplicationsUseCase: GetMyApplicationsUseCase,
    private val saveJobUseCase: SaveJobUseCase,
    private val unsaveJobUseCase: UnsaveJobUseCase,
    private val getSavedJobsUseCase: GetSavedJobsUseCase,
    private val getCandidateProfileUseCase: GetCandidateProfileUseCase,
    private val getCandidateResumesUseCase: GetCandidateResumesUseCase,
    private val getCompaniesUseCase: GetCompaniesUseCase,
    private val getCompanyDashboardUseCase: GetCompanyDashboardUseCase,
    private val getAtsPipelineUseCase: GetAtsPipelineUseCase,
    private val updateAtsStageUseCase: UpdateAtsStageUseCase,
    private val getInterviewsUseCase: GetInterviewsUseCase,
    private val scheduleInterviewUseCase: ScheduleInterviewUseCase,
    private val respondInterviewUseCase: RespondInterviewUseCase,
    private val deleteJobUseCase: DeleteJobUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(JobsUiState())
    val uiState: StateFlow<JobsUiState> = _uiState.asStateFlow()

    init {
        loadInitialData()
    }

    fun onEvent(event: JobsUiEvent) {
        when (event) {
            is JobsUiEvent.SetTab -> {
                _uiState.update { it.copy(currentTab = event.tab) }
                when (event.tab) {
                    JobsTab.FIND_JOBS -> searchJobs()
                    JobsTab.APPLICATIONS -> loadApplications()
                    JobsTab.SAVED -> loadSavedJobs()
                    JobsTab.INTERVIEWS -> loadInterviews()
                    JobsTab.EMPLOYER -> loadEmployerData()
                }
            }
            is JobsUiEvent.UpdateSearchQuery -> {
                _uiState.update { it.copy(searchQuery = event.query) }
            }
            is JobsUiEvent.FilterWorkplace -> {
                _uiState.update { it.copy(selectedWorkplaceType = event.type) }
                searchJobs()
            }
            is JobsUiEvent.FilterEmployment -> {
                _uiState.update { it.copy(selectedEmploymentType = event.type) }
                searchJobs()
            }
            is JobsUiEvent.Search -> {
                searchJobs()
            }
            is JobsUiEvent.SelectJob -> {
                loadJobDetails(event.job.id)
            }
            is JobsUiEvent.DismissJobDetails -> {
                _uiState.update { it.copy(selectedJobDetails = null) }
            }
            is JobsUiEvent.ToggleSaveJob -> {
                toggleSaveJob(event.job)
            }
            is JobsUiEvent.OpenApplyDialog -> {
                _uiState.update { it.copy(selectedJobDetails = event.job, showApplyDialog = true, errorMessage = null) }
            }
            is JobsUiEvent.CloseApplyDialog -> {
                _uiState.update { it.copy(showApplyDialog = false) }
            }
            is JobsUiEvent.SubmitApplication -> {
                submitApplication(event)
            }
            is JobsUiEvent.WithdrawApplication -> {
                withdrawApplication(event.applicationId)
            }
            is JobsUiEvent.SelectCompany -> {
                selectCompany(event.company)
            }
            is JobsUiEvent.MoveCandidateStage -> {
                moveStage(event.applicationId, event.newStage)
            }
            is JobsUiEvent.OpenScheduleInterviewDialog -> {
                _uiState.update { it.copy(schedulingCandidate = event.candidate) }
            }
            is JobsUiEvent.ScheduleInterview -> {
                scheduleInterview(
                    event.applicationId,
                    event.scheduledAt,
                    event.interviewType,
                    event.meetingUrl,
                    event.location,
                    event.instructions
                )
            }
            is JobsUiEvent.RespondInterview -> {
                respondInterview(event.interviewId, event.accept)
            }
            is JobsUiEvent.CreateJob -> {
                createJob(event.params)
            }
            is JobsUiEvent.DeleteJob -> {
                deleteJob(event.id)
            }
            is JobsUiEvent.OpenCreateJobDialog -> {
                _uiState.update { it.copy(showCreateJobDialog = event.show) }
            }
            is JobsUiEvent.ClearMessages -> {
                _uiState.update { it.copy(errorMessage = null, successMessage = null) }
            }
        }
    }

    private fun loadInitialData() {
        searchJobs()
        viewModelScope.launch {
            getCandidateProfileUseCase().collect { result ->
                result.onSuccess { profile ->
                    _uiState.update { it.copy(candidateProfile = profile) }
                }
            }
            getCandidateResumesUseCase().collect { result ->
                result.onSuccess { resumes ->
                    _uiState.update { it.copy(candidateResumes = resumes) }
                }
            }
            getCompaniesUseCase(mineOnly = true).collect { result ->
                result.onSuccess { companies ->
                    _uiState.update {
                        it.copy(
                            myCompanies = companies,
                            selectedCompany = companies.firstOrNull()
                        )
                    }
                    companies.firstOrNull()?.let { loadCompanyDashboard(it.id) }
                }
            }
        }
    }

    fun searchJobs() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            searchJobsUseCase(
                query = _uiState.value.searchQuery.ifEmpty { null },
                workplaceType = _uiState.value.selectedWorkplaceType,
                employmentType = _uiState.value.selectedEmploymentType
            ).collect { result ->
                result.onSuccess { list ->
                    _uiState.update { state ->
                        val appliedJobIds = state.applications.map { it.jobPostingId }.toSet()
                        val synchronized = list.map { job ->
                            if (appliedJobIds.contains(job.id)) job.copy(hasApplied = true) else job
                        }
                        state.copy(isLoading = false, jobs = synchronized)
                    }
                }.onFailure { err ->
                    _uiState.update { it.copy(isLoading = false, errorMessage = err.message) }
                }
            }
        }
    }

    private fun loadJobDetails(id: Long) {
        if (id <= 0) {
            _uiState.update { it.copy(selectedJobDetails = null) }
            return
        }
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            getJobDetailsUseCase(id).collect { result ->
                result.onSuccess { job ->
                    val hasApplied = job.hasApplied || _uiState.value.applications.any { it.jobPostingId == job.id }
                    _uiState.update { it.copy(isLoading = false, selectedJobDetails = job.copy(hasApplied = hasApplied)) }
                }.onFailure { err ->
                    _uiState.update { it.copy(isLoading = false, errorMessage = err.message) }
                }
            }
        }
    }

    private fun toggleSaveJob(job: Job) {
        viewModelScope.launch {
            val isSaved = job.isSaved
            val action = if (isSaved) unsaveJobUseCase(job.id) else saveJobUseCase(job.id)
            action.collect { result ->
                result.onSuccess {
                    _uiState.update { state ->
                        val updated = state.jobs.map {
                            if (it.id == job.id) it.copy(isSaved = !isSaved) else it
                        }
                        state.copy(
                            jobs = updated,
                            successMessage = if (isSaved) "Job removed from saved list" else "Job saved successfully!"
                        )
                    }
                }
            }
        }
    }

    private fun submitApplication(event: JobsUiEvent.SubmitApplication) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            val params = mapOf(
                "full_name" to event.fullName,
                "email" to event.email,
                "phone" to event.phone,
                "resume_id" to event.resumeId,
                "cover_letter" to event.coverLetter,
                "expected_salary" to event.expectedSalary
            )
            applyForJobUseCase(event.jobId, params).collect { result ->
                result.onSuccess { app ->
                    _uiState.update { state ->
                        val updatedJobs = state.jobs.map { j ->
                            if (j.id == event.jobId) j.copy(hasApplied = true) else j
                        }
                        val updatedSavedJobs = state.savedJobs.map { j ->
                            if (j.id == event.jobId) j.copy(hasApplied = true) else j
                        }
                        state.copy(
                            isLoading = false,
                            showApplyDialog = false,
                            errorMessage = null,
                            jobs = updatedJobs,
                            savedJobs = updatedSavedJobs,
                            selectedJobDetails = state.selectedJobDetails?.let {
                                if (it.id == event.jobId) it.copy(hasApplied = true) else it
                            },
                            successMessage = "Application submitted successfully! 🎯"
                        )
                    }
                    loadApplications()
                }.onFailure { err ->
                    _uiState.update { it.copy(isLoading = false, errorMessage = err.message) }
                }
            }
        }
    }

    private fun loadApplications() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            getMyApplicationsUseCase().collect { result ->
                result.onSuccess { list ->
                    _uiState.update { state ->
                        val appliedJobIds = list.map { it.jobPostingId }.toSet()
                        val updatedJobs = state.jobs.map { job ->
                            if (appliedJobIds.contains(job.id)) job.copy(hasApplied = true) else job
                        }
                        val updatedSavedJobs = state.savedJobs.map { job ->
                            if (appliedJobIds.contains(job.id)) job.copy(hasApplied = true) else job
                        }
                        state.copy(
                            isLoading = false,
                            applications = list,
                            jobs = updatedJobs,
                            savedJobs = updatedSavedJobs,
                            selectedJobDetails = state.selectedJobDetails?.let {
                                if (appliedJobIds.contains(it.id)) it.copy(hasApplied = true) else it
                            }
                        )
                    }
                }.onFailure { err ->
                    _uiState.update { it.copy(isLoading = false, errorMessage = err.message) }
                }
            }
        }
    }

    private fun loadSavedJobs() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            getSavedJobsUseCase().collect { result ->
                result.onSuccess { list ->
                    _uiState.update { it.copy(isLoading = false, savedJobs = list) }
                }.onFailure { err ->
                    _uiState.update { it.copy(isLoading = false, errorMessage = err.message) }
                }
            }
        }
    }

    private fun loadInterviews() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            getInterviewsUseCase().collect { result ->
                result.onSuccess { list ->
                    _uiState.update { it.copy(isLoading = false, interviews = list) }
                }.onFailure { err ->
                    _uiState.update { it.copy(isLoading = false, errorMessage = err.message) }
                }
            }
        }
    }

    private fun loadEmployerData() {
        val company = _uiState.value.selectedCompany
        if (company != null) {
            loadCompanyDashboard(company.id)
            loadAtsPipeline(company.id)
        }
    }

    private fun selectCompany(company: CompanyItem) {
        _uiState.update { it.copy(selectedCompany = company) }
        loadCompanyDashboard(company.id)
        loadAtsPipeline(company.id)
    }

    private fun loadCompanyDashboard(companyId: Long) {
        viewModelScope.launch {
            getCompanyDashboardUseCase(companyId).collect { result ->
                result.onSuccess { metrics ->
                    _uiState.update { it.copy(employerMetrics = metrics) }
                }
            }
        }
    }

    private fun loadAtsPipeline(companyId: Long) {
        viewModelScope.launch {
            getAtsPipelineUseCase(companyId).collect { result ->
                result.onSuccess { columns ->
                    _uiState.update { it.copy(atsColumns = columns) }
                }
            }
        }
    }

    private fun moveStage(applicationId: Long, stage: String) {
        viewModelScope.launch {
            updateAtsStageUseCase(applicationId, stage).collect { result ->
                result.onSuccess {
                    _uiState.value.selectedCompany?.id?.let { loadAtsPipeline(it) }
                    _uiState.update { it.copy(successMessage = "Candidate moved to $stage") }
                }
            }
        }
    }

    private fun respondInterview(interviewId: Long, accept: Boolean) {
        viewModelScope.launch {
            respondInterviewUseCase(interviewId, if (accept) "accept" else "decline").collect { result ->
                result.onSuccess {
                    loadInterviews()
                    _uiState.update { it.copy(successMessage = if (accept) "Interview confirmed!" else "Interview declined.") }
                }.onFailure { err ->
                    _uiState.update { it.copy(errorMessage = err.message) }
                }
            }
        }
    }

    private fun scheduleInterview(
        applicationId: Long,
        scheduledAt: String,
        interviewType: String,
        meetingUrl: String?,
        location: String?,
        instructions: String?
    ) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            scheduleInterviewUseCase(
                applicationId,
                scheduledAt,
                interviewType,
                meetingUrl,
                location,
                instructions
            ).collect { result ->
                result.onSuccess {
                    _uiState.update { state ->
                        state.copy(
                            isLoading = false,
                            schedulingCandidate = null,
                            successMessage = "Interview scheduled successfully! 📅"
                        )
                    }
                    loadInterviews()
                    _uiState.value.selectedCompany?.id?.let {
                        loadAtsPipeline(it)
                        loadCompanyDashboard(it)
                    }
                }.onFailure { err ->
                    _uiState.update { it.copy(isLoading = false, errorMessage = err.message) }
                }
            }
        }
    }

    private fun createJob(params: Map<String, Any?>) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            createJobUseCase(params).collect { result ->
                result.onSuccess { job ->
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            showCreateJobDialog = false,
                            successMessage = "Job posted successfully! 💼"
                        )
                    }
                    searchJobs()
                    loadEmployerData()
                }.onFailure { err ->
                    _uiState.update { it.copy(isLoading = false, errorMessage = err.message) }
                }
            }
        }
    }

    private fun withdrawApplication(applicationId: Long) {
        viewModelScope.launch {
            loadApplications()
            _uiState.update { it.copy(successMessage = "Application withdrawn.") }
        }
    }

    private fun deleteJob(id: Long) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            deleteJobUseCase(id).collect { result ->
                result.onSuccess {
                    _uiState.update { state ->
                        state.copy(
                            isLoading = false,
                            jobs = state.jobs.filter { it.id != id },
                            savedJobs = state.savedJobs.filter { it.id != id },
                            successMessage = "Job deleted successfully."
                        )
                    }
                }.onFailure { err ->
                    _uiState.update { it.copy(isLoading = false, errorMessage = err.message) }
                }
            }
        }
    }
}

