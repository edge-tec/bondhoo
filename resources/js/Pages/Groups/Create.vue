<template>
  <AppLayout
    current-route="groups"
    :unread-count="unreadCount"
    :unread-messages-count="unreadMessagesCount"
    :pending-requests="pendingRequests"
    :contacts="contacts"
    :suggestions="suggestions"
    :upcoming-events="upcomingEvents"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <!-- Header Section with Blue Group Icon -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-[#1877F2] shadow-sm">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
              Create Group
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
              Build a community around what you care about.
            </p>
          </div>
        </div>

        <a
          href="/groups"
          class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to Groups
        </a>
      </div>
    </div>

    <!-- Error Alert Banner -->
    <div
      v-if="errorMessage"
      class="mb-6 p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-2xl text-sm text-rose-700 dark:text-rose-300 flex items-start gap-3 shadow-sm animate-fade-in"
    >
      <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <div class="flex-1">
        <h4 class="font-bold">Creation Error</h4>
        <p class="mt-0.5">{{ errorMessage }}</p>
      </div>
      <button @click="errorMessage = null" class="text-rose-400 hover:text-rose-600 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Main Content Layout: Left Form + Right Live Preview -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
      <!-- Left Column: Multi-Step Form Wizard -->
      <div class="lg:col-span-7 xl:col-span-7">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8">
          
          <!-- Stepper Indicator (1 Basic Info, 2 Members, 3 Settings, 4 Review) -->
          <div class="flex items-center justify-between mb-8 pb-6 border-b border-slate-100 dark:border-slate-800">
            <!-- Step 1 Button -->
            <button
              type="button"
              @click="goToStep(1)"
              class="flex items-center gap-2 group focus:outline-none"
            >
              <span
                class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                :class="currentStep === 1 ? 'bg-[#1877F2] text-white shadow-md shadow-blue-500/25 ring-2 ring-blue-500/20' : (currentStep > 1 ? 'bg-emerald-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700')"
              >
                <svg v-if="currentStep > 1" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span v-else>1</span>
              </span>
              <span
                class="text-xs sm:text-sm font-semibold transition-colors"
                :class="currentStep === 1 ? 'text-[#1877F2]' : (currentStep > 1 ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400')"
              >
                Basic Info
              </span>
            </button>

            <!-- Line 1 -->
            <div class="flex-1 h-0.5 mx-2 sm:mx-4 transition-colors" :class="currentStep > 1 ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-800'"></div>

            <!-- Step 2 Button -->
            <button
              type="button"
              @click="goToStep(2)"
              class="flex items-center gap-2 group focus:outline-none"
            >
              <span
                class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                :class="currentStep === 2 ? 'bg-[#1877F2] text-white shadow-md shadow-blue-500/25 ring-2 ring-blue-500/20' : (currentStep > 2 ? 'bg-emerald-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700')"
              >
                <svg v-if="currentStep > 2" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span v-else>2</span>
              </span>
              <span
                class="text-xs sm:text-sm font-semibold transition-colors"
                :class="currentStep === 2 ? 'text-[#1877F2]' : (currentStep > 2 ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400')"
              >
                Members
              </span>
            </button>

            <!-- Line 2 -->
            <div class="flex-1 h-0.5 mx-2 sm:mx-4 transition-colors" :class="currentStep > 2 ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-800'"></div>

            <!-- Step 3 Button -->
            <button
              type="button"
              @click="goToStep(3)"
              class="flex items-center gap-2 group focus:outline-none"
            >
              <span
                class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                :class="currentStep === 3 ? 'bg-[#1877F2] text-white shadow-md shadow-blue-500/25 ring-2 ring-blue-500/20' : (currentStep > 3 ? 'bg-emerald-500 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700')"
              >
                <svg v-if="currentStep > 3" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span v-else>3</span>
              </span>
              <span
                class="text-xs sm:text-sm font-semibold transition-colors"
                :class="currentStep === 3 ? 'text-[#1877F2]' : (currentStep > 3 ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400')"
              >
                Settings
              </span>
            </button>

            <!-- Line 3 -->
            <div class="flex-1 h-0.5 mx-2 sm:mx-4 transition-colors" :class="currentStep > 3 ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-800'"></div>

            <!-- Step 4 Button -->
            <button
              type="button"
              @click="goToStep(4)"
              class="flex items-center gap-2 group focus:outline-none"
            >
              <span
                class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                :class="currentStep === 4 ? 'bg-[#1877F2] text-white shadow-md shadow-blue-500/25 ring-2 ring-blue-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700'"
              >
                4
              </span>
              <span
                class="text-xs sm:text-sm font-semibold transition-colors"
                :class="currentStep === 4 ? 'text-[#1877F2]' : 'text-slate-400'"
              >
                Review
              </span>
            </button>
          </div>

          <!-- STEP 1: BASIC GROUP INFORMATION -->
          <div v-show="currentStep === 1" class="space-y-6">
            <div>
              <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                1. Group Information
              </h2>
            </div>

            <!-- Group Name Input with Counter -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label for="groupName" class="block text-sm font-bold text-slate-800 dark:text-slate-200">
                  Group Name <span class="text-rose-500">*</span>
                </label>
                <span class="text-xs text-slate-400 font-medium">
                  {{ form.name.length }}/100
                </span>
              </div>
              <input
                id="groupName"
                v-model="form.name"
                type="text"
                maxlength="100"
                placeholder="Enter group name"
                class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:border-transparent transition-all shadow-sm"
                :class="{ 'border-rose-400 focus:ring-rose-500': errors.name }"
              />
              <p v-if="errors.name" class="mt-1.5 text-xs text-rose-500 font-medium">
                {{ errors.name }}
              </p>
            </div>

            <!-- Group Category Dropdown -->
            <div>
              <label for="groupCategory" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                Category <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <select
                  id="groupCategory"
                  v-model="form.category"
                  class="w-full appearance-none px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:border-transparent transition-all shadow-sm cursor-pointer"
                  :class="{ 'border-rose-400 focus:ring-rose-500': errors.category }"
                >
                  <option value="" disabled selected>Select a category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.name">
                    {{ cat.name }}
                  </option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
              <p v-if="errors.category" class="mt-1.5 text-xs text-rose-500 font-medium">
                {{ errors.category }}
              </p>
            </div>

            <!-- Group Description Textarea with Counter -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label for="groupDesc" class="block text-sm font-bold text-slate-800 dark:text-slate-200">
                  Description <span class="text-rose-500">*</span>
                </label>
                <span class="text-xs text-slate-400 font-medium">
                  {{ form.description.length }}/500
                </span>
              </div>
              <textarea
                id="groupDesc"
                v-model="form.description"
                rows="4"
                maxlength="500"
                placeholder="Tell people about your group..."
                class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:border-transparent transition-all shadow-sm resize-none"
                :class="{ 'border-rose-400 focus:ring-rose-500': errors.description }"
              ></textarea>
              <p v-if="errors.description" class="mt-1.5 text-xs text-rose-500 font-medium">
                {{ errors.description }}
              </p>
            </div>

            <!-- Photo & Cover Upload Cards Side-by-Side -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <!-- Group Photo Upload Card -->
              <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                  Group Photo
                </label>
                <div
                  @click="triggerAvatarUpload"
                  class="relative group cursor-pointer border-2 border-dashed rounded-2xl p-5 text-center flex flex-col items-center justify-center transition-all bg-slate-50/50 dark:bg-slate-800/40 hover:bg-blue-50/40 dark:hover:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-[#1877F2]"
                  :class="avatarPreview ? 'border-solid border-[#1877F2]/40 bg-white dark:bg-slate-800' : ''"
                >
                  <input
                    ref="avatarInputRef"
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/gif"
                    class="hidden"
                    @change="handleAvatarFileSelect"
                  />

                  <!-- Preview State -->
                  <div v-if="avatarPreview" class="relative group/photo flex flex-col items-center">
                    <img
                      :src="avatarPreview"
                      alt="Group Avatar Preview"
                      class="w-20 h-20 rounded-2xl object-cover ring-4 ring-blue-500/20 shadow-md mb-2"
                    />
                    <div class="flex items-center gap-2">
                      <span class="text-xs font-semibold text-[#1877F2] group-hover:underline">Change photo</span>
                      <button
                        type="button"
                        @click.stop="removeAvatar"
                        class="text-xs text-rose-500 hover:text-rose-700 font-semibold"
                      >
                        Remove
                      </button>
                    </div>
                  </div>

                  <!-- Empty State -->
                  <div v-else class="flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-950/60 text-[#1877F2] flex items-center justify-center mb-2.5 transition-transform group-hover:scale-105">
                      <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                      </svg>
                    </div>
                    <span class="text-sm font-bold text-slate-800 dark:text-slate-200">
                      Upload photo
                    </span>
                    <span class="text-xs text-slate-400 mt-0.5">
                      Recommended size: 500x500
                    </span>
                  </div>

                  <!-- Uploading Spinner -->
                  <div v-if="isUploadingAvatar" class="absolute inset-0 bg-white/80 dark:bg-slate-900/80 rounded-2xl flex items-center justify-center">
                    <div class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                  </div>
                </div>
              </div>

              <!-- Cover Photo Upload Card -->
              <div>
                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-1.5">
                  Cover Photo
                </label>
                <div
                  @click="triggerCoverUpload"
                  class="relative group cursor-pointer border-2 border-dashed rounded-2xl p-5 text-center flex flex-col items-center justify-center transition-all bg-slate-50/50 dark:bg-slate-800/40 hover:bg-blue-50/40 dark:hover:bg-slate-800 border-slate-200 dark:border-slate-700 hover:border-[#1877F2]"
                  :class="coverPreview ? 'border-solid border-[#1877F2]/40 bg-white dark:bg-slate-800' : ''"
                >
                  <input
                    ref="coverInputRef"
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/gif"
                    class="hidden"
                    @change="handleCoverFileSelect"
                  />

                  <!-- Preview State -->
                  <div v-if="coverPreview" class="relative group/cover flex flex-col items-center w-full">
                    <img
                      :src="coverPreview"
                      alt="Group Cover Preview"
                      class="w-full h-20 rounded-xl object-cover ring-2 ring-blue-500/20 shadow-md mb-2"
                    />
                    <div class="flex items-center gap-2">
                      <span class="text-xs font-semibold text-[#1877F2] group-hover:underline">Change cover</span>
                      <button
                        type="button"
                        @click.stop="removeCover"
                        class="text-xs text-rose-500 hover:text-rose-700 font-semibold"
                      >
                        Remove
                      </button>
                    </div>
                  </div>

                  <!-- Empty State -->
                  <div v-else class="flex flex-col items-center">
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-950/60 text-[#1877F2] flex items-center justify-center mb-2.5 transition-transform group-hover:scale-105">
                      <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                      </svg>
                    </div>
                    <span class="text-sm font-bold text-slate-800 dark:text-slate-200">
                      Upload cover photo
                    </span>
                    <span class="text-xs text-slate-400 mt-0.5">
                      Recommended size: 1200x400
                    </span>
                  </div>

                  <!-- Uploading Spinner -->
                  <div v-if="isUploadingCover" class="absolute inset-0 bg-white/80 dark:bg-slate-900/80 rounded-2xl flex items-center justify-center">
                    <div class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Action Bar -->
            <div class="flex items-center justify-between pt-6 border-t border-slate-100 dark:border-slate-800">
              <a
                href="/groups"
                class="px-5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
              >
                Cancel
              </a>
              <button
                type="button"
                @click="validateAndNext(2)"
                class="px-6 py-2.5 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center gap-2"
              >
                Next
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </button>
            </div>
          </div>

          <!-- STEP 2: ADD MEMBERS / INVITE FRIENDS -->
          <div v-show="currentStep === 2" class="space-y-6">
            <div>
              <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                2. Add Members
              </h2>
              <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Invite friends to join your group. You can add more members later.
              </p>
            </div>

            <!-- Search Friends Input -->
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                v-model="friendSearch"
                type="text"
                placeholder="Search friends..."
                class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/60 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2] focus:border-transparent transition-all"
              />
            </div>

            <!-- Select All Checkbox -->
            <div class="flex items-center justify-between px-1">
              <label class="flex items-center gap-2.5 cursor-pointer select-none">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                  class="w-4 h-4 text-[#1877F2] rounded border-slate-300 focus:ring-[#1877F2] cursor-pointer"
                />
                <span class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200">
                  Select All ({{ filteredFriends.length }})
                </span>
              </label>

              <span class="text-xs font-semibold text-[#1877F2]">
                {{ form.invited_user_ids.length }} selected
              </span>
            </div>

            <!-- Friends List -->
            <div class="space-y-2 max-h-[360px] overflow-y-auto pr-1">
              <div
                v-for="friend in filteredFriends"
                :key="friend.id"
                @click="toggleFriendSelection(friend.id)"
                class="flex items-center justify-between p-3 rounded-2xl border transition-all cursor-pointer"
                :class="isFriendSelected(friend.id) ? 'border-[#1877F2] bg-blue-50/30 dark:bg-blue-950/20' : 'border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
              >
                <div class="flex items-center gap-3 min-w-0">
                  <input
                    type="checkbox"
                    :checked="isFriendSelected(friend.id)"
                    @click.stop="toggleFriendSelection(friend.id)"
                    class="w-4 h-4 text-[#1877F2] rounded border-slate-300 focus:ring-[#1877F2] cursor-pointer"
                  />
                  <img
                    :src="friend.avatar_url || 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80'"
                    :alt="friend.name"
                    class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-800 shrink-0"
                  />
                  <div class="min-w-0">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                      {{ friend.name }}
                    </h4>
                    <p class="text-xs text-slate-400 truncate">
                      {{ friend.mutual }}
                    </p>
                  </div>
                </div>

                <span
                  class="text-xs px-2.5 py-1 rounded-full font-semibold transition-colors"
                  :class="isFriendSelected(friend.id) ? 'bg-[#1877F2] text-white' : 'text-slate-400 bg-slate-100 dark:bg-slate-800'"
                >
                  {{ isFriendSelected(friend.id) ? 'Invited' : 'Invite' }}
                </span>
              </div>

              <div v-if="filteredFriends.length === 0" class="text-center py-8 text-slate-400 text-sm">
                No friends found matching your search.
              </div>
            </div>

            <!-- Action Bar -->
            <div class="flex items-center justify-between pt-6 border-t border-slate-100 dark:border-slate-800">
              <button
                type="button"
                @click="goToStep(1)"
                class="px-5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
              >
                Back
              </button>
              <button
                type="button"
                @click="validateAndNext(3)"
                class="px-6 py-2.5 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center gap-2"
              >
                Next
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </button>
            </div>
          </div>

          <!-- STEP 3: PRIVACY & SETTINGS -->
          <div v-show="currentStep === 3" class="space-y-6">
            <div>
              <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                3. Privacy & Settings
              </h2>
            </div>

            <!-- Group Privacy Radio Cards -->
            <div class="space-y-3">
              <label class="block text-sm font-bold text-slate-800 dark:text-slate-200">
                Group Privacy
              </label>

              <!-- Public Option -->
              <div
                @click="form.privacy = 'public'"
                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-4"
                :class="form.privacy === 'public' ? 'border-[#1877F2] bg-blue-50/30 dark:bg-blue-950/20 ring-1 ring-[#1877F2]' : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
              >
                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 mt-0.5" :class="form.privacy === 'public' ? 'bg-blue-100 text-[#1877F2] dark:bg-blue-900/50' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                  </svg>
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                      Public
                    </h4>
                    <input
                      type="radio"
                      name="group_privacy"
                      value="public"
                      :checked="form.privacy === 'public'"
                      class="w-4 h-4 text-[#1877F2] focus:ring-[#1877F2]"
                    />
                  </div>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Anyone can see the group, its members and posts.
                  </p>
                </div>
              </div>

              <!-- Private Option -->
              <div
                @click="form.privacy = 'private'"
                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-4"
                :class="form.privacy === 'private' ? 'border-[#1877F2] bg-blue-50/30 dark:bg-blue-950/20 ring-1 ring-[#1877F2]' : 'border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
              >
                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 mt-0.5" :class="form.privacy === 'private' ? 'bg-blue-100 text-[#1877F2] dark:bg-blue-900/50' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                      Private
                    </h4>
                    <input
                      type="radio"
                      name="group_privacy"
                      value="private"
                      :checked="form.privacy === 'private'"
                      class="w-4 h-4 text-[#1877F2] focus:ring-[#1877F2]"
                    />
                  </div>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Only members can see the group, its members and posts.
                  </p>
                </div>
              </div>
            </div>

            <!-- Additional Settings Toggles -->
            <div class="space-y-4 pt-2">
              <label class="block text-sm font-bold text-slate-800 dark:text-slate-200">
                Additional Settings
              </label>

              <!-- Toggle: Allow members to invite others -->
              <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <div>
                  <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                    Allow members to invite others
                  </h4>
                  <p class="text-xs text-slate-400 mt-0.5">
                    Permit existing members to send invitations to friends.
                  </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="form.allow_member_invites"
                    class="sr-only peer"
                  />
                  <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
                </label>
              </div>

              <!-- Toggle: Allow posting by members -->
              <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <div>
                  <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                    Allow posting by members
                  </h4>
                  <p class="text-xs text-slate-400 mt-0.5">
                    Members can write posts directly to the group feed.
                  </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="form.allow_member_posts"
                    class="sr-only peer"
                  />
                  <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
                </label>
              </div>

              <!-- Toggle: Require admin approval for new members -->
              <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                <div>
                  <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                    Require admin approval for new members
                  </h4>
                  <p class="text-xs text-slate-400 mt-0.5">
                    Pending join requests must be approved by admins or moderators.
                  </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="form.require_admin_approval"
                    class="sr-only peer"
                  />
                  <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1877F2]"></div>
                </label>
              </div>
            </div>

            <!-- Action Bar -->
            <div class="flex items-center justify-between pt-6 border-t border-slate-100 dark:border-slate-800">
              <button
                type="button"
                @click="goToStep(2)"
                class="px-5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
              >
                Back
              </button>
              <button
                type="button"
                @click="validateAndNext(4)"
                class="px-6 py-2.5 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center gap-2"
              >
                Next
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </button>
            </div>
          </div>

          <!-- STEP 4: REVIEW & CREATE -->
          <div v-show="currentStep === 4" class="space-y-6">
            <div>
              <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                4. Review & Create
              </h2>
              <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Review your group configuration before creating your community.
              </p>
            </div>

            <!-- Summary Cards -->
            <div class="space-y-4">
              <!-- Basic Info Summary -->
              <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Basic Info</span>
                  <button @click="goToStep(1)" class="text-xs font-bold text-[#1877F2] hover:underline">Edit</button>
                </div>
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Group Name</span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ form.name || 'Untitled Group' }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Category</span>
                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ form.category || 'Not selected' }}</span>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-slate-600 dark:text-slate-400 block mb-0.5">Description</span>
                    <p class="text-xs sm:text-sm text-slate-800 dark:text-slate-200 whitespace-pre-wrap">{{ form.description || 'No description provided.' }}</p>
                  </div>
                </div>
              </div>

              <!-- Privacy & Settings Summary -->
              <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Privacy & Rules</span>
                  <button @click="goToStep(3)" class="text-xs font-bold text-[#1877F2] hover:underline">Edit</button>
                </div>
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Privacy Mode</span>
                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full capitalize" :class="form.privacy === 'public' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300'">
                      {{ form.privacy }} Group
                    </span>
                  </div>
                  <div class="flex items-center justify-between text-xs sm:text-sm">
                    <span class="text-slate-600 dark:text-slate-400">Member Invitations</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ form.allow_member_invites ? 'Allowed' : 'Admin only' }}</span>
                  </div>
                  <div class="flex items-center justify-between text-xs sm:text-sm">
                    <span class="text-slate-600 dark:text-slate-400">Member Posting</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ form.allow_member_posts ? 'Direct posting' : 'Admin approval' }}</span>
                  </div>
                  <div class="flex items-center justify-between text-xs sm:text-sm">
                    <span class="text-slate-600 dark:text-slate-400">New Member Approval</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ form.require_admin_approval ? 'Required' : 'Instant join' }}</span>
                  </div>
                </div>
              </div>

              <!-- Members Summary -->
              <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Invitations</span>
                  <button @click="goToStep(2)" class="text-xs font-bold text-[#1877F2] hover:underline">Edit</button>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Initial Invitations</span>
                  <span class="text-sm font-bold text-[#1877F2]">{{ form.invited_user_ids.length }} friends invited</span>
                </div>
              </div>
            </div>

            <!-- Action Bar with Submit Button -->
            <div class="flex items-center justify-between pt-6 border-t border-slate-100 dark:border-slate-800">
              <button
                type="button"
                @click="goToStep(3)"
                class="px-5 py-2.5 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                :disabled="isSubmitting"
              >
                Back
              </button>
              <button
                type="button"
                @click="submitGroupCreation"
                :disabled="isSubmitting"
                class="px-7 py-3 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-lg shadow-blue-500/25 transition-all flex items-center gap-2.5 disabled:opacity-50"
              >
                <div v-if="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                <span>{{ isSubmitting ? 'Creating Group...' : 'Create Group' }}</span>
                <svg v-if="!isSubmitting" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </button>
            </div>
          </div>

        </div>
      </div>

      <!-- Right Column: Live Group Preview Panel -->
      <div class="lg:col-span-5 xl:col-span-5 sticky top-24">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
          
          <!-- Live Preview Header -->
          <div class="p-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
              Live Preview
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              This is how your group will appear to members.
            </p>
          </div>

          <!-- Preview Card Body -->
          <div class="p-4 sm:p-5">
            <!-- Group Cover Banner Preview -->
            <div class="relative h-36 sm:h-44 rounded-2xl overflow-hidden bg-gradient-to-r from-blue-500 to-indigo-600 shadow-inner">
              <img
                :src="coverPreview || 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1200&q=80'"
                alt="Cover Preview"
                class="w-full h-full object-cover"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
            </div>

            <!-- Group Profile Avatar & Meta -->
            <div class="relative px-2 pt-0 pb-3 flex flex-col items-center text-center -mt-10 sm:-mt-12">
              <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full ring-4 ring-white dark:ring-slate-900 shadow-lg overflow-hidden bg-[#1877F2] flex items-center justify-center text-white">
                <img
                  v-if="avatarPreview"
                  :src="avatarPreview"
                  alt="Avatar Preview"
                  class="w-full h-full object-cover"
                />
                <svg v-else class="w-10 h-10 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
              </div>

              <!-- Group Name & Privacy Preview -->
              <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white mt-3 truncate max-w-full">
                {{ form.name || 'Group Name' }}
              </h3>
              
              <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 mt-1">
                <span class="flex items-center gap-1">
                  <svg v-if="form.privacy === 'public'" class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                  </svg>
                  <svg v-else class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  {{ form.privacy === 'public' ? 'Public Group' : 'Private Group' }}
                </span>
                <span>•</span>
                <span>1 member</span>
                <span v-if="form.category">•</span>
                <span v-if="form.category" class="text-[#1877F2] font-bold">{{ form.category }}</span>
              </div>

              <!-- Group Navigation Tabs Mock -->
              <div class="flex items-center justify-center gap-4 mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 w-full text-xs font-bold text-slate-500">
                <span class="text-[#1877F2] border-b-2 border-[#1877F2] pb-1">About</span>
                <span class="hover:text-slate-800 dark:hover:text-slate-200">Posts</span>
                <span class="hover:text-slate-800 dark:hover:text-slate-200">Members</span>
                <span class="hover:text-slate-800 dark:hover:text-slate-200 flex items-center gap-0.5">
                  More
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </span>
              </div>

              <!-- Join Button Mock (as in design) -->
              <button
                type="button"
                disabled
                class="w-full mt-4 py-2.5 rounded-2xl bg-[#1877F2] text-white text-sm font-bold shadow-md shadow-blue-500/20 cursor-default opacity-95"
              >
                Join Group
              </button>
            </div>

            <!-- Group Description Section -->
            <div class="mt-4 p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                Group Description
              </h4>
              <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
                {{ form.description || 'This is a preview of your group. Add a description to let people know what your group is about.' }}
              </p>
            </div>

            <!-- Group Guidelines Card (Matching Mockup) -->
            <div class="mt-4 p-4 rounded-2xl bg-blue-50/40 dark:bg-blue-950/20 border border-blue-100/80 dark:border-blue-900/40">
              <h4 class="text-xs font-bold uppercase tracking-wider text-[#1877F2] mb-2.5">
                Group Guidelines
              </h4>
              <ul class="space-y-2 text-xs text-slate-700 dark:text-slate-300 font-medium">
                <li class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                  </svg>
                  <span>Be respectful and kind</span>
                </li>
                <li class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                  </svg>
                  <span>No spam or self promotion</span>
                </li>
                <li class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                  </svg>
                  <span>Follow community rules</span>
                </li>
              </ul>
              <div class="mt-2.5 pt-2 border-t border-blue-100 dark:border-blue-900/40">
                <span class="text-xs font-bold text-[#1877F2] hover:underline cursor-pointer">See more</span>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { User, SuggestedFriend, UpcomingEvent } from '../../types';

interface Category {
  id: string;
  name: string;
  slug: string;
}

interface FriendItem {
  id: number;
  name: string;
  username: string;
  avatar_url: string | null;
  mutual_friends_count: number;
  mutual: string;
}

const props = withDefaults(
  defineProps<{
    categories: Category[];
    friends: FriendItem[];
    pendingRequests?: any[];
    contacts?: User[];
    suggestions?: SuggestedFriend[];
    upcomingEvents?: UpcomingEvent[];
    unreadCount?: number;
    unreadMessagesCount?: number;
  }>(),
  {
    categories: () => [],
    friends: () => [],
    pendingRequests: () => [],
    contacts: () => [],
    suggestions: () => [],
    upcomingEvents: () => [],
    unreadCount: 0,
    unreadMessagesCount: 0,
  }
);

// Form Wizard State
const currentStep = ref<number>(1);
const errorMessage = ref<string | null>(null);
const isSubmitting = ref<boolean>(false);

// Upload States
const isUploadingAvatar = ref<boolean>(false);
const isUploadingCover = ref<boolean>(false);
const avatarInputRef = ref<HTMLInputElement | null>(null);
const coverInputRef = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(null);
const coverPreview = ref<string | null>(null);

// Search State in Step 2
const friendSearch = ref<string>('');

// Main Form Data
const form = reactive({
  name: '',
  category: '',
  description: '',
  avatar_url: '',
  cover_image_url: '',
  privacy: 'public' as 'public' | 'private',
  allow_member_invites: true,
  allow_member_posts: true,
  require_admin_approval: false,
  invited_user_ids: [] as number[],
  rules: [
    { title: 'Be respectful and kind', description: 'Treat fellow community members with dignity and respect.' },
    { title: 'No spam or self-promotion', description: 'Promotional spam and unsolicited links are prohibited.' },
    { title: 'Follow community rules', description: 'Adhere to group standards and platform guidelines.' },
  ],
});

// Inline Validation Errors
const errors = reactive({
  name: '',
  category: '',
  description: '',
});

// Friends Filtered by Search
const filteredFriends = computed(() => {
  if (!friendSearch.value.trim()) {
    return props.friends;
  }
  const q = friendSearch.value.toLowerCase();
  return props.friends.filter(f =>
    f.name.toLowerCase().includes(q) || f.username.toLowerCase().includes(q)
  );
});

// Select All Toggle
const isAllSelected = computed(() => {
  if (filteredFriends.value.length === 0) return false;
  return filteredFriends.value.every(f => form.invited_user_ids.includes(f.id));
});

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    // Unselect all visible in current filter
    const visibleIds = filteredFriends.value.map(f => f.id);
    form.invited_user_ids = form.invited_user_ids.filter(id => !visibleIds.includes(id));
  } else {
    // Select all visible
    const newIds = new Set([...form.invited_user_ids, ...filteredFriends.value.map(f => f.id)]);
    form.invited_user_ids = Array.from(newIds);
  }
};

const isFriendSelected = (id: number): boolean => {
  return form.invited_user_ids.includes(id);
};

const toggleFriendSelection = (id: number) => {
  const index = form.invited_user_ids.indexOf(id);
  if (index > -1) {
    form.invited_user_ids.splice(index, 1);
  } else {
    form.invited_user_ids.push(id);
  }
};

// Navigation between steps
const goToStep = (step: number) => {
  if (step > currentStep.value) {
    validateAndNext(step);
  } else {
    currentStep.value = step;
    errorMessage.value = null;
  }
};

const validateAndNext = (targetStep: number) => {
  errorMessage.value = null;

  if (currentStep.value === 1) {
    errors.name = '';
    errors.category = '';
    errors.description = '';

    let hasError = false;
    if (!form.name.trim()) {
      errors.name = 'Group name is required.';
      hasError = true;
    } else if (form.name.trim().length < 3) {
      errors.name = 'Group name must be at least 3 characters.';
      hasError = true;
    }

    if (!form.category) {
      errors.category = 'Please select a category.';
      hasError = true;
    }

    if (!form.description.trim()) {
      errors.description = 'Group description is required.';
      hasError = true;
    } else if (form.description.trim().length < 10) {
      errors.description = 'Description should be at least 10 characters.';
      hasError = true;
    }

    if (hasError) {
      errorMessage.value = 'Please correct the highlighted fields before proceeding.';
      return;
    }
  }

  currentStep.value = targetStep;
};

// Avatar File Handlers
const getCsrfToken = (): string => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
};

// Avatar File Handlers
const triggerAvatarUpload = () => {
  avatarInputRef.value?.click();
};

const handleAvatarFileSelect = async (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  // Local instant preview
  avatarPreview.value = URL.createObjectURL(file);

  const formData = new FormData();
  formData.append('avatar', file);
  formData.append('type', 'avatar');

  isUploadingAvatar.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch('/api/v1/groups/media/upload', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: formData,
    });
    const resData = await res.json();
    if (res.ok && resData.data?.url) {
      form.avatar_url = resData.data.url;
      avatarPreview.value = resData.data.url;
    } else {
      errorMessage.value = resData.message || 'Failed to upload group photo.';
    }
  } catch (err: any) {
    console.error('Avatar upload failed', err);
    errorMessage.value = 'Failed to upload group photo.';
  } finally {
    isUploadingAvatar.value = false;
  }
};

const removeAvatar = () => {
  avatarPreview.value = null;
  form.avatar_url = '';
  if (avatarInputRef.value) {
    avatarInputRef.value.value = '';
  }
};

// Cover File Handlers
const triggerCoverUpload = () => {
  coverInputRef.value?.click();
};

const handleCoverFileSelect = async (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  // Local instant preview
  coverPreview.value = URL.createObjectURL(file);

  const formData = new FormData();
  formData.append('cover', file);
  formData.append('type', 'cover');

  isUploadingCover.value = true;
  try {
    const csrf = getCsrfToken();
    const res = await fetch('/api/v1/groups/media/upload', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: formData,
    });
    const resData = await res.json();
    if (res.ok && resData.data?.url) {
      form.cover_image_url = resData.data.url;
      coverPreview.value = resData.data.url;
    } else {
      errorMessage.value = resData.message || 'Failed to upload cover photo.';
    }
  } catch (err: any) {
    console.error('Cover upload failed', err);
    errorMessage.value = 'Failed to upload cover photo.';
  } finally {
    isUploadingCover.value = false;
  }
};

const removeCover = () => {
  coverPreview.value = null;
  form.cover_image_url = '';
  if (coverInputRef.value) {
    coverInputRef.value.value = '';
  }
};

// Final Submission
const submitGroupCreation = async () => {
  isSubmitting.value = true;
  errorMessage.value = null;

  try {
    const payload = {
      name: form.name.trim(),
      category: form.category,
      description: form.description.trim(),
      avatar_url: form.avatar_url || null,
      cover_image_url: form.cover_image_url || null,
      privacy: form.privacy,
      allow_member_invites: form.allow_member_invites,
      allow_member_posts: form.allow_member_posts,
      require_admin_approval: form.require_admin_approval,
      invited_user_ids: form.invited_user_ids,
      rules: form.rules,
    };

    const csrf = getCsrfToken();
    const res = await fetch('/api/v1/groups', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify(payload),
    });

    const resData = await res.json();
    if (res.ok && resData.success && resData.data) {
      const group = resData.data;
      const targetUrl = group.slug ? `/groups/${group.slug}` : `/groups/${group.id}`;
      router.visit(targetUrl);
    } else {
      if (resData.errors) {
        const firstKey = Object.keys(resData.errors)[0];
        errorMessage.value = resData.errors[firstKey][0];
      } else {
        errorMessage.value = resData.message || 'Failed to create group.';
      }
    }
  } catch (err: any) {
    console.error('Group creation failed', err);
    errorMessage.value = 'An unexpected error occurred while creating the group.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.2s ease-out forwards;
}
</style>
