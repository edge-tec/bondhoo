package com.connectly.android.feature.groups.domain.repository

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Group
import kotlinx.coroutines.flow.Flow

interface GroupsRepository {
    fun getGroups(filter: String, page: Int = 1): Flow<Result<List<Group>>>
    fun getFeaturedGroup(): Flow<Result<Group>>
    fun createGroup(name: String, description: String?, privacy: String): Flow<Result<Group>>
    fun toggleJoinGroup(groupId: Long, currentJoined: Boolean): Flow<Result<Boolean>>
}

