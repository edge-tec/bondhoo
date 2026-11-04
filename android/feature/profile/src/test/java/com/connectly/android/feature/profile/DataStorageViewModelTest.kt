package com.connectly.android.feature.profile

import android.content.Context
import android.content.SharedPreferences
import com.connectly.android.core.network.api.ApiResponse
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.feature.profile.presentation.storage.DataStorageIntent
import com.connectly.android.feature.profile.presentation.storage.DataStorageViewModel
import io.mockk.*
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import org.junit.After
import org.junit.Assert.*
import org.junit.Before
import org.junit.Test
import retrofit2.Response
import java.io.File

@OptIn(ExperimentalCoroutinesApi::class)
class DataStorageViewModelTest {

    private val testDispatcher = StandardTestDispatcher()
    private val api: ConnectlyApi = mockk(relaxed = true)
    private val context: Context = mockk(relaxed = true)
    private val sharedPrefs: SharedPreferences = mockk(relaxed = true)
    private val prefsEditor: SharedPreferences.Editor = mockk(relaxed = true)
    private val cacheDir: File = mockk(relaxed = true)
    private lateinit var viewModel: DataStorageViewModel

    @Before
    fun setup() {
        Dispatchers.setMain(testDispatcher)
        every { context.getSharedPreferences(any(), any()) } returns sharedPrefs
        every { sharedPrefs.edit() } returns prefsEditor
        every { prefsEditor.putBoolean(any(), any()) } returns prefsEditor
        every { prefsEditor.putString(any(), any()) } returns prefsEditor

        every { sharedPrefs.getBoolean("data_saver", any()) } returns false
        every { sharedPrefs.getString("auto_download_photos", any()) } returns "cellular_wifi"
        every { sharedPrefs.getString("auto_download_videos", any()) } returns "wifi_only"
        every { sharedPrefs.getString("auto_download_docs", any()) } returns "wifi_only"
        every { sharedPrefs.getString("upload_quality", any()) } returns "standard"

        every { context.cacheDir } returns cacheDir
        every { context.externalCacheDir } returns null
        every { cacheDir.exists() } returns true
        every { cacheDir.listFiles() } returns emptyArray()

        coEvery { api.exportUserData() } returns Response.success(ApiResponse(data = emptyMap()))

        viewModel = DataStorageViewModel(api, context).apply {
            ioDispatcher = testDispatcher
        }
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun testInitialPreferencesLoad() = runTest {
        testDispatcher.scheduler.advanceUntilIdle()
        val state = viewModel.uiState.value

        assertFalse(state.dataSaverEnabled)
        assertEquals("cellular_wifi", state.autoDownloadPhotos)
        assertEquals("wifi_only", state.autoDownloadVideos)
        assertEquals("wifi_only", state.autoDownloadDocuments)
        assertEquals("standard", state.uploadQuality)
    }

    @Test
    fun testDataSaverAndQualityUpdates() = runTest {
        viewModel.handleIntent(DataStorageIntent.DataSaverToggled(true))
        assertTrue(viewModel.uiState.value.dataSaverEnabled)
        verify { prefsEditor.putBoolean("data_saver", true) }

        viewModel.handleIntent(DataStorageIntent.UploadQualityChanged("high"))
        assertEquals("high", viewModel.uiState.value.uploadQuality)
        verify { prefsEditor.putString("upload_quality", "high") }
    }

    @Test
    fun testAutoDownloadUpdates() = runTest {
        viewModel.handleIntent(DataStorageIntent.AutoDownloadPhotosChanged("never"))
        assertEquals("never", viewModel.uiState.value.autoDownloadPhotos)
        verify { prefsEditor.putString("auto_download_photos", "never") }

        viewModel.handleIntent(DataStorageIntent.AutoDownloadVideosChanged("never"))
        assertEquals("never", viewModel.uiState.value.autoDownloadVideos)
        verify { prefsEditor.putString("auto_download_videos", "never") }

        viewModel.handleIntent(DataStorageIntent.AutoDownloadDocumentsChanged("wifi_only"))
        assertEquals("wifi_only", viewModel.uiState.value.autoDownloadDocuments)
        verify { prefsEditor.putString("auto_download_docs", "wifi_only") }
    }

    @Test
    fun testClearCache() = runTest {
        viewModel.handleIntent(DataStorageIntent.ClearCache)
        testDispatcher.scheduler.advanceUntilIdle()

        assertFalse(viewModel.uiState.value.isClearingCache)
        assertEquals("Temporary cache cleared safely without affecting your account.", viewModel.uiState.value.successMessage)
    }

    @Test
    fun testPersonalDataExport() = runTest {
        viewModel.handleIntent(DataStorageIntent.RequestDataExport)
        testDispatcher.scheduler.advanceUntilIdle()

        coVerify { api.exportUserData() }
        assertFalse(viewModel.uiState.value.isExportingData)
        assertEquals("Personal data export archive generated successfully.", viewModel.uiState.value.successMessage)
    }
}

