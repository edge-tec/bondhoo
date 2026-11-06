package com.connectly.android.feature.learning.presentation.learning

import com.connectly.android.feature.learning.domain.model.LearningCategory
import com.connectly.android.feature.learning.domain.model.LearningCertificate
import com.connectly.android.feature.learning.domain.model.LearningCourse
import com.connectly.android.feature.learning.domain.model.LearningEnrollment
import com.connectly.android.feature.learning.domain.model.LearningSection

enum class LearningTab {
    DISCOVER,
    MY_LEARNING,
    CERTIFICATES
}

data class LearningUiState(
    val isLoading: Boolean = false,
    val activeTab: LearningTab = LearningTab.DISCOVER,
    val categories: List<LearningCategory> = emptyList(),
    val selectedCategoryId: Long? = null,
    val courses: List<LearningCourse> = emptyList(),
    val myLearning: List<LearningEnrollment> = emptyList(),
    val certificates: List<LearningCertificate> = emptyList(),
    val selectedCourseCurriculum: List<LearningSection> = emptyList(),
    val isLoadingCurriculum: Boolean = false,
    val searchQuery: String = "",
    val enrolledSuccessMessage: String? = null,
    val errorMessage: String? = null
)

sealed interface LearningIntent {
    data class SelectTab(val tab: LearningTab) : LearningIntent
    data class SelectCategory(val categoryId: Long?) : LearningIntent
    data class SearchQueryChanged(val query: String) : LearningIntent
    data class EnrollCourse(val courseId: Long) : LearningIntent
    data class LoadCurriculum(val courseId: Long) : LearningIntent
    data class CompleteLesson(val lessonId: Long, val courseId: Long) : LearningIntent
    data object Refresh : LearningIntent
    data object ClearMessage : LearningIntent
}

sealed interface LearningUiEffect {
    data class NavigateToCourse(val slug: String) : LearningUiEffect
    data class NavigateToLesson(val lessonId: Long) : LearningUiEffect
    data class ShowSnackbar(val message: String) : LearningUiEffect
}


