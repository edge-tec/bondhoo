package com.connectly.android.feature.jobs.presentation.jobs

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.connectly.android.core.designsystem.theme.ConnectlyBlue
import com.connectly.android.core.model.*

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun JobsHomeScreen(
    onNavigateBack: () -> Unit = {},
    modifier: Modifier = Modifier,
    viewModel: JobsViewModel = hiltViewModel()
) {
    val state by viewModel.uiState.collectAsState()
    val snackbarHostState = remember { SnackbarHostState() }

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
                    if (state.currentTab == JobsTab.EMPLOYER) {
                        IconButton(onClick = { viewModel.onEvent(JobsUiEvent.CreateJob(emptyMap())) }) {
                            Icon(imageVector = Icons.Default.AddBusiness, contentDescription = "Post Job")
                        }
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
            // Tab Selector
            PrimaryTabRow(
                selectedTabIndex = state.currentTab.ordinal,
                containerColor = MaterialTheme.colorScheme.surface
            ) {
                Tab(
                    selected = state.currentTab == JobsTab.FIND_JOBS,
                    onClick = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.FIND_JOBS)) },
                    text = { Text("Jobs") },
                    icon = { Icon(Icons.Default.Work, contentDescription = null, modifier = Modifier.size(18.dp)) }
                )
                Tab(
                    selected = state.currentTab == JobsTab.APPLICATIONS,
                    onClick = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.APPLICATIONS)) },
                    text = { Text("Applied") },
                    icon = { Icon(Icons.Default.Assignment, contentDescription = null, modifier = Modifier.size(18.dp)) }
                )
                Tab(
                    selected = state.currentTab == JobsTab.SAVED,
                    onClick = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.SAVED)) },
                    text = { Text("Saved") },
                    icon = { Icon(Icons.Default.Bookmark, contentDescription = null, modifier = Modifier.size(18.dp)) }
                )
                Tab(
                    selected = state.currentTab == JobsTab.INTERVIEWS,
                    onClick = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.INTERVIEWS)) },
                    text = { Text("Interviews") },
                    icon = { Icon(Icons.Default.Videocam, contentDescription = null, modifier = Modifier.size(18.dp)) }
                )
                Tab(
                    selected = state.currentTab == JobsTab.EMPLOYER,
                    onClick = { viewModel.onEvent(JobsUiEvent.SetTab(JobsTab.EMPLOYER)) },
                    text = { Text("Employer") },
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
                    onWithdraw = { viewModel.onEvent(JobsUiEvent.WithdrawApplication(it)) }
                )
                JobsTab.SAVED -> SavedJobsContent(
                    jobs = state.savedJobs,
                    onUnsave = { viewModel.onEvent(JobsUiEvent.ToggleSaveJob(it)) },
                    onApply = { viewModel.onEvent(JobsUiEvent.OpenApplyDialog(it)) },
                    onSelectJob = { viewModel.onEvent(JobsUiEvent.SelectJob(it)) }
                )
                JobsTab.INTERVIEWS -> InterviewsContent(
                    interviews = state.interviews,
                    onRespond = { id, accept -> viewModel.onEvent(JobsUiEvent.RespondInterview(id, accept)) }
                )
                JobsTab.EMPLOYER -> EmployerContent(
                    state = state,
                    onSelectCompany = { viewModel.onEvent(JobsUiEvent.SelectCompany(it)) },
                    onMoveStage = { appId, stage -> viewModel.onEvent(JobsUiEvent.MoveCandidateStage(appId, stage)) },
                    onCreateJob = { viewModel.onEvent(JobsUiEvent.CreateJob(it)) }
                )
            }
        }
    }

    // Apply Dialog
    if (state.showApplyDialog && state.selectedJobDetails != null) {
        ApplyJobDialog(
            job = state.selectedJobDetails!!,
            resumes = state.candidateResumes,
            defaultProfile = state.candidateProfile,
            onDismiss = { viewModel.onEvent(JobsUiEvent.CloseApplyDialog) },
            onSubmit = { name, email, phone, resumeId, coverLetter, salary ->
                viewModel.onEvent(
                    JobsUiEvent.SubmitApplication(
                        jobId = state.selectedJobDetails!!.id,
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
            onDismiss = { viewModel.onEvent(JobsUiEvent.SelectJob(state.selectedJobDetails!!.copy(id = -1))) },
            onApply = { viewModel.onEvent(JobsUiEvent.OpenApplyDialog(state.selectedJobDetails!!)) },
            onToggleSave = { viewModel.onEvent(JobsUiEvent.ToggleSaveJob(state.selectedJobDetails!!)) }
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
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
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
    onWithdraw: (Long) -> Unit
) {
    if (applications.isEmpty()) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text("No applications submitted yet.", color = MaterialTheme.colorScheme.outline)
        }
    } else {
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            items(applications) { app ->
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp)
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
                        Text(
                            text = "Applied on: ${app.appliedAtFormatted ?: "Recent"}",
                            fontSize = 12.sp,
                            color = MaterialTheme.colorScheme.outline
                        )
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
    onSelectJob: (Job) -> Unit
) {
    if (jobs.isEmpty()) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text("No saved jobs.", color = MaterialTheme.colorScheme.outline)
        }
    } else {
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
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
    onRespond: (Long, Boolean) -> Unit
) {
    if (interviews.isEmpty()) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text("No upcoming interviews scheduled.", color = MaterialTheme.colorScheme.outline)
        }
    } else {
        LazyColumn(
            modifier = Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            items(interviews) { item ->
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(16.dp)
                ) {
                    Column(modifier = Modifier.padding(16.dp)) {
                        Text(
                            text = "Interview for ${item.jobTitle ?: "Position"}",
                            fontWeight = FontWeight.Bold,
                            fontSize = 16.sp
                        )
                        Text(
                            text = item.companyName ?: "",
                            fontSize = 13.sp,
                            color = MaterialTheme.colorScheme.onSurfaceVariant
                        )
                        Spacer(modifier = Modifier.height(8.dp))
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Icon(Icons.Default.Event, null, modifier = Modifier.size(16.dp), tint = ConnectlyBlue)
                            Spacer(modifier = Modifier.width(6.dp))
                            Text(item.scheduledAtFormatted, fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                        }
                        if (!item.meetingUrl.isNullOrEmpty()) {
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                text = "Link: ${item.meetingUrl}",
                                fontSize = 12.sp,
                                color = ConnectlyBlue
                            )
                        }
                        Spacer(modifier = Modifier.height(12.dp))
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            Button(
                                onClick = { onRespond(item.id, true) },
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Text("Accept")
                            }
                            OutlinedButton(
                                onClick = { onRespond(item.id, false) },
                                shape = RoundedCornerShape(8.dp)
                            ) {
                                Text("Decline")
                            }
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
    onCreateJob: (Map<String, Any?>) -> Unit
) {
    val scrollState = rememberScrollState()

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(scrollState)
            .padding(16.dp)
    ) {
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
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Text(col.title, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                            Text("${col.count} Candidates", fontSize = 12.sp, color = MaterialTheme.colorScheme.outline)
                        }
                        Spacer(modifier = Modifier.height(6.dp))
                        col.candidates.forEach { cand ->
                            Surface(
                                modifier = Modifier
                                    .fillMaxWidth()
                                    .padding(vertical = 3.dp),
                                shape = RoundedCornerShape(8.dp),
                                color = MaterialTheme.colorScheme.surface
                            ) {
                                Row(
                                    modifier = Modifier.padding(8.dp),
                                    horizontalArrangement = Arrangement.SpaceBetween,
                                    verticalAlignment = Alignment.CenterVertically
                                ) {
                                    Column {
                                        Text(cand.candidateName, fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                                        Text(cand.candidateEmail, fontSize = 11.sp, color = MaterialTheme.colorScheme.outline)
                                    }
                                    Button(
                                        onClick = { onMoveStage(cand.id, "shortlisted") },
                                        contentPadding = PaddingValues(horizontal = 8.dp, vertical = 2.dp)
                                    ) {
                                        Text("Next Stage", fontSize = 11.sp)
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
    onDismiss: () -> Unit,
    onSubmit: (String, String, String, Long?, String?, Int?) -> Unit
) {
    var fullName by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var phone by remember { mutableStateOf("") }
    var coverLetter by remember { mutableStateOf("") }
    var expectedSalary by remember { mutableStateOf(job.salaryMin?.toString() ?: "") }
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
                OutlinedTextField(
                    value = fullName,
                    onValueChange = { fullName = it },
                    label = { Text("Full Name *") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = email,
                    onValueChange = { email = it },
                    label = { Text("Email *") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = phone,
                    onValueChange = { phone = it },
                    label = { Text("Phone Number *") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = expectedSalary,
                    onValueChange = { expectedSalary = it },
                    label = { Text("Expected Salary (BDT)") },
                    singleLine = true,
                    modifier = Modifier.fillMaxWidth()
                )
                OutlinedTextField(
                    value = coverLetter,
                    onValueChange = { coverLetter = it },
                    label = { Text("Cover Letter") },
                    maxLines = 4,
                    modifier = Modifier.fillMaxWidth()
                )
                if (resumes.isNotEmpty()) {
                    Text("Select Resume:", fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                    resumes.forEach { r ->
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            RadioButton(
                                selected = selectedResumeId == r.id,
                                onClick = { selectedResumeId = r.id }
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
                        fullName,
                        email,
                        phone,
                        selectedResumeId,
                        coverLetter.ifEmpty { null },
                        expectedSalary.toIntOrNull()
                    )
                },
                enabled = fullName.isNotBlank() && email.isNotBlank() && phone.isNotBlank()
            ) {
                Text("Submit Application")
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
