package com.connectly.android.feature.notifications

import android.content.Context
import android.content.SharedPreferences
import com.connectly.android.core.network.api.ApiResponse
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.core.network.api.DeviceSessionDto
import com.connectly.android.core.network.api.RegisterDeviceRequest
import com.connectly.android.feature.notifications.data.fcm.DeviceTokenManager
import io.mockk.coEvery
import io.mockk.coVerify
import io.mockk.every
import io.mockk.mockk
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.runTest
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Before
import org.junit.Test
import retrofit2.Response

@OptIn(ExperimentalCoroutinesApi::class)
class DeviceTokenManagerTest {

    private val testDispatcher = StandardTestDispatcher()
    private val context: Context = mockk(relaxed = true)
    private val prefs: SharedPreferences = mockk(relaxed = true)
    private val editor: SharedPreferences.Editor = mockk(relaxed = true)
    private val api: ConnectlyApi = mockk(relaxed = true)

    private lateinit var deviceTokenManager: DeviceTokenManager

    @Before
    fun setUp() {
        every { context.getSharedPreferences(any(), any()) } returns prefs
        every { prefs.edit() } returns editor
        every { editor.putString(any(), any()) } returns editor
        every { editor.putLong(any(), any()) } returns editor
        every { editor.remove(any()) } returns editor
        every { editor.apply() } returns Unit

        every { prefs.getString("device_id", null) } returns "test_device_uuid_123"
        every { prefs.getString("fcm_push_token", null) } returns "old_fcm_token"
        every { prefs.getLong("backend_session_id", -1L) } returns 42L

        deviceTokenManager = DeviceTokenManager(context, api, testDispatcher)
    }

    @Test
    fun `deviceId returns persisted device id from preferences`() {
        val deviceId = deviceTokenManager.deviceId
        assertEquals("test_device_uuid_123", deviceId)
    }

    @Test
    fun `onNewToken persists token and triggers API registration`() = runTest(testDispatcher) {
        val newToken = "fcm_token_rotated_xyz_987"
        val mockSession = DeviceSessionDto(
            id = 42L,
            device_id = "test_device_uuid_123",
            platform = "android",
            push_token = newToken
        )
        coEvery { api.registerDevice(any()) } returns Response.success(
            ApiResponse(data = mockSession, message = "Registered")
        )

        deviceTokenManager.onNewToken(newToken)
        testScheduler.advanceUntilIdle()

        coVerify {
            editor.putString("fcm_push_token", newToken)
        }
    }

    @Test
    fun `syncTokenWithBackend invokes registerDevice with correct payload`() = runTest(testDispatcher) {
        val testToken = "sample_test_token"
        coEvery { api.registerDevice(any()) } returns Response.success(
            ApiResponse(
                data = DeviceSessionDto(
                    id = 101L,
                    device_id = "test_device_uuid_123",
                    platform = "android",
                    push_token = testToken
                ),
                message = "Device registered"
            )
        )

        deviceTokenManager.syncTokenWithBackend(testToken)
        testScheduler.advanceUntilIdle()

        coVerify {
            api.registerDevice(
                match<RegisterDeviceRequest> {
                    it.device_id == "test_device_uuid_123" &&
                        it.platform == "android" &&
                        it.push_token == testToken
                }
            )
        }
    }

    @Test
    fun `revokeToken deletes backend device session and clears local preferences`() = runTest(testDispatcher) {
        coEvery { api.revokeDeviceSession(42L) } returns Response.success(
            ApiResponse(data = mapOf("revoked" to true), message = "Revoked")
        )

        deviceTokenManager.revokeToken()
        testScheduler.advanceUntilIdle()

        coVerify {
            api.revokeDeviceSession(42L)
            editor.putString("fcm_push_token", null)
            editor.remove("backend_session_id")
        }
    }
}
