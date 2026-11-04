package com.connectly.android.feature.parental.data

import com.connectly.android.feature.parental.model.*
import retrofit2.Response
import retrofit2.http.*

interface ParentalApiService {

    // Parent API
    @POST("api/v1/parental/families")
    suspend fun createFamily(
        @Body request: Map<String, String>
    ): Response<Map<String, Any>>

    @GET("api/v1/parental/families/{familyId}")
    suspend fun getFamily(
        @Path("familyId") familyId: Long
    ): Response<Map<String, Any>>

    @POST("api/v1/parental/families/{familyId}/members")
    suspend fun addMember(
        @Path("familyId") familyId: Long,
        @Body request: Map<String, String>
    ): Response<Map<String, Any>>

    @POST("api/v1/parental/families/{familyId}/pairing-codes")
    suspend fun generatePairingCode(
        @Path("familyId") familyId: Long,
        @Body request: Map<String, Long>
    ): Response<PairingCodeResponse>

    @GET("api/v1/parental/children/{childMemberId}/screen-time")
    suspend fun getScreenTime(
        @Path("childMemberId") childMemberId: Long,
        @Query("date") date: String?
    ): Response<Map<String, Any>>

    @POST("api/v1/parental/devices/{deviceId}/commands")
    suspend fun dispatchCommand(
        @Path("deviceId") deviceId: Long,
        @Body request: Map<String, Any>
    ): Response<Map<String, Any>>

    // Child Agent API
    @POST("api/v1/parental/device/pair")
    suspend fun pairDevice(
        @Body request: Map<String, String>
    ): Response<Map<String, Any>>

    @POST("api/v1/parental/device/usage/sync")
    suspend fun syncUsage(
        @Header("Authorization") bearerToken: String,
        @Body request: Map<String, Any>
    ): Response<Map<String, Any>>

    @GET("api/v1/parental/device/policies")
    suspend fun getPolicies(
        @Header("Authorization") bearerToken: String
    ): Response<Map<String, Any>>

    @GET("api/v1/parental/device/commands/pending")
    suspend fun getPendingCommands(
        @Header("Authorization") bearerToken: String
    ): Response<Map<String, Any>>

    @POST("api/v1/parental/device/commands/{uuid}/ack")
    suspend fun acknowledgeCommand(
        @Header("Authorization") bearerToken: String,
        @Path("uuid") uuid: String,
        @Body request: Map<String, String>
    ): Response<Map<String, Any>>
}

