package com.connectly.android.feature.groups.domain.usecase

import com.connectly.android.core.common.result.Result
import com.connectly.android.core.model.Group
import com.connectly.android.feature.groups.domain.repository.GroupsRepository
import kotlinx.coroutines.flow.Flow
import javax.inject.Inject

class GetGroupsUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(filter: String, page: Int = 1): Flow<Result<List<Group>>> {
        return repository.getGroups(filter, page)
    }
}

class GetFeaturedGroupUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(): Flow<Result<Group>> {
        return repository.getFeaturedGroup()
    }
}

class ToggleJoinGroupUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(groupId: Long, currentJoined: Boolean): Flow<Result<Boolean>> {
        return repository.toggleJoinGroup(groupId, currentJoined)
    }
}

class CreateGroupUseCase @Inject constructor(
    private val repository: GroupsRepository
) {
    operator fun invoke(name: String, description: String?, privacy: String): Flow<Result<Group>> {
        return repository.createGroup(name, description, privacy)
    }
}

