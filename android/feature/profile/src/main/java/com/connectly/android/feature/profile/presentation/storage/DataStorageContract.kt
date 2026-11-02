package com.connectly.android.feature.profile.presentation.storage

data class DataStorageState(
    val cacheSizeBytes: Long = 0L,
    val cacheSizeFormatted: String = "0 B",
    val dataSaverEnabled: Boolean = false,
    val autoDownloadPhotos: String = "cellular_wifi", // "cellular_wifi", "wifi_only", "never"
    val autoDownloadVideos: String = "wifi_only", // "wifi_only", "never"
    val autoDownloadDocuments: String = "wifi_only", // "wifi_only", "never"
    val uploadQuality: String = "standard", // "high", "standard", "compressed"
    val isClearingCache: Boolean = false,
    val isExportingData: Boolean = false,
    val successMessage: String? = null,
    val errorMessage: String? = null
)

sealed interface DataStorageIntent {
    object RefreshStorageUsage : DataStorageIntent
    object ClearCache : DataStorageIntent
    data class DataSaverToggled(val enabled: Boolean) : DataStorageIntent
    data class AutoDownloadPhotosChanged(val mode: String) : DataStorageIntent
    data class AutoDownloadVideosChanged(val mode: String) : DataStorageIntent
    data class AutoDownloadDocumentsChanged(val mode: String) : DataStorageIntent
    data class UploadQualityChanged(val quality: String) : DataStorageIntent
    object RequestDataExport : DataStorageIntent
    object DismissFeedback : DataStorageIntent
}

