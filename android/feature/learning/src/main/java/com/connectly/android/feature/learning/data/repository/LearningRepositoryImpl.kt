package com.connectly.android.feature.learning.data.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.EnrollCourseRequest
import com.connectly.android.core.network.api.LearningCategoryDto
import com.connectly.android.core.network.api.LearningCourseDto
import com.connectly.android.core.network.api.UpdateLessonProgressRequest
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
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.jsonObject
import kotlinx.serialization.json.jsonPrimitive
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class LearningRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi
) : LearningRepository {

    private fun extractErrorMessage(errorBodyString: String?, fallback: String): String {
        if (errorBodyString.isNullOrBlank()) return fallback
        return try {
            val obj = Json.parseToJsonElement(errorBodyString).jsonObject
            obj["message"]?.jsonPrimitive?.content?.takeIf { it.isNotBlank() } ?: fallback
        } catch (_: Exception) {
            "\"message\"\\s*:\\s*\"([^\"]+)\"".toRegex().find(errorBodyString)?.groupValues?.getOrNull(1) ?: fallback
        }
    }

    private fun LearningCategoryDto.toDomain(): LearningCategory = LearningCategory(
        id = id,
        name = name,
        slug = slug,
        icon = icon,
        description = description,
        coursesCount = courses_count,
        children = children.map { it.toDomain() }
    )

    private fun LearningCourseDto.toDomain(overrideEnrolled: Boolean? = null): LearningCourse = LearningCourse(
        id = id,
        instructorId = instructor_id,
        categoryId = category_id,
        title = title,
        slug = slug,
        subtitle = subtitle,
        description = description,
        level = level,
        language = language,
        thumbnailUrl = thumbnail_url,
        promotionalVideoUrl = promotional_video_url,
        type = type,
        price = price,
        discountedPrice = discounted_price,
        currency = currency,
        status = status,
        durationMinutes = duration_minutes,
        totalSections = total_sections,
        totalLessons = total_lessons,
        enrollmentsCount = enrollments_count,
        ratingAvg = rating_avg,
        ratingCount = rating_count,
        isFeatured = is_featured,
        instructorName = instructor?.name,
        categoryName = category?.name,
        isEnrolled = overrideEnrolled ?: (is_enrolled ?: false)
    )

    override fun getCategories(): Flow<Result<List<LearningCategory>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getLearningCategories()
            if (response.isSuccessful && response.body() != null) {
                val dtos = response.body()?.data ?: emptyList()
                emit(Result.Success(dtos.map { it.toDomain() }))
            } else {
                val msg = extractErrorMessage(response.errorBody()?.string(), response.body()?.message ?: "Failed to fetch categories")
                emit(Result.Error(Exception(msg)))
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
                emit(Result.Success(dtos.map { it.toDomain() }))
            } else {
                val msg = extractErrorMessage(response.errorBody()?.string(), response.body()?.message ?: "Failed to fetch courses")
                emit(Result.Error(Exception(msg)))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun getCourseDetails(slug: String): Flow<Result<LearningCourse>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getLearningCourseDetails(slug)
            val payload = response.body()?.data
            if (response.isSuccessful && payload?.course != null) {
                emit(Result.Success(payload.course!!.toDomain(overrideEnrolled = payload.is_enrolled)))
            } else {
                val msg = extractErrorMessage(response.errorBody()?.string(), "Course not found")
                emit(Result.Error(Exception(msg)))
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
                                isCompleted = lDto.is_completed
                            )
                        }
                    )
                }
                emit(Result.Success(sections))
            } else {
                val msg = extractErrorMessage(response.errorBody()?.string(), "Failed to fetch curriculum")
                emit(Result.Error(Exception(msg)))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun enrollCourse(courseId: Long, couponCode: String?): Flow<Result<LearningEnrollment>> = flow {
        emit(Result.Loading)
        try {
            val body = EnrollCourseRequest(coupon_code = couponCode?.trim()?.takeIf { it.isNotBlank() })
            val response = api.enrollInCourse(courseId, body)
            if (response.isSuccessful && response.body()?.data != null) {
                val dto = response.body()!!.data
                val enrollment = LearningEnrollment(
                    id = dto.id,
                    courseId = if (dto.course_id != 0L) dto.course_id else courseId,
                    courseTitle = dto.course?.title?.takeIf { it.isNotBlank() } ?: "Enrolled Course",
                    courseThumbnail = dto.course?.thumbnail_url,
                    instructorName = dto.course?.instructor?.name,
                    status = dto.status,
                    progressPercentage = dto.course_progress?.percentage ?: 0.0,
                    completedLessons = dto.course_progress?.completed_lessons_count ?: 0,
                    totalLessons = dto.course_progress?.total_lessons_count ?: (dto.course?.total_lessons ?: 0),
                    isCompleted = dto.status == "completed" || (dto.course_progress?.is_completed == true)
                )
                emit(Result.Success(enrollment))
            } else {
                val fallback = when (response.code()) {
                    401 -> "Please sign in to enroll in this course."
                    403 -> "You are not authorized to enroll in this course."
                    422 -> "Could not complete enrollment. Please check course eligibility or wallet balance."
                    else -> "Enrollment failed (${response.code()})"
                }
                val msg = extractErrorMessage(response.errorBody()?.string(), response.body()?.message ?: fallback)
                emit(Result.Error(Exception(msg)))
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
                val list = dtos
                    .filter { it.status == "active" || it.status == "completed" }
                    .map { dto ->
                        LearningEnrollment(
                            id = dto.id,
                            courseId = dto.course_id,
                            courseTitle = dto.course?.title ?: "Course",
                            courseThumbnail = dto.course?.thumbnail_url,
                            instructorName = dto.course?.instructor?.name,
                            status = dto.status,
                            progressPercentage = if (dto.status == "completed") 100.0 else (dto.course_progress?.percentage ?: 0.0),
                            completedLessons = dto.course_progress?.completed_lessons_count ?: 0,
                            totalLessons = dto.course_progress?.total_lessons_count ?: (dto.course?.total_lessons ?: 0),
                            isCompleted = dto.status == "completed" || (dto.course_progress?.is_completed == true)
                        )
                    }
                emit(Result.Success(list))
            } else {
                val msg = extractErrorMessage(response.errorBody()?.string(), "Failed to fetch my learning list")
                emit(Result.Error(Exception(msg)))
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
                UpdateLessonProgressRequest(
                    position_seconds = positionSeconds,
                    mark_completed = markCompleted
                )
            )
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                val msg = extractErrorMessage(response.errorBody()?.string(), "Progress sync failed (${response.code()})")
                emit(Result.Error(Exception(msg)))
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
                val dto = response.body()!!.data
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
            val answersJson = kotlinx.serialization.json.buildJsonObject {
                answers.forEach { (k, v) ->
                    put(k.toString(), kotlinx.serialization.json.JsonPrimitive(v.toString()))
                }
            }
            val submitBody = kotlinx.serialization.json.buildJsonObject {
                put("answers", answersJson)
            }
            val submitRes = api.submitQuizAttempt(attemptId, submitBody)
            if (submitRes.isSuccessful && submitRes.body() != null) {
                val dto = submitRes.body()!!.data
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
