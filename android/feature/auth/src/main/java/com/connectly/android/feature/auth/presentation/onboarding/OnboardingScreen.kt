package com.connectly.android.feature.auth.presentation.onboarding

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.pager.HorizontalPager
import androidx.compose.foundation.pager.rememberPagerState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.connectly.android.core.designsystem.component.ConnectlyPrimaryButton
import com.connectly.android.core.designsystem.theme.*
import kotlinx.coroutines.launch

data class OnboardingItem(
    val title: String,
    val subtitle: String,
    val icon: ImageVector,
    val iconColor: Color,
    val iconBackground: Color
)

@Composable
fun OnboardingScreen(
    onNavigateToRegister: () -> Unit,
    onNavigateToLogin: () -> Unit,
    modifier: Modifier = Modifier
) {
    val items = listOf(
        OnboardingItem(
            title = "Real-Time Experience",
            subtitle = "Connect instantly with ultra low-latency messaging, HD voice, video calls, and interactive live streams.",
            icon = Icons.Default.Bolt,
            iconColor = ConnectlyPurple,
            iconBackground = ConnectlyPurple.copy(alpha = 0.12f)
        ),
        OnboardingItem(
            title = "Your Privacy Our Priority",
            subtitle = "Full control over your audience, hardware-backed token security, and enterprise-grade privacy protection.",
            icon = Icons.Default.Shield,
            iconColor = ConnectlyGreen,
            iconBackground = ConnectlyGreen.copy(alpha = 0.12f)
        ),
        OnboardingItem(
            title = "Build Your Community",
            subtitle = "Join vibrant groups, follow dynamic business pages, and organize impactful real-world and virtual events.",
            icon = Icons.Default.Groups,
            iconColor = ConnectlyElectricBlue,
            iconBackground = ConnectlyElectricBlue.copy(alpha = 0.12f)
        ),
        OnboardingItem(
            title = "Share Your Moments",
            subtitle = "Express yourself through high-res photos, rich formatted stories, trending vertical reels, and custom reactions.",
            icon = Icons.Default.Favorite,
            iconColor = ConnectlyRose,
            iconBackground = ConnectlyRose.copy(alpha = 0.12f)
        ),
        OnboardingItem(
            title = "Discover More",
            subtitle = "Explore suggested friends, trending hashtags, verified local creators, and popular public communities.",
            icon = Icons.Default.Star,
            iconColor = ConnectlyAmber,
            iconBackground = ConnectlyAmber.copy(alpha = 0.12f)
        )
    )

    val pagerState = rememberPagerState(pageCount = { items.size })
    val coroutineScope = rememberCoroutineScope()

    Scaffold(
        containerColor = SurfaceLight,
        topBar = {
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .statusBarsPadding()
                    .padding(horizontal = 20.dp, vertical = 12.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                // Connectly Brand
                Row(
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    Box(
                        modifier = Modifier
                            .size(32.dp)
                            .clip(CircleShape)
                            .background(ConnectlyPrimaryGradient),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = Icons.Default.People,
                            contentDescription = null,
                            tint = Color.White,
                            modifier = Modifier.size(18.dp)
                        )
                    }
                    Text(
                        text = "Connectly",
                        fontWeight = FontWeight.Bold,
                        fontSize = 18.sp,
                        color = ConnectlyElectricBlue
                    )
                }

                Text(
                    text = "Skip",
                    color = TextSecondaryLight,
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Medium,
                    modifier = Modifier.clickable { onNavigateToLogin() }
                )
            }
        },
        modifier = modifier
    ) { paddingValues ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
                .padding(horizontal = 24.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(modifier = Modifier.height(20.dp))

            // Subtitle banner from design reference
            Text(
                text = "A Next-Generation Social Networking Platform",
                fontSize = 13.sp,
                fontWeight = FontWeight.SemiBold,
                color = ConnectlyIndigo,
                letterSpacing = 0.3.sp,
                textAlign = TextAlign.Center
            )

            Spacer(modifier = Modifier.height(6.dp))

            Text(
                text = "More Than a Social App — A Community for Everyone",
                fontSize = 14.sp,
                color = TextSecondaryLight,
                textAlign = TextAlign.Center
            )

            Spacer(modifier = Modifier.height(24.dp))

            // Horizontal Pager
            HorizontalPager(
                state = pagerState,
                modifier = Modifier
                    .weight(1f)
                    .fillMaxWidth()
            ) { page ->
                val item = items[page]
                Column(
                    modifier = Modifier
                        .fillMaxSize()
                        .padding(16.dp),
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.Center
                ) {
                    // Feature Icon
                    Box(
                        modifier = Modifier
                            .size(120.dp)
                            .clip(CircleShape)
                            .background(item.iconBackground),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(
                            imageVector = item.icon,
                            contentDescription = item.title,
                            tint = item.iconColor,
                            modifier = Modifier.size(60.dp)
                        )
                    }

                    Spacer(modifier = Modifier.height(32.dp))

                    Text(
                        text = item.title,
                        style = MaterialTheme.typography.headlineMedium,
                        fontWeight = FontWeight.Bold,
                        color = TextPrimaryLight,
                        textAlign = TextAlign.Center
                    )

                    Spacer(modifier = Modifier.height(12.dp))

                    Text(
                        text = item.subtitle,
                        style = MaterialTheme.typography.bodyLarge,
                        color = TextSecondaryLight,
                        textAlign = TextAlign.Center,
                        lineHeight = 22.sp
                    )
                }
            }

            // Pager Indicators
            Row(
                horizontalArrangement = Arrangement.Center,
                verticalAlignment = Alignment.CenterVertically,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 16.dp)
            ) {
                repeat(items.size) { index ->
                    val isSelected = pagerState.currentPage == index
                    Box(
                        modifier = Modifier
                            .padding(horizontal = 4.dp)
                            .height(8.dp)
                            .width(if (isSelected) 24.dp else 8.dp)
                            .clip(CircleShape)
                            .background(if (isSelected) ConnectlyElectricBlue else BorderLight)
                    )
                }
            }

            Spacer(modifier = Modifier.height(12.dp))

            // CTA Button: "Join Today ->" matching design reference bottom bar
            ConnectlyPrimaryButton(
                text = if (pagerState.currentPage == items.size - 1) "Join Today" else "Next",
                trailingIcon = Icons.AutoMirrored.Filled.ArrowForward,
                onClick = {
                    if (pagerState.currentPage < items.size - 1) {
                        coroutineScope.launch {
                            pagerState.animateScrollToPage(pagerState.currentPage + 1)
                        }
                    } else {
                        onNavigateToRegister()
                    }
                }
            )

            Spacer(modifier = Modifier.height(12.dp))

            // Existing account login
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 24.dp),
                horizontalArrangement = Arrangement.Center,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = "Already have an account? ",
                    color = TextSecondaryLight,
                    fontSize = 14.sp
                )
                Text(
                    text = "Log In",
                    color = ConnectlyElectricBlue,
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.clickable { onNavigateToLogin() }
                )
            }
        }
    }
}
