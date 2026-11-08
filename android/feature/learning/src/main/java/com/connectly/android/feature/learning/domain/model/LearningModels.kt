package com.connectly.android.feature.learning.domain.model

data class LearningCategory(
    val id: Long,
    val name: String,
    val slug: String,
    val icon: String?,
    val description: String?,
    val coursesCount: Int,
    val children: List<LearningCategory> = emptyList()
)

data class LearningCourse(
    val id: Long,
    val instructorId: Long,
    val categoryId: Long?,
    val title: String,
    val slug: String,
    val subtitle: String?,
    val description: String,
    val level: String,
    val language: String,
    val thumbnailUrl: String?,
    val promotionalVideoUrl: String?,
    val type: String,
    val price: Double,
    val discountedPrice: Double?,
    val currency: String,
    val status: String,
    val durationMinutes: Int,
    val totalSections: Int,
    val totalLessons: Int,
    val enrollmentsCount: Int,
    val ratingAvg: Double,
    val ratingCount: Int,
    val isFeatured: Boolean,
    val instructorName: String?,
    val categoryName: String?,
    val isEnrolled: Boolean = false
)

data class LearningSection(
    val id: Long,
    val courseId: Long,
    val title: String,
    val description: String?,
    val sortOrder: Int,
    val lessons: List<LearningLesson>
)

data class LearningLesson(
    val id: Long,
    val sectionId: Long,
    val courseId: Long,
    val title: String,
    val slug: String,
    val type: String,
    val content: String?,
    val videoUrl: String?,
    val videoDurationSeconds: Int,
    val sortOrder: Int,
    val isPreview: Boolean,
    val isMandatory: Boolean,
    val isCompleted: Boolean = false
)

data class LearningEnrollment(
    val id: Long,
    val courseId: Long,
    val courseTitle: String,
    val courseThumbnail: String?,
    val instructorName: String?,
    val status: String,
    val progressPercentage: Double,
    val completedLessons: Int,
    val totalLessons: Int,
    val isCompleted: Boolean
)

data class LearningQuiz(
    val id: Long,
    val lessonId: Long,
    val title: String,
    val instructions: String?,
    val timeLimitMinutes: Int,
    val passingScore: Int,
    val maxAttempts: Int,
    val questions: List<LearningQuizQuestion>
)

data class LearningQuizQuestion(
    val id: Long,
    val question: String,
    val type: String,
    val points: Int,
    val options: List<LearningQuizOption>
)

data class LearningQuizOption(
    val id: Long,
    val text: String
)

data class LearningQuizResult(
    val score: Double,
    val totalPoints: Int,
    val earnedPoints: Int,
    val passed: Boolean
)

data class LearningCertificate(
    val id: Long,
    val certificateCode: String,
    val courseTitle: String,
    val studentName: String,
    val instructorName: String,
    val issuedAt: String?,
    val pdfUrl: String?,
    val qrCodeUrl: String?,
    val isValid: Boolean
)

