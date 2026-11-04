# Phase 13 — Stage 02: Android LMS Integration Architecture
**Project:** Connectly / JUGAJUG Enterprise Professional Social Networking Platform  
**Stage:** 02 — LMS Database, Backend Architecture & API Foundation  
**Date:** November 2026  
**Author:** Principal Software Architect & Lead Android Integration Engineer  

---

## 1. Android Module Architecture

The LMS feature is hosted inside `:feature:learning`, following the established Clean Architecture and MVI pattern:

```
android/feature/learning/src/main/java/com/connectly/android/feature/learning/
├── data/
│   ├── remote/
│   │   ├── dto/
│   │   │   ├── LearningCategoryDto.kt
│   │   │   ├── LearningCourseDto.kt
│   │   │   ├── LearningSectionDto.kt
│   │   │   ├── LearningLessonDto.kt
│   │   │   ├── LearningEnrollmentDto.kt
│   │   │   ├── LearningQuizDto.kt
│   │   │   ├── LearningAssignmentDto.kt
│   │   │   └── LearningCertificateDto.kt
│   │   └── api/
│   │       └── LearningApi.kt
│   ├── mapper/
│   │   └── LearningMappers.kt
│   └── repository/
│       └── LearningRepositoryImpl.kt
├── domain/
│   ├── model/
│   │   └── LearningModels.kt
│   ├── repository/
│   │   └── LearningRepository.kt
│   └── usecase/
│       ├── GetLearningCoursesUseCase.kt
│       ├── GetCourseDetailsUseCase.kt
│       ├── EnrollCourseUseCase.kt
│       ├── SyncLessonProgressUseCase.kt
│       ├── GetQuizUseCase.kt
│       ├── SubmitQuizUseCase.kt
│       └── ClaimCertificateUseCase.kt
├── presentation/
│   ├── learning/
│   │   ├── LearningHomeScreen.kt (Discover, My Learning, Certificates)
│   │   ├── LearningContract.kt (UiState, Intent, Effect)
│   │   └── LearningViewModel.kt
│   ├── details/
│   │   ├── CourseDetailsScreen.kt
│   │   └── CourseDetailsViewModel.kt
│   ├── player/
│   │   ├── VideoLearningScreen.kt (ExoPlayer Integration)
│   │   └── VideoLearningViewModel.kt
│   ├── quiz/
│   │   ├── QuizScreen.kt
│   │   └── QuizViewModel.kt
│   └── instructor/
│       ├── InstructorDashboardScreen.kt
│       └── CourseCreationScreen.kt
├── di/
│   └── LearningModule.kt
└── navigation/
    └── LearningNavigation.kt
```

---

## 2. DTO Specifications & Kotlinx Serialization

```kotlin
@Serializable
data class LearningCourseDto(
    val id: Long,
    val instructor_id: Long,
    val category_id: Long? = null,
    val title: String,
    val slug: String,
    val subtitle: String? = null,
    val description: String,
    val level: String = "all_levels",
    val language: String = "en",
    val type: String = "free",
    val price: Double = 0.0,
    val discounted_price: Double? = null,
    val currency: String = "BDT",
    val status: String = "draft",
    val duration_minutes: Int = 0,
    val total_sections: Int = 0,
    val total_lessons: Int = 0,
    val enrollments_count: Int = 0,
    val rating_avg: Double = 0.0,
    val rating_count: Int = 0,
    val is_featured: Boolean = false,
    val thumbnail_url: String? = null,
    val promotional_video_url: String? = null,
    val instructor: InstructorSummaryDto? = null,
    val category: CategorySummaryDto? = null,
    val skills: List<SkillSummaryDto> = emptyList()
)

@Serializable
data class LearningEnrollmentDto(
    val id: Long,
    val course_id: Long,
    val course_title: String,
    val course_thumbnail: String? = null,
    val instructor_name: String? = null,
    val status: String,
    val progress_percentage: Double = 0.0,
    val completed_lessons: Int = 0,
    val total_lessons: Int = 0,
    val is_completed: Boolean = false,
    val enrolled_at: String? = null
)

@Serializable
data class LearningCertificateDto(
    val id: Long,
    val certificate_code: String,
    val course_title: String,
    val student_name: String,
    val instructor_name: String,
    val issued_at: String? = null,
    val pdf_url: String? = null,
    val qr_code_url: String? = null,
    val is_valid: Boolean = true
)
```

---

## 3. Media Player & ExoPlayer Integration

In `VideoLearningScreen.kt`:
* Reuses `:core:media` Media3 ExoPlayer.
* Lifecycle-aware player initialization: binds to `DisposableEffect` with automatic release on `onDispose`.
* Pings `SyncLessonProgressUseCase(lessonId, position, completed)` every 15 seconds of playback.
* When playback crosses 90%, automatically invokes completion endpoint with local state transition.

---

## 4. Navigation Graph Integration

Compatible with `MainActivity.kt`:
```kotlin
fun NavGraphBuilder.learningGraph(
    rootNavController: NavController
) {
    composable(LEARNING_ROUTE) {
        LearningHomeScreen(
            onNavigateBack = { rootNavController.popBackStack() },
            onNavigateToCourse = { slug -> rootNavController.navigate("course_details/$slug") },
            onNavigateToLesson = { lessonId -> rootNavController.navigate("lesson_player/$lessonId") },
            onNavigateToInstructorHub = { rootNavController.navigate("instructor_dashboard") }
        )
    }
    // Sub-screens: course_details/{slug}, lesson_player/{lessonId}, quiz/{quizId}
}
```
`navigateToLearning()` invoked from `MenuScreen.kt` remains the primary entry point.

