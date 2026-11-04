package com.connectly.android.feature.parental.model

data class Family(
    val id: Long,
    val name: String,
    val ownerUserId: Long,
    val timezone: String,
    val status: String,
    val members: List<FamilyMember> = emptyList()
)

data class FamilyMember(
    val id: Long,
    val familyId: Long,
    val userId: Long?,
    val name: String,
    val role: String, // parent, guardian, child
    val membershipStatus: String,
    val devices: List<ChildDevice> = emptyList()
)

data class ChildDevice(
    val id: Long,
    val familyId: Long,
    val childMemberId: Long,
    val deviceUuid: String,
    val deviceName: String,
    val platform: String,
    val deviceStatus: String, // active, revoked
    val lastSeenAt: String?
)

data class PairingCodeResponse(
    val success: Boolean,
    val message: String,
    val pairingCode: String,
    val expiresAt: String
)

data class ScreenTimeSummary(
    val childId: Long,
    val date: String,
    val totalSeconds: Long,
    val totalMinutes: Long,
    val apps: List<AppUsageStat>
)

data class AppUsageStat(
    val package: String,
    val durationSeconds: Long,
    val durationMinutes: Long
)

data class DevicePolicy(
    val policyVersion: Int,
    val dailyScreenTimeLimitMinutes: Int?,
    val bedtimeCurfew: BedtimeCurfew?,
    val blacklistedPackages: List<String>,
    val whitelistedPackages: List<String>
)

data class BedtimeCurfew(
    val start: String,
    val end: String
)

data class RemoteCommand(
    val commandUuid: String,
    val commandType: String,
    val payload: Map<String, Any>?,
    val issuedAt: String
)

