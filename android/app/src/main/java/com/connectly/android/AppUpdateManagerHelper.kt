package com.connectly.android

import android.util.Log
import androidx.activity.result.ActivityResultLauncher
import androidx.activity.result.IntentSenderRequest
import androidx.activity.result.contract.ActivityResultContracts
import androidx.fragment.app.FragmentActivity
import com.google.android.play.core.appupdate.AppUpdateInfo
import com.google.android.play.core.appupdate.AppUpdateManager
import com.google.android.play.core.appupdate.AppUpdateManagerFactory
import com.google.android.play.core.appupdate.AppUpdateOptions
import com.google.android.play.core.install.InstallStateUpdatedListener
import com.google.android.play.core.install.model.AppUpdateType
import com.google.android.play.core.install.model.InstallStatus
import com.google.android.play.core.install.model.UpdateAvailability
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

/**
 * Manages Google Play In-App Updates for Bondhoo.
 * - Uses IMMEDIATE flow for high-priority (priority >= 4) or stale (>= 7 days) updates.
 * - Uses FLEXIBLE flow for standard updates, exposing [isUpdateDownloaded] so the UI can
 *   prompt the user to restart and apply the update at their convenience.
 * - Fails silently on non-Play-Store installs (e.g., direct bondhoo.apk sideloads).
 */
class AppUpdateManagerHelper(
    private val activity: FragmentActivity
) {
    private val appUpdateManager: AppUpdateManager = AppUpdateManagerFactory.create(activity)

    private val _isUpdateDownloaded = MutableStateFlow(false)
    val isUpdateDownloaded: StateFlow<Boolean> = _isUpdateDownloaded.asStateFlow()

    private var activeUpdateType: Int = AppUpdateType.FLEXIBLE

    private val updateLauncher: ActivityResultLauncher<IntentSenderRequest> =
        activity.registerForActivityResult(ActivityResultContracts.StartIntentSenderForResult()) { result ->
            if (result.resultCode != FragmentActivity.RESULT_OK) {
                Log.d(TAG, "In-app update flow cancelled or failed (resultCode=${result.resultCode})")
            }
        }

    private val installStateListener = InstallStateUpdatedListener { state ->
        if (state.installStatus() == InstallStatus.DOWNLOADED) {
            _isUpdateDownloaded.value = true
        }
    }

    fun registerAndCheckForUpdate() {
        appUpdateManager.registerListener(installStateListener)
        appUpdateManager.appUpdateInfo
            .addOnSuccessListener { info ->
                handleAppUpdateInfo(info)
            }
            .addOnFailureListener { err ->
                // Expected on sideloaded APKs or emulators without Play Store
                Log.d(TAG, "Play In-App Update check skipped: ${err.message}")
            }
    }

    fun resumePendingUpdateIfNeeded() {
        appUpdateManager.appUpdateInfo
            .addOnSuccessListener { info ->
                if (info.installStatus() == InstallStatus.DOWNLOADED) {
                    _isUpdateDownloaded.value = true
                } else if (
                    activeUpdateType == AppUpdateType.IMMEDIATE &&
                    info.updateAvailability() == UpdateAvailability.DEVELOPER_TRIGGERED_UPDATE_IN_PROGRESS
                ) {
                    startUpdateFlow(info, AppUpdateType.IMMEDIATE)
                }
            }
            .addOnFailureListener { /* ignore */ }
    }

    fun completeFlexibleUpdate() {
        appUpdateManager.completeUpdate()
    }

    fun unregister() {
        appUpdateManager.unregisterListener(installStateListener)
    }

    private fun handleAppUpdateInfo(info: AppUpdateInfo) {
        if (info.installStatus() == InstallStatus.DOWNLOADED) {
            _isUpdateDownloaded.value = true
            return
        }

        if (info.updateAvailability() != UpdateAvailability.UPDATE_AVAILABLE) {
            return
        }

        val stalenessDays = info.clientVersionStalenessDays() ?: 0
        val requiresImmediate = info.updatePriority() >= IMMEDIATE_PRIORITY_THRESHOLD ||
            stalenessDays >= IMMEDIATE_STALENESS_DAYS

        val chosenType = when {
            requiresImmediate && info.isUpdateTypeAllowed(AppUpdateType.IMMEDIATE) -> AppUpdateType.IMMEDIATE
            info.isUpdateTypeAllowed(AppUpdateType.FLEXIBLE) -> AppUpdateType.FLEXIBLE
            info.isUpdateTypeAllowed(AppUpdateType.IMMEDIATE) -> AppUpdateType.IMMEDIATE
            else -> return
        }

        activeUpdateType = chosenType
        startUpdateFlow(info, chosenType)
    }

    private fun startUpdateFlow(info: AppUpdateInfo, updateType: Int) {
        try {
            appUpdateManager.startUpdateFlowForResult(
                info,
                updateLauncher,
                AppUpdateOptions.newBuilder(updateType).build()
            )
        } catch (e: Exception) {
            Log.w(TAG, "Unable to start Play In-App Update flow: ${e.message}")
        }
    }

    companion object {
        private const val TAG = "AppUpdateManagerHelper"
        private const val IMMEDIATE_PRIORITY_THRESHOLD = 4
        private const val IMMEDIATE_STALENESS_DAYS = 7
    }
}
