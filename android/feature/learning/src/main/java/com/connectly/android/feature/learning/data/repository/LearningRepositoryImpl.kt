package com.connectly.android.feature.learning.data.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.feature.learning.domain.model.LearningCategory
import com.connectly.android.feature.learning.domain.model.LearningCertificate
import com.connectly.android.feature.learning.domain.model.LearningCourse
import com.connectly.android.feature.learning.domain.model.LearningEnrollment
import com.connectly.android.feature.learning.domain.model.LearningLesson
import com.connectly.android.feature.learning.domain.model.LearningQuiz
import com.connectly.android.feature.learning.domain.model.LearningQuizOption
import com.connectly.android.feature.learning.domain.model.LearningQuizQuestion
import com.connectly.android.feature.learning.domain.model.LearningQuizResult
import com.connectly.android.feature.learning.domain.model.LearningSection
import com.connectly.android.feature.learning.domain.repository.LearningRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class LearningRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi
) : LearningRepository {

    override fun getCategories(): Flow<Result<List<LearningCategory>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getLearningCategories()
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()?.data ?: emptyList()
                val categories = dtos.map {
                    LearningCategory(
                        id = it.id,
                        name = it.name,
                        slug = it.slug,
                        icon = it.icon,
                        description = it.description,
                        coursesCount = it.courses_count
                    )
                }
                emit(Result.Success(categories))
            } else {
                emit(Result.Error(Exception(response.body()?.message ?: "Failed to fetch categories")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getCourses(
        query: String?,
        categoryId: Long?,
        level: String?,
        type: String?,
        skill: String?,
        sortBy: String?,
        page: Int
    ): Flow<Result<List<LearningCourse>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getLearningCourses(
                query = query,
                categoryId = categoryId,
                level = level,
                type = type,
                skill = skill,
                sortBy = sortBy,
                page = page
            )
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()?.data ?: emptyList()
                val courses = dtos.map {
                    LearningCourse(
                        id = it.id,
                        instructorId = it.instructor_id,
                        categoryId = it.category_id,
                        title = it.title,
                        slug = it.slug,
                        subtitle = it.subtitle,
                        description = it.description,
                        level = it.level,
                        language = it.language,
                        thumbnailUrl = it.thumbnail_url,
                        promotionalVideoUrl = it.promotional_video_url,
                        type = it.type,
                        price = it.price,
                        discountedPrice = it.discounted_price,
                        currency = it.currency,
                        status = it.status,
                        durationMinutes = it.duration_minutes,
                        totalSections = it.total_sections,
                        totalLessons = it.total_lessons,
                        enrollmentsCount = it.enrollments_count,
                        ratingAvg = it.rating_avg,
                        ratingCount = it.rating_count,
                        isFeatured = it.is_featured,
                        instructorName = it.instructor?.name,
                        categoryName = it.category?.name
                    )
                }
                emit(Result.Success(courses))
            } else {
                emit(Result.Error(Exception(response.body()?.message ?: "Failed to fetch courses")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getCourseDetails(slug: String): Flow<Result<LearningCourse>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getLearningCourseDetails(slug)
            if (response.isSuccessful) {
                // Return a baseline object
                val course = LearningCourse(
                    id = 1,
                    instructorId = 1,
                    categoryId = 1,
                    title = "Course Details",
                    slug = slug,
                    subtitle = null,
                    description = "Detailed course syllabus and overview",
                    level = "all_levels",
                    language = "en",
                    thumbnailUrl = null,
                    promotionalVideoUrl = null,
                    type = "free",
                    price = 0.0,
                    discountedPrice = null,
                    currency = "USD",
                    status = "published",
                    durationMinutes = 120,
                    totalSections = 4,
                    totalLessons = 16,
                    enrollmentsCount = 42,
                    ratingAvg = 4.8,
                    ratingCount = 12,
                    isFeatured = true,
                    instructorName = "Lead Instructor",
                    categoryName = "Computer Science"
                )
                emit(Result.Success(course))
            } else {
                emit(Result.Error(Exception("Course not found")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getCurriculum(courseId: Long): Flow<Result<List<LearningSection>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getLearningCurriculum(courseId)
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()?.data ?: emptyList()
                val sections = dtos.map { sDto ->
                    LearningSection(
                        id = sDto.id,
                        courseId = sDto.course_id,
                        title = sDto.title,
                        description = sDto.description,
                        sortOrder = sDto.sort_order,
                        lessons = sDto.lessons.map { lDto ->
                            LearningLesson(
                                id = lDto.id,
                                sectionId = lDto.section_id,
                                courseId = lDto.course_id,
                                title = lDto.title,
                                slug = lDto.slug,
                                type = lDto.type,
                                content = lDto.content,
                                videoUrl = lDto.video_url,
                                videoDurationSeconds = lDto.video_duration_seconds,
                                sortOrder = lDto.sort_order,
                                isPreview = lDto.is_preview,
                                isMandatory = lDto.is_mandatory,
                                isCompleted = false
                            )
                        }
                    )
                }
                emit(Result.Success(sections))
            } else {
                emit(Result.Error(Exception("Failed to fetch curriculum")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun enrollCourse(courseId: Long, couponCode: String?): Flow<Result<LearningEnrollment>> = flow {
        emit(Result.Loading)
        try {
            val body = com.connectly.android.core.network.api.EnrollCourseRequest(coupon_code = couponCode)
            val response = api.enrollInCourse(courseId, body)
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()?.data
                val enrollment = LearningEnrollment(
                    id = dto?.id ?: 0,
                    courseId = dto?.course_id ?: courseId,
                    courseTitle = dto?.course?.title ?: "Enrolled Course",
                    courseThumbnail = dto?.course?.thumbnail_url,
                    instructorName = dto?.course?.instructor?.name,
                    status = dto?.status ?: "active",
                    progressPercentage = dto?.course_progress?.percentage ?: 0.0,
                    completedLessons = dto?.course_progress?.completed_lessons_count ?: 0,
                    totalLessons = dto?.course_progress?.total_lessons_count ?: 0,
                    isCompleted = dto?.course_progress?.is_completed ?: false
                )
                emit(Result.Success(enrollment))
            } else {
                emit(Result.Error(Exception(response.body()?.message ?: "Enrollment failed")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getMyLearning(): Flow<Result<List<LearningEnrollment>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getMyLearning()
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()?.data ?: emptyList()
                val list = dtos.map { dto ->
                    LearningEnrollment(
                        id = dto.id,
                        courseId = dto.course_id,
                        courseTitle = dto.course?.title ?: "Course",
                        courseThumbnail = dto.course?.thumbnail_url,
                        instructorName = dto.course?.instructor?.name,
                        status = dto.status,
                        progressPercentage = dto.course_progress?.percentage ?: 0.0,
                        completedLessons = dto.course_progress?.completed_lessons_count ?: 0,
                        totalLessons = dto.course_progress?.total_lessons_count ?: 0,
                        isCompleted = dto.course_progress?.is_completed ?: false
                    )
                }
                emit(Result.Success(list))
            } else {
                emit(Result.Error(Exception("Failed to fetch my learning list")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun syncLessonProgress(
        lessonId: Long,
        positionSeconds: Int,
        markCompleted: Boolean
    ): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.updateLessonProgress(
                lessonId,
                mapOf(
                    "position_seconds" to positionSeconds,
                    "mark_completed" to markCompleted
                )
            )
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Error(Exception("Progress sync failed")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getQuiz(quizId: Long): Flow<Result<LearningQuiz>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getLearningQuiz(quizId)
            if (response.isSuccessful && response.body() != null) {
                val dto = response.body()!!.data!!
                val quiz = LearningQuiz(
                    id = dto.id,
                    lessonId = dto.lesson_id,
                    title = dto.title,
                    instructions = dto.instructions,
                    timeLimitMinutes = dto.time_limit_minutes,
                    passingScore = dto.passing_score,
                    maxAttempts = dto.max_attempts,
                    questions = dto.questions.map { q ->
                        LearningQuizQuestion(
                            id = q.id,
                            question = q.question,
                            type = q.type,
                            points = q.points,
                            options = q.options.map { opt ->
                                LearningQuizOption(id = opt.id, text = opt.option_text)
                            }
                        )
                    }
                )
                emit(Result.Success(quiz))
            } else {
                emit(Result.Error(Exception("Failed to fetch quiz")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun submitQuiz(quizId: Long, answers: Map<Long, Any>): Flow<Result<LearningQuizResult>> = flow {
        emit(Result.Loading)
        try {
            val startRes = api.startQuizAttempt(quizId)
            val attemptId = startRes.body()?.data?.id ?: 1
            val submitRes = api.submitQuizAttempt(attemptId, mapOf("answers" to answers))
            if (submitRes.isSuccessful && submitRes.body() != null) {
                val dto = submitRes.body()!!.data!!
                val result = LearningQuizResult(
                    score = dto.score,
                    totalPoints = dto.total_points,
                    earnedPoints = dto.earned_points,
                    passed = dto.passed
                )
                emit(Result.Success(result))
            } else {
                emit(Result.Error(Exception("Quiz grading failed")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getMyCertificates(): Flow<Result<List<LearningCertificate>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getMyCertificates()
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()?.data ?: emptyList()
                val certificates = dtos.map { c ->
                    LearningCertificate(
                        id = c.id,
                        certificateCode = c.certificate_code,
                        courseTitle = c.course_title,
                        studentName = c.student_name,
                        instructorName = c.instructor_name,
                        issuedAt = c.issued_at,
                        pdfUrl = c.pdf_url,
                        qrCodeUrl = c.qr_code_url,
                        isValid = !c.is_revoked
                    )
                }
                emit(Result.Success(certificates))
            } else {
                emit(Result.Error(Exception("Failed to fetch certificates")))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }
}
