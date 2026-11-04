package com.connectly.android.core.model

data class Job(
    val id: Long,
    val companyId: Long? = null,
    val categoryId: Long? = null,
    val title: String,
    val slug: String? = null,
    val companyName: String,
    val companyLogo: String? = null,
    val location: String,
    val country: String? = null,
    val city: String? = null,
    val locationType: String = "onsite",
    val locationTypeLabel: String = "📍 অনসাইট (অফিস)",
    val workplaceType: String = "onsite",
    val employmentType: String = "full_time",
    val employmentTypeLabel: String = "ফুল-টাইম (Full-time)",
    val experienceLevel: String = "mid",
    val salaryFormatted: String = "আলোচনা সাপেক্ষে",
    val salaryMin: Int? = null,
    val salaryMax: Int? = null,
    val salaryCurrency: String = "BDT",
    val isNegotiable: Boolean = false,
    val vacancies: Int = 1,
    val description: String = "",
    val responsibilities: String? = null,
    val requirements: String? = null,
    val qualifications: String? = null,
    val benefits: String? = null,
    val deadlineFormatted: String? = null,
    val isExpired: Boolean = false,
    val status: String = "active",
    val applicationsCount: Int = 0,
    val viewsCount: Int = 0,
    val hasApplied: Boolean = false,
    val isSaved: Boolean = false,
    val skills: List<String> = emptyList(),
    val isVerifiedCompany: Boolean = false
)

data class CompanyItem(
    val id: Long,
    val name: String,
    val slug: String? = null,
    val logo: String? = null,
    val coverImage: String? = null,
    val description: String? = null,
    val industry: String? = null,
    val companySize: String? = null,
    val foundedYear: Int? = null,
    val website: String? = null,
    val email: String? = null,
    val phone: String? = null,
    val headquarters: String? = null,
    val isVerified: Boolean = false,
    val viewerRole: String? = null,
    val activeJobsCount: Int = 0,
    val totalJobsCount: Int = 0
)

data class JobApplicationItem(
    val id: Long,
    val jobPostingId: Long,
    val companyId: Long? = null,
    val userId: Long,
    val resumeId: Long? = null,
    val fullName: String,
    val email: String,
    val phone: String,
    val coverLetter: String? = null,
    val resumeUrl: String? = null,
    val expectedSalary: Int? = null,
    val status: String = "applied",
    val statusLabel: String = "দাখিলকৃত (Applied)",
    val statusColor: String = "#0284c7",
    val statusBg: String = "#e0f2fe",
    val appliedAtFormatted: String? = null,
    val jobTitle: String? = null,
    val companyName: String? = null
)

data class CandidateProfileItem(
    val id: Long = 0,
    val userId: Long = 0,
    val professionalTitle: String? = null,
    val professionalSummary: String? = null,
    val experienceYears: Int = 0,
    val currentCompany: String? = null,
    val currentPosition: String? = null,
    val expectedSalary: Int? = null,
    val salaryCurrency: String = "BDT",
    val availabilityStatus: String = "open_to_work",
    val profileVisibility: String = "public"
)

data class CandidateResumeItem(
    val id: Long,
    val userId: Long,
    val title: String,
    val originalFilename: String,
    val mimeType: String,
    val fileSizeFormatted: String,
    val isDefault: Boolean = false
)

data class InterviewItem(
    val id: Long,
    val applicationId: Long,
    val companyId: Long? = null,
    val companyName: String? = null,
    val interviewType: String = "video",
    val scheduledAtFormatted: String = "",
    val durationMinutes: Int = 45,
    val timezone: String = "Asia/Dhaka",
    val meetingUrl: String? = null,
    val location: String? = null,
    val instructions: String? = null,
    val status: String = "scheduled",
    val jobTitle: String? = null,
    val candidateName: String? = null
)

data class JobAlertItem(
    val id: Long,
    val userId: Long,
    val title: String,
    val keywords: String? = null,
    val frequency: String = "daily",
    val isActive: Boolean = true
)

data class AtsColumn(
    val stageKey: String,
    val title: String,
    val color: String,
    val count: Int,
    val candidates: List<AtsCandidateItem> = emptyList()
)

data class AtsCandidateItem(
    val id: Long,
    val jobId: Long,
    val jobTitle: String?,
    val candidateName: String,
    val candidateEmail: String,
    val avatar: String? = null,
    val appliedAt: String? = null,
    val expectedSalary: Int? = null
)

data class EmployerMetrics(
    val totalJobs: Int = 0,
    val activeJobs: Int = 0,
    val draftJobs: Int = 0,
    val pausedJobs: Int = 0,
    val closedJobs: Int = 0,
    val totalApplications: Int = 0,
    val newApplications: Int = 0,
    val shortlistedCandidates: Int = 0,
    val interviewsScheduled: Int = 0,
    val offersSent: Int = 0,
    val hiredCandidates: Int = 0
)

