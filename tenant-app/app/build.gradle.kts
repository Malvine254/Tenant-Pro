import java.util.Properties

plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.hilt)
    alias(libs.plugins.ksp)
    alias(libs.plugins.google.services)
}

// Read backend configuration from local.properties
val localProperties = Properties()
val localPropertiesFile = rootProject.file("local.properties")
if (localPropertiesFile.exists()) {
    localPropertiesFile.inputStream().use { localProperties.load(it) }
}

// Get backend configuration. By default, use the hosted production API unless overridden in local.properties.
val defaultBaseUrl = "https://app.starmaxltd.com/api/"
val backendHost = localProperties.getProperty("backend.host")
val backendPort = localProperties.getProperty("backend.port")
val baseUrl = localProperties.getProperty("backend.baseUrl")
    ?: if (!backendHost.isNullOrBlank() && !backendPort.isNullOrBlank()) {
        "http://$backendHost:$backendPort/api/"
    } else {
        defaultBaseUrl
    }
val mobileApiKey = localProperties.getProperty("mobile.apiKey", "")

android {
    namespace = "com.tenantpro.app"
    compileSdk = 35

    defaultConfig {
        applicationId = "com.tenantpro.app"
        minSdk = 26
        targetSdk = 35
        versionCode = 1
        versionName = "1.0.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"

        // Automatically uses IP from local.properties
        buildConfigField("String", "BASE_URL", "\"$baseUrl\"")
        buildConfigField("String", "MOBILE_API_KEY", "\"$mobileApiKey\"")
    }

    buildTypes {
        debug {
            isDebuggable = true
            // Uses the same BASE_URL from defaultConfig (read from local.properties)
        }
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
            buildConfigField("String", "BASE_URL", "\"https://app.starmaxltd.com/api/\"")
            buildConfigField("String", "MOBILE_API_KEY", "\"$mobileApiKey\"")
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }

    buildFeatures {
        viewBinding = true
        buildConfig = true
    }
}

dependencies {
    // AndroidX core
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.appcompat)
    implementation(libs.androidx.activity.ktx)
    implementation(libs.androidx.fragment.ktx)

    // Lifecycle / ViewModel / LiveData
    implementation(libs.androidx.lifecycle.viewmodel.ktx)
    implementation(libs.androidx.lifecycle.livedata.ktx)
    implementation(libs.androidx.lifecycle.runtime.ktx)

    // Navigation Component
    implementation(libs.androidx.navigation.fragment.ktx)
    implementation(libs.androidx.navigation.ui.ktx)
    implementation(libs.androidx.biometric)

    // Coroutines
    implementation(libs.kotlinx.coroutines.android)

    // Hilt DI
    implementation(libs.hilt.android)
    ksp(libs.hilt.compiler)

    // Retrofit + OkHttp + GSON
    implementation(libs.retrofit)
    implementation(libs.retrofit.converter.gson)
    implementation(libs.okhttp)
    implementation(libs.okhttp.logging)
    implementation(libs.gson)

    // DataStore (token persistence)
    implementation(libs.androidx.datastore.preferences)

    // UI
    implementation(libs.material)
    implementation(libs.androidx.constraintlayout)
    implementation(libs.androidx.splashscreen)
    implementation(libs.androidx.swiperefreshlayout)
    implementation(libs.mpandroidchart)
    implementation("androidx.work:work-runtime-ktx:2.9.1")
    implementation(libs.glide)

    // Room (offline cache)
    implementation(libs.androidx.room.runtime)
    implementation(libs.androidx.room.ktx)
    ksp(libs.androidx.room.compiler)

    // Firebase (BOM manages versions)
    implementation(platform(libs.firebase.bom))
    implementation(libs.firebase.messaging)
    implementation(libs.kotlinx.coroutines.play.services)

    // Testing
    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)
}
