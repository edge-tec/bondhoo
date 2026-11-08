package com.connectly.android.core.network.di

import android.os.Build
import com.connectly.android.core.network.AuthInterceptor
import com.connectly.android.core.network.TokenAuthenticator
import com.connectly.android.core.network.api.ConnectlyApi
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.components.SingletonComponent
import kotlinx.serialization.json.Json
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.kotlinx.serialization.asConverterFactory
import java.util.concurrent.TimeUnit
import javax.inject.Singleton

@Module
@InstallIn(SingletonComponent::class)
object NetworkModule {

    const val PROD_BASE_URL = "https://bondhoo.com/api/"
    const val PROD_HOST = "bondhoo.com"

    /**
     * Primary live production Base URL for all network requests.
     * All options, registration, login, and real-time operations connect to bondhoo.com.
     */
    val BASE_URL: String = PROD_BASE_URL

    fun resolveMediaUrl(url: String?): String? {
        if (url.isNullOrBlank()) return null
        val targetHost = PROD_HOST
        val sanitized = url.replace("localhost:8000", targetHost)
            .replace("127.0.0.1:8000", targetHost)
            .replace("10.0.2.2:8000", targetHost)
            .replace("192.168.0.184:8000", targetHost)
            .replace("http://bondhoo.com", "https://bondhoo.com")
        return when {
            sanitized.startsWith("https://") -> sanitized
            sanitized.startsWith("http://") -> sanitized.replace("http://", "https://")
            sanitized.startsWith("/") -> "https://$targetHost$sanitized"
            else -> "https://$targetHost/$sanitized"
        }
    }

    @Provides
    @Singleton
    fun providesJson(): Json = Json {
        ignoreUnknownKeys = true
        isLenient = true
        encodeDefaults = true
        coerceInputValues = true
    }

    @Provides
    @Singleton
    fun providesOkHttpClient(
        authInterceptor: AuthInterceptor,
        tokenAuthenticator: TokenAuthenticator
    ): OkHttpClient {
        val logging = HttpLoggingInterceptor().apply {
            level = if (com.connectly.android.core.network.BuildConfig.DEBUG) {
                HttpLoggingInterceptor.Level.BODY
            } else {
                HttpLoggingInterceptor.Level.NONE
            }
        }

        return OkHttpClient.Builder()
            .addInterceptor(authInterceptor)
            .addInterceptor(logging)
            .authenticator(tokenAuthenticator)
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .retryOnConnectionFailure(true)
            .build()
    }

    @Provides
    @Singleton
    fun providesRetrofit(
        okHttpClient: OkHttpClient,
        json: Json
    ): Retrofit {
        val contentType = "application/json".toMediaType()
        return Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(json.asConverterFactory(contentType))
            .build()
    }

    @Provides
    @Singleton
    fun providesConnectlyApi(retrofit: Retrofit): ConnectlyApi {
        return retrofit.create(ConnectlyApi::class.java)
    }
}
