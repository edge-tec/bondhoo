import com.google.firebase.crashlytics.buildtools.gradle.CrashlyticsExtension

plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
    alias(libs.plugins.hilt.android)
    alias(libs.plugins.ksp)
    alias(libs.plugins.google.services)
    alias(libs.plugins.firebase.crashlytics)
}

android {
    namespace = "com.connectly.android"
    compileSdk = 36

    defaultConfig {
        applicationId = "com.jugajug.android"
        minSdk = 26
        targetSdk = 36
        versionCode = 4
        versionName = "1.0.3"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    signingConfigs {
        create("release") {
            val keystorePropsFile = rootProject.file("keystore.properties")
            val keystoreProps: Map<String, String> = if (keystorePropsFile.exists()) {
                keystorePropsFile.readLines()
                    .map { it.trim() }
                    .filter { it.isNotEmpty() && !it.startsWith("#") && it.contains("=") }
                    .associate { line ->
                        val idx = line.indexOf('=')
                        line.substring(0, idx).trim() to line.substring(idx + 1).trim()
                    }
            } else {
                emptyMap()
            }
            val storeFilePath = System.getenv("BONDHOO_UPLOAD_STORE_FILE")
                ?: keystoreProps["storeFile"]
            val storePass = System.getenv("BONDHOO_UPLOAD_STORE_PASSWORD")
                ?: keystoreProps["storePassword"]
            val kAlias = System.getenv("BONDHOO_UPLOAD_KEY_ALIAS")
                ?: keystoreProps["keyAlias"]
            val kPass = System.getenv("BONDHOO_UPLOAD_KEY_PASSWORD")
                ?: keystoreProps["keyPassword"]

            val resolvedStoreFile = storeFilePath?.takeIf { it.isNotBlank() }?.let { path ->
                val f = File(path)
                if (f.isAbsolute) f else rootProject.file(path)
            }

            if (resolvedStoreFile != null && resolvedStoreFile.exists() &&
                !storePass.isNullOrBlank() && !kAlias.isNullOrBlank() && !kPass.isNullOrBlank()) {
                storeFile = resolvedStoreFile
                storePassword = storePass
                keyAlias = kAlias
                keyPassword = kPass
            }
        }
    }

    buildTypes {
        debug {
            applicationIdSuffix = ".debug"
            isDebuggable = true
        }
        create("staging") {
            initWith(getByName("release"))
            applicationIdSuffix = ".staging"
            matchingFallbacks += listOf("release")
            val releaseConfig = signingConfigs.getByName("release")
            if (releaseConfig.storeFile != null) {
                signingConfig = releaseConfig
            }
        }
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            val googleServicesFile = file("google-services.json")
            val isRealFirebaseConfig = googleServicesFile.exists() &&
                !googleServicesFile.readText().contains("connectly-placeholder-dev")
            configure<CrashlyticsExtension> {
                mappingFileUploadEnabled = isRealFirebaseConfig
            }
            val releaseConfig = signingConfigs.getByName("release")
            if (releaseConfig.storeFile != null) {
                signingConfig = releaseConfig
            }
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }

    // Optional ABI split APK support for smaller direct website downloads (-PsplitAbi=true)
    if (project.hasProperty("splitAbi")) {
        splits {
            abi {
                isEnable = true
                reset()
                include("arm64-v8a", "armeabi-v7a")
                isUniversalApk = true
            }
        }
    }

    lint {
        checkReleaseBuilds = false
        abortOnError = false
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }

    buildFeatures {
        compose = true
        buildConfig = true
    }
}

dependencies {
    implementation(project(":core:common"))
    implementation(project(":core:model"))
    implementation(project(":core:network"))
    implementation(project(":core:database"))
    implementation(project(":core:datastore"))
    implementation(project(":core:designsystem"))
    implementation(project(":feature:auth"))
    implementation(project(":feature:profile"))
    implementation(project(":feature:feed"))
    implementation(project(":feature:stories"))
    implementation(project(":feature:reels"))
    implementation(project(":feature:messenger"))
    implementation(project(":feature:calling"))
    implementation(project(":feature:groups"))
    implementation(project(":feature:events"))
    implementation(project(":feature:live"))
    implementation(project(":feature:notifications"))
    implementation(project(":feature:jobs"))
    implementation(project(":feature:learning"))

    implementation(libs.coil.network.okhttp)
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.appcompat)
    implementation(libs.androidx.biometric)
    implementation(libs.androidx.lifecycle.runtime.ktx)
    implementation(libs.androidx.activity.compose)
    implementation(libs.androidx.navigation.compose)
    implementation(libs.androidx.work.runtime.ktx)

    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.compose.ui)
    implementation(libs.androidx.compose.ui.graphics)
    implementation(libs.androidx.compose.ui.tooling.preview)
    implementation(libs.androidx.compose.material3)
    implementation(libs.androidx.compose.material.icons.extended)

    implementation(libs.hilt.android)
    ksp(libs.hilt.compiler)
    implementation(libs.androidx.hilt.navigation.compose)

    implementation(platform(libs.firebase.bom))
    implementation(libs.firebase.crashlytics)
    implementation(libs.firebase.analytics)
    implementation(libs.play.app.update)
    implementation(libs.play.app.update.ktx)

    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.test.ext.junit)
    androidTestImplementation(libs.androidx.test.espresso.core)
    androidTestImplementation(platform(libs.androidx.compose.bom))
    androidTestImplementation(libs.androidx.compose.ui.test.junit4)
    debugImplementation(libs.androidx.compose.ui.tooling)
    debugImplementation(libs.androidx.compose.ui.test.manifest)
}

