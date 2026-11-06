package com.connectly.android.feature.jobs.domain.repository

import com.connectly.android.core.model.*
import kotlinx.coroutines.flow.Flow

interface JobRepository {
    fun searchJobs(
        query: String? = null,
        categoryId: Long? = null,
        employmentType: String? = null,
        workplaceType: String? = null,
        location: String? = null,
        salaryMin: Int? = null,
        salaryMax: Int? = null,
        sortBy: String? = null
    ): Flow<Result<List<Job>>>

    fun getJobDetails(id: Long): Flow<Result<Job>>
    fun createJob(params: Map<String, Any?>): Flow<Result<Job>>
    fun deleteJob(id: Long): Flow<Result<Unit>>
    fun applyForJob(jobId: Long, params: Map<String, Any?>): Flow<Result<JobApplicationItem>>
    fun getMyApplications(): Flow<Result<List<JobApplicationItem>>>
    fun saveJob(id: Long): Flow<Result<Unit>>
    fun unsaveJob(id: Long): Flow<Result<Unit>>
    fun getSavedJobs(): Flow<Result<List<Job>>>
    fun getCandidateProfile(): Flow<Result<CandidateProfileItem>>
    fun updateCandidateProfile(params: Map<String, Any?>): Flow<Result<CandidateProfileItem>>
    fun getCandidateResumes(): Flow<Result<List<CandidateResumeItem>>>
    fun getJobAlerts(): Flow<Result<List<JobAlertItem>>>
    fun createJobAlert(params: Map<String, Any?>): Flow<Result<JobAlertItem>>
    fun getCompanies(mineOnly: Boolean = false): Flow<Result<List<CompanyItem>>>
    fun createCompany(params: Map<String, Any?>): Flow<Result<CompanyItem>>
    fun getCompanyDashboard(companyId: Long): Flow<Result<EmployerMetrics>>
    fun getAtsPipeline(companyId: Long, jobId: Long? = null): Flow<Result<List<AtsColumn>>>
    fun updateAtsStage(applicationId: Long, stage: String, notes: String? = null): Flow<Result<JobApplicationItem>>
    fun getInterviews(): Flow<Result<List<InterviewItem>>>
    fun scheduleInterview(
        applicationId: Long,
        scheduledAt: String,
        interviewType: String = "video",
        meetingUrl: String? = null,
        location: String? = null,
        instructions: String? = null
    ): Flow<Result<InterviewItem>>
    fun respondInterview(interviewId: Long, response: String): Flow<Result<InterviewItem>>
}

