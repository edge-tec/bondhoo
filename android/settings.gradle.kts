pluginManagement {
    repositories {
        google {
            content {
                includeGroupByRegex("com\\.android.*")
                includeGroupByRegex("com\\.google.*")
                includeGroupByRegex("androidx.*")
            }
        }
        mavenCentral()
        gradlePluginPortal()
    }
}

dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
        maven { url = java.net.URI("https://jitpack.io") }
    }
}

rootProject.name = "Connectly"

include(":app")
include(":core:common")
include(":core:model")
include(":core:network")
include(":core:database")
include(":core:datastore")
include(":core:designsystem")
include(":core:media")
include(":core:webrtc")
include(":core:websocket")
include(":feature:auth")
include(":feature:feed")
include(":feature:stories")
include(":feature:reels")
include(":feature:groups")
include(":feature:messenger")
include(":feature:calling")
include(":feature:profile")
include(":feature:events")
include(":feature:live")
include(":feature:notifications")
include(":feature:settings")
include(":feature:jobs")
include(":feature:learning")

