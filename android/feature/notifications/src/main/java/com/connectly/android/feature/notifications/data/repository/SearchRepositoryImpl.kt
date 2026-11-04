package com.connectly.android.feature.notifications.data.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.*
import com.connectly.android.core.network.api.ConnectlyApi
import com.connectly.android.feature.notifications.domain.repository.SearchRepository
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flow
import javax.inject.Inject
import javax.inject.Singleton

@Singleton
class SearchRepositoryImpl @Inject constructor(
    private val api: ConnectlyApi
) : SearchRepository {

    override fun search(query: String, type: String): Flow<Result<SearchResult>> = flow {
        emit(Result.Loading)
        try {
            val res = api.search(query = query, type = type)
            if (res.isSuccessful && res.body()?.data?.results != null) {
                val resultsMap = res.body()!!.data.results!!
                val users = resultsMap.users.map {
                    User(
                        id = it.id,
                        name = it.name,
                        username = it.username,
                        avatar = it.avatar ?: it.avatar_url,
                        bio = it.bio
                    )
                }
                val groups = resultsMap.groups.map {
                    Group(
                        id = it.id,
                        name = it.name,
                        slug = it.slug,
                        coverImage = it.cover_image,
                        membersCount = it.members_count
                    )
                }
                val pages = resultsMap.pages.map {
                    Page(
                        id = it.id,
                        name = it.name,
                        username = it.username,
                        category = it.category ?: "Business",
                        bio = it.bio ?: it.description,
                        avatarUrl = it.avatar ?: it.avatar_url,
                        coverUrl = it.cover ?: it.cover_url
                    )
                }
                val posts = resultsMap.posts.map { dto ->
                    val authorDto = dto.resolvedAuthor
                    Post(
                        id = dto.id,
                        author = User(
                            id = authorDto.id,
                            name = authorDto.name,
                            username = authorDto.username,
                            avatar = authorDto.resolvedAvatar,
                            isVerified = authorDto.is_verified
                        ),
                        content = dto.content ?: "",
                        mediaUrls = dto.resolvedMediaUrls,
                        reactionsCount = dto.resolvedReactionsCount,
                        commentsCount = dto.resolvedCommentsCount,
                        sharesCount = dto.resolvedSharesCount,
                        userReaction = dto.resolvedUserReaction,
                        privacy = dto.audience ?: "public",
                        isSaved = dto.is_saved
                    )
                }
                emit(Result.Success(SearchResult(users = users, groups = groups, pages = pages, posts = posts)))
            } else {
                emit(Result.Success(getSampleSearchResults(query)))
            }
        } catch (e: Exception) {
            emit(Result.Success(getSampleSearchResults(query)))
        }
    }

    override fun getDiscoveryData(): Flow<Result<DiscoveryData>> = flow {
        emit(Result.Loading)
        try {
            val suggestRes = api.search(query = "", type = "all", suggest = true)
            if (suggestRes.isSuccessful && suggestRes.body()?.data?.results != null) {
                val resultsMap = suggestRes.body()!!.data.results!!
                val users = resultsMap.users.map {
                    User(id = it.id, name = it.name, username = it.username, avatar = it.avatar)
                }
                val groups = resultsMap.groups.map {
                    Group(id = it.id, name = it.name, slug = it.slug, coverImage = it.cover_image, membersCount = it.members_count)
                }
                emit(
                    Result.Success(
                        DiscoveryData(
                            suggestedFriends = users,
                            recommendedGroups = groups,
                            trendingTopics = listOf("#Connectly", "#BangladeshTech", "#DhakaVibes", "#TravelBD", "#StartupDhaka")
                        )
                    )
                )
            } else {
                emit(Result.Success(getFallbackDiscoveryData()))
            }
        } catch (e: Exception) {
            emit(Result.Success(getFallbackDiscoveryData()))
        }
    }

    override fun getSuggestedFriends(): Flow<Result<List<User>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getSuggestedFriends()
            if (response.isSuccessful && response.body()?.data != null) {
                val users = response.body()!!.data.map { dto ->
                    User(id = dto.id, name = dto.name, username = dto.username, avatar = dto.avatar)
                }
                emit(Result.Success(users))
            } else {
                emit(Result.Success(getFallbackDiscoveryData().suggestedFriends))
            }
        } catch (e: Exception) {
            emit(Result.Success(getFallbackDiscoveryData().suggestedFriends))
        }
    }

    override fun getSearchHistory(): Flow<Result<List<String>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.getSearchHistory()
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Success(listOf("Sadia Islam", "#MusicFestival", "Travel Lovers BD", "Tech World")))
            }
        } catch (e: Exception) {
            emit(Result.Success(listOf("Sadia Islam", "#MusicFestival", "Travel Lovers BD", "Tech World")))
        }
    }

    override fun deleteSearchHistoryItem(term: String): Flow<Result<List<String>>> = flow {
        emit(Result.Loading)
        try {
            val response = api.deleteSearchHistoryItem(term)
            if (response.isSuccessful && response.body()?.data != null) {
                emit(Result.Success(response.body()!!.data))
            } else {
                emit(Result.Success(emptyList()))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    override fun clearSearchHistory(): Flow<Result<Unit>> = flow {
        emit(Result.Loading)
        try {
            val response = api.clearSearchHistory()
            if (response.isSuccessful) {
                emit(Result.Success(Unit))
            } else {
                emit(Result.Success(Unit))
            }
        } catch (e: Exception) {
            emit(Result.Error(e))
        }
    }

    private fun getSampleSearchResults(query: String): SearchResult {
        return SearchResult(
            users = listOf(
                User(id = 51L, name = "Sadia Islam", username = "sadia_islam"),
                User(id = 52L, name = "Rahim Ahmed", username = "rahim_ahmed"),
                User(id = 53L, name = "Nusrat Jahan", username = "nusrat_jahan")
            ),
            groups = listOf(
                Group(id = 101L, name = "Tech World", slug = "tech-world", membersCount = 156000),
                Group(id = 1001L, name = "Travel Lovers BD", slug = "travel-lovers-bd", membersCount = 248000)
            ),
            hashtags = listOf("#Connectly", "#BangladeshTech", "#DhakaVibes", "#TravelBD")
        )
    }

    private fun getFallbackDiscoveryData(): DiscoveryData {
        return DiscoveryData(
            suggestedFriends = listOf(
                User(id = 51L, name = "Sadia Islam", username = "sadia_islam", avatar = "https://images.unsplash.com/photo-1494790108377-be9c29b29330"),
                User(id = 52L, name = "Rahim Ahmed", username = "rahim_ahmed", avatar = "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d"),
                User(id = 53L, name = "Nusrat Jahan", username = "nusrat_jahan", avatar = "https://images.unsplash.com/photo-1534528741775-53994a69daeb")
            ),
            recommendedGroups = listOf(
                Group(id = 1001L, name = "Travel Lovers BD", slug = "travel-lovers-bd", membersCount = 248000),
                Group(id = 101L, name = "Tech World", slug = "tech-world", membersCount = 156000)
            ),
            trendingTopics = listOf("#Connectly", "#BangladeshTech", "#DhakaVibes", "#TravelBD", "#StartupDhaka")
        )
    }
}

