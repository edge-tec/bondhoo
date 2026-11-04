package com.connectly.android.feature.learning.presentation.learning

import androidx.compose.foundation.background
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
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.PlayCircle
import androidx.compose.material.icons.filled.School
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Star
import androidx.compose.material.icons.filled.Verified
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import coil3.compose.AsyncImage
import com.connectly.android.feature.learning.domain.model.LearningCertificate
import com.connectly.android.feature.learning.domain.model.LearningCourse
import com.connectly.android.feature.learning.domain.model.LearningEnrollment

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
                            tint = MaterialTheme.colorScheme.primary,
                            modifier = Modifier.size(28.dp)
                        )
                        Spacer(modifier = Modifier.width(8.dp))
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
            // Search Input
            OutlinedTextField(
                value = state.searchQuery,
                onValueChange = { viewModel.handleIntent(LearningIntent.SearchQueryChanged(it)) },
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 16.dp, vertical = 8.dp),
                placeholder = { Text("Search courses, skills, or topics...") },
                leadingIcon = {
                    Icon(
                        imageVector = Icons.Default.Search,
                        contentDescription = "Search"
                    )
                },
                singleLine = true,
                shape = RoundedCornerShape(12.dp)
            )

            // Tabs Row
            TabRow(
                selectedTabIndex = state.activeTab.ordinal,
                modifier = Modifier.fillMaxWidth()
            ) {
                Tab(
                    selected = state.activeTab == LearningTab.DISCOVER,
                    onClick = { viewModel.handleIntent(LearningIntent.SelectTab(LearningTab.DISCOVER)) },
                    text = { Text("Discover", fontWeight = FontWeight.SemiBold) }
                )
                Tab(
                    selected = state.activeTab == LearningTab.MY_LEARNING,
                    onClick = { viewModel.handleIntent(LearningIntent.SelectTab(LearningTab.MY_LEARNING)) },
                    text = {
                        Text(
                            text = if (state.myLearning.isNotEmpty()) "My Learning (${state.myLearning.size})" else "My Learning",
                            fontWeight = FontWeight.SemiBold
                        )
                    }
                )
                Tab(
                    selected = state.activeTab == LearningTab.CERTIFICATES,
                    onClick = { viewModel.handleIntent(LearningIntent.SelectTab(LearningTab.CERTIFICATES)) },
                    text = {
                        Text(
                            text = if (state.certificates.isNotEmpty()) "Certificates (${state.certificates.size})" else "Certificates",
                            fontWeight = FontWeight.SemiBold
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
                    CircularProgressIndicator()
                }
            } else {
                when (state.activeTab) {
                    LearningTab.DISCOVER -> DiscoverTabContent(
                        state = state,
                        onSelectCategory = { viewModel.handleIntent(LearningIntent.SelectCategory(it)) },
                        onEnroll = { viewModel.handleIntent(LearningIntent.EnrollCourse(it)) },
                        onCourseClick = onNavigateToCourse
                    )
                    LearningTab.MY_LEARNING -> MyLearningTabContent(
                        enrollments = state.myLearning,
                        onContinue = onNavigateToLesson
                    )
                    LearningTab.CERTIFICATES -> CertificatesTabContent(
                        certificates = state.certificates
                    )
                }
            }
        }
    }
}

@Composable
private fun DiscoverTabContent(
    state: LearningUiState,
    onSelectCategory: (Long?) -> Unit,
    onEnroll: (Long) -> Unit,
    onCourseClick: (String) -> Unit
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
                            label = { Text("All Categories") }
                        )
                    }
                    items(state.categories) { cat ->
                        FilterChip(
                            selected = state.selectedCategoryId == cat.id,
                            onClick = { onSelectCategory(cat.id) },
                            label = { Text(cat.name) }
                        )
                    }
                }
            }
        }

        // Empty State
        if (state.courses.isEmpty()) {
            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
                ) {
                    Column(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(24.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Icon(
                            imageVector = Icons.Default.School,
                            contentDescription = null,
                            modifier = Modifier.size(48.dp),
                            tint = MaterialTheme.colorScheme.primary
                        )
                        Spacer(modifier = Modifier.height(12.dp))
                        Text(
                            text = "No courses found",
                            style = MaterialTheme.typography.titleMedium,
                            fontWeight = FontWeight.Bold
                        )
                        Text(
                            text = "Try searching for a different skill or category",
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                    }
                }
            }
        } else {
            items(state.courses) { course ->
                CourseCard(
                    course = course,
                    onEnroll = { onEnroll(course.id) },
                    onClick = { onCourseClick(course.slug) }
                )
            }
        }
    }
}

@Composable
private fun CourseCard(
    course: LearningCourse,
    onEnroll: () -> Unit,
    onClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(16.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column {
            // Thumbnail or Placeholder Banner
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(140.dp)
                    .background(Color(0xFF1E293B))
            ) {
                if (course.thumbnailUrl != null) {
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
                            modifier = Modifier.size(48.dp)
                        )
                    }
                }

                // Level badge
                Box(
                    modifier = Modifier
                        .padding(8.dp)
                        .clip(RoundedCornerShape(8.dp))
                        .background(Color.Black.copy(alpha = 0.7f))
                        .padding(horizontal = 8.dp, vertical = 4.dp)
                        .align(Alignment.TopEnd)
                ) {
                    Text(
                        text = course.level.replace('_', ' ').uppercase(),
                        color = Color.White,
                        fontSize = 10.sp,
                        fontWeight = FontWeight.Bold
                    )
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

                if (course.subtitle != null) {
                    Spacer(modifier = Modifier.height(4.dp))
                    Text(
                        text = course.subtitle,
                        style = MaterialTheme.typography.bodyMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                        maxLines = 2,
                        overflow = TextOverflow.Ellipsis
                    )
                }

                Spacer(modifier = Modifier.height(8.dp))

                // Instructor & Rating Row
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.SpaceBetween
                ) {
                    Text(
                        text = "Instructor: ${course.instructorName ?: "Connectly Faculty"}",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.primary,
                        fontWeight = FontWeight.Medium
                    )

                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Icon(
                            imageVector = Icons.Default.Star,
                            contentDescription = null,
                            tint = Color(0xFFF59E0B),
                            modifier = Modifier.size(16.dp)
                        )
                        Spacer(modifier = Modifier.width(4.dp))
                        Text(
                            text = "${course.ratingAvg} (${course.enrollmentsCount} enrolled)",
                            style = MaterialTheme.typography.bodySmall,
                            fontWeight = FontWeight.SemiBold
                        )
                    }
                }

                Spacer(modifier = Modifier.height(12.dp))

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
                        color = if (course.price == 0.0) Color(0xFF10B981) else MaterialTheme.colorScheme.primary
                    )

                    Button(
                        onClick = onEnroll,
                        shape = RoundedCornerShape(10.dp)
                    ) {
                        Text(if (course.price == 0.0) "Enroll Free" else "Enroll Now")
                    }
                }
            }
        }
    }
}

@Composable
private fun MyLearningTabContent(
    enrollments: List<LearningEnrollment>,
    onContinue: (Long) -> Unit
) {
    if (enrollments.isEmpty()) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(32.dp),
            contentAlignment = Alignment.Center
        ) {
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Icon(
                    imageVector = Icons.Default.School,
                    contentDescription = null,
                    modifier = Modifier.size(56.dp),
                    tint = MaterialTheme.colorScheme.primary
                )
                Spacer(modifier = Modifier.height(16.dp))
                Text(
                    text = "No enrolled courses yet",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
                Text(
                    text = "Explore the marketplace to start your professional journey.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
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
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
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
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(
                                        imageVector = Icons.Default.CheckCircle,
                                        contentDescription = null,
                                        tint = Color(0xFF10B981),
                                        modifier = Modifier.size(16.dp)
                                    )
                                    Spacer(modifier = Modifier.width(4.dp))
                                    Text(
                                        text = "Completed",
                                        color = Color(0xFF10B981),
                                        fontSize = 12.sp,
                                        fontWeight = FontWeight.Bold
                                    )
                                }
                            }
                        }

                        Spacer(modifier = Modifier.height(8.dp))

                        // Progress Bar
                        LinearProgressIndicator(
                            progress = { (enrollment.progressPercentage / 100.0).toFloat() },
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(8.dp)
                                .clip(RoundedCornerShape(4.dp)),
                        )

                        Spacer(modifier = Modifier.height(6.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text(
                                text = "${enrollment.progressPercentage.toInt()}% Complete",
                                style = MaterialTheme.typography.bodySmall,
                                fontWeight = FontWeight.SemiBold
                            )
                            Text(
                                text = "${enrollment.completedLessons}/${enrollment.totalLessons} lessons",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                        }

                        Spacer(modifier = Modifier.height(12.dp))

                        Button(
                            onClick = { onContinue(enrollment.courseId) },
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(10.dp)
                        ) {
                            Icon(
                                imageVector = Icons.Default.PlayCircle,
                                contentDescription = null,
                                modifier = Modifier.size(18.dp)
                            )
                            Spacer(modifier = Modifier.width(8.dp))
                            Text(if (enrollment.isCompleted) "Review Course" else "Continue Learning")
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun CertificatesTabContent(
    certificates: List<LearningCertificate>
) {
    if (certificates.isEmpty()) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(32.dp),
            contentAlignment = Alignment.Center
        ) {
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                Icon(
                    imageVector = Icons.Default.Verified,
                    contentDescription = null,
                    modifier = Modifier.size(56.dp),
                    tint = MaterialTheme.colorScheme.primary
                )
                Spacer(modifier = Modifier.height(16.dp))
                Text(
                    text = "No certificates earned yet",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold
                )
                Text(
                    text = "Complete courses and assessments to receive verified credentials.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
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
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Box(
                                modifier = Modifier
                                    .size(40.dp)
                                    .clip(CircleShape)
                                    .background(Color(0xFF10B981)),
                                contentAlignment = Alignment.Center
                            ) {
                                Icon(
                                    imageVector = Icons.Default.Verified,
                                    contentDescription = null,
                                    tint = Color.White
                                )
                            }
                            Spacer(modifier = Modifier.width(12.dp))
                            Column {
                                Text(
                                    text = cert.courseTitle,
                                    style = MaterialTheme.typography.titleMedium,
                                    fontWeight = FontWeight.Bold
                                )
                                Text(
                                    text = "Issued to ${cert.studentName}",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }

                        Spacer(modifier = Modifier.height(12.dp))

                        Text(
                            text = "Credential ID: ${cert.certificateCode}",
                            style = MaterialTheme.typography.bodyMedium,
                            fontWeight = FontWeight.SemiBold,
                            color = MaterialTheme.colorScheme.primary
                        )
                    }
                }
            }
        }
    }
}

