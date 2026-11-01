# ==============================================================================
# Connectly Android Security Hardening & R8 / ProGuard Obfuscation Rules
# ==============================================================================

# 1. Strip debug logs in release builds to prevent credential & sensitive data leakage
-assumenosideeffects class android.util.Log {
    public static boolean isLoggable(java.lang.String, int);
    public static int v(...);
    public static int d(...);
    public static int i(...);
    public static int w(...);
}

# 2. Obfuscation & Source File Scrubbing
-repackageclasses 'com.connectly.android.obf'
-allowaccessmodification
-renamesourcefileattribute 'SourceFile'
-keepattributes SourceFile,LineNumberTable,*Annotation*,Signature,InnerClasses,EnclosingMethod

# 3. Kotlinx Serialization
-keepattributes *Annotation*,InnerClasses
-dontnote kotlinx.serialization.SerializationKt
-keepclassmembers class * {
    *** Companion;
}
-keepclasseswithmembers class * {
    kotlinx.serialization.KSerializer serializer(...);
}
-keep,includedescriptorclasses class com.connectly.android.core.model.** { *; }
-keepclassmembers class com.connectly.android.core.model.** {
    *** Companion;
}

# 4. Retrofit 2 & OkHttp 3
-dontwarn okio.**
-dontwarn retrofit2.**
-dontwarn okhttp3.**
-keepattributes EnclosingMethod
-keepclassmembers,allowobfuscation interface * {
    @retrofit2.http.* <methods>;
}

# 5. Room Database & SQLCipher
-keepclassmembers class * extends androidx.room.RoomDatabase {
    <init>();
}
-keep class * extends androidx.room.RoomDatabase
-dontwarn androidx.room.paging.**
-keep class com.connectly.android.core.database.entity.** { *; }

# 6. Android Security Crypto & Keystore
-keep class androidx.security.crypto.** { *; }
-dontwarn androidx.security.crypto.**

# 7. WebRTC Calling Engine
-keep class org.webrtc.** { *; }
-dontwarn org.webrtc.**

# 8. Coil Image Loading & AndroidX Media3
-keep class coil3.** { *; }
-dontwarn coil3.**
-keep class androidx.media3.** { *; }
-dontwarn androidx.media3.**

# 9. Google Play Services & Firebase Cloud Messaging
-keep class com.google.firebase.** { *; }
-dontwarn com.google.firebase.**
-keep class com.google.android.gms.** { *; }
-dontwarn com.google.android.gms.**
