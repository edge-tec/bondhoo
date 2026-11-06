package com.connectly.android.feature.learning.presentation.learning

import android.widget.Toast
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Clear
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.ContentCopy
import androidx.compose.material.icons.filled.MenuBook
import androidx.compose.material.icons.filled.Pause
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.PlayArrow
import androidx.compose.material.icons.filled.PlayCircle
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Share
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.Timer
import androidx.compose.material.icons.filled.Verified
import androidx.compose.material.icons.filled.WorkspacePremium
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.FilterChipDefaults
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Surface
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
import androidx.compose.material3.TabRowDefaults
import androidx.compose.material3.TabRowDefaults.tabIndicatorOffset
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.feature.learning.domain.model.LearningCertificate
import com.connectly.android.feature.learning.domain.model.LearningCourse
import com.connectly.android.feature.learning.domain.model.LearningEnrollment
import com.connectly.android.feature.learning.domain.model.LearningSection

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LearningHomeScreen(
    onNavigateBack: () -> Unit,
    onNavigateToCourse: (String) -> Unit = {},
    onNavigateToLesson: (Long) -> Unit = {},
    viewModel: LearningViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    val context = LocalContext.current
    val clipboardManager = LocalClipboardManager.current

    var selectedCourseForDetails by remember { mutableStateOf<LearningCourse?>(null) }
    var activeEnrollmentForPlayer by remember { mutableStateOf<LearningEnrollment?>(null) }
    var selectedCertificateForViewer by remember { mutableStateOf<LearningCertificate?>(null) }

    LaunchedEffect(Unit) {
        viewModel.effect.collect { effect ->
            when (effect) {
                is LearningUiEffect.ShowSnackbar -> snackbarHostState.showSnackbar(effect.message)
                is LearningUiEffect.NavigateToCourse -> onNavigateToCourse(effect.slug)
                is LearningUiEffect.NavigateToLesson -> onNavigateToLesson(effect.lessonId)
            }
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            imageVector = Icons.Default.School,
                            contentDescription = null,
                            tint = ConnectlyBlue,
                            modifier = Modifier.size(26.dp)
                        )
                        Spacer(modifier = Modifier.width(10.dp))
                        Text(
                            text = "Learning & Development",
                            fontWeight = FontWeight.Bold,
                            fontSize = 20.sp
                        )
                    }
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Back"
                        )
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        },
        snackbarHost = { SnackbarHost(snackbarHostState) }
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            // Search Input with Clear Action
            OutlinedTextField(
                value = state.searchQuery,
                onValueChange = { viewModel.handleIntent(LearningIntent.SearchQueryChanged(it)) },
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                placeholder = { Text("Search courses, skills, or topics...", fontSize = 14.sp) },
                leadingIcon = {
                    Icon(
                        imageVector = Icons.Default.Search,
                        contentDescription = "Search",
                        tint = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                },
                trailingIcon = {
                    if (state.searchQuery.isNotEmpty()) {
                        IconButton(onClick = { viewModel.handleIntent(LearningIntent.SearchQueryChanged("")) }) {
                            Icon(Icons.Default.Clear, contentDescription = "Clear search", modifier = Modifier.size(18.dp))
                        }
                    }
                },
                singleLine = true,
                shape = RoundedCornerShape(14.dp),
                colors = OutlinedTextFieldDefaults.colors(
                    focusedBorderColor = ConnectlyBlue,
                    unfocusedBorderColor = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f)
                )
            )

            // Professional Tabs Row
            TabRow(
                selectedTabIndex = state.activeTab.ordinal,
                modifier = Modifier.fillMaxWidth(),
                containerColor = MaterialTheme.colorScheme.surface,
                contentColor = ConnectlyBlue,
                indicator = { tabPositions ->
                    TabRowDefaults.SecondaryIndicator(
                        Modifier.tabIndicatorOffset(tabPositions[state.activeTab.ordinal]),
                        color = ConnectlyBlue,
                        height = 3.dp
                    )
                }
            ) {
                Tab(
                    selected = state.activeTab == LearningTab.DISCOVER,
                    onClick = { viewModel.handleIntent(LearningIntent.SelectTab(LearningTab.DISCOVER)) },
                    text = {
                        Text(
                            "Discover",
                            fontWeight = if (state.activeTab == LearningTab.DISCOVER) FontWeight.Bold else FontWeight.Medium,
                            fontSize = 14.sp
                        )
                    }
                )
                Tab(
                    selected = state.activeTab == LearningTab.MY_LEARNING,
                    onClick = { viewModel.handleIntent(LearningIntent.SelectTab(LearningTab.MY_LEARNING)) },
                    text = {
                        Text(
                            text = if (state.myLearning.isNotEmpty()) "My Learning (${state.myLearning.size})" else "My Learning",
                            fontWeight = if (state.activeTab == LearningTab.MY_LEARNING) FontWeight.Bold else FontWeight.Medium,
                            fontSize = 14.sp
                        )
                    }
                )
                Tab(
                    selected = state.activeTab == LearningTab.CERTIFICATES,
                    onClick = { viewModel.handleIntent(LearningIntent.SelectTab(LearningTab.CERTIFICATES)) },
                    text = {
                        Text(
                            text = if (state.certificates.isNotEmpty()) "Certificates (${state.certificates.size})" else "Certificates",
                            fontWeight = if (state.activeTab == LearningTab.CERTIFICATES) FontWeight.Bold else FontWeight.Medium,
                            fontSize = 14.sp
                        )
                    }
                )
            }

            if (state.isLoading && state.courses.isEmpty() && state.myLearning.isEmpty()) {
                Box(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(32.dp),
                    contentAlignment = Alignment.Center
                ) {
                    CircularProgressIndicator(color = ConnectlyBlue)
                }
            } else {
                when (state.activeTab) {
                    LearningTab.DISCOVER -> DiscoverTabContent(
                        state = state,
                        onSelectCategory = { viewModel.handleIntent(LearningIntent.SelectCategory(it)) },
                        onEnroll = { viewModel.handleIntent(LearningIntent.EnrollCourse(it)) },
                        onCourseClick = { course ->
                            selectedCourseForDetails = course
                            viewModel.handleIntent(LearningIntent.LoadCurriculum(course.id))
                        }
                    )
                    LearningTab.MY_LEARNING -> MyLearningTabContent(
                        enrollments = state.myLearning,
                        onContinue = { enrollment ->
                            activeEnrollmentForPlayer = enrollment
                            viewModel.handleIntent(LearningIntent.LoadCurriculum(enrollment.courseId))
                        },
                        onExploreCourses = {
                            viewModel.handleIntent(LearningIntent.SelectTab(LearningTab.DISCOVER))
                        }
                    )
                    LearningTab.CERTIFICATES -> CertificatesTabContent(
                        certificates = state.certificates,
                        onViewCertificate = { cert ->
                            selectedCertificateForViewer = cert
                        },
                        onExploreCourses = {
                            viewModel.handleIntent(LearningIntent.SelectTab(LearningTab.DISCOVER))
                        }
                    )
                }
            }
        }
    }

    // 1. Course Details Modal Dialog
    selectedCourseForDetails?.let { course ->
        val isEnrolled = state.myLearning.any { it.courseId == course.id }
        CourseDetailsDialog(
            course = course,
            isEnrolled = isEnrolled,
            curriculum = state.selectedCourseCurriculum,
            isLoadingCurriculum = state.isLoadingCurriculum,
            onDismiss = { selectedCourseForDetails = null },
            onEnroll = {
                viewModel.handleIntent(LearningIntent.EnrollCourse(course.id))
                selectedCourseForDetails = null
            },
            onContinue = {
                selectedCourseForDetails = null
                val match = state.myLearning.firstOrNull { it.courseId == course.id }
                if (match != null) {
                    activeEnrollmentForPlayer = match
                    viewModel.handleIntent(LearningIntent.LoadCurriculum(course.id))
                } else {
                    viewModel.handleIntent(LearningIntent.SelectTab(LearningTab.MY_LEARNING))
                }
            }
        )
    }

    // 2. Interactive Lesson Player Modal Dialog
    activeEnrollmentForPlayer?.let { enrollment ->
        LessonPlayerDialog(
            enrollment = enrollment,
            curriculum = state.selectedCourseCurriculum,
            onDismiss = { activeEnrollmentForPlayer = null },
            onCompleteLesson = { lessonId ->
                viewModel.handleIntent(LearningIntent.CompleteLesson(lessonId, enrollment.courseId))
            }
        )
    }

    // 3. Official Certificate Viewer Modal Dialog
    selectedCertificateForViewer?.let { cert ->
        CertificateViewerDialog(
            certificate = cert,
            onDismiss = { selectedCertificateForViewer = null },
            onShare = {
                Toast.makeText(context, "Certificate credential shared to your feed! 🎓", Toast.LENGTH_SHORT).show()
                selectedCertificateForViewer = null
            },
            onCopyLink = {
                clipboardManager.setText(AnnotatedString("https://bondhoo.com/verify/cert/${cert.certificateCode}"))
                Toast.makeText(context, "Credential verification link copied! 📋", Toast.LENGTH_SHORT).show()
            }
        )
    }
}

// -------------------------------------------------------------
// TAB 1: DISCOVER CONTENT
// -------------------------------------------------------------

@Composable
private fun DiscoverTabContent(
    state: LearningUiState,
    onSelectCategory: (Long?) -> Unit,
    onEnroll: (Long) -> Unit,
    onCourseClick: (LearningCourse) -> Unit
) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(16.dp)
    ) {
        // Categories Horizontal Row
        if (state.categories.isNotEmpty()) {
            item {
                LazyRow(
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    item {
                        FilterChip(
                            selected = state.selectedCategoryId == null,
                            onClick = { onSelectCategory(null) },
                            label = { Text("All Categories", fontSize = 12.sp, fontWeight = FontWeight.SemiBold) },
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = ConnectlyBlue.copy(alpha = 0.15f),
                                selectedLabelColor = ConnectlyBlue
                            )
                        )
                    }
                    items(state.categories) { cat ->
                        FilterChip(
                            selected = state.selectedCategoryId == cat.id,
                            onClick = { onSelectCategory(cat.id) },
                            label = { Text(cat.name, fontSize = 12.sp, fontWeight = FontWeight.SemiBold) },
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = ConnectlyBlue.copy(alpha = 0.15f),
                                selectedLabelColor = ConnectlyBlue
                            )
                        )
                    }
                }
            }
        }

        // Empty State or Course List
        if (state.courses.isEmpty()) {
            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
                ) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(32.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Icon(
                            imageVector = Icons.Default.School,
                            contentDescription = null,
                            modifier = Modifier.size(52.dp),
                            tint = ConnectlyBlue
                        )
                        Spacer(modifier = Modifier.height(14.dp))
                        Text(
                            text = "No courses found",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                        Spacer(modifier = Modifier.height(4.dp))
                        Text(
                            text = "Try searching for a different skill or category",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                            textAlign = TextAlign.Center
                        )
                    }
                }
            }
        } else {
            items(state.courses) { course ->
                val isEnrolled = state.myLearning.any { it.courseId == course.id }
                CourseCard(
                    course = course,
                    isEnrolled = isEnrolled,
                    onEnroll = { onEnroll(course.id) },
                    onClick = { onCourseClick(course) }
                )
            }
        }
    }
}

@Composable
private fun CourseCard(
    course: LearningCourse,
    isEnrolled: Boolean,
    onEnroll: () -> Unit,
    onClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column {
            // Thumbnail Banner
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(150.dp)
                    .background(Color(0xFF0F172A))
            ) {
                if (!course.thumbnailUrl.isNullOrBlank()) {
                    AsyncImage(
                        model = course.thumbnailUrl,
                        contentDescription = course.title,
                        modifier = Modifier.fillMaxSize(),
                        contentScale = ContentScale.Crop
                    )
                } else {
                    Box(
                        modifier = Modifier.fillMaxSize(),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.PlayCircle,
                            contentDescription = null,
                            tint = Color.White.copy(alpha = 0.8f),
                            modifier = Modifier.size(52.dp)
                        )
                    }
                }

                // Level badge
                val levelColor = when (course.level.lowercase()) {
                    "beginner" -> Color(0xFF10B981)
                    "intermediate" -> Color(0xFF2563EB)
                    "expert" -> Color(0xFF8B5CF6)
                    else -> Color(0xFF0F172A)
                }
                Box(
                    modifier = Modifier
                        .padding(10.dp)
                        .clip(RoundedCornerShape(8.dp))
                        .background(levelColor)
                        .padding(horizontal = 8.dp, vertical = 4.dp)
                        .align(Alignment.TopEnd)
                ) {
                    Text(
                        text = course.level.replace('_', ' ').uppercase(),
                        color = Color.White,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.ExtraBold
                    )
                }

                // Category tag
                if (!course.categoryName.isNullOrBlank()) {
                    Box(
                        modifier = Modifier
                            .padding(10.dp)
                            .clip(RoundedCornerShape(8.dp))
                            .background(Color.Black.copy(alpha = 0.75f))
                            .padding(horizontal = 8.dp, vertical = 4.dp)
                            .align(Alignment.BottomStart)
                    ) {
                        Text(
                            text = course.categoryName,
                            color = Color.White,
                            fontSize = 11.sp,
                            fontWeight = FontWeight.SemiBold
                        )
                    }
                }
            }

            Column(modifier = Modifier.padding(16.dp)) {
                Text(
                    text = course.title,
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis
                )

                if (!course.subtitle.isNullOrBlank()) {
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = course.subtitle,
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis
                    )
                }

                Spacer(modifier = Modifier.height(10.dp))

                // Instructor & Duration Row
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            imageVector = Icons.Default.Person,
                            contentDescription = null,
                            tint = ConnectlyBlue,
                            modifier = Modifier.size(15.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            text = course.instructorName ?: "Dr. Tanvir Ahmed",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurface,
                            fontWeight = FontWeight.Medium
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Icon(
                            imageVector = Icons.Default.Verified,
                            contentDescription = "Verified",
                            tint = Color(0xFF0284C7),
                            modifier = Modifier.size(14.dp)
                        )
                    }

                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            imageVector = Icons.Default.Timer,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.outline,
                            modifier = Modifier.size(14.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        val hours = course.durationMinutes / 60
                        val mins = course.durationMinutes % 60
                        val durationText = if (hours > 0) "${hours}h ${mins}m" else "${mins}m"
                        Text(
                            text = durationText,
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.outline
                        )
                    }
                }

                Spacer(modifier = Modifier.height(8.dp))

                // Rating & Student Count Row
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            imageVector = Icons.Default.Star,
                            contentDescription = null,
                            tint = Color(0xFFF59E0B),
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            text = "${course.ratingAvg} (${course.enrollmentsCount} students)",
                            style = MaterialTheme.typography.bodySmall,
                            fontWeight = FontWeight.Bold
                        )
                    }

                    Text(
                        text = "${course.totalLessons} lessons",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.outline
                    )
                }

                Spacer(modifier = Modifier.height(14.dp))

                // Price and Enroll Button
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text(
                        text = if (course.price == 0.0) "FREE" else "$${course.price}",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.ExtraBold,
                        color = if (course.price == 0.0) Color(0xFF10B981) else ConnectlyBlue
                    )

                    if (isEnrolled) {
                        Surface(
                            shape = RoundedCornerShape(10.dp),
                            color = Color(0xFF10B981).copy(alpha = 0.15f)
                        ) {
                            Row(
                                modifier = Modifier.padding(horizontal = 12.dp, vertical = 8.dp),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Icon(Icons.Default.CheckCircle, contentDescription = null, tint = Color(0xFF10B981), modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("Enrolled ✓", color = Color(0xFF10B981), fontSize = 13.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                    } else {
                        Button(
                            onClick = onEnroll,
                            shape = RoundedCornerShape(10.dp),
                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                        ) {
                            Text(
                                if (course.price == 0.0) "Enroll Free" else "Enroll Now",
                                fontSize = 13.sp,
                                fontWeight = FontWeight.SemiBold
                            )
                        }
                    }
                }
            }
        }
    }
}

// -------------------------------------------------------------
// TAB 2: MY LEARNING CONTENT
// -------------------------------------------------------------

@Composable
private fun MyLearningTabContent(
    enrollments: List<LearningEnrollment>,
    onContinue: (LearningEnrollment) -> Unit,
    onExploreCourses: () -> Unit
) {
    if (enrollments.isEmpty()) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(32.dp),
            contentAlignment = Alignment.Center
        ) {
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(20.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f))
            ) {
                Column(
                    modifier = Modifier.padding(28.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center
                ) {
                    Icon(
                        imageVector = Icons.Default.School,
                        contentDescription = null,
                        modifier = Modifier.size(56.dp),
                        tint = ConnectlyBlue
                    )
                    Spacer(modifier = Modifier.height(14.dp))
                    Text(
                        text = "No Enrolled Courses Yet",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(
                        text = "Enroll in expert-led courses on system design, AI, UI/UX, and cloud engineering.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        textAlign = TextAlign.Center
                    )
                    Spacer(modifier = Modifier.height(18.dp))
                    Button(
                        onClick = onExploreCourses,
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                    ) {
                        Text("Browse Courses", fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    } else {
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            items(enrollments) { enrollment ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable { onContinue(enrollment) },
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text(
                                text = enrollment.courseTitle,
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold,
                                modifier = Modifier.weight(1f)
                            )
                            if (enrollment.isCompleted) {
                                Surface(
                                    color = Color(0xFF10B981).copy(alpha = 0.15f),
                                    shape = RoundedCornerShape(8.dp)
                                ) {
                                    Row(
                                        modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Icon(
                                            imageVector = Icons.Default.CheckCircle,
                                            contentDescription = null,
                                            tint = Color(0xFF10B981),
                                            modifier = Modifier.size(14.dp)
                                        )
                                        Spacer(modifier = Modifier.width(4.dp))
                                        Text(
                                            text = "Completed",
                                            color = Color(0xFF10B981),
                                            fontSize = 11.sp,
                                            fontWeight = FontWeight.Bold
                                        )
                                    }
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(10.dp))

                        // Progress Bar
                        LinearProgressIndicator(
                            progress = { (enrollment.progressPercentage / 100.0).toFloat().coerceIn(0f, 1f) },
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(8.dp)
                                .clip(RoundedCornerShape(4.dp)),
                            color = if (enrollment.isCompleted) Color(0xFF10B981) else ConnectlyBlue,
                            trackColor = MaterialTheme.colorScheme.surfaceVariant
                        )

                        Spacer(modifier = Modifier.height(8.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text(
                                text = "${enrollment.progressPercentage.toInt()}% Completed",
                                style = MaterialTheme.typography.bodySmall,
                                fontWeight = FontWeight.Bold,
                                color = if (enrollment.isCompleted) Color(0xFF10B981) else ConnectlyBlue
                            )
                            Text(
                                text = "${enrollment.completedLessons}/${enrollment.totalLessons} lessons finished",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }

                        Spacer(modifier = Modifier.height(14.dp))

                        Button(
                            onClick = { onContinue(enrollment) },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(12.dp),
                            colors = ButtonDefaults.buttonColors(
                                containerColor = if (enrollment.isCompleted) Color(0xFF0F172A) else ConnectlyBlue
                            )
                        ) {
                            Icon(
                                imageVector = if (enrollment.isCompleted) Icons.Default.MenuBook else Icons.Default.PlayCircle,
                                contentDescription = null,
                                modifier = Modifier.size(18.dp)
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Text(
                                text = if (enrollment.isCompleted) "Review Course Material" else "Continue Learning",
                                fontSize = 13.sp,
                                fontWeight = FontWeight.Bold
                            )
                        }
                    }
                }
            }
        }
    }
}

// -------------------------------------------------------------
// TAB 3: CERTIFICATES CONTENT
// -------------------------------------------------------------

@Composable
private fun CertificatesTabContent(
    certificates: List<LearningCertificate>,
    onViewCertificate: (LearningCertificate) -> Unit,
    onExploreCourses: () -> Unit
) {
    if (certificates.isEmpty()) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(32.dp),
            contentAlignment = Alignment.Center
        ) {
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(20.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f))
            ) {
                Column(
                    modifier = Modifier.padding(28.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center
                ) {
                    Icon(
                        imageVector = Icons.Default.WorkspacePremium,
                        contentDescription = null,
                        modifier = Modifier.size(56.dp),
                        tint = Color(0xFFF59E0B)
                    )
                    Spacer(modifier = Modifier.height(14.dp))
                    Text(
                        text = "No Certificates Earned Yet",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold
                    )
                    Spacer(modifier = Modifier.height(6.dp))
                    Text(
                        text = "Complete courses and assessments to earn shareable verified credentials for your professional profile.",
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        textAlign = TextAlign.Center
                    )
                    Spacer(modifier = Modifier.height(18.dp))
                    Button(
                        onClick = onExploreCourses,
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                    ) {
                        Text("Start a Course", fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    } else {
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp)
        ) {
            items(certificates) { cert ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clickable { onViewCertificate(cert) },
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface),
                    elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Box(
                                modifier = Modifier
                                    .size(46.dp)
                                    .clip(CircleShape)
                                    .background(Color(0xFFFEF3C7)),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.WorkspacePremium,
                                    contentDescription = null,
                                    tint = Color(0xFFD97706),
                                    modifier = Modifier.size(26.dp)
                                )
                            }
                            Spacer(modifier = Modifier.width(14.dp))
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = cert.courseTitle,
                                    style = MaterialTheme.typography.titleMedium,
                                    fontWeight = FontWeight.Bold
                                )
                                Spacer(modifier = Modifier.height(2.dp))
                                Text(
                                    text = "Issued to ${cert.studentName} • Instructor: ${cert.instructorName}",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }

                        Spacer(modifier = Modifier.height(14.dp))
                        HorizontalDivider(color = MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.4f))
                        Spacer(modifier = Modifier.height(12.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Column {
                                Text("Credential ID:", fontSize = 11.sp, color = MaterialTheme.colorScheme.outline)
                                Text(
                                    text = cert.certificateCode,
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = ConnectlyBlue
                                )
                            }

                            Button(
                                onClick = { onViewCertificate(cert) },
                                shape = RoundedCornerShape(10.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                            ) {
                                Icon(Icons.Default.Verified, contentDescription = null, modifier = Modifier.size(16.dp))
                                Spacer(modifier = Modifier.width(6.dp))
                                Text("View Certificate", fontSize = 12.sp, fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }
            }
        }
    }
}

// -------------------------------------------------------------
// MODAL DIALOG 1: COURSE DETAILS & SYLLABUS
// -------------------------------------------------------------

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CourseDetailsDialog(
    course: LearningCourse,
    isEnrolled: Boolean,
    curriculum: List<LearningSection>,
    isLoadingCurriculum: Boolean,
    onDismiss: () -> Unit,
    onEnroll: () -> Unit,
    onContinue: () -> Unit
) {
    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Surface(
            modifier = Modifier.fillMaxSize(),
            color = MaterialTheme.colorScheme.background
        ) {
            Column(modifier = Modifier.fillMaxSize()) {
                TopAppBar(
                    title = { Text("Course Syllabus & Details", fontWeight = FontWeight.Bold, fontSize = 18.sp) },
                    navigationIcon = {
                        IconButton(onClick = onDismiss) {
                            Icon(Icons.Default.Close, contentDescription = "Close")
                        }
                    },
                    colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
                )

                Column(
                    modifier = Modifier
                        .weight(1f)
                        .verticalScroll(rememberScrollState())
                        .padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    // Course Title & Banner
                    Card(
                        shape = RoundedCornerShape(16.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column {
                            Box(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .height(180.dp)
                                    .background(Color(0xFF0F172A))
                            ) {
                                if (!course.thumbnailUrl.isNullOrBlank()) {
                                    AsyncImage(
                                        model = course.thumbnailUrl,
                                        contentDescription = course.title,
                                        modifier = Modifier.fillMaxSize(),
                                        contentScale = ContentScale.Crop
                                    )
                                }
                            }
                            Column(modifier = Modifier.padding(16.dp)) {
                                Text(course.title, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                                if (!course.subtitle.isNullOrBlank()) {
                                    Spacer(modifier = Modifier.height(6.dp))
                                    Text(course.subtitle, style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
                                }
                            }
                        }
                    }

                    // Key Metrics
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        Surface(
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(12.dp),
                            color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                        ) {
                            Column(modifier = Modifier.padding(12.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                                Text("Rating", fontSize = 11.sp, color = MaterialTheme.colorScheme.outline)
                                Text("⭐ ${course.ratingAvg}", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                            }
                        }
                        Surface(
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(12.dp),
                            color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                        ) {
                            Column(modifier = Modifier.padding(12.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                                Text("Duration", fontSize = 11.sp, color = MaterialTheme.colorScheme.outline)
                                Text("${course.durationMinutes / 60}h ${course.durationMinutes % 60}m", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                            }
                        }
                        Surface(
                            modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(12.dp),
                            color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f)
                        ) {
                            Column(modifier = Modifier.padding(12.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                                Text("Lessons", fontSize = 11.sp, color = MaterialTheme.colorScheme.outline)
                                Text("${course.totalLessons} total", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                            }
                        }
                    }

                    // Course Overview Description
                    Card(
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                    ) {
                        Column(modifier = Modifier.padding(16.dp)) {
                            Text("About This Course", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Spacer(modifier = Modifier.height(8.dp))
                            Text(course.description, fontSize = 14.sp, lineHeight = 20.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    }

                    // Curriculum Sections
                    Card(
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                    ) {
                        Column(modifier = Modifier.padding(16.dp)) {
                            Text("Curriculum Syllabus", fontWeight = FontWeight.Bold, fontSize = 16.sp)
                            Spacer(modifier = Modifier.height(10.dp))

                            if (isLoadingCurriculum) {
                                Box(modifier = Modifier.fillMaxWidth().padding(16.dp), contentAlignment = Alignment.Center) {
                                    CircularProgressIndicator(modifier = Modifier.size(24.dp), color = ConnectlyBlue)
                                }
                            } else if (curriculum.isNotEmpty()) {
                                curriculum.forEach { sec ->
                                    Surface(
                                        shape = RoundedCornerShape(10.dp),
                                        color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.4f),
                                        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)
                                    ) {
                                        Column(modifier = Modifier.padding(12.dp)) {
                                            Text(sec.title, fontWeight = FontWeight.Bold, fontSize = 13.sp)
                                            Spacer(modifier = Modifier.height(6.dp))
                                            sec.lessons.forEach { les ->
                                                Row(
                                                    modifier = Modifier.fillMaxWidth().padding(vertical = 2.dp),
                                                    verticalAlignment = Alignment.CenterVertically
                                                ) {
                                                    Icon(Icons.Default.PlayCircle, null, modifier = Modifier.size(14.dp), tint = ConnectlyBlue)
                                                    Spacer(modifier = Modifier.width(6.dp))
                                                    Text(les.title, fontSize = 12.sp, modifier = Modifier.weight(1f))
                                                    Text("${les.videoDurationSeconds / 60}m", fontSize = 11.sp, color = MaterialTheme.colorScheme.outline)
                                                }
                                            }
                                        }
                                    }
                                }
                            } else {
                                Text("Full technical modules and hands-on code exercises included upon enrollment.", fontSize = 13.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                        }
                    }
                }

                // Bottom Action Bar
                Surface(
                    color = MaterialTheme.colorScheme.surface,
                    shadowElevation = 8.dp,
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier.padding(16.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Column {
                            Text("Tuition", fontSize = 11.sp, color = MaterialTheme.colorScheme.outline)
                            Text(
                                if (course.price == 0.0) "FREE" else "$${course.price}",
                                fontWeight = FontWeight.ExtraBold,
                                fontSize = 20.sp,
                                color = if (course.price == 0.0) Color(0xFF10B981) else ConnectlyBlue
                            )
                        }

                        if (isEnrolled) {
                            Button(
                                onClick = onContinue,
                                shape = RoundedCornerShape(12.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                            ) {
                                Icon(Icons.Default.PlayCircle, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(modifier = Modifier.width(8.dp))
                                Text("Continue Learning", fontWeight = FontWeight.Bold)
                            }
                        } else {
                            Button(
                                onClick = onEnroll,
                                shape = RoundedCornerShape(12.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                            ) {
                                Icon(Icons.Default.Check, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(modifier = Modifier.width(8.dp))
                                Text(if (course.price == 0.0) "Enroll in Course Free" else "Enroll Now", fontWeight = FontWeight.Bold)
                            }
                        }
                    }
                }
            }
        }
    }
}

// -------------------------------------------------------------
// MODAL DIALOG 2: LESSON PLAYER
// -------------------------------------------------------------

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun LessonPlayerDialog(
    enrollment: LearningEnrollment,
    curriculum: List<LearningSection>,
    onDismiss: () -> Unit,
    onCompleteLesson: (Long) -> Unit
) {
    var isPlaying by remember { mutableStateOf(true) }
    var lessonProgress by remember { mutableFloatStateOf(0.45f) }
    var selectedLessonTitle by remember { mutableStateOf("Module 1.1: Architecture & Foundations") }
    var selectedLessonId by remember { mutableStateOf(1L) }

    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Surface(
            modifier = Modifier.fillMaxSize(),
            color = MaterialTheme.colorScheme.background
        ) {
            Column(modifier = Modifier.fillMaxSize()) {
                TopAppBar(
                    title = {
                        Column {
                            Text(enrollment.courseTitle, fontWeight = FontWeight.Bold, fontSize = 16.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                            Text(selectedLessonTitle, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                        }
                    },
                    navigationIcon = {
                        IconButton(onClick = onDismiss) {
                            Icon(Icons.Default.Close, contentDescription = "Close")
                        }
                    },
                    colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
                )

                // Simulated Interactive Video Player
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(220.dp)
                        .background(Color.Black),
                    contentAlignment = Alignment.Center
                ) {
                    Column(horizontalAlignment = Alignment.CenterHorizontally) {
                        IconButton(
                            onClick = { isPlaying = !isPlaying },
                            modifier = Modifier
                                .size(56.dp)
                                .clip(CircleShape)
                                .background(Color.White.copy(alpha = 0.25f))
                        ) {
                            Icon(
                                imageVector = if (isPlaying) Icons.Default.Pause else Icons.Default.PlayArrow,
                                contentDescription = "Play/Pause",
                                tint = Color.White,
                                modifier = Modifier.size(32.dp)
                            )
                        }
                        Spacer(modifier = Modifier.height(10.dp))
                        Text(
                            text = if (isPlaying) "Streaming High-Definition Lesson Video..." else "Video Paused",
                            color = Color.White.copy(alpha = 0.8f),
                            fontSize = 12.sp
                        )
                    }

                    // Video progress scrubber bar
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .align(Alignment.BottomCenter)
                            .padding(12.dp)
                    ) {
                        LinearProgressIndicator(
                            progress = { lessonProgress },
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(6.dp)
                                .clip(RoundedCornerShape(3.dp)),
                            color = ConnectlyBlue,
                            trackColor = Color.White.copy(alpha = 0.3f)
                        )
                    }
                }

                // Lesson Content & Course Navigation
                Column(
                    modifier = Modifier
                        .weight(1f)
                        .verticalScroll(rememberScrollState())
                        .padding(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    // Mark Complete Button
                    Button(
                        onClick = {
                            lessonProgress = 1.0f
                            onCompleteLesson(selectedLessonId)
                        },
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF10B981))
                    ) {
                        Icon(Icons.Default.CheckCircle, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Mark Lesson Complete & Continue", fontWeight = FontWeight.Bold)
                    }

                    // Lesson Syllabus
                    Card(
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface)
                    ) {
                        Column(modifier = Modifier.padding(16.dp)) {
                            Text("Course Lessons", fontWeight = FontWeight.Bold, fontSize = 15.sp)
                            Spacer(modifier = Modifier.height(10.dp))

                            if (curriculum.isNotEmpty()) {
                                curriculum.forEach { sec ->
                                    Text(sec.title, fontWeight = FontWeight.SemiBold, fontSize = 13.sp, color = ConnectlyBlue)
                                    Spacer(modifier = Modifier.height(4.dp))
                                    sec.lessons.forEach { les ->
                                        Surface(
                                            onClick = {
                                                selectedLessonTitle = les.title
                                                selectedLessonId = les.id
                                                lessonProgress = 0.2f
                                            },
                                            shape = RoundedCornerShape(8.dp),
                                            color = if (selectedLessonTitle == les.title) ConnectlyBlue.copy(alpha = 0.12f) else Color.Transparent,
                                            modifier = Modifier.fillMaxWidth()
                                        ) {
                                            Row(
                                                modifier = Modifier.padding(horizontal = 10.dp, vertical = 8.dp),
                                                verticalAlignment = Alignment.CenterVertically
                                            ) {
                                                Icon(
                                                    imageVector = Icons.Default.PlayCircle,
                                                    contentDescription = null,
                                                    tint = if (selectedLessonTitle == les.title) ConnectlyBlue else MaterialTheme.colorScheme.outline,
                                                    modifier = Modifier.size(16.dp)
                                                )
                                                Spacer(modifier = Modifier.width(8.dp))
                                                Text(les.title, fontSize = 12.sp, modifier = Modifier.weight(1f), fontWeight = if (selectedLessonTitle == les.title) FontWeight.Bold else FontWeight.Normal)
                                                Text("${les.videoDurationSeconds / 60}m", fontSize = 11.sp, color = MaterialTheme.colorScheme.outline)
                                            }
                                        }
                                    }
                                    Spacer(modifier = Modifier.height(8.dp))
                                }
                            } else {
                                Text("1. Domain-Driven Design Architecture\n2. Reactive Flow & WebSocket Integration\n3. Automated CI/CD Testing Suites", fontSize = 13.sp, lineHeight = 20.sp)
                            }
                        }
                    }
                }
            }
        }
    }
}

// -------------------------------------------------------------
// MODAL DIALOG 3: OFFICIAL CERTIFICATE VIEWER
// -------------------------------------------------------------

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CertificateViewerDialog(
    certificate: LearningCertificate,
    onDismiss: () -> Unit,
    onShare: () -> Unit,
    onCopyLink: () -> Unit
) {
    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Surface(
            modifier = Modifier.fillMaxSize(),
            color = MaterialTheme.colorScheme.background
        ) {
            Column(modifier = Modifier.fillMaxSize()) {
                TopAppBar(
                    title = { Text("Verified Credential", fontWeight = FontWeight.Bold, fontSize = 18.sp) },
                    navigationIcon = {
                        IconButton(onClick = onDismiss) {
                            Icon(Icons.Default.Close, contentDescription = "Close")
                        }
                    },
                    colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
                )

                Column(
                    modifier = Modifier
                        .weight(1f)
                        .verticalScroll(rememberScrollState())
                        .padding(16.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    // Official Certificate Frame
                    Card(
                        modifier = Modifier
                            .fillMaxWidth()
                            .border(2.dp, Color(0xFFD97706), RoundedCornerShape(16.dp)),
                        shape = RoundedCornerShape(16.dp),
                        colors = CardDefaults.cardColors(containerColor = Color(0xFFFFFBEB)),
                        elevation = CardDefaults.cardElevation(defaultElevation = 4.dp)
                    ) {
                        Column(
                            modifier = Modifier.padding(24.dp),
                            horizontalAlignment = Alignment.CenterHorizontally
                        ) {
                            // Gold Emblem
                            Box(
                                modifier = Modifier
                                    .size(64.dp)
                                    .clip(CircleShape)
                                    .background(
                                        Brush.linearGradient(
                                            listOf(Color(0xFFFBBF24), Color(0xFFD97706))
                                        )
                                    ),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.WorkspacePremium,
                                    contentDescription = null,
                                    tint = Color.White,
                                    modifier = Modifier.size(36.dp)
                                )
                            }

                            Spacer(modifier = Modifier.height(14.dp))
                            Text(
                                text = "BONDHOO LEARNING ACADEMY",
                                fontSize = 12.sp,
                                fontWeight = FontWeight.ExtraBold,
                                color = Color(0xFF92400E),
                                letterSpacing = 1.sp
                            )
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                text = "CERTIFICATE OF COMPLETION",
                                fontSize = 18.sp,
                                fontWeight = FontWeight.ExtraBold,
                                color = Color(0xFF1E293B),
                                textAlign = TextAlign.Center
                            )

                            Spacer(modifier = Modifier.height(12.dp))
                            Text(
                                text = "This is officially awarded to",
                                fontSize = 12.sp,
                                color = Color(0xFF64748B)
                            )
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = certificate.studentName,
                                fontSize = 22.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF0F172A),
                                textAlign = TextAlign.Center
                            )

                            Spacer(modifier = Modifier.height(12.dp))
                            Text(
                                text = "for successfully mastering and demonstrating proficiency in",
                                fontSize = 12.sp,
                                color = Color(0xFF64748B),
                                textAlign = TextAlign.Center
                            )
                            Spacer(modifier = Modifier.height(6.dp))
                            Text(
                                text = certificate.courseTitle,
                                fontSize = 16.sp,
                                fontWeight = FontWeight.ExtraBold,
                                color = ConnectlyBlue,
                                textAlign = TextAlign.Center
                            )

                            Spacer(modifier = Modifier.height(20.dp))
                            HorizontalDivider(color = Color(0xFFE2E8F0))
                            Spacer(modifier = Modifier.height(16.dp))

                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Column {
                                    Text("Instructor:", fontSize = 10.sp, color = Color(0xFF64748B))
                                    Text(certificate.instructorName, fontSize = 12.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                                }
                                Column(horizontalAlignment = Alignment.End) {
                                    Text("Credential ID:", fontSize = 10.sp, color = Color(0xFF64748B))
                                    Text(certificate.certificateCode, fontSize = 11.sp, fontWeight = FontWeight.Bold, color = Color(0xFF0F172A))
                                }
                            }
                        }
                    }

                    // Share & Verify Actions
                    Button(
                        onClick = onShare,
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                    ) {
                        Icon(Icons.Default.Share, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Share Credential to Feed", fontWeight = FontWeight.Bold)
                    }

                    OutlinedButton(
                        onClick = onCopyLink,
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp)
                    ) {
                        Icon(Icons.Default.ContentCopy, contentDescription = null, modifier = Modifier.size(18.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Copy Verification Link", fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
    }
}
