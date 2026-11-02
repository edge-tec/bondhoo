package com.connectly.android.feature.profile.presentation.storage

import android.content.Context
import android.content.SharedPreferences
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.connectly.android.core.network.api.ConnectlyApi
import dagger.hilt.android.lifecycle.HiltViewModel
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import java.io.File
import java.text.DecimalFormat
import javax.inject.Inject

@HiltViewModel
class DataStorageViewModel @Inject constructor(
    private val api: ConnectlyApi,
    @ApplicationContext private val context: Context
) : ViewModel() {

    private val prefs: SharedPreferences by lazy {
        context.getSharedPreferences("connectly_storage_prefs", Context.MODE_PRIVATE)
    }

    private val _uiState = MutableStateFlow(DataStorageState())
    val uiState: StateFlow<DataStorageState> = _uiState.asStateFlow()

    init {
        loadPreferences()
        refreshStorageUsage()
    }

    fun handleIntent(intent: DataStorageIntent) {
        when (intent) {
            DataStorageIntent.RefreshStorageUsage -> refreshStorageUsage()
            DataStorageIntent.ClearCache -> clearCache()
            is DataStorageIntent.DataSaverToggled -> updateDataSaver(intent.enabled)
            is DataStorageIntent.AutoDownloadPhotosChanged -> updateAutoDownloadPhotos(intent.mode)
            is DataStorageIntent.AutoDownloadVideosChanged -> updateAutoDownloadVideos(intent.mode)
            is DataStorageIntent.AutoDownloadDocumentsChanged -> updateAutoDownloadDocuments(intent.mode)
            is DataStorageIntent.UploadQualityChanged -> updateUploadQuality(intent.quality)
            DataStorageIntent.RequestDataExport -> requestExport()
            DataStorageIntent.DismissFeedback -> _uiState.update { it.copy(successMessage = null, errorMessage = null) }
        }
    }

    private fun loadPreferences() {
        val dataSaver = prefs.getBoolean("data_saver", false)
        val photos = prefs.getString("auto_download_photos", "cellular_wifi") ?: "cellular_wifi"
        val videos = prefs.getString("auto_download_videos", "wifi_only") ?: "wifi_only"
        val docs = prefs.getString("auto_download_docs", "wifi_only") ?: "wifi_only"
        val quality = prefs.getString("upload_quality", "standard") ?: "standard"

        _uiState.update {
            it.copy(
                dataSaverEnabled = dataSaver,
                autoDownloadPhotos = photos,
                autoDownloadVideos = videos,
                autoDownloadDocuments = docs,
                uploadQuality = quality
            )
        }
    }

    fun refreshStorageUsage() {
        viewModelScope.launch {
            val totalBytes = withContext(Dispatchers.IO) {
                calculateCacheBytes()
            }
            _uiState.update {
                it.copy(
                    cacheSizeBytes = totalBytes,
                    cacheSizeFormatted = formatBytes(totalBytes)
                )
            }
        }
    }

    private fun clearCache() {
        _uiState.update { it.copy(isClearingCache = true) }
        viewModelScope.launch {
            withContext(Dispatchers.IO) {
                try {
                    deleteDirContents(context.cacheDir)
                    context.externalCacheDir?.let { deleteDirContents(it) }
                } catch (_: Exception) {
                }
            }
            val newBytes = withContext(Dispatchers.IO) {
                calculateCacheBytes()
            }
            _uiState.update {
                it.copy(
                    isClearingCache = false,
                    cacheSizeBytes = newBytes,
                    cacheSizeFormatted = formatBytes(newBytes),
                    successMessage = "Temporary cache cleared safely without affecting your account."
                )
            }
        }
    }

    private fun updateDataSaver(enabled: Boolean) {
        _uiState.update { it.copy(dataSaverEnabled = enabled) }
        prefs.edit().putBoolean("data_saver", enabled).apply()
    }

    private fun updateAutoDownloadPhotos(mode: String) {
        _uiState.update { it.copy(autoDownloadPhotos = mode) }
        prefs.edit().putString("auto_download_photos", mode).apply()
    }

    private fun updateAutoDownloadVideos(mode: String) {
        _uiState.update { it.copy(autoDownloadVideos = mode) }
        prefs.edit().putString("auto_download_videos", mode).apply()
    }

    private fun updateAutoDownloadDocuments(mode: String) {
        _uiState.update { it.copy(autoDownloadDocuments = mode) }
        prefs.edit().putString("auto_download_docs", mode).apply()
    }

    private fun updateUploadQuality(quality: String) {
        _uiState.update { it.copy(uploadQuality = quality) }
        prefs.edit().putString("upload_quality", quality).apply()
    }

    private fun requestExport() {
        _uiState.update { it.copy(isExportingData = true) }
        viewModelScope.launch {
            try {
                val response = api.exportUserData()
                if (response.isSuccessful) {
                    _uiState.update {
                        it.copy(
                            isExportingData = false,
                            successMessage = "Personal data export archive generated successfully."
                        )
                    }
                } else {
                    _uiState.update {
                        it.copy(
                            isExportingData = false,
                            errorMessage = "Export request could not be processed at this time."
                        )
                    }
                }
            } catch (e: Exception) {
                _uiState.update {
                    it.copy(
                        isExportingData = false,
                        errorMessage = e.localizedMessage ?: "Network error during export request."
                    )
                }
            }
        }
    }

    private fun calculateCacheBytes(): Long {
        var size = calculateDirSize(context.cacheDir)
        context.externalCacheDir?.let { size += calculateDirSize(it) }
        return size
    }

    private fun calculateDirSize(dir: File?): Long {
        if (dir == null || !dir.exists()) return 0L
        var bytes = 0L
        dir.listFiles()?.forEach { file ->
            bytes += if (file.isDirectory) calculateDirSize(file) else file.length()
        }
        return bytes
    }

    private fun deleteDirContents(dir: File?): Boolean {
        if (dir == null || !dir.exists()) return false
        var success = true
        dir.listFiles()?.forEach { file ->
            if (file.isDirectory) {
                deleteDirContents(file)
            }
            success = success && file.delete()
        }
        return success
    }

    private fun formatBytes(bytes: Long): String {
        if (bytes <= 0) return "0 B"
        val units = arrayOf("B", "KB", "MB", "GB")
        val digitGroups = (Math.log10(bytes.toDouble()) / Math.log10(1024.0)).toInt()
        return DecimalFormat("#,##0.#").format(bytes / Math.pow(1024.0, digitGroups.toDouble())) + " " + units[digitGroups]
    }
}
