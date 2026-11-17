package com.connectly.android.feature.learning.presentation.learning

import com.connectly.android.feature.learning.domain.model.LearningCategory
import com.connectly.android.feature.learning.domain.model.LearningCertificate
import com.connectly.android.feature.learning.domain.model.LearningCourse
import com.connectly.android.feature.learning.domain.model.LearningEnrollment
import com.connectly.android.feature.learning.domain.model.LearningSection

enum class LearningTab {
    DISCOVER,
    MY_LEARNING,
    CERTIFICATES,
    MY_COURSES
}

data class LearningUiState(
    val isLoading: Boolean = false,
    val activeTab: LearningTab = LearningTab.DISCOVER,
    val categories: List<LearningCategory> = emptyList(),
    val selectedCategoryId: Long? = null,
    val selectedLevel: String? = null,
    val selectedType: String? = null,
    val courses: List<LearningCourse> = emptyList(),
    val myLearning: List<LearningEnrollment> = emptyList(),
    val certificates: List<LearningCertificate> = emptyList(),
    val instructorCourses: List<LearningCourse> = emptyList(),
    val isCreatingCourse: Boolean = false,
    val showCreateCourseDialog: Boolean = false,
    val createCourseSuccessMessage: String? = null,
    val selectedCourseCurriculum: List<LearningSection> = emptyList(),
    val isLoadingCurriculum: Boolean = false,
    val enrollingCourseIds: Set<Long> = emptySet(),
    val completingLessonIds: Set<Long> = emptySet(),
    val searchQuery: String = "",
    val enrolledSuccessMessage: String? = null,
    val enrollErrorMessage: String? = null,
    val errorMessage: String? = null
) {
    val inProgressCount: Int get() = myLearning.count { !it.isCompleted }
    val completedCount: Int get() = myLearning.count { it.isCompleted }
    val instructorCoursesCount: Int get() = instructorCourses.size
}

sealed interface LearningIntent {
    data class SelectTab(val tab: LearningTab) : LearningIntent
    data class SelectCategory(val categoryId: Long?) : LearningIntent
    data class SelectLevel(val level: String?) : LearningIntent
    data class SelectType(val type: String?) : LearningIntent
    data class SearchQueryChanged(val query: String) : LearningIntent
    data class EnrollCourse(val courseId: Long, val couponCode: String? = null) : LearningIntent
    data class LoadCurriculum(val courseId: Long) : LearningIntent
    data class CompleteLesson(val lessonId: Long, val courseId: Long) : LearningIntent
    data class CreateCourse(
        val title: String,
        val description: String,
        val categoryId: Long? = null,
        val type: String = "free",
        val price: Double = 0.0,
        val discountedPrice: Double? = null,
        val level: String = "all_levels",
        val language: String = "bn",
        val thumbnailUrl: String? = null,
        val publish: Boolean = true
    ) : LearningIntent
    data object LoadInstructorCourses : LearningIntent
    data class ToggleCreateCourseDialog(val show: Boolean) : LearningIntent
    data object Refresh : LearningIntent
    data object ClearMessage : LearningIntent
    data object ClearEnrollError : LearningIntent
}

sealed interface LearningUiEffect {
    data class NavigateToCourse(val slug: String) : LearningUiEffect
    data class NavigateToLesson(val lessonId: Long) : LearningUiEffect
    data class ShowSnackbar(val message: String) : LearningUiEffect
}
