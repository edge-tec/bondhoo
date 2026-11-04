package com.connectly.android.feature.learning.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.learning.domain.model.LearningCategory
import com.connectly.android.feature.learning.domain.model.LearningCertificate
import com.connectly.android.feature.learning.domain.model.LearningCourse
import com.connectly.android.feature.learning.domain.model.LearningEnrollment
import com.connectly.android.feature.learning.domain.model.LearningQuiz
import com.connectly.android.feature.learning.domain.model.LearningQuizResult
import com.connectly.android.feature.learning.domain.model.LearningSection
import com.connectly.android.feature.learning.domain.repository.LearningRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

class GetLearningCategoriesUseCase @Inject constructor(
    private val repository: LearningRepository
) {
    operator fun invoke(): Flow<Result<List<LearningCategory>>> = repository.getCategories()
}

class GetLearningCoursesUseCase @Inject constructor(
    private val repository: LearningRepository
) {
    operator fun invoke(
        query: String? = null,
        categoryId: Long? = null,
        level: String? = null,
        type: String? = null,
        skill: String? = null,
        sortBy: String? = null,
        page: Int = 1
    ): Flow<Result<List<LearningCourse>>> = repository.getCourses(query, categoryId, level, type, skill, sortBy, page)
}

class EnrollCourseUseCase @Inject constructor(
    private val repository: LearningRepository
) {
    operator fun invoke(courseId: Long, couponCode: String? = null): Flow<Result<LearningEnrollment>> =
        repository.enrollCourse(courseId, couponCode)
}

class GetMyLearningUseCase @Inject constructor(
    private val repository: LearningRepository
) {
    operator fun invoke(): Flow<Result<List<LearningEnrollment>>> = repository.getMyLearning()
}

class GetCurriculumUseCase @Inject constructor(
    private val repository: LearningRepository
) {
    operator fun invoke(courseId: Long): Flow<Result<List<LearningSection>>> = repository.getCurriculum(courseId)
}

class SyncLessonProgressUseCase @Inject constructor(
    private val repository: LearningRepository
) {
    operator fun invoke(lessonId: Long, positionSeconds: Int, markCompleted: Boolean): Flow<Result<Unit>> =
        repository.syncLessonProgress(lessonId, positionSeconds, markCompleted)
}

class GetQuizUseCase @Inject constructor(
    private val repository: LearningRepository
) {
    operator fun invoke(quizId: Long): Flow<Result<LearningQuiz>> = repository.getQuiz(quizId)
}

class SubmitQuizUseCase @Inject constructor(
    private val repository: LearningRepository
) {
    operator fun invoke(quizId: Long, answers: Map<Long, Any>): Flow<Result<LearningQuizResult>> =
        repository.submitQuiz(quizId, answers)
}

class GetMyCertificatesUseCase @Inject constructor(
    private val repository: LearningRepository
) {
    operator fun invoke(): Flow<Result<List<LearningCertificate>>> = repository.getMyCertificates()
}

