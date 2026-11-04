package com.connectly.android.feature.jobs.presentation.jobs

import com.connectly.android.core.model.*

enum class JobsTab {
    FIND_JOBS,
    APPLICATIONS,
    SAVED,
    INTERVIEWS,
    EMPLOYER
}

data class JobsUiState(
    val currentTab: JobsTab = JobsTab.FIND_JOBS,
    val isLoading: Boolean = false,
    val searchQuery: String = "",
    val selectedWorkplaceType: String? = null,
    val selectedEmploymentType: String? = null,
    val jobs: List<Job> = emptyList(),
    val applications: List<JobApplicationItem> = emptyList(),
    val savedJobs: List<Job> = emptyList(),
    val interviews: List<InterviewItem> = emptyList(),
    val candidateProfile: CandidateProfileItem? = null,
    val candidateResumes: List<CandidateResumeItem> = emptyList(),
    val employerMetrics: EmployerMetrics = EmployerMetrics(),
    val atsColumns: List<AtsColumn> = emptyList(),
    val myCompanies: List<CompanyItem> = emptyList(),
    val selectedCompany: CompanyItem? = null,
    val selectedJobDetails: Job? = null,
    val showApplyDialog: Boolean = false,
    val showCreateJobDialog: Boolean = false,
    val errorMessage: String? = null,
    val successMessage: String? = null
)

sealed interface JobsUiEvent {
    data class SetTab(val tab: JobsTab) : JobsUiEvent
    data class UpdateSearchQuery(val query: String) : JobsUiEvent
    data class FilterWorkplace(val type: String?) : JobsUiEvent
    data class FilterEmployment(val type: String?) : JobsUiEvent
    data object Search : JobsUiEvent
    data class SelectJob(val job: Job) : JobsUiEvent
    data class ToggleSaveJob(val job: Job) : JobsUiEvent
    data class OpenApplyDialog(val job: Job) : JobsUiEvent
    data object CloseApplyDialog : JobsUiEvent
    data class SubmitApplication(
        val jobId: Long,
        val fullName: String,
        val email: String,
        val phone: String,
        val resumeId: Long?,
        val coverLetter: String?,
        val expectedSalary: Int?
    ) : JobsUiEvent
    data class WithdrawApplication(val applicationId: Long) : JobsUiEvent
    data class SelectCompany(val company: CompanyItem) : JobsUiEvent
    data class MoveCandidateStage(val applicationId: Long, val newStage: String) : JobsUiEvent
    data class RespondInterview(val interviewId: Long, val accept: Boolean) : JobsUiEvent
    data class CreateJob(val params: Map<String, Any?>) : JobsUiEvent
    data object ClearMessages : JobsUiEvent
}

