package com.connectly.android.feature.learning.presentation.learning

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.learning.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asSharedFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LearningViewModel @Inject constructor(
    private val getCategoriesUseCase: GetLearningCategoriesUseCase,
    private val getCoursesUseCase: GetLearningCoursesUseCase,
    private val enrollCourseUseCase: EnrollCourseUseCase,
    private val getMyLearningUseCase: GetMyLearningUseCase,
    private val getMyCertificatesUseCase: GetMyCertificatesUseCase,
    private val getCurriculumUseCase: GetCurriculumUseCase,
    private val syncLessonProgressUseCase: SyncLessonProgressUseCase
) : ViewModel() {

    private val _uiState = MutableStateFlow(LearningUiState())
    val uiState: StateFlow<LearningUiState> = _uiState.asStateFlow()

    private val _effect = MutableSharedFlow<LearningUiEffect>()
    val effect: SharedFlow<LearningUiEffect> = _effect.asSharedFlow()

    init {
        loadData()
    }

    fun handleIntent(intent: LearningIntent) {
        when (intent) {
            is LearningIntent.SelectTab -> {
                _uiState.update { it.copy(activeTab = intent.tab) }
                when (intent.tab) {
                    LearningTab.DISCOVER -> loadCourses()
                    LearningTab.MY_LEARNING -> loadMyLearning()
                    LearningTab.CERTIFICATES -> loadCertificates()
                }
            }
            is LearningIntent.SelectCategory -> {
                _uiState.update { it.copy(selectedCategoryId = intent.categoryId) }
                loadCourses()
            }
            is LearningIntent.SearchQueryChanged -> {
                _uiState.update { it.copy(searchQuery = intent.query) }
                loadCourses()
            }
            is LearningIntent.EnrollCourse -> enrollInCourse(intent.courseId)
            is LearningIntent.LoadCurriculum -> loadCurriculum(intent.courseId)
            is LearningIntent.CompleteLesson -> completeLesson(intent.lessonId, intent.courseId)
            is LearningIntent.Refresh -> loadData()
            is LearningIntent.ClearMessage -> _uiState.update { it.copy(enrolledSuccessMessage = null, errorMessage = null) }
        }
    }

    private fun loadData() {
        loadCategories()
        loadCourses()
        loadMyLearning()
        loadCertificates()
    }

    private fun loadCategories() {
        viewModelScope.launch {
            getCategoriesUseCase().collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(categories = res.data) }
                }
            }
        }
    }

    private fun loadCourses() {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            getCoursesUseCase(
                query = _uiState.value.searchQuery.ifBlank { null },
                categoryId = _uiState.value.selectedCategoryId
            ).collect { res ->
                when (res) {
                    is Result.Success -> {
                        _uiState.update { it.copy(isLoading = false, courses = res.data) }
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isLoading = false, errorMessage = res.exception.message) }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun loadMyLearning() {
        viewModelScope.launch {
            getMyLearningUseCase().collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(myLearning = res.data) }
                }
            }
        }
    }

    private fun loadCertificates() {
        viewModelScope.launch {
            getMyCertificatesUseCase().collect { res ->
                if (res is Result.Success) {
                    _uiState.update { it.copy(certificates = res.data) }
                }
            }
        }
    }

    private fun loadCurriculum(courseId: Long) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoadingCurriculum = true) }
            getCurriculumUseCase(courseId).collect { res ->
                when (res) {
                    is Result.Success -> {
                        _uiState.update { it.copy(isLoadingCurriculum = false, selectedCourseCurriculum = res.data) }
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isLoadingCurriculum = false) }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun completeLesson(lessonId: Long, courseId: Long) {
        viewModelScope.launch {
            syncLessonProgressUseCase(lessonId, 1800, true).collect { res ->
                if (res is Result.Success) {
                    loadMyLearning()
                    loadCertificates()
                    _effect.emit(LearningUiEffect.ShowSnackbar("Lesson completed! Progress updated 🎉"))
                }
            }
        }
    }

    private fun enrollInCourse(courseId: Long) {
        viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true) }
            enrollCourseUseCase(courseId).collect { res ->
                when (res) {
                    is Result.Success -> {
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                enrolledSuccessMessage = "Enrolled in ${res.data.courseTitle}!"
                            )
                        }
                        loadMyLearning()
                        _effect.emit(LearningUiEffect.ShowSnackbar("Enrolled successfully! 🚀"))
                    }
                    is Result.Error -> {
                        _uiState.update { it.copy(isLoading = false, errorMessage = res.exception.message) }
                        _effect.emit(LearningUiEffect.ShowSnackbar(res.exception.message ?: "Enrollment failed"))
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }
}

