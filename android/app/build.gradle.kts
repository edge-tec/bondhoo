plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
    alias(libs.plugins.hilt.android)
    alias(libs.plugins.ksp)
    alias(libs.plugins.google.services)
}

android {
    namespace = "com.connectly.android"
    compileSdk = 36

    defaultConfig {
        applicationId = "com.jugajug.android"
        minSdk = 26
        targetSdk = 36
        versionCode = 2
        versionName = "1.0.1"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    signingConfigs {
        create("release") {
            val storeFilePath = System.getenv("BONDHOO_UPLOAD_STORE_FILE")
            val storePass = System.getenv("BONDHOO_UPLOAD_STORE_PASSWORD")
            val kAlias = System.getenv("BONDHOO_UPLOAD_KEY_ALIAS")
            val kPass = System.getenv("BONDHOO_UPLOAD_KEY_PASSWORD")

            if (!storeFilePath.isNullOrBlank() && File(storeFilePath).exists() &&
                !storePass.isNullOrBlank() && !kAlias.isNullOrBlank() && !kPass.isNullOrBlank()) {
                storeFile = File(storeFilePath)
                storePassword = storePass
                keyAlias = kAlias
                keyPassword = kPass
            } else {
                val isReleaseTask = gradle.startParameter.taskNames.any {
                    it.contains("Release", ignoreCase = true)
                }
                if (isReleaseTask) {
                    throw GradleException(
                        "BONDHOO Security Enforcement: Release builds require secure signing credentials. " +
                        "Set BONDHOO_UPLOAD_STORE_FILE, BONDHOO_UPLOAD_STORE_PASSWORD, " +
                        "BONDHOO_UPLOAD_KEY_ALIAS, and BONDHOO_UPLOAD_KEY_PASSWORD environment variables."
                    )
                }
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
            signingConfig = signingConfigs.getByName("release")
        }
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            signingConfig = signingConfigs.getByName("release")
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
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
    implementation(libs.androidx.lifecycle.runtime.ktx)
    implementation(libs.androidx.activity.compose)
    implementation(libs.androidx.navigation.compose)
    implementation(libs.androidx.work.runtime.ktx)

    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.compose.ui)
    implementation(libs.androidx.compose.ui.graphics)
    implementation(libs.androidx.compose.ui.tooling.preview)
    implementation(libs.androidx.compose.material3)

    implementation(libs.hilt.android)
    ksp(libs.hilt.compiler)
    implementation(libs.androidx.hilt.navigation.compose)

    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.test.ext.junit)
    androidTestImplementation(libs.androidx.test.espresso.core)
    androidTestImplementation(platform(libs.androidx.compose.bom))
    androidTestImplementation(libs.androidx.compose.ui.test.junit4)
    debugImplementation(libs.androidx.compose.ui.tooling)
    debugImplementation(libs.androidx.compose.ui.test.manifest)
}

