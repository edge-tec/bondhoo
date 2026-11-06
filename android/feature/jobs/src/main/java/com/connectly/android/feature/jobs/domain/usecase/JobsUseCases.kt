package com.connectly.android.feature.jobs.domain.usecase

import com.connectly.android.core.model.*
import com.connectly.android.feature.jobs.domain.repository.JobRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

class SearchJobsUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(
        query: String? = null,
        categoryId: Long? = null,
        employmentType: String? = null,
        workplaceType: String? = null,
        location: String? = null,
        salaryMin: Int? = null,
        salaryMax: Int? = null,
        sortBy: String? = null
    ): Flow<Result<List<Job>>> = repository.searchJobs(
        query, categoryId, employmentType, workplaceType, location, salaryMin, salaryMax, sortBy
    )
}

class GetJobDetailsUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(id: Long): Flow<Result<Job>> = repository.getJobDetails(id)
}

class CreateJobUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(params: Map<String, Any?>): Flow<Result<Job>> = repository.createJob(params)
}

class DeleteJobUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(id: Long): Flow<Result<Unit>> = repository.deleteJob(id)
}

class ApplyForJobUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(jobId: Long, params: Map<String, Any?>): Flow<Result<JobApplicationItem>> =
        repository.applyForJob(jobId, params)
}

class GetMyApplicationsUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(): Flow<Result<List<JobApplicationItem>>> = repository.getMyApplications()
}

class SaveJobUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(id: Long): Flow<Result<Unit>> = repository.saveJob(id)
}

class UnsaveJobUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(id: Long): Flow<Result<Unit>> = repository.unsaveJob(id)
}

class GetSavedJobsUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(): Flow<Result<List<Job>>> = repository.getSavedJobs()
}

class GetCandidateProfileUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(): Flow<Result<CandidateProfileItem>> = repository.getCandidateProfile()
}

class UpdateCandidateProfileUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(params: Map<String, Any?>): Flow<Result<CandidateProfileItem>> =
        repository.updateCandidateProfile(params)
}

class GetCandidateResumesUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(): Flow<Result<List<CandidateResumeItem>>> = repository.getCandidateResumes()
}

class GetJobAlertsUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(): Flow<Result<List<JobAlertItem>>> = repository.getJobAlerts()
}

class CreateJobAlertUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(params: Map<String, Any?>): Flow<Result<JobAlertItem>> = repository.createJobAlert(params)
}

class GetCompaniesUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(mineOnly: Boolean = false): Flow<Result<List<CompanyItem>>> =
        repository.getCompanies(mineOnly)
}

class CreateCompanyUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(params: Map<String, Any?>): Flow<Result<CompanyItem>> =
        repository.createCompany(params)
}

class GetCompanyDashboardUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(companyId: Long): Flow<Result<EmployerMetrics>> =
        repository.getCompanyDashboard(companyId)
}

class GetAtsPipelineUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(companyId: Long, jobId: Long? = null): Flow<Result<List<AtsColumn>>> =
        repository.getAtsPipeline(companyId, jobId)
}

class UpdateAtsStageUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(
        applicationId: Long,
        stage: String,
        notes: String? = null
    ): Flow<Result<JobApplicationItem>> =
        repository.updateAtsStage(applicationId, stage, notes)
}

class GetInterviewsUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(): Flow<Result<List<InterviewItem>>> = repository.getInterviews()
}

class ScheduleInterviewUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(
        applicationId: Long,
        scheduledAt: String,
        interviewType: String = "video",
        meetingUrl: String? = null,
        location: String? = null,
        instructions: String? = null
    ): Flow<Result<InterviewItem>> =
        repository.scheduleInterview(
            applicationId,
            scheduledAt,
            interviewType,
            meetingUrl,
            location,
            instructions
        )
}

class RespondInterviewUseCase @Inject constructor(
    private val repository: JobRepository
) {
    operator fun invoke(interviewId: Long, response: String): Flow<Result<InterviewItem>> =
        repository.respondInterview(interviewId, response)
}

