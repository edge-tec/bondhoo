package com.connectly.android.feature.jobs.data.repository

import com.connectly.android.core.model.*
import com.connectly.android.core.network.api.*
import com.connectly.android.feature.jobs.domain.repository.JobRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class JobRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi
) : JobRepository {

    override fun searchJobs(
        query: String?,
        categoryId: Long?,
        employmentType: String?,
        workplaceType: String?,
        location: String?,
        salaryMin: Int?,
        salaryMax: Int?,
        sortBy: String?
    ): Flow<Result<List<Job>>> = flow {
        try {
            val response = api.getJobs(
                query = query,
                categoryId = categoryId,
                employmentType = employmentType,
                workplaceType = workplaceType,
                location = location,
                salaryMin = salaryMin,
                salaryMax = salaryMax,
                sortBy = sortBy
            )
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()!!.data?.get("jobs") ?: emptyList()
                val items = dtos.map { it.toDomain() }
                emit(Result.success(items))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to load jobs" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun getJobDetails(id: Long): Flow<Result<Job>> = flow {
        try {
            val response = api.getJobDetails(id)
            val jobDto = response.body()?.data?.get("job")
            if (response.isSuccessful && jobDto != null) {
                emit(Result.success(jobDto.toDomain()))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to load job details" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun createJob(params: Map<String, Any?>): Flow<Result<Job>> = flow {
        try {
            val response = api.createJob(params)
            val jobDto = response.body()?.data?.get("job")
            if (response.isSuccessful && jobDto != null) {
                emit(Result.success(jobDto.toDomain()))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to create job" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun applyForJob(jobId: Long, params: Map<String, Any?>): Flow<Result<JobApplicationItem>> = flow {
        try {
            val response = api.applyForJob(jobId, params)
            val appDto = response.body()?.data?.get("application")
            if (response.isSuccessful && appDto != null) {
                emit(Result.success(appDto.toDomain()))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to apply for job" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun getMyApplications(): Flow<Result<List<JobApplicationItem>>> = flow {
        try {
            val response = api.getMyApplications()
            val apps = response.body()?.data ?: emptyList()
            if (response.isSuccessful) {
                emit(Result.success(apps.map { it.toDomain() }))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to load applications" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun saveJob(id: Long): Flow<Result<Unit>> = flow {
        try {
            val response = api.saveJob(id)
            if (response.isSuccessful) {
                emit(Result.success(Unit))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to save job" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun unsaveJob(id: Long): Flow<Result<Unit>> = flow {
        try {
            val response = api.unsaveJob(id)
            if (response.isSuccessful) {
                emit(Result.success(Unit))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to unsave job" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun getSavedJobs(): Flow<Result<List<Job>>> = flow {
        try {
            val response = api.getSavedJobs()
            val jobs = response.body()?.data?.get("jobs") ?: emptyList()
            if (response.isSuccessful) {
                emit(Result.success(jobs.map { it.toDomain() }))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to load saved jobs" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun getCandidateProfile(): Flow<Result<CandidateProfileItem>> = flow {
        try {
            val response = api.getCandidateProfile()
            val dto = response.body()?.data?.get("profile")
            if (response.isSuccessful && dto != null) {
                emit(Result.success(dto.toDomain()))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to load profile" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun updateCandidateProfile(params: Map<String, Any?>): Flow<Result<CandidateProfileItem>> = flow {
        try {
            val response = api.updateCandidateProfile(params)
            val dto = response.body()?.data?.get("profile")
            if (response.isSuccessful && dto != null) {
                emit(Result.success(dto.toDomain()))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to update profile" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun getCandidateResumes(): Flow<Result<List<CandidateResumeItem>>> = flow {
        try {
            val response = api.getCandidateResumes()
            val resumes = response.body()?.data?.get("resumes") ?: emptyList()
            if (response.isSuccessful) {
                emit(Result.success(resumes.map { it.toDomain() }))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to load resumes" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun getJobAlerts(): Flow<Result<List<JobAlertItem>>> = flow {
        try {
            val response = api.getJobAlerts()
            val alerts = response.body()?.data?.get("alerts") ?: emptyList()
            if (response.isSuccessful) {
                emit(Result.success(alerts.map { it.toDomain() }))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to load alerts" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun createJobAlert(params: Map<String, Any?>): Flow<Result<JobAlertItem>> = flow {
        try {
            val response = api.createJobAlert(params)
            val dto = response.body()?.data?.get("alert")
            if (response.isSuccessful && dto != null) {
                emit(Result.success(dto.toDomain()))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to create alert" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun getCompanies(mineOnly: Boolean): Flow<Result<List<CompanyItem>>> = flow {
        try {
            val response = api.getCompanies(if (mineOnly) true else null)
            val list = response.body()?.data?.get("companies") ?: emptyList()
            if (response.isSuccessful) {
                emit(Result.success(list.map { it.toDomain() }))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to load companies" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun createCompany(params: Map<String, Any?>): Flow<Result<CompanyItem>> = flow {
        try {
            val response = api.createCompany(params)
            val dto = response.body()?.data?.get("company")
            if (response.isSuccessful && dto != null) {
                emit(Result.success(dto.toDomain()))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to create company" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun getCompanyDashboard(companyId: Long): Flow<Result<EmployerMetrics>> = flow {
        try {
            val response = api.getCompanyDashboard(companyId)
            val dto = response.body()?.data
            if (response.isSuccessful && dto != null) {
                emit(Result.success(dto.metrics.toDomain()))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to load dashboard" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun getAtsPipeline(companyId: Long, jobId: Long?): Flow<Result<List<AtsColumn>>> = flow {
        try {
            val response = api.getAtsPipeline(companyId, jobId)
            val pipeline = response.body()?.data
            if (response.isSuccessful && pipeline != null) {
                emit(Result.success(pipeline.columns.map { it.toDomain() }))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to load ATS pipeline" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun updateAtsStage(
        applicationId: Long,
        stage: String,
        notes: String?
    ): Flow<Result<JobApplicationItem>> = flow {
        try {
            val response = api.updateAtsStage(applicationId, mapOf("stage" to stage, "notes" to notes))
            val appDto = response.body()?.data?.get("application")
            if (response.isSuccessful && appDto != null) {
                emit(Result.success(appDto.toDomain()))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to update stage" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun getInterviews(): Flow<Result<List<InterviewItem>>> = flow {
        try {
            val response = api.getInterviews()
            val list = response.body()?.data?.get("interviews") ?: emptyList()
            if (response.isSuccessful) {
                emit(Result.success(list.map { it.toDomain() }))
            } else {
                emit(Result.failure(Exception(response.message().ifEmpty { "Failed to load interviews" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }

    override fun respondInterview(interviewId: Long, response: String): Flow<Result<InterviewItem>> = flow {
        try {
            val res = api.respondInterview(interviewId, mapOf("status" to response))
            val dto = res.body()?.data?.get("interview")
            if (res.isSuccessful && dto != null) {
                emit(Result.success(dto.toDomain()))
            } else {
                emit(Result.failure(Exception(res.message().ifEmpty { "Failed to respond to interview" })))
            }
        } catch (e: Exception) {
            emit(Result.failure(e))
        }
    }
}

// -----------------------------------------------------------------
// Mappers
// -----------------------------------------------------------------
private fun JobDto.toDomain(): Job = Job(
    id = id,
    companyId = company_id,
    categoryId = category_id,
    title = title,
    slug = slug,
    companyName = company?.name ?: company_name,
    companyLogo = company?.logo ?: company_logo,
    location = location,
    country = country,
    city = city,
    locationType = location_type,
    locationTypeLabel = location_type_label,
    workplaceType = workplace_type,
    employmentType = employment_type,
    employmentTypeLabel = employment_type_label,
    experienceLevel = experience_level,
    salaryFormatted = salary_formatted,
    salaryMin = salary_min,
    salaryMax = salary_max,
    salaryCurrency = salary_currency,
    isNegotiable = salary_negotiable,
    vacancies = vacancies,
    description = description,
    responsibilities = responsibilities,
    requirements = requirements,
    qualifications = qualifications,
    benefits = benefits,
    deadlineFormatted = deadline_formatted ?: deadline,
    isExpired = is_expired,
    status = status,
    applicationsCount = applications_count,
    viewsCount = views_count,
    hasApplied = has_applied,
    isSaved = is_saved,
    skills = skills.map { it.name },
    isVerifiedCompany = company?.is_verified ?: false
)

private fun JobApplicationDto.toDomain(): JobApplicationItem = JobApplicationItem(
    id = id,
    jobPostingId = job_posting_id,
    companyId = company_id,
    userId = user_id,
    resumeId = resume_id,
    fullName = full_name,
    email = email,
    phone = phone,
    coverLetter = cover_letter,
    resumeUrl = resume_url,
    expectedSalary = expected_salary,
    status = status,
    statusLabel = status_label,
    statusColor = status_color,
    statusBg = status_bg,
    appliedAtFormatted = applied_at_human ?: applied_at ?: created_at,
    jobTitle = job_title,
    companyName = company_name
)

private fun CandidateProfileDto.toDomain(): CandidateProfileItem = CandidateProfileItem(
    id = id,
    userId = user_id,
    professionalTitle = professional_title,
    professionalSummary = professional_summary,
    experienceYears = experience_years,
    currentCompany = current_company,
    currentPosition = current_position,
    expectedSalary = expected_salary,
    salaryCurrency = salary_currency,
    availabilityStatus = availability_status,
    profileVisibility = profile_visibility
)

private fun CandidateResumeDto.toDomain(): CandidateResumeItem = CandidateResumeItem(
    id = id,
    userId = user_id,
    title = title,
    originalFilename = original_filename,
    mimeType = mime_type,
    fileSizeFormatted = file_size_formatted,
    isDefault = is_default
)

private fun InterviewDto.toDomain(): InterviewItem = InterviewItem(
    id = id,
    applicationId = application_id,
    companyId = company_id,
    companyName = company_name,
    interviewType = interview_type,
    scheduledAtFormatted = scheduled_at_formatted.ifEmpty { scheduled_at },
    durationMinutes = duration_minutes,
    timezone = timezone,
    meetingUrl = meeting_url,
    location = location,
    instructions = instructions,
    status = status,
    jobTitle = job_title,
    candidateName = candidate_name
)

private fun JobAlertDto.toDomain(): JobAlertItem = JobAlertItem(
    id = id,
    userId = user_id,
    title = title,
    keywords = keywords,
    frequency = frequency,
    isActive = is_active
)

private fun CompanyDto.toDomain(): CompanyItem = CompanyItem(
    id = id,
    name = name,
    slug = slug,
    logo = logo,
    coverImage = cover_image,
    description = description,
    industry = industry,
    companySize = company_size,
    foundedYear = founded_year,
    website = website,
    email = email,
    phone = phone,
    headquarters = headquarters,
    isVerified = is_verified,
    viewerRole = viewer_role,
    activeJobsCount = active_jobs_count,
    totalJobsCount = total_jobs_count
)

private fun AtsColumnDto.toDomain(): AtsColumn = AtsColumn(
    stageKey = stage_key,
    title = title,
    color = color,
    count = count,
    candidates = candidates.map {
        AtsCandidateItem(
            id = it.id,
            jobId = it.job_id,
            jobTitle = it.job_title,
            candidateName = it.candidate_name,
            candidateEmail = it.candidate_email,
            avatar = it.avatar,
            appliedAt = it.applied_at,
            expectedSalary = it.expected_salary
        )
    }
)

private fun EmployerMetricsDto.toDomain(): EmployerMetrics = EmployerMetrics(
    totalJobs = total_jobs,
    activeJobs = active_jobs,
    draftJobs = draft_jobs,
    pausedJobs = paused_jobs,
    closedJobs = closed_jobs,
    totalApplications = total_applications,
    newApplications = new_applications,
    shortlistedCandidates = shortlisted_candidates,
    interviewsScheduled = interviews_scheduled,
    offersSent = offers_sent,
    hiredCandidates = hired_candidates
)

