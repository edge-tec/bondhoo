package com.connectly.android.feature.learning.presentation.learning

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.common.result.Result
import com.connectly.android.feature.learning.domain.usecase.*
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Job
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

    private var coursesJob: Job? = null

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
            is LearningIntent.SelectLevel -> {
                _uiState.update { it.copy(selectedLevel = intent.level) }
                loadCourses()
            }
            is LearningIntent.SelectType -> {
                _uiState.update { it.copy(selectedType = intent.type) }
                loadCourses()
            }
            is LearningIntent.SearchQueryChanged -> {
                _uiState.update { it.copy(searchQuery = intent.query) }
                loadCourses()
            }
            is LearningIntent.EnrollCourse -> enrollInCourse(intent.courseId, intent.couponCode)
            is LearningIntent.LoadCurriculum -> loadCurriculum(intent.courseId)
            is LearningIntent.CompleteLesson -> completeLesson(intent.lessonId, intent.courseId)
            is LearningIntent.Refresh -> loadData()
            is LearningIntent.ClearMessage -> _uiState.update {
                it.copy(enrolledSuccessMessage = null, errorMessage = null, enrollErrorMessage = null)
            }
            is LearningIntent.ClearEnrollError -> _uiState.update { it.copy(enrollErrorMessage = null) }
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
        coursesJob?.cancel()
        coursesJob = viewModelScope.launch {
            _uiState.update { it.copy(isLoading = true, errorMessage = null) }
            val current = _uiState.value
            try {
                getCoursesUseCase(
                    query = current.searchQuery.ifBlank { null },
                    categoryId = current.selectedCategoryId,
                    level = current.selectedLevel,
                    type = current.selectedType
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
            } catch (e: CancellationException) {
                throw e
            }
        }
    }

    private fun loadMyLearning() {
        viewModelScope.launch {
            getMyLearningUseCase().collect { res ->
                if (res is Result.Success) {
                    _uiState.update { state ->
                        val enrolledIds = res.data.map { it.courseId }.toSet()
                        val syncedCourses = state.courses.map { c ->
                            if (c.id in enrolledIds) c.copy(isEnrolled = true) else c
                        }
                        state.copy(myLearning = res.data, courses = syncedCourses)
                    }
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
                        _uiState.update { it.copy(isLoadingCurriculum = false, errorMessage = res.exception.message) }
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun completeLesson(lessonId: Long, courseId: Long) {
        if (lessonId <= 0L || lessonId in _uiState.value.completingLessonIds) return
        viewModelScope.launch {
            _uiState.update { it.copy(completingLessonIds = it.completingLessonIds + lessonId) }
            syncLessonProgressUseCase(lessonId, 1800, true).collect { res ->
                when (res) {
                    is Result.Success -> {
                        _uiState.update { state ->
                            val updatedCurriculum = state.selectedCourseCurriculum.map { section ->
                                section.copy(
                                    lessons = section.lessons.map { les ->
                                        if (les.id == lessonId) les.copy(isCompleted = true) else les
                                    }
                                )
                            }
                            state.copy(
                                completingLessonIds = state.completingLessonIds - lessonId,
                                selectedCourseCurriculum = updatedCurriculum
                            )
                        }
                        loadMyLearning()
                        loadCertificates()
                        _effect.emit(LearningUiEffect.ShowSnackbar("Lesson completed! Progress updated 🎉"))
                    }
                    is Result.Error -> {
                        _uiState.update {
                            it.copy(
                                completingLessonIds = it.completingLessonIds - lessonId,
                                errorMessage = res.exception.message
                            )
                        }
                        _effect.emit(LearningUiEffect.ShowSnackbar(res.exception.message ?: "Failed to sync lesson progress"))
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }

    private fun enrollInCourse(courseId: Long, couponCode: String? = null) {
        if (courseId in _uiState.value.enrollingCourseIds) return

        viewModelScope.launch {
            _uiState.update {
                it.copy(
                    isLoading = true,
                    enrollingCourseIds = it.enrollingCourseIds + courseId,
                    enrollErrorMessage = null,
                    errorMessage = null
                )
            }
            enrollCourseUseCase(courseId, couponCode?.trim()?.takeIf { it.isNotBlank() }).collect { res ->
                when (res) {
                    is Result.Success -> {
                        val enrollment = res.data
                        _uiState.update { state ->
                            val nextMyLearning = listOf(enrollment) + state.myLearning.filter { it.courseId != enrollment.courseId }
                            val nextCourses = state.courses.map { c ->
                                if (c.id == courseId) c.copy(isEnrolled = true) else c
                            }
                            state.copy(
                                isLoading = false,
                                enrollingCourseIds = state.enrollingCourseIds - courseId,
                                myLearning = nextMyLearning,
                                courses = nextCourses,
                                enrollErrorMessage = null,
                                enrolledSuccessMessage = "Enrolled in ${enrollment.courseTitle}!"
                            )
                        }
                        loadMyLearning()
                        loadCurriculum(courseId)
                        _effect.emit(LearningUiEffect.ShowSnackbar("Enrolled successfully! 🚀"))
                    }
                    is Result.Error -> {
                        val msg = res.exception.message ?: "Enrollment failed"
                        _uiState.update {
                            it.copy(
                                isLoading = false,
                                enrollingCourseIds = it.enrollingCourseIds - courseId,
                                enrollErrorMessage = msg,
                                errorMessage = msg
                            )
                        }
                        _effect.emit(LearningUiEffect.ShowSnackbar(msg))
                    }
                    Result.Loading -> Unit
                }
            }
        }
    }
}
