package com.connectly.android.feature.learning.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.learning.domain.model.LearningCategory
import com.connectly.android.feature.learning.domain.model.LearningCertificate
import com.connectly.android.feature.learning.domain.model.LearningCourse
import com.connectly.android.feature.learning.domain.model.LearningEnrollment
import com.connectly.android.feature.learning.domain.model.LearningQuiz
import com.connectly.android.feature.learning.domain.model.LearningQuizResult
import com.connectly.android.feature.learning.domain.model.LearningSection
import kotlinx.coroutines.flow.Flow

interface LearningRepository {
    fun getCategories(): Flow<Result<List<LearningCategory>>>

    fun getCourses(
        query: String? = null,
        categoryId: Long? = null,
        level: String? = null,
        type: String? = null,
        skill: String? = null,
        sortBy: String? = null,
        page: Int = 1
    ): Flow<Result<List<LearningCourse>>>

    fun getCourseDetails(slug: String): Flow<Result<LearningCourse>>

    fun getCurriculum(courseId: Long): Flow<Result<List<LearningSection>>>

    fun enrollCourse(courseId: Long, couponCode: String? = null): Flow<Result<LearningEnrollment>>

    fun getMyLearning(): Flow<Result<List<LearningEnrollment>>>

    fun syncLessonProgress(lessonId: Long, positionSeconds: Int, markCompleted: Boolean): Flow<Result<Unit>>

    fun getQuiz(quizId: Long): Flow<Result<LearningQuiz>>

    fun submitQuiz(quizId: Long, answers: Map<Long, Any>): Flow<Result<LearningQuizResult>>

    fun getMyCertificates(): Flow<Result<List<LearningCertificate>>>

    fun createCourse(
        title: String,
        description: String,
        categoryId: Long? = null,
        type: String = "free",
        price: Double = 0.0,
        discountedPrice: Double? = null,
        level: String = "all_levels",
        language: String = "bn",
        thumbnailUrl: String? = null,
        publish: Boolean = true
    ): Flow<Result<LearningCourse>>

    fun getInstructorCourses(): Flow<Result<List<LearningCourse>>>
}

