package com.connectly.android.feature.jobs.presentation.jobs

import android.content.Intent
import android.net.Uri
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.*
import androidx.compose.material.icons.outlined.BookmarkBorder
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import android.webkit.PermissionRequest
import android.webkit.WebChromeClient
import android.webkit.WebResourceRequest
import android.webkit.WebView
import android.webkit.WebViewClient
import android.widget.Toast
import androidx.compose.ui.platform.LocalClipboardManager
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.viewinterop.AndroidView
import androidx.compose.ui.window.Dialog
import androidx.compose.ui.window.DialogProperties
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.model.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun JobsHomeScreen(
    onNavigateBack: () -> Unit = {},
    onJoinVideoCall: (Long) -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: JobsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }
    val clipboardManager = LocalClipboardManager.current
    val context = LocalContext.current

    var meetingInterviewForDialog by remember { mutableStateOf<InterviewItem?>(null) }
    var inAppMeetingUrl by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(state.errorMessage, state.successMessage) {
        state.errorMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.onEvent(JobsUiEvent.ClearMessages)
        }
        state.successMessage?.let {
            snackbarHostState.showSnackbar(it)
            viewModel.onEvent(JobsUiEvent.ClearMessages)
        }
    }

    Scaffold(
        modifier = modifier.fillMaxSize(),
        snackbarHost = { SnackbarHost(snackbarHostState) },
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        text = "Jobs & Recruitment",
                        fontWeight = FontWeight.Bold,
                        fontSize = 20.sp
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onNavigateBack) {
                        Icon(
                            imageVector = Icons.AutoMirrored.Filled.ArrowBack,
                            contentDescription = "Back"
                        )
                    }
                },
                actions = {
                    IconButton(onClick = { viewModel.onEvent(JobsUiEvent.OpenCreateJobDialog(true)) }) {
                        Icon(imageVector = Icons.Default.AddBusiness, contentDescription = "Post Job", tint = ConnectlyBlue)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = MaterialTheme.colorScheme.surface
                )
            )
        }
    ) { innerPadding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(innerPadding)
        ) {
            // Tab Selector - Modern Scrollable Tab Row with Single-Line Labels
            ScrollableTabRow(
                selectedTabIndex = state.currentTab.ordinal,
                containerColor = MaterialTheme.colorScheme.surface,
                edgePadding = 12.dp,
                divider = {}
            ) {
                Tab(
                    selected = state.currentTab == JobsTab.FIND_JOBS,
                    onClick = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.FIND_JOBS)) },
                    text = { Text("Jobs", maxLines = 1, softWrap = false, fontWeight = FontWeight.SemiBold) },
                    icon = { Icon(Icons.Default.Work, contentDescription = null, modifier = Modifier.size(18.dp)) }
                )
                Tab(
                    selected = state.currentTab == JobsTab.APPLICATIONS,
                    onClick = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.APPLICATIONS)) },
                    text = { Text("Applied", maxLines = 1, softWrap = false, fontWeight = FontWeight.SemiBold) },
                    icon = { Icon(Icons.Default.Assignment, contentDescription = null, modifier = Modifier.size(18.dp)) }
                )
                Tab(
                    selected = state.currentTab == JobsTab.SAVED,
                    onClick = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.SAVED)) },
                    text = { Text("Saved", maxLines = 1, softWrap = false, fontWeight = FontWeight.SemiBold) },
                    icon = { Icon(Icons.Default.Bookmark, contentDescription = null, modifier = Modifier.size(18.dp)) }
                )
                Tab(
                    selected = state.currentTab == JobsTab.INTERVIEWS,
                    onClick = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.INTERVIEWS)) },
                    text = { Text("Interviews", maxLines = 1, softWrap = false, fontWeight = FontWeight.SemiBold) },
                    icon = { Icon(Icons.Default.Videocam, contentDescription = null, modifier = Modifier.size(18.dp)) }
                )
                Tab(
                    selected = state.currentTab == JobsTab.EMPLOYER,
                    onClick = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.EMPLOYER)) },
                    text = { Text("Employer", maxLines = 1, softWrap = false, fontWeight = FontWeight.SemiBold) },
                    icon = { Icon(Icons.Default.BusinessCenter, contentDescription = null, modifier = Modifier.size(18.dp)) }
                )
            }

            if (state.isLoading) {
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            }

            // Tab Content
            when (state.currentTab) {
                JobsTab.FIND_JOBS -> FindJobsContent(
                    state = state,
                    onSearchChange = { viewModel.onEvent(JobsUiEvent.UpdateSearchQuery(it)) },
                    onSearchSubmit = { viewModel.onEvent(JobsUiEvent.Search) },
                    onWorkplaceFilter = { viewModel.onEvent(JobsUiEvent.FilterWorkplace(it)) },
                    onSelectJob = { viewModel.onEvent(JobsUiEvent.SelectJob(it)) },
                    onSaveJob = { viewModel.onEvent(JobsUiEvent.ToggleSaveJob(it)) },
                    onApplyJob = { viewModel.onEvent(JobsUiEvent.OpenApplyDialog(it)) }
                )
                JobsTab.APPLICATIONS -> ApplicationsContent(
                    applications = state.applications,
                    onWithdraw = { viewModel.onEvent(JobsUiEvent.WithdrawApplication(it)) },
                    onBrowseJobs = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.FIND_JOBS)) }
                )
                JobsTab.SAVED -> SavedJobsContent(
                    jobs = state.savedJobs,
                    onUnsave = { viewModel.onEvent(JobsUiEvent.ToggleSaveJob(it)) },
                    onApply = { viewModel.onEvent(JobsUiEvent.OpenApplyDialog(it)) },
                    onSelectJob = { viewModel.onEvent(JobsUiEvent.SelectJob(it)) },
                    onBrowseJobs = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.FIND_JOBS)) }
                )
                JobsTab.INTERVIEWS -> InterviewsContent(
                    interviews = state.interviews,
                    onRespond = { id, accept -> viewModel.onEvent(JobsUiEvent.RespondInterview(id, accept)) },
                    onBrowseJobs = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.FIND_JOBS)) },
                    onJoinMeeting = { meetingInterviewForDialog = it }
                )
                JobsTab.EMPLOYER -> EmployerContent(
                    state = state,
                    onSelectCompany = { viewModel.onEvent(JobsUiEvent.SelectCompany(it)) },
                    onMoveStage = { appId, stage -> viewModel.onEvent(JobsUiEvent.MoveCandidateStage(appId, stage)) },
                    onOpenCreateJob = { viewModel.onEvent(JobsUiEvent.OpenCreateJobDialog(true)) },
                    onScheduleCandidate = { viewModel.onEvent(JobsUiEvent.OpenScheduleInterviewDialog(it)) }
                )
            }
        }
    }

    // Apply Dialog
    if (state.showApplyDialog && state.selectedJobDetails != null) {
        val selectedJob = state.selectedJobDetails!!
        val existingApp = state.applications.firstOrNull { it.jobPostingId == selectedJob.id }
        ApplyJobDialog(
            job = selectedJob,
            resumes = state.candidateResumes,
            defaultProfile = state.candidateProfile,
            lastApplication = state.applications.firstOrNull(),
            existingApplication = existingApp,
            errorMessage = state.errorMessage,
            onDismiss = { viewModel.onEvent(JobsUiEvent.CloseApplyDialog) },
            onSubmit = { name, email, phone, resumeId, coverLetter, salary ->
                viewModel.onEvent(
                    JobsUiEvent.SubmitApplication(
                        jobId = selectedJob.id,
                        fullName = name,
                        email = email,
                        phone = phone,
                        resumeId = resumeId,
                        coverLetter = coverLetter,
                        expectedSalary = salary
                    )
                )
            }
        )
    }

    // Job Details Bottom Sheet / Dialog
    if (state.selectedJobDetails != null && !state.showApplyDialog) {
        JobDetailsDialog(
            job = state.selectedJobDetails!!,
            onDismiss = { viewModel.onEvent(JobsUiEvent.DismissJobDetails) },
            onApply = { viewModel.onEvent(JobsUiEvent.OpenApplyDialog(state.selectedJobDetails!!)) },
            onToggleSave = { viewModel.onEvent(JobsUiEvent.ToggleSaveJob(state.selectedJobDetails!!)) }
        )
    }

    // Create Job Dialog
    if (state.showCreateJobDialog) {
        CreateJobDialog(
            companies = state.myCompanies,
            selectedCompany = state.selectedCompany,
            onDismiss = { viewModel.onEvent(JobsUiEvent.OpenCreateJobDialog(false)) },
            onSubmit = { params ->
                viewModel.onEvent(JobsUiEvent.CreateJob(params))
            }
        )
    }

    // Schedule Interview Dialog
    if (state.schedulingCandidate != null) {
        ScheduleInterviewDialog(
            candidate = state.schedulingCandidate!!,
            onDismiss = { viewModel.onEvent(JobsUiEvent.OpenScheduleInterviewDialog(null)) },
            onSubmit = { appId, scheduledAt, type, meetingUrl, location, instructions ->
                viewModel.onEvent(
                    JobsUiEvent.ScheduleInterview(
                        applicationId = appId,
                        scheduledAt = scheduledAt,
                        interviewType = type,
                        meetingUrl = meetingUrl,
                        location = location,
                        instructions = instructions
                    )
                )
            }
        )
    }

    // Interview Meeting Options Dialog
    meetingInterviewForDialog?.let { interview ->
        InterviewMeetingOptionsDialog(
            interview = interview,
            onDismiss = { meetingInterviewForDialog = null },
            onJoinBondhooCall = {
                meetingInterviewForDialog = null
                onJoinVideoCall(interview.id)
            },
            onOpenInAppWeb = {
                val url = interview.meetingUrl ?: ""
                meetingInterviewForDialog = null
                inAppMeetingUrl = url
            },
            onCopyLink = {
                interview.meetingUrl?.let { url ->
                    clipboardManager.setText(AnnotatedString(url))
                    Toast.makeText(context, "Meeting link copied to clipboard! 📋", Toast.LENGTH_SHORT).show()
                }
            }
        )
    }

    // In-App Video Meeting WebView Dialog (Blocks Play Store redirect)
    inAppMeetingUrl?.let { url ->
        InAppVideoMeetingWebViewDialog(
            url = url,
            onDismiss = { inAppMeetingUrl = null }
        )
    }
}

@Composable
private fun FindJobsContent(
    state: JobsUiState,
    onSearchChange: (String) -> Unit,
    onSearchSubmit: () -> Unit,
    onWorkplaceFilter: (String?) -> Unit,
    onSelectJob: (Job) -> Unit,
    onSaveJob: (Job) -> Unit,
    onApplyJob: (Job) -> Unit
) {
    Column(modifier = Modifier.fillMaxSize()) {
        // Search Bar
        OutlinedTextField(
            value = state.searchQuery,
            onValueChange = onSearchChange,
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 8.dp),
            placeholder = { Text("Search by title, skill, or location...") },
            leadingIcon = { Icon(Icons.Default.Search, contentDescription = "Search") },
            trailingIcon = {
                if (state.searchQuery.isNotEmpty()) {
                    IconButton(onClick = onSearchSubmit) {
                        Icon(Icons.Default.ArrowForward, contentDescription = "Submit")
                    }
                }
            },
            singleLine = true,
            shape = RoundedCornerShape(24.dp)
        )

        // Filter Pills
        LazyRow(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 16.dp, vertical = 4.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            val filters = listOf(
                null to "All Jobs",
                "remote" to "🌐 Remote",
                "hybrid" to "🔀 Hybrid",
                "onsite" to "📍 Onsite"
            )
            items(filters) { (key, label) ->
                FilterChip(
                    selected = state.selectedWorkplaceType == key,
                    onClick = { onWorkplaceFilter(key) },
                    label = { Text(label, fontSize = 12.sp) }
                )
            }
        }

        if (state.jobs.isEmpty() && !state.isLoading) {
            Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Column(horizontalAlignment = Alignment.CenterHorizontally) {
                    Icon(
                        imageVector = Icons.Default.WorkOutline,
                        contentDescription = null,
                        modifier = Modifier.size(64.dp),
                        tint = MaterialTheme.colorScheme.outline
                    )
                    Spacer(modifier = Modifier.height(12.dp))
                    Text(
                        text = "No jobs found",
                        fontWeight = FontWeight.Medium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }
        } else {
            LazyColumn(
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                items(state.jobs) { job ->
                    JobCard(
                        job = job,
                        onClick = { onSelectJob(job) },
                        onSave = { onSaveJob(job) },
                        onApply = { onApplyJob(job) }
                    )
                }
            }
        }
    }
}

@Composable
fun JobCard(
    job: Job,
    onClick: () -> Unit,
    onSave: () -> Unit,
    onApply: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(16.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f))
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Row(modifier = Modifier.weight(1f), verticalAlignment = Alignment.CenterVertically) {
                    Box(
                        modifier = Modifier
                            .size(44.dp)
                            .clip(RoundedCornerShape(10.dp))
                            .background(ConnectlyBlue.copy(alpha = 0.15f)),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            text = job.companyName.take(2).uppercase(),
                            fontWeight = FontWeight.Bold,
                            color = ConnectlyBlue
                        )
                    }
                    Spacer(modifier = Modifier.width(12.dp))
                    Column {
                        Text(
                            text = job.title,
                            fontWeight = FontWeight.Bold,
                            fontSize = 16.sp,
                            maxLines = 1,
                            overflow = TextOverflow.Ellipsis
                        )
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Text(
                                text = job.companyName,
                                fontSize = 13.sp,
                                color = MaterialTheme.colorScheme.onSurfaceVariant
                            )
                            if (job.isVerifiedCompany) {
                                Spacer(modifier = Modifier.width(4.dp))
                                Icon(
                                    imageVector = Icons.Default.Verified,
                                    contentDescription = "Verified",
                                    tint = Color(0xFF0284C7),
                                    modifier = Modifier.size(14.dp)
                                )
                            }
                        }
                    }
                }
                IconButton(onClick = onSave) {
                    Icon(
                        imageVector = if (job.isSaved) Icons.Default.Bookmark else Icons.Outlined.BookmarkBorder,
                        contentDescription = "Save",
                        tint = if (job.isSaved) ConnectlyBlue else MaterialTheme.colorScheme.onSurfaceVariant
                    )
                }
            }

            Spacer(modifier = Modifier.height(10.dp))

            // Badges row
            @OptIn(ExperimentalLayoutApi::class)
            FlowRow(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                AssistChip(
                    onClick = {},
                    label = { Text(job.location, fontSize = 11.sp) },
                    leadingIcon = { Icon(Icons.Default.LocationOn, null, modifier = Modifier.size(12.dp)) }
                )
                AssistChip(
                    onClick = {},
                    label = { Text(job.employmentTypeLabel.ifEmpty { job.employmentType }, fontSize = 11.sp) }
                )
                AssistChip(
                    onClick = {},
                    label = { Text(job.salaryFormatted, fontSize = 11.sp, fontWeight = FontWeight.SemiBold) }
                )
            }

            if (job.skills.isNotEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    job.skills.take(3).forEach { skill ->
                        Surface(
                            shape = RoundedCornerShape(6.dp),
                            color = MaterialTheme.colorScheme.surface
                        ) {
                            Text(
                                text = skill,
                                modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                fontSize = 11.sp,
                                color = MaterialTheme.colorScheme.onSurface
                            )
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(12.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "${job.applicationsCount} applicants",
                    fontSize = 12.sp,
                    color = MaterialTheme.colorScheme.outline
                )
                Button(
                    onClick = onApply,
                    enabled = !job.hasApplied && !job.isExpired,
                    shape = RoundedCornerShape(12.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                ) {
                    Text(
                        text = if (job.hasApplied) "Applied ✓" else "Apply Now",
                        fontSize = 13.sp,
                        fontWeight = FontWeight.SemiBold
                    )
                }
            }
        }
    }
}

@Composable
private fun ApplicationsContent(
    applications: List<JobApplicationItem>,
    onWithdraw: (Long) -> Unit,
    onBrowseJobs: () -> Unit
) {
    if (applications.isEmpty()) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(24.dp),
            contentAlignment = Alignment.Center
        ) {
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(20.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f))
            ) {
                Column(
                    modifier = Modifier.padding(24.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center
                ) {
                    Box(
                        modifier = Modifier
                            .size(72.dp)
                            .clip(CircleShape)
                            .background(ConnectlyBlue.copy(alpha = 0.12f)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.Assignment,
                            contentDescription = null,
                            modifier = Modifier.size(36.dp),
                            tint = ConnectlyBlue
                        )
                    }
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(
                        text = "No Applications Submitted Yet",
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        text = "Find and apply for top tech, design, marketing, and leadership opportunities. Track your application status and recruiter updates here.",
                        fontSize = 13.sp,
                        color = MaterialTheme.colorScheme.outline,
                        lineHeight = 18.sp,
                        textAlign = androidx.compose.ui.text.style.TextAlign.Center
                    )
                    Spacer(modifier = Modifier.height(20.dp))
                    Button(
                        onClick = onBrowseJobs,
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                    ) {
                        Icon(Icons.Default.Search, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Explore Open Positions", fontWeight = FontWeight.SemiBold)
                    }
                }
            }
        }
    } else {
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "Your Submitted Applications (${applications.size})",
                        fontWeight = FontWeight.Bold,
                        fontSize = 16.sp
                    )
                    TextButton(onClick = onBrowseJobs) {
                        Text("Apply to More", fontSize = 12.sp, color = ConnectlyBlue)
                    }
                }
            }
            items(applications) { app ->
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    text = app.jobTitle ?: "Job Application",
                                    fontWeight = FontWeight.Bold,
                                    fontSize = 16.sp
                                )
                                Text(
                                    text = app.companyName ?: "",
                                    fontSize = 13.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                            Surface(
                                shape = RoundedCornerShape(8.dp),
                                color = Color(android.graphics.Color.parseColor(app.statusBg))
                            ) {
                                Text(
                                    text = app.statusLabel,
                                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                                    fontSize = 12.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = Color(android.graphics.Color.parseColor(app.statusColor))
                                )
                            }
                        }
                        Spacer(modifier = Modifier.height(10.dp))
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(
                                text = "Applied: ${app.appliedAtFormatted ?: "Recent"}",
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.outline
                            )
                            if (app.status == "applied" || app.status == "pending") {
                                TextButton(
                                    onClick = { onWithdraw(app.id) },
                                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 0.dp)
                                ) {
                                    Text("Withdraw", fontSize = 12.sp, color = MaterialTheme.colorScheme.error)
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun SavedJobsContent(
    jobs: List<Job>,
    onUnsave: (Job) -> Unit,
    onApply: (Job) -> Unit,
    onSelectJob: (Job) -> Unit,
    onBrowseJobs: () -> Unit
) {
    if (jobs.isEmpty()) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(24.dp),
            contentAlignment = Alignment.Center
        ) {
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(20.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f))
            ) {
                Column(
                    modifier = Modifier.padding(24.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center
                ) {
                    Box(
                        modifier = Modifier
                            .size(72.dp)
                            .clip(CircleShape)
                            .background(ConnectlyBlue.copy(alpha = 0.12f)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.Bookmark,
                            contentDescription = null,
                            modifier = Modifier.size(36.dp),
                            tint = ConnectlyBlue
                        )
                    }
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(
                        text = "No Saved Jobs Yet",
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        text = "Bookmark interesting vacancies to apply later or compare salary packages and benefits across companies.",
                        fontSize = 13.sp,
                        color = MaterialTheme.colorScheme.outline,
                        lineHeight = 18.sp,
                        textAlign = androidx.compose.ui.text.style.TextAlign.Center
                    )
                    Spacer(modifier = Modifier.height(20.dp))
                    Button(
                        onClick = onBrowseJobs,
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                    ) {
                        Icon(Icons.Default.Search, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Search Available Jobs", fontWeight = FontWeight.SemiBold)
                    }
                }
            }
        }
    } else {
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "Saved Jobs (${jobs.size})",
                        fontWeight = FontWeight.Bold,
                        fontSize = 16.sp
                    )
                    TextButton(onClick = onBrowseJobs) {
                        Text("Find More", fontSize = 12.sp, color = ConnectlyBlue)
                    }
                }
            }
            items(jobs) { job ->
                JobCard(
                    job = job,
                    onClick = { onSelectJob(job) },
                    onSave = { onUnsave(job) },
                    onApply = { onApply(job) }
                )
            }
        }
    }
}

@Composable
private fun InterviewsContent(
    interviews: List<InterviewItem>,
    onRespond: (Long, Boolean) -> Unit,
    onBrowseJobs: () -> Unit,
    onJoinMeeting: (InterviewItem) -> Unit
) {
    val context = LocalContext.current

    if (interviews.isEmpty()) {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .padding(24.dp),
            contentAlignment = Alignment.Center
        ) {
            Card(
                modifier = Modifier.fillMaxWidth(),
                shape = RoundedCornerShape(20.dp),
                colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.35f))
            ) {
                Column(
                    modifier = Modifier.padding(24.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center
                ) {
                    Box(
                        modifier = Modifier
                            .size(72.dp)
                            .clip(CircleShape)
                            .background(ConnectlyBlue.copy(alpha = 0.12f)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.Videocam,
                            contentDescription = null,
                            modifier = Modifier.size(36.dp),
                            tint = ConnectlyBlue
                        )
                    }
                    Spacer(modifier = Modifier.height(16.dp))
                    Text(
                        text = "No Upcoming Interviews",
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    Text(
                        text = "When recruiters review your job applications and schedule an interview, it will appear here with calendar dates, meeting links, and instant accept/decline controls.",
                        fontSize = 13.sp,
                        color = MaterialTheme.colorScheme.outline,
                        lineHeight = 18.sp,
                        textAlign = androidx.compose.ui.text.style.TextAlign.Center
                    )
                    Spacer(modifier = Modifier.height(20.dp))
                    Button(
                        onClick = onBrowseJobs,
                        shape = RoundedCornerShape(12.dp),
                        colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                    ) {
                        Icon(Icons.Default.Work, contentDescription = null, modifier = Modifier.size(16.dp))
                        Spacer(modifier = Modifier.width(8.dp))
                        Text("Browse Open Positions", fontWeight = FontWeight.SemiBold)
                    }
                }
            }
        }
    } else {
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            item {
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "Scheduled Interviews (${interviews.size})",
                        fontWeight = FontWeight.Bold,
                        fontSize = 16.sp
                    )
                    TextButton(onClick = onBrowseJobs) {
                        Text("Find More Jobs", fontSize = 12.sp, color = ConnectlyBlue)
                    }
                }
            }
            items(interviews) { item ->
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp),
                    elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Row(
                                modifier = Modifier.weight(1f),
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Box(
                                    modifier = Modifier
                                        .size(40.dp)
                                        .clip(CircleShape)
                                        .background(ConnectlyBlue.copy(alpha = 0.15f)),
                                    contentAlignment = Alignment.Center
                                ) {
                                    Text(
                                        text = (item.companyName?.take(1) ?: "C").uppercase(),
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 18.sp,
                                        color = ConnectlyBlue
                                    )
                                }
                                Spacer(modifier = Modifier.width(10.dp))
                                Column {
                                    Text(
                                        text = item.companyName ?: "Company",
                                        fontWeight = FontWeight.Bold,
                                        fontSize = 15.sp
                                    )
                                    Text(
                                        text = item.jobTitle ?: "Position",
                                        fontSize = 12.sp,
                                        color = MaterialTheme.colorScheme.onSurfaceVariant
                                    )
                                }
                            }

                            // Status badge
                            val (badgeBg, badgeColor, statusText) = when (item.status.lowercase()) {
                                "confirmed", "accepted" -> Triple(Color(0xFFDCFCE7), Color(0xFF166534), "Confirmed ✓")
                                "declined" -> Triple(Color(0xFFFEE2E2), Color(0xFF991B1B), "Declined")
                                "rescheduled" -> Triple(Color(0xFFE0E7FF), Color(0xFF3730A3), "Rescheduled")
                                "completed" -> Triple(Color(0xFFF3F4F6), Color(0xFF4B5563), "Completed")
                                else -> Triple(Color(0xFFFEF3C7), Color(0xFF92400E), "Pending Action")
                            }

                            Surface(
                                shape = RoundedCornerShape(8.dp),
                                color = badgeBg
                            ) {
                                Text(
                                    text = statusText,
                                    modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = badgeColor
                                )
                            }
                        }

                        Spacer(modifier = Modifier.height(14.dp))

                        // Schedule info chips
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(8.dp)
                        ) {
                            AssistChip(
                                onClick = {},
                                label = { Text(item.scheduledAtFormatted, fontSize = 11.sp, fontWeight = FontWeight.SemiBold) },
                                leadingIcon = { Icon(Icons.Default.Event, null, modifier = Modifier.size(14.dp), tint = ConnectlyBlue) }
                            )
                            AssistChip(
                                onClick = {},
                                label = {
                                    Text(
                                        when (item.interviewType.lowercase()) {
                                            "video" -> "Video Call"
                                            "phone" -> "Phone"
                                            else -> "In-Person"
                                        },
                                        fontSize = 11.sp
                                    )
                                },
                                leadingIcon = {
                                    Icon(
                                        if (item.interviewType.lowercase() == "video") Icons.Default.Videocam else if (item.interviewType.lowercase() == "phone") Icons.Default.Phone else Icons.Default.Business,
                                        null,
                                        modifier = Modifier.size(14.dp)
                                    )
                                }
                            )
                        }

                        val loc = item.location
                        if (!loc.isNullOrBlank()) {
                            Spacer(modifier = Modifier.height(6.dp))
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.LocationOn, null, modifier = Modifier.size(14.dp), tint = MaterialTheme.colorScheme.outline)
                                Spacer(modifier = Modifier.width(4.dp))
                                Text(loc, fontSize = 12.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
                            }
                        }

                        val notes = item.instructions
                        if (!notes.isNullOrBlank()) {
                            Spacer(modifier = Modifier.height(8.dp))
                            Surface(
                                shape = RoundedCornerShape(8.dp),
                                color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                                modifier = Modifier.fillMaxWidth()
                            ) {
                                Text(
                                    text = "Notes: $notes",
                                    modifier = Modifier.padding(10.dp),
                                    fontSize = 12.sp,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant
                                )
                            }
                        }

                        if (!item.meetingUrl.isNullOrEmpty()) {
                            Spacer(modifier = Modifier.height(10.dp))
                            Button(
                                onClick = { onJoinMeeting(item) },
                                modifier = Modifier.fillMaxWidth(),
                                shape = RoundedCornerShape(10.dp),
                                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                            ) {
                                Icon(Icons.Default.VideoCall, contentDescription = null, modifier = Modifier.size(18.dp))
                                Spacer(modifier = Modifier.width(8.dp))
                                Text("Join Video Meeting", fontSize = 13.sp, fontWeight = FontWeight.Bold)
                            }
                        }

                        Spacer(modifier = Modifier.height(12.dp))

                        // Status actions
                        if (item.status.lowercase() == "scheduled") {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.spacedBy(8.dp)
                            ) {
                                Button(
                                    onClick = { onRespond(item.id, true) },
                                    modifier = Modifier.weight(1f),
                                    shape = RoundedCornerShape(10.dp),
                                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF16A34A))
                                ) {
                                    Icon(Icons.Default.Check, null, modifier = Modifier.size(16.dp))
                                    Spacer(modifier = Modifier.width(6.dp))
                                    Text("Accept & Confirm", fontSize = 12.sp)
                                }
                                OutlinedButton(
                                    onClick = { onRespond(item.id, false) },
                                    modifier = Modifier.weight(1f),
                                    shape = RoundedCornerShape(10.dp)
                                ) {
                                    Icon(Icons.Default.Close, null, modifier = Modifier.size(16.dp))
                                    Spacer(modifier = Modifier.width(6.dp))
                                    Text("Decline", fontSize = 12.sp)
                                }
                            }
                        } else if (item.status.lowercase() in listOf("confirmed", "accepted")) {
                            Text(
                                text = "✓ Invitation accepted. Please arrive on time at the scheduled meeting link.",
                                fontSize = 12.sp,
                                color = Color(0xFF16A34A),
                                fontWeight = FontWeight.Medium
                            )
                        } else if (item.status.lowercase() == "declined") {
                            Text(
                                text = "✕ You declined this interview invitation.",
                                fontSize = 12.sp,
                                color = MaterialTheme.colorScheme.error
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun EmployerContent(
    state: JobsUiState,
    onSelectCompany: (CompanyItem) -> Unit,
    onMoveStage: (Long, String) -> Unit,
    onOpenCreateJob: () -> Unit,
    onScheduleCandidate: (AtsCandidateItem) -> Unit
) {
    val scrollState = rememberScrollState()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(scrollState)
            .padding(16.dp)
    ) {
        // Quick Action - Post New Job
        Button(
            onClick = onOpenCreateJob,
            modifier = Modifier.fillMaxWidth().height(48.dp),
            shape = RoundedCornerShape(12.dp),
            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
        ) {
            Icon(Icons.Default.AddBusiness, contentDescription = null, modifier = Modifier.size(20.dp))
            Spacer(modifier = Modifier.width(8.dp))
            Text("Post a New Job Opening", fontWeight = FontWeight.Bold, fontSize = 15.sp)
        }
        Spacer(modifier = Modifier.height(16.dp))

        // Company Selector
        if (state.myCompanies.isNotEmpty()) {
            Text("Select Company", fontWeight = FontWeight.Bold, fontSize = 14.sp)
            Spacer(modifier = Modifier.height(8.dp))
            LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                items(state.myCompanies) { company ->
                    FilterChip(
                        selected = state.selectedCompany?.id == company.id,
                        onClick = { onSelectCompany(company) },
                        label = { Text(company.name) }
                    )
                }
            }
            Spacer(modifier = Modifier.height(16.dp))
        }

        // Metrics Grid
        Text("Recruitment Overview", fontWeight = FontWeight.Bold, fontSize = 16.sp)
        Spacer(modifier = Modifier.height(10.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            MetricCard("Total Jobs", state.employerMetrics.totalJobs.toString(), Modifier.weight(1f))
            MetricCard("Active Jobs", state.employerMetrics.activeJobs.toString(), Modifier.weight(1f))
            MetricCard("Applicants", state.employerMetrics.totalApplications.toString(), Modifier.weight(1f))
        }
        Spacer(modifier = Modifier.height(10.dp))
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            MetricCard("Shortlisted", state.employerMetrics.shortlistedCandidates.toString(), Modifier.weight(1f))
            MetricCard("Interviews", state.employerMetrics.interviewsScheduled.toString(), Modifier.weight(1f))
            MetricCard("Hired", state.employerMetrics.hiredCandidates.toString(), Modifier.weight(1f))
        }

        Spacer(modifier = Modifier.height(24.dp))

        // ATS Pipeline Board
        Text("ATS Candidate Pipeline", fontWeight = FontWeight.Bold, fontSize = 16.sp)
        Spacer(modifier = Modifier.height(10.dp))

        if (state.atsColumns.isEmpty()) {
            Text("No active candidates in pipeline.", color = MaterialTheme.colorScheme.outline)
        } else {
            state.atsColumns.forEach { col ->
                Card(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(vertical = 4.dp),
                    shape = RoundedCornerShape(12.dp)
                ) {
                    Column(modifier = Modifier.padding(12.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically
                        ) {
                            Text(col.title, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                            Surface(
                                shape = RoundedCornerShape(8.dp),
                                color = MaterialTheme.colorScheme.surfaceVariant
                            ) {
                                Text(
                                    "${col.count} Candidates",
                                    modifier = Modifier.padding(horizontal = 6.dp, vertical = 2.dp),
                                    fontSize = 11.sp,
                                    fontWeight = FontWeight.SemiBold,
                                    color = MaterialTheme.colorScheme.outline
                                )
                            }
                        }
                        Spacer(modifier = Modifier.height(6.dp))
                        col.candidates.forEach { cand ->
                            Surface(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(vertical = 4.dp),
                                shape = RoundedCornerShape(10.dp),
                                color = MaterialTheme.colorScheme.surface,
                                border = BorderStroke(1.dp, MaterialTheme.colorScheme.outlineVariant.copy(alpha = 0.5f))
                            ) {
                                Column(modifier = Modifier.padding(10.dp)) {
                                    Row(
                                        modifier = Modifier.fillMaxWidth(),
                                        horizontalArrangement = Arrangement.SpaceBetween,
                                        verticalAlignment = Alignment.CenterVertically
                                    ) {
                                        Column(modifier = Modifier.weight(1f)) {
                                            Text(cand.candidateName, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                                            Text(cand.candidateEmail, fontSize = 12.sp, color = MaterialTheme.colorScheme.outline)
                                            if (!cand.jobTitle.isNullOrBlank()) {
                                                Text("Applied for: ${cand.jobTitle}", fontSize = 11.sp, color = ConnectlyBlue)
                                            }
                                        }
                                        IconButton(onClick = { onScheduleCandidate(cand) }) {
                                            Icon(
                                                Icons.Default.Videocam,
                                                contentDescription = "Schedule Interview",
                                                tint = ConnectlyBlue
                                            )
                                        }
                                    }

                                    Spacer(modifier = Modifier.height(8.dp))

                                    // Action Row: Stage movement chips & Schedule button
                                    Row(
                                        modifier = Modifier.horizontalScroll(rememberScrollState()),
                                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                                    ) {
                                        Button(
                                            onClick = { onScheduleCandidate(cand) },
                                            shape = RoundedCornerShape(8.dp),
                                            contentPadding = PaddingValues(horizontal = 8.dp, vertical = 2.dp),
                                            colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                                        ) {
                                            Icon(Icons.Default.Event, null, modifier = Modifier.size(12.dp))
                                            Spacer(modifier = Modifier.width(4.dp))
                                            Text("Interview", fontSize = 11.sp)
                                        }

                                        val stageOptions = listOf(
                                            "under_review" to "Review",
                                            "shortlisted" to "Shortlist",
                                            "interview" to "Interview Stage",
                                            "offer" to "Offer",
                                            "hired" to "Hire",
                                            "rejected" to "Reject"
                                        )

                                        stageOptions.forEach { (stageKey, stageLabel) ->
                                            if (col.stageKey != stageKey) {
                                                OutlinedButton(
                                                    onClick = { onMoveStage(cand.id, stageKey) },
                                                    shape = RoundedCornerShape(8.dp),
                                                    contentPadding = PaddingValues(horizontal = 8.dp, vertical = 2.dp)
                                                ) {
                                                    Text(stageLabel, fontSize = 11.sp)
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun MetricCard(title: String, value: String, modifier: Modifier = Modifier) {
    Card(modifier = modifier, shape = RoundedCornerShape(12.dp)) {
        Column(modifier = Modifier.padding(12.dp), horizontalAlignment = Alignment.CenterHorizontally) {
            Text(value, fontWeight = FontWeight.Bold, fontSize = 20.sp, color = ConnectlyBlue)
            Spacer(modifier = Modifier.height(2.dp))
            Text(title, fontSize = 11.sp, color = MaterialTheme.colorScheme.outline)
        }
    }
}

@Composable
fun ApplyJobDialog(
    job: Job,
    resumes: List<CandidateResumeItem>,
    defaultProfile: CandidateProfileItem?,
    lastApplication: JobApplicationItem? = null,
    existingApplication: JobApplicationItem? = null,
    errorMessage: String? = null,
    onDismiss: () -> Unit,
    onSubmit: (String, String, String, Long?, String?, Int?) -> Unit
) {
    val isAlreadyApplied = job.hasApplied || existingApplication != null
    var fullName by remember { mutableStateOf(lastApplication?.fullName ?: "") }
    var email by remember { mutableStateOf(lastApplication?.email ?: "") }
    var phone by remember { mutableStateOf(lastApplication?.phone ?: "") }
    var coverLetter by remember { mutableStateOf("") }
    var expectedSalary by remember {
        mutableStateOf(
            defaultProfile?.expectedSalary?.toString()
                ?: (job.salaryMin?.toString() ?: "")
        )
    }
    var selectedResumeId by remember { mutableStateOf<Long?>(resumes.firstOrNull { it.isDefault }?.id ?: resumes.firstOrNull()?.id) }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Apply for ${job.title}") },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                if (isAlreadyApplied) {
                    Surface(
                        color = Color(0xFFFEF3C7),
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Column(modifier = Modifier.padding(10.dp)) {
                            Text(
                                text = "You have already applied for this position.",
                                color = Color(0xFF92400E),
                                fontWeight = FontWeight.Bold,
                                fontSize = 13.sp
                            )
                            if (existingApplication != null) {
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(
                                    text = "Current Status: ${existingApplication.statusLabel.ifBlank { existingApplication.status }}",
                                    color = Color(0xFF78350F),
                                    fontSize = 12.sp
                                )
                            }
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "Check the 'Applied' or 'Interviews' tab to track updates or join scheduled sessions.",
                                color = Color(0xFF92400E),
                                fontSize = 11.sp
                            )
                        }
                    }
                }

                if (!errorMessage.isNullOrBlank()) {
                    Surface(
                        color = MaterialTheme.colorScheme.errorContainer,
                        shape = RoundedCornerShape(8.dp),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text(
                            text = errorMessage,
                            color = MaterialTheme.colorScheme.onErrorContainer,
                            fontSize = 12.sp,
                            modifier = Modifier.padding(10.dp)
                        )
                    }
                }
                OutlinedTextField(
                    value = fullName,
                    onValueChange = { fullName = it },
                    label = { Text("Full Name *") },
                    singleLine = true,
                    enabled = !isAlreadyApplied,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = email,
                    onValueChange = { email = it },
                    label = { Text("Email *") },
                    singleLine = true,
                    enabled = !isAlreadyApplied,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = phone,
                    onValueChange = { phone = it },
                    label = { Text("Phone Number *") },
                    singleLine = true,
                    enabled = !isAlreadyApplied,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = expectedSalary,
                    onValueChange = { expectedSalary = it },
                    label = { Text("Expected Salary (BDT)") },
                    singleLine = true,
                    enabled = !isAlreadyApplied,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = coverLetter,
                    onValueChange = { coverLetter = it },
                    label = { Text("Cover Letter") },
                    maxLines = 4,
                    enabled = !isAlreadyApplied,
                    modifier = Modifier.fillMaxWidth()
                )
                if (resumes.isNotEmpty()) {
                    Text("Select Resume:", fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                    resumes.forEach { r ->
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            RadioButton(
                                selected = selectedResumeId == r.id,
                                onClick = { if (!isAlreadyApplied) selectedResumeId = r.id },
                                enabled = !isAlreadyApplied
                            )
                            Text(r.title, fontSize = 13.sp)
                        }
                    }
                }
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    onSubmit(
                        fullName.trim(),
                        email.trim(),
                        phone.trim(),
                        selectedResumeId,
                        coverLetter.trim().ifEmpty { null },
                        expectedSalary.toIntOrNull()
                    )
                },
                enabled = !isAlreadyApplied && fullName.isNotBlank() && email.isNotBlank() && phone.isNotBlank()
            ) {
                Text(if (isAlreadyApplied) "Already Applied ✓" else "Submit Application")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) { Text("Cancel") }
        }
    )
}

@Composable
fun JobDetailsDialog(
    job: Job,
    onDismiss: () -> Unit,
    onApply: () -> Unit,
    onToggleSave: () -> Unit
) {
    if (job.id <= 0) return

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Column {
                Text(job.title, fontWeight = FontWeight.Bold, fontSize = 18.sp)
                Text(job.companyName, fontSize = 14.sp, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
        },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text(
                    text = "Salary: ${job.salaryFormatted}",
                    fontWeight = FontWeight.SemiBold,
                    color = ConnectlyBlue
                )
                Text(text = "Location: ${job.location} (${job.locationTypeLabel})")
                Text(text = "Employment: ${job.employmentTypeLabel}")
                val resp = job.responsibilities
                if (!resp.isNullOrEmpty()) {
                    Text("Responsibilities:", fontWeight = FontWeight.Bold)
                    Text(resp)
                }
                val req = job.requirements
                if (!req.isNullOrEmpty()) {
                    Text("Requirements:", fontWeight = FontWeight.Bold)
                    Text(req)
                }
                val ben = job.benefits
                if (!ben.isNullOrEmpty()) {
                    Text("Benefits:", fontWeight = FontWeight.Bold)
                    Text(ben)
                }
                Text("Description:", fontWeight = FontWeight.Bold)
                Text(job.description)
            }
        },
        confirmButton = {
            Button(
                onClick = onApply,
                enabled = !job.hasApplied && !job.isExpired
            ) {
                Text(if (job.hasApplied) "Applied ✓" else "Apply")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) { Text("Close") }
        }
    )
}

@Composable
fun CreateJobDialog(
    companies: List<CompanyItem>,
    selectedCompany: CompanyItem?,
    onDismiss: () -> Unit,
    onSubmit: (Map<String, Any?>) -> Unit
) {
    var activeCompany by remember { mutableStateOf(selectedCompany ?: companies.firstOrNull()) }
    var title by remember { mutableStateOf("") }
    var location by remember { mutableStateOf("Dhaka, Bangladesh") }
    var selectedWorkplace by remember { mutableStateOf("onsite") }
    var selectedEmployment by remember { mutableStateOf("full_time") }
    var experienceLevel by remember { mutableStateOf("mid") }
    var salaryMin by remember { mutableStateOf("") }
    var salaryMax by remember { mutableStateOf("") }
    var vacancies by remember { mutableStateOf("1") }
    var skillsInput by remember { mutableStateOf("") }
    var description by remember { mutableStateOf("") }
    var requirements by remember { mutableStateOf("") }
    var benefits by remember { mutableStateOf("") }

    val workplaceOptions = listOf("onsite" to "📍 Onsite", "remote" to "🌐 Remote", "hybrid" to "🔀 Hybrid")
    val employmentOptions = listOf(
        "full_time" to "Full-time",
        "part_time" to "Part-time",
        "contract" to "Contract",
        "internship" to "Internship",
        "freelance" to "Freelance"
    )
    val experienceOptions = listOf(
        "entry" to "Entry-level",
        "mid" to "Mid-level",
        "senior" to "Senior",
        "lead" to "Team Lead"
    )

    AlertDialog(
        onDismissRequest = onDismiss,
        title = { Text("Post a Job Opening", fontWeight = FontWeight.Bold) },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                if (companies.isNotEmpty()) {
                    Text("Publish as Company:", fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                    Row(
                        modifier = Modifier.horizontalScroll(rememberScrollState()),
                        horizontalArrangement = Arrangement.spacedBy(6.dp)
                    ) {
                        companies.forEach { comp ->
                            FilterChip(
                                selected = activeCompany?.id == comp.id,
                                onClick = { activeCompany = comp },
                                label = { Text(comp.name, fontSize = 12.sp) }
                            )
                        }
                    }
                }

                OutlinedTextField(
                    value = title,
                    onValueChange = { title = it },
                    label = { Text("Job Title *") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = location,
                    onValueChange = { location = it },
                    label = { Text("Location *") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                Text("Workplace Type:", fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                Row(
                    modifier = Modifier.horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    workplaceOptions.forEach { (typeKey, label) ->
                        FilterChip(
                            selected = selectedWorkplace == typeKey,
                            onClick = { selectedWorkplace = typeKey },
                            label = { Text(label, fontSize = 12.sp) }
                        )
                    }
                }

                Text("Employment Type:", fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                Row(
                    modifier = Modifier.horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    employmentOptions.forEach { (empKey, label) ->
                        FilterChip(
                            selected = selectedEmployment == empKey,
                            onClick = { selectedEmployment = empKey },
                            label = { Text(label, fontSize = 12.sp) }
                        )
                    }
                }

                Text("Experience Level:", fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                Row(
                    modifier = Modifier.horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    experienceOptions.forEach { (expKey, expLabel) ->
                        FilterChip(
                            selected = experienceLevel == expKey,
                            onClick = { experienceLevel = expKey },
                            label = { Text(expLabel, fontSize = 12.sp) }
                        )
                    }
                }

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    OutlinedTextField(
                        value = salaryMin,
                        onValueChange = { salaryMin = it },
                        label = { Text("Min Salary (BDT)") },
                        singleLine = true,
                        modifier = Modifier.weight(1f)
                    )
                    OutlinedTextField(
                        value = salaryMax,
                        onValueChange = { salaryMax = it },
                        label = { Text("Max Salary (BDT)") },
                        singleLine = true,
                        modifier = Modifier.weight(1f)
                    )
                }

                OutlinedTextField(
                    value = vacancies,
                    onValueChange = { vacancies = it },
                    label = { Text("Vacancies") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = skillsInput,
                    onValueChange = { skillsInput = it },
                    label = { Text("Required Skills (comma separated, e.g. Laravel, Kotlin)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    label = { Text("Job Description *") },
                    maxLines = 4,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = requirements,
                    onValueChange = { requirements = it },
                    label = { Text("Requirements & Responsibilities") },
                    maxLines = 3,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = benefits,
                    onValueChange = { benefits = it },
                    label = { Text("Perks & Benefits") },
                    maxLines = 3,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    val skillsList = skillsInput.split(",").map { it.trim() }.filter { it.isNotEmpty() }
                    val minSal = salaryMin.toIntOrNull()
                    val maxSal = salaryMax.toIntOrNull()
                    val map = mutableMapOf<String, Any?>(
                        "title" to title,
                        "location" to location,
                        "workplace_type" to selectedWorkplace,
                        "location_type" to selectedWorkplace,
                        "employment_type" to selectedEmployment,
                        "experience_level" to experienceLevel,
                        "salary_min" to minSal,
                        "salary_max" to (if (maxSal != null && minSal != null && maxSal < minSal) minSal else maxSal),
                        "salary_currency" to "BDT",
                        "vacancies" to (vacancies.toIntOrNull() ?: 1),
                        "description" to description,
                        "requirements" to requirements.ifEmpty { null },
                        "benefits" to benefits.ifEmpty { null }
                    )
                    if (skillsList.isNotEmpty()) {
                        map["skills"] = skillsList
                    }
                    activeCompany?.id?.let { map["company_id"] = it }
                    onSubmit(map)
                },
                enabled = title.isNotBlank() && location.isNotBlank() && description.isNotBlank(),
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
            ) {
                Text("Publish Job")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) { Text("Cancel") }
        }
    )
}

@Composable
fun ScheduleInterviewDialog(
    candidate: AtsCandidateItem,
    onDismiss: () -> Unit,
    onSubmit: (Long, String, String, String?, String?, String?) -> Unit
) {
    var interviewType by remember { mutableStateOf("video") }
    var scheduledAt by remember {
        mutableStateOf(
            java.time.LocalDateTime.now().plusDays(2).withHour(15).withMinute(0)
                .format(java.time.format.DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss"))
        )
    }
    var meetingUrl by remember { mutableStateOf("https://meet.google.com/connectly-${candidate.id}") }
    var location by remember { mutableStateOf("Google Meet Video Conference") }
    var instructions by remember { mutableStateOf("Please be ready with your portfolio and a working camera/microphone.") }

    AlertDialog(
        onDismissRequest = onDismiss,
        title = {
            Column {
                Text("Schedule Candidate Interview", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                Text(
                    text = "${candidate.candidateName} • ${candidate.jobTitle ?: "Position"}",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )
            }
        },
        text = {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .verticalScroll(rememberScrollState()),
                verticalArrangement = Arrangement.spacedBy(10.dp)
            ) {
                Text("Interview Format:", fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                Row(
                    modifier = Modifier.horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(6.dp)
                ) {
                    listOf("video" to "📹 Video Call", "phone" to "📞 Phone", "in_person" to "🏢 In-Person").forEach { (typeKey, label) ->
                        FilterChip(
                            selected = interviewType == typeKey,
                            onClick = {
                                interviewType = typeKey
                                if (typeKey == "video") {
                                    meetingUrl = "https://meet.google.com/connectly-${candidate.id}"
                                    location = "Google Meet Video Conference"
                                } else if (typeKey == "phone") {
                                    location = "Phone Call (${candidate.candidateEmail})"
                                    meetingUrl = ""
                                } else {
                                    location = "Company Headquarters, Dhaka"
                                    meetingUrl = ""
                                }
                            },
                            label = { Text(label, fontSize = 12.sp) }
                        )
                    }
                }

                OutlinedTextField(
                    value = scheduledAt,
                    onValueChange = { scheduledAt = it },
                    label = { Text("Scheduled Date & Time (YYYY-MM-DD HH:MM:SS) *") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                if (interviewType == "video") {
                    OutlinedTextField(
                        value = meetingUrl,
                        onValueChange = { meetingUrl = it },
                        label = { Text("Meeting URL (Google Meet / Zoom)") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )
                }

                OutlinedTextField(
                    value = location,
                    onValueChange = { location = it },
                    label = { Text("Location / Channel *") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )

                OutlinedTextField(
                    value = instructions,
                    onValueChange = { instructions = it },
                    label = { Text("Candidate Instructions & Agenda") },
                    maxLines = 3,
                    modifier = Modifier.fillMaxWidth()
                )
            }
        },
        confirmButton = {
            Button(
                onClick = {
                    onSubmit(
                        candidate.id,
                        scheduledAt,
                        interviewType,
                        meetingUrl.ifEmpty { null },
                        location.ifEmpty { null },
                        instructions.ifEmpty { null }
                    )
                },
                enabled = scheduledAt.isNotBlank() && location.isNotBlank(),
                colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
            ) {
                Text("Schedule & Send Invite")
            }
        },
        dismissButton = {
            TextButton(onClick = onDismiss) { Text("Cancel") }
        }
    )
}

@Composable
fun InterviewMeetingOptionsDialog(
    interview: InterviewItem,
    onDismiss: () -> Unit,
    onJoinBondhooCall: () -> Unit,
    onOpenInAppWeb: () -> Unit,
    onCopyLink: () -> Unit
) {
    Dialog(
        onDismissRequest = onDismiss,
        properties = DialogProperties(usePlatformDefaultWidth = false)
    ) {
        Surface(
            modifier = Modifier
                .fillMaxWidth(0.92f)
                .wrapContentHeight(),
            shape = RoundedCornerShape(24.dp),
            color = MaterialTheme.colorScheme.surface,
            tonalElevation = 6.dp
        ) {
            Column(
                modifier = Modifier
                    .padding(24.dp)
                    .verticalScroll(rememberScrollState()),
                horizontalAlignment = Alignment.CenterHorizontally
            ) {
                Box(
                    modifier = Modifier
                        .size(64.dp)
                        .clip(CircleShape)
                        .background(ConnectlyBlue.copy(alpha = 0.12f)),
                    contentAlignment = Alignment.Center
                ) {
                    Icon(
                        imageVector = Icons.Default.VideoCall,
                        contentDescription = null,
                        tint = ConnectlyBlue,
                        modifier = Modifier.size(36.dp)
                    )
                }

                Spacer(modifier = Modifier.height(14.dp))

                Text(
                    text = "Live Video Interview",
                    fontSize = 20.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.onSurface
                )

                Spacer(modifier = Modifier.height(4.dp))

                Text(
                    text = interview.jobTitle ?: "Job Interview",
                    fontSize = 15.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = ConnectlyBlue
                )

                Text(
                    text = interview.companyName ?: "BONDHOO Employer",
                    fontSize = 13.sp,
                    color = MaterialTheme.colorScheme.onSurfaceVariant
                )

                Spacer(modifier = Modifier.height(12.dp))

                Surface(
                    shape = RoundedCornerShape(12.dp),
                    color = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.5f),
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Row(
                        modifier = Modifier.padding(12.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Icon(
                            imageVector = Icons.Default.CalendarToday,
                            contentDescription = null,
                            modifier = Modifier.size(16.dp),
                            tint = ConnectlyBlue
                        )
                        Spacer(modifier = Modifier.width(8.dp))
                        Text(
                            text = interview.scheduledAtFormatted,
                            fontSize = 13.sp,
                            fontWeight = FontWeight.Medium,
                            color = MaterialTheme.colorScheme.onSurface
                        )
                    }
                }

                Spacer(modifier = Modifier.height(20.dp))

                // Option 1: Direct in-app BONDHOO Video Meeting
                Button(
                    onClick = onJoinBondhooCall,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(14.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = ConnectlyBlue)
                ) {
                    Icon(Icons.Default.Videocam, null, modifier = Modifier.size(20.dp))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Join BONDHOO Live Video Room", fontSize = 14.sp, fontWeight = FontWeight.Bold)
                }

                Spacer(modifier = Modifier.height(10.dp))

                // Option 2: In-App Browser meeting (Desktop user agent - blocks Play Store redirect)
                Button(
                    onClick = onOpenInAppWeb,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(14.dp),
                    colors = ButtonDefaults.buttonColors(containerColor = Color(0xFF0F172A))
                ) {
                    Icon(Icons.Default.OpenInBrowser, null, modifier = Modifier.size(18.dp))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Join via In-App Browser Meeting", fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                }

                Spacer(modifier = Modifier.height(10.dp))

                // Option 3: Copy Meeting Link
                OutlinedButton(
                    onClick = onCopyLink,
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(14.dp)
                ) {
                    Icon(Icons.Default.ContentCopy, null, modifier = Modifier.size(16.dp))
                    Spacer(modifier = Modifier.width(8.dp))
                    Text("Copy Meeting Link", fontSize = 13.sp)
                }

                Spacer(modifier = Modifier.height(12.dp))

                // Close / Dismiss
                TextButton(
                    onClick = onDismiss,
                    modifier = Modifier.fillMaxWidth()
                ) {
                    Text("Dismiss", color = MaterialTheme.colorScheme.outline)
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun InAppVideoMeetingWebViewDialog(
    url: String,
    onDismiss: () -> Unit
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
                    title = {
                        Text("Video Interview Room", fontSize = 16.sp, fontWeight = FontWeight.Bold)
                    },
                    navigationIcon = {
                        IconButton(onClick = onDismiss) {
                            Icon(Icons.Default.Close, contentDescription = "Close")
                        }
                    },
                    colors = TopAppBarDefaults.topAppBarColors(containerColor = MaterialTheme.colorScheme.surface)
                )

                AndroidView(
                    modifier = Modifier.fillMaxSize(),
                    factory = { ctx ->
                        WebView(ctx).apply {
                            settings.javaScriptEnabled = true
                            settings.domStorageEnabled = true
                            settings.databaseEnabled = true
                            settings.mediaPlaybackRequiresUserGesture = false
                            // Desktop User-Agent prevents Google Meet from redirecting to Google Play Store
                            settings.userAgentString = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"

                            webChromeClient = object : WebChromeClient() {
                                override fun onPermissionRequest(request: PermissionRequest) {
                                    request.grant(request.resources)
                                }
                            }

                            webViewClient = object : WebViewClient() {
                                override fun shouldOverrideUrlLoading(view: WebView?, request: WebResourceRequest?): Boolean {
                                    val reqUrl = request?.url?.toString() ?: ""
                                    // Block automatic Play Store redirect
                                    if (reqUrl.startsWith("market://") || reqUrl.contains("play.google.com/store")) {
                                        return true
                                    }
                                    return false
                                }
                            }

                            loadUrl(url)
                        }
                    }
                )
            }
        }
    }
}
