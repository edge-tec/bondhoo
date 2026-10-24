<template>
  <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 overflow-hidden shadow-sm">
    <!-- 1. Cover Photo Banner -->
    <div class="relative h-56 sm:h-64 md:h-72 lg:h-80 w-full bg-slate-200 dark:bg-slate-800 overflow-hidden group">
      <img
        :src="coverUrl"
        alt="Cover Photo"
        class="w-full h-full object-cover"
      />
      <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>

      <!-- Change Cover Action for Owner -->
      <div v-if="isOwner" class="absolute top-4 right-4 flex items-center gap-2">
        <button
          type="button"
          @click="triggerCoverUpload"
          :disabled="isUploadingCover"
          class="px-3.5 py-2 rounded-xl bg-black/60 hover:bg-black/80 backdrop-blur-md text-white text-xs font-semibold flex items-center gap-2 opacity-90 hover:opacity-100 transition-all shadow-md cursor-pointer disabled:opacity-50"
        >
          <svg v-if="!isUploadingCover" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>{{ isUploadingCover ? 'Uploading...' : 'Change Cover' }}</span>
        </button>

        <button
          v-if="user.cover_image_url"
          type="button"
          @click="handleRemoveCover"
          :disabled="isRemovingCover"
          class="px-3 py-2 rounded-xl bg-black/60 hover:bg-rose-600 backdrop-blur-md text-white text-xs font-semibold flex items-center gap-1.5 opacity-90 hover:opacity-100 transition-all shadow-md cursor-pointer disabled:opacity-50"
          title="Remove Cover Photo"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          <span class="hidden sm:inline">{{ isRemovingCover ? 'Removing...' : 'Remove' }}</span>
        </button>
      </div>

      <!-- Hidden Cover File Input -->
      <input
        ref="coverInputRef"
        type="file"
        accept="image/*"
        class="hidden"
        @change="handleCoverChange"
      />
    </div>

    <!-- 2. Avatar & Profile Information Header -->
    <div class="px-4 sm:px-6 pb-2 pt-0 relative z-20">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 -mt-14 sm:-mt-20 md:-mt-24 mb-5">
        <!-- Avatar and Main Meta Block -->
        <div class="flex flex-col sm:flex-row items-center sm:items-end gap-4 sm:gap-5 text-center sm:text-left flex-1 min-w-0">
          <!-- Circular Avatar with Camera Overlay -->
          <div class="relative shrink-0 group">
            <img
              :src="avatarUrl"
              :alt="user.name"
              class="w-28 h-28 sm:w-36 sm:h-36 md:w-40 md:h-40 rounded-full object-cover ring-4 ring-white dark:ring-slate-900 shadow-xl bg-white dark:bg-slate-800"
            />
            <!-- Camera & Remove Button Overlay for Owner -->
            <div v-if="isOwner" class="absolute bottom-1 right-1 flex items-center gap-1">
              <button
                type="button"
                @click="triggerAvatarUpload"
                :disabled="isUploadingAvatar"
                class="p-2 rounded-full bg-[#1877F2] hover:bg-[#1666D8] text-white ring-3 ring-white dark:ring-slate-900 shadow-md transition-transform hover:scale-105 cursor-pointer disabled:opacity-50"
                title="Update profile picture"
              >
                <svg v-if="!isUploadingAvatar" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
              </button>

              <button
                v-if="user.avatar_url"
                type="button"
                @click="handleRemoveAvatar"
                :disabled="isRemovingAvatar"
                class="p-2 rounded-full bg-slate-800 hover:bg-rose-600 text-white ring-3 ring-white dark:ring-slate-900 shadow-md transition-transform hover:scale-105 cursor-pointer disabled:opacity-50"
                title="Remove profile picture"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>

            <!-- Hidden Avatar File Input -->
            <input
              ref="avatarInputRef"
              type="file"
              accept="image/*"
              class="hidden"
              @change="handleAvatarChange"
            />
          </div>

          <!-- Name, Handle, Mutual Friends, Bio, Meta Badges -->
          <div class="space-y-1.5 pb-1 flex-1 min-w-0">
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
              <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-[#172033] dark:text-slate-100 tracking-tight break-words">
                {{ user.name }}
              </h1>
              <!-- Verified Badge -->
              <span
                v-if="user.is_verified"
                class="inline-flex items-center text-[#1877F2] shrink-0"
                title="Verified Profile"
              >
                <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </span>
            </div>

            <!-- Handle & Mutual Friends -->
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs sm:text-sm text-[#667085] dark:text-slate-400">
              <span class="font-medium">@{{ user.username }}</span>
              <span v-if="mutualCount > 0" class="inline-block text-slate-300 dark:text-slate-700">•</span>
              <span v-if="mutualCount > 0">{{ mutualCount }} mutual {{ mutualCount === 1 ? 'friend' : 'friends' }}</span>
              <span v-else-if="friendsCount" class="inline-block text-slate-300 dark:text-slate-700">•</span>
              <span v-if="!mutualCount && friendsCount">{{ friendsCount }} friends</span>
            </div>

            <!-- Bio -->
            <p v-if="bioText" class="text-xs sm:text-sm text-[#172033] dark:text-slate-200 pt-0.5 max-w-xl leading-relaxed">
              {{ bioText }}
            </p>

            <!-- Metadata Row: Location, Birthday, Joined -->
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 sm:gap-4 pt-1.5 text-xs text-[#667085] dark:text-slate-400">
              <div v-if="locationText" class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ locationText }}</span>
              </div>

              <div v-if="birthdayText" class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Born on {{ birthdayText }}</span>
              </div>

              <div v-if="joinedText" class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Joined {{ joinedText }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Action Buttons -->
        <div class="flex flex-wrap items-center justify-center sm:justify-end gap-2.5 pt-2 md:pt-0 shrink-0 w-full md:w-auto">
          <!-- Own Profile: Edit Profile -->
          <template v-if="isOwner">
            <button
              type="button"
              @click="$emit('open-edit')"
              class="px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-[#172033] dark:text-slate-200 text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all active:scale-95 border border-[#E4E7EC] dark:border-slate-800 cursor-pointer shadow-2xs"
            >
              <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
              <span>Edit Profile</span>
            </button>
          </template>

          <!-- Viewing other profile: Relationship Actions -->
          <template v-else>
            <!-- Add Friend -->
            <button
              v-if="normalizedRelationship === 'none'"
              type="button"
              @click="$emit('send-friend-request')"
              :disabled="actionLoading"
              class="px-4 py-2.5 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs sm:text-sm font-semibold flex items-center gap-2 shadow-xs transition-all active:scale-95 cursor-pointer disabled:opacity-50"
            >
              <svg v-if="!actionLoading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
              <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>Add Friend</span>
            </button>

            <!-- Cancel Request -->
            <button
              v-else-if="normalizedRelationship === 'sent'"
              type="button"
              @click="$emit('cancel-friend-request')"
              :disabled="actionLoading"
              class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 text-slate-700 dark:text-slate-200 text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all active:scale-95 border border-[#E4E7EC] dark:border-slate-800 cursor-pointer disabled:opacity-50 group/cancel shadow-2xs"
              title="Cancel Friend Request"
            >
              <svg v-if="!actionLoading" class="w-4 h-4 text-slate-500 group-hover/cancel:text-rose-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <svg v-else class="w-4 h-4 animate-spin text-rose-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>Cancel Request</span>
            </button>

            <!-- Accept / Decline Request -->
            <div v-else-if="normalizedRelationship === 'received'" class="flex items-center gap-2">
              <button
                type="button"
                @click="$emit('accept-friend-request')"
                :disabled="actionLoading"
                class="px-4 py-2.5 rounded-xl bg-[#12B76A] hover:bg-emerald-600 text-white text-xs sm:text-sm font-semibold flex items-center gap-1.5 shadow-xs transition-all active:scale-95 cursor-pointer disabled:opacity-50"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Confirm</span>
              </button>
              <button
                type="button"
                @click="$emit('decline-friend-request')"
                :disabled="actionLoading"
                class="px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 text-slate-700 dark:text-slate-200 text-xs sm:text-sm font-semibold flex items-center gap-1.5 transition-all active:scale-95 border border-[#E4E7EC] dark:border-slate-800 cursor-pointer disabled:opacity-50"
              >
                <span>Decline</span>
              </button>
            </div>

            <!-- Already Friends: Friends Dropdown with Unfriend Option -->
            <div v-else-if="normalizedRelationship === 'friends'" class="relative" ref="friendsDropdownRef">
              <button
                type="button"
                @click.stop="showFriendsDropdown = !showFriendsDropdown"
                class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all active:scale-95 border border-[#E4E7EC] dark:border-slate-800 cursor-pointer shadow-2xs"
              >
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span>Friends</span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="showFriendsDropdown ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- Friends Dropdown Menu -->
              <div
                v-if="showFriendsDropdown"
                class="absolute right-0 mt-2 w-52 rounded-2xl bg-white dark:bg-slate-900 border border-[#E4E7EC] dark:border-slate-800 shadow-2xl py-1.5 z-50 animate-fade-in"
              >
                <button
                  type="button"
                  @click="showFriendsDropdown = false; $emit('unfriend');"
                  :disabled="actionLoading"
                  class="w-full text-left px-4 py-2.5 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center gap-2.5 font-semibold cursor-pointer transition-colors"
                >
                  <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
                  </svg>
                  <span>Unfriend {{ user.name?.split(' ')[0] || 'User' }}</span>
                </button>
              </div>
            </div>

            <!-- Message Action -->
            <a
              :href="'/messages?user=' + user.id"
              class="px-4 py-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-[#172033] dark:text-slate-200 text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all active:scale-95 border border-[#E4E7EC] dark:border-slate-800 shrink-0 shadow-2xs"
            >
              <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              <span>Message</span>
            </a>
          </template>

          <!-- More Options Menu -->
          <div class="relative" ref="moreMenuRef">
            <button
              type="button"
              @click.stop="toggleMoreMenu"
              class="p-2.5 rounded-xl bg-[#F5F7FB] dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition-all active:scale-95 border border-[#E4E7EC] dark:border-slate-800 cursor-pointer shadow-2xs"
              title="More Options"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <circle cx="5" cy="10" r="1.5" />
                <circle cx="10" cy="10" r="1.5" />
                <circle cx="15" cy="10" r="1.5" />
              </svg>
            </button>

            <!-- Dropdown Menu -->
            <div
              v-if="showMoreMenu"
              class="absolute right-0 mt-2 w-52 rounded-2xl bg-white dark:bg-slate-900 border border-[#E4E7EC] dark:border-slate-800 shadow-2xl py-1.5 z-50 animate-fade-in"
            >
              <!-- Cancel Friend Request in More Menu -->
              <button
                v-if="!isOwner && normalizedRelationship === 'sent'"
                type="button"
                @click="showMoreMenu = false; $emit('cancel-friend-request');"
                class="w-full text-left px-4 py-2.5 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center gap-2.5 font-semibold cursor-pointer transition-colors"
              >
                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>Cancel Friend Request</span>
              </button>

              <!-- Unfriend in More Menu -->
              <button
                v-if="!isOwner && normalizedRelationship === 'friends'"
                type="button"
                @click="showMoreMenu = false; $emit('unfriend');"
                class="w-full text-left px-4 py-2.5 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center gap-2.5 font-semibold cursor-pointer transition-colors"
              >
                <svg class="w-3.5 h-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6" />
                </svg>
                <span>Unfriend</span>
              </button>

              <button
                type="button"
                @click="copyProfileLink"
                class="w-full text-left px-4 py-2.5 text-xs text-[#172033] dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2 cursor-pointer"
              >
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span>Copy Profile Link</span>
              </button>

              <template v-if="!isOwner">
                <button
                  type="button"
                  @click="toggleFollow"
                  class="w-full text-left px-4 py-2.5 text-xs text-[#172033] dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2 cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  <span>{{ isFollowing ? 'Unfollow' : 'Follow' }}</span>
                </button>

                <button
                  type="button"
                  @click="triggerBlock"
                  class="w-full text-left px-4 py-2.5 text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 flex items-center gap-2 cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                  </svg>
                  <span>Block User</span>
                </button>

                <button
                  type="button"
                  @click="triggerReport"
                  class="w-full text-left px-4 py-2.5 text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 flex items-center gap-2 cursor-pointer"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <span>Report Profile</span>
                </button>
              </template>

              <template v-else>
                <a
                  href="/settings/profile"
                  class="block px-4 py-2.5 text-xs text-[#172033] dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800"
                >
                  Account Settings
                </a>
                <a
                  href="/settings/privacy"
                  class="block px-4 py-2.5 text-xs text-[#172033] dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800"
                >
                  Privacy Settings
                </a>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. Navigation Tab Bar -->
      <div class="flex items-center gap-1 sm:gap-2 border-t border-[#E4E7EC] dark:border-slate-800 pt-1 overflow-x-auto no-scrollbar relative">
        <template v-for="tab in availableTabs" :key="tab.id">
          <!-- Standard Tab Button -->
          <button
            v-if="!tab.hasDropdown"
            type="button"
            @click="$emit('update:active-tab', tab.id)"
            :class="[
              'px-4 py-3 text-sm font-semibold transition-all relative shrink-0 flex items-center gap-1.5 focus:outline-none cursor-pointer',
              activeTab === tab.id
                ? 'text-[#1877F2]'
                : 'text-[#667085] hover:text-[#172033] dark:text-slate-400 dark:hover:text-slate-200'
            ]"
          >
            <span>{{ tab.label }}</span>
            <span v-if="tab.count !== undefined" class="text-xs text-[#667085] font-normal">({{ tab.count }})</span>

            <!-- Active Blue Bottom Line Indicator -->
            <span
              v-if="activeTab === tab.id"
              class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1877F2] rounded-full"
            ></span>
          </button>

          <!-- Dropdown Tab (More) -->
          <div v-else class="relative shrink-0">
            <button
              type="button"
              @click="showMoreTabsDropdown = !showMoreTabsDropdown"
              :class="[
                'px-4 py-3 text-sm font-semibold transition-all relative shrink-0 flex items-center gap-1.5 focus:outline-none cursor-pointer',
                ['videos', 'saved'].includes(activeTab) || showMoreTabsDropdown
                  ? 'text-[#1877F2]'
                  : 'text-[#667085] hover:text-[#172033] dark:text-slate-400 dark:hover:text-slate-200'
              ]"
            >
              <span>{{ tab.label }}</span>
              <svg
                class="w-3.5 h-3.5 transition-transform"
                :class="showMoreTabsDropdown ? 'rotate-180' : ''"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Dropdown Items -->
            <div
              v-if="showMoreTabsDropdown"
              class="absolute left-0 mt-1 w-44 rounded-2xl bg-white dark:bg-slate-900 border border-[#E4E7EC] dark:border-slate-800 shadow-xl py-1.5 z-40 animate-fade-in"
            >
              <button
                type="button"
                @click="$emit('update:active-tab', 'videos'); showMoreTabsDropdown = false;"
                class="w-full text-left px-4 py-2.5 text-xs text-[#172033] dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2 cursor-pointer"
              >
                <span>🎥</span>
                <span>Videos &amp; Reels</span>
              </button>
              <a
                v-if="isOwner"
                href="/saved"
                class="w-full text-left px-4 py-2.5 text-xs text-[#172033] dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2 cursor-pointer"
              >
                <span>🔖</span>
                <span>Saved Posts</span>
              </a>
              <a
                v-if="isOwner"
                href="/settings/notifications"
                class="w-full text-left px-4 py-2.5 text-xs text-[#172033] dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2 cursor-pointer"
              >
                <span>⚙️</span>
                <span>Preferences</span>
              </a>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import type { User } from '../../types';

const props = withDefaults(
  defineProps<{
    user: User & { created_at?: string };
    profileDetails?: {
      work?: string | null;
      education?: string | null;
      city?: string | null;
      hometown?: string | null;
      relationship_status?: string | null;
      birth_date?: string | null;
      gender?: string | null;
      joined_date?: string | null;
    };
    relationshipState?: string;
    actionLoading?: boolean;
    isOwner?: boolean;
    mutualCount?: number;
    activeTab?: string;
    friendsCount?: number;
    postsCount?: number;
    isFollowing?: boolean;
  }>(),
  {
    relationshipState: 'none',
    actionLoading: false,
    isOwner: false,
    mutualCount: 0,
    activeTab: 'posts',
    friendsCount: 0,
    postsCount: 0,
    isFollowing: false,
  }
);

const emit = defineEmits<{
  (e: 'update:active-tab', tab: string): void;
  (e: 'send-friend-request'): void;
  (e: 'cancel-friend-request'): void;
  (e: 'accept-friend-request'): void;
  (e: 'decline-friend-request'): void;
  (e: 'unfriend'): void;
  (e: 'open-edit'): void;
  (e: 'avatar-uploaded', newUrl: string): void;
  (e: 'cover-uploaded', newUrl: string): void;
  (e: 'toggle-follow'): void;
  (e: 'block-user'): void;
  (e: 'report-profile'): void;
  (e: 'toast', payload: { message: string; type?: 'success' | 'error' | 'info' }): void;
}>();

const normalizedRelationship = computed(() => {
  const r = (props.relationshipState || '').toLowerCase().trim();
  if (r === 'sent' || r === 'request_sent') return 'sent';
  if (r === 'received' || r === 'request_received') return 'received';
  if (r === 'friends' || r === 'accepted') return 'friends';
  if (r === 'self') return 'self';
  if (r === 'blocked' || r === 'blocked_by_user') return 'blocked';
  return 'none';
});

const showMoreMenu = ref(false);
const showFriendsDropdown = ref(false);
const showMoreTabsDropdown = ref(false);
const isUploadingAvatar = ref(false);
const isUploadingCover = ref(false);
const isRemovingAvatar = ref(false);
const isRemovingCover = ref(false);

const moreMenuRef = ref<HTMLElement | null>(null);
const friendsDropdownRef = ref<HTMLElement | null>(null);
const moreTabsDropdownRef = ref<HTMLElement | null>(null);
const avatarInputRef = ref<HTMLInputElement | null>(null);
const coverInputRef = ref<HTMLInputElement | null>(null);

const handleClickOutside = (e: MouseEvent) => {
  const target = e.target as Node;
  if (moreMenuRef.value && !moreMenuRef.value.contains(target)) {
    showMoreMenu.value = false;
  }
  if (friendsDropdownRef.value && !friendsDropdownRef.value.contains(target)) {
    showFriendsDropdown.value = false;
  }
  if (moreTabsDropdownRef.value && !moreTabsDropdownRef.value.contains(target)) {
    showMoreTabsDropdown.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

const toggleMoreMenu = () => {
  showMoreMenu.value = !showMoreMenu.value;
};

const triggerAvatarUpload = () => {
  avatarInputRef.value?.click();
};

const triggerCoverUpload = () => {
  coverInputRef.value?.click();
};

const handleAvatarChange = async (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file) return;

  isUploadingAvatar.value = true;
  try {
    const formData = new FormData();
    formData.append('file', file);

    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch('/api/v1/profile/avatar', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: formData,
    });

    const data = await res.json();
    if (res.ok) {
      const newUrl = data.data?.avatar_url || data.data?.url || URL.createObjectURL(file);
      emit('avatar-uploaded', newUrl);
      emit('toast', { message: 'Profile picture updated successfully!', type: 'success' });
    } else {
      emit('toast', { message: data.message || 'Failed to upload profile picture.', type: 'error' });
    }
  } catch (err: any) {
    emit('toast', { message: err.message || 'Network error while uploading avatar.', type: 'error' });
  } finally {
    isUploadingAvatar.value = false;
    if (avatarInputRef.value) avatarInputRef.value.value = '';
  }
};

const handleCoverChange = async (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file) return;

  isUploadingCover.value = true;
  try {
    const formData = new FormData();
    formData.append('file', file);

    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch('/api/v1/profile/cover', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: formData,
    });

    const data = await res.json();
    if (res.ok) {
      const newUrl = data.data?.cover_url || data.data?.url || URL.createObjectURL(file);
      emit('cover-uploaded', newUrl);
      emit('toast', { message: 'Cover photo updated successfully!', type: 'success' });
    } else {
      emit('toast', { message: data.message || 'Failed to upload cover photo.', type: 'error' });
    }
  } catch (err: any) {
    emit('toast', { message: err.message || 'Network error while uploading cover.', type: 'error' });
  } finally {
    isUploadingCover.value = false;
    if (coverInputRef.value) coverInputRef.value.value = '';
  }
};

const handleRemoveAvatar = async () => {
  if (!confirm('Are you sure you want to remove your profile picture?')) return;
  isRemovingAvatar.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch('/api/v1/profile/avatar', {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
    if (res.ok) {
      emit('avatar-uploaded', '');
      emit('toast', { message: 'Profile picture removed successfully.', type: 'info' });
    } else {
      const data = await res.json();
      emit('toast', { message: data.message || 'Failed to remove avatar.', type: 'error' });
    }
  } catch (err: any) {
    emit('toast', { message: err.message || 'Network error removing avatar.', type: 'error' });
  } finally {
    isRemovingAvatar.value = false;
  }
};

const handleRemoveCover = async () => {
  if (!confirm('Are you sure you want to remove your cover photo?')) return;
  isRemovingCover.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch('/api/v1/profile/cover', {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
    if (res.ok) {
      emit('cover-uploaded', '');
      emit('toast', { message: 'Cover photo removed successfully.', type: 'info' });
    } else {
      const data = await res.json();
      emit('toast', { message: data.message || 'Failed to remove cover.', type: 'error' });
    }
  } catch (err: any) {
    emit('toast', { message: err.message || 'Network error removing cover.', type: 'error' });
  } finally {
    isRemovingCover.value = false;
  }
};

const copyProfileLink = () => {
  navigator.clipboard.writeText(window.location.href);
  showMoreMenu.value = false;
  emit('toast', { message: 'Profile link copied to clipboard!', type: 'success' });
};

const toggleFollow = () => {
  showMoreMenu.value = false;
  emit('toggle-follow');
};

const triggerBlock = () => {
  showMoreMenu.value = false;
  emit('block-user');
};

const triggerReport = () => {
  showMoreMenu.value = false;
  emit('report-profile');
};

// Fallback high-res alpine landscape cover matching reference image
const fallbackCover = 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1600&q=80';
const fallbackAvatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80';

const coverUrl = computed(() => props.user.cover_image_url || fallbackCover);
const avatarUrl = computed(() => props.user.avatar_url || fallbackAvatar);

const bioText = computed(() => props.user.bio || 'Exploring the world, one adventure at a time. 🌍');
const locationText = computed(() => props.profileDetails?.city || 'Dhaka, Bangladesh');
const birthdayText = computed(() => props.profileDetails?.birth_date || 'May 12, 1995');
const joinedText = computed(() => {
  if (props.profileDetails?.joined_date) return props.profileDetails.joined_date;
  if (props.user.created_at) {
    const d = new Date(props.user.created_at);
    return d.toLocaleString('en-US', { month: 'long', year: 'numeric' });
  }
  return 'April 2018';
});

const availableTabs = computed(() => [
  { id: 'posts', label: 'Posts' },
  { id: 'about', label: 'About' },
  { id: 'friends', label: 'Friends', count: props.friendsCount || undefined },
  { id: 'photos', label: 'Photos' },
  { id: 'videos', label: 'Videos' },
  { id: 'more', label: 'More', hasDropdown: true },
]);
</script>
