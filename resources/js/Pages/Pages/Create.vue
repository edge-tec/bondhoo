<template>
  <AppLayout
    current-route="pages"
    :unread-count="unreadCount"
    :unread-messages-count="unreadMessagesCount"
    :pending-requests="pendingRequests"
    :contacts="contacts"
    :suggestions="suggestions"
    :upcoming-events="upcomingEvents"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <!-- Header Section with Flag Icon -->
    <div class="mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-[#1877F2] shadow-sm">
          <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            Create a Page
          </h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            Build your brand, business or community on SocialHub
          </p>
        </div>
      </div>
    </div>

    <!-- Error Alert Banner -->
    <div v-if="submissionError" class="mb-6 p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-2xl text-sm text-rose-700 dark:text-rose-300 flex items-start gap-3 shadow-sm animate-fade-in">
      <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      <div class="flex-1">
        <h4 class="font-bold">Creation Notice</h4>
        <p class="mt-0.5">{{ submissionError }}</p>
      </div>
      <button @click="submissionError = null" class="text-rose-400 hover:text-rose-600 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Desktop 2-Column Layout / Mobile Stacked Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
      <!-- Left/Center Column: Multi-Step Wizard Form -->
      <div class="lg:col-span-7 xl:col-span-7 space-y-6">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 sm:p-8">
          <!-- Stepper Navigation -->
          <div class="flex items-center justify-between max-w-lg mx-auto mb-8 relative">
            <!-- Step 1 -->
            <div class="flex items-center gap-2.5 z-10">
              <button
                type="button"
                @click="goToStep(1)"
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all',
                  currentStep === 1
                    ? 'bg-[#1877F2] text-white shadow-md shadow-blue-500/20'
                    : currentStep > 1
                    ? 'bg-emerald-500 text-white'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700'
                ]"
              >
                <svg v-if="currentStep > 1" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span v-else>1</span>
              </button>
              <span
                :class="[
                  'text-xs font-bold transition-colors hidden sm:inline',
                  currentStep === 1 ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'
                ]"
              >
                Page Information
              </span>
            </div>

            <!-- Divider 1 -->
            <div
              :class="[
                'h-0.5 flex-1 mx-3 sm:mx-4 transition-colors',
                currentStep > 1 ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-800'
              ]"
            ></div>

            <!-- Step 2 -->
            <div class="flex items-center gap-2.5 z-10">
              <button
                type="button"
                @click="goToStep(2)"
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all',
                  currentStep === 2
                    ? 'bg-[#1877F2] text-white shadow-md shadow-blue-500/20'
                    : currentStep > 2
                    ? 'bg-emerald-500 text-white'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700'
                ]"
              >
                <svg v-if="currentStep > 2" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span v-else>2</span>
              </button>
              <span
                :class="[
                  'text-xs font-bold transition-colors hidden sm:inline',
                  currentStep === 2 ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'
                ]"
              >
                Profile & Cover
              </span>
            </div>

            <!-- Divider 2 -->
            <div
              :class="[
                'h-0.5 flex-1 mx-3 sm:mx-4 transition-colors',
                currentStep > 2 ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-800'
              ]"
            ></div>

            <!-- Step 3 -->
            <div class="flex items-center gap-2.5 z-10">
              <button
                type="button"
                @click="goToStep(3)"
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all',
                  currentStep === 3
                    ? 'bg-[#1877F2] text-white shadow-md shadow-blue-500/20'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700'
                ]"
              >
                3
              </button>
              <span
                :class="[
                  'text-xs font-bold transition-colors hidden sm:inline',
                  currentStep === 3 ? 'text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'
                ]"
              >
                Review & Create
              </span>
            </div>
          </div>

          <!-- STEP 1: Page Details & Privacy -->
          <div v-show="currentStep === 1" class="space-y-8 animate-fade-in">
            <!-- 1. Page Details Section -->
            <div class="space-y-5">
              <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">
                  1. Page Details
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Tell people about your Page. You can always edit this later.
                </p>
              </div>

              <!-- Page Name Input -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                  Page Name <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <input
                    v-model="form.name"
                    type="text"
                    maxlength="100"
                    placeholder="Enter page name"
                    @input="onNameInput"
                    :class="[
                      'w-full px-4 py-2.5 rounded-xl border text-sm transition-all focus:outline-none focus:ring-2 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white',
                      errors.name
                        ? 'border-rose-400 focus:ring-rose-500/30'
                        : 'border-slate-300 dark:border-slate-700 focus:border-[#1877F2] focus:ring-[#1877F2]/20'
                    ]"
                  />
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span v-if="errors.name" class="text-rose-500 font-medium">{{ errors.name }}</span>
                  <span v-else class="text-slate-400 dark:text-slate-500">Use a recognizable name for your business or brand.</span>
                  <span class="text-slate-400 font-medium ml-auto">{{ form.name.length }}/100</span>
                </div>
              </div>

              <!-- Page Handle / Username Input -->
              <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                  <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    Page Username / Handle <span class="text-slate-400 font-normal lowercase">(optional)</span>
                  </label>
                  <button
                    v-if="isCustomUsername"
                    type="button"
                    @click="resetUsernameToAuto"
                    class="text-[11px] text-[#1877F2] hover:underline"
                  >
                    Auto-generate from Name
                  </button>
                </div>
                <div class="relative flex items-center">
                  <span class="absolute left-3.5 text-slate-400 font-bold text-sm">@</span>
                  <input
                    v-model="form.username"
                    type="text"
                    maxlength="50"
                    placeholder="yourpagename"
                    @input="onUsernameInput"
                    :class="[
                      'w-full pl-8 pr-10 py-2.5 rounded-xl border text-sm transition-all focus:outline-none focus:ring-2 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white',
                      errors.username
                        ? 'border-rose-400 focus:ring-rose-500/30'
                        : 'border-slate-300 dark:border-slate-700 focus:border-[#1877F2] focus:ring-[#1877F2]/20'
                    ]"
                  />
                  <!-- Availability Indicator -->
                  <div class="absolute right-3.5 flex items-center">
                    <span v-if="form.username.length >= 3" class="text-emerald-500" title="Valid username">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                      </svg>
                    </span>
                  </div>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span v-if="errors.username" class="text-rose-500 font-medium">{{ errors.username }}</span>
                  <span v-else class="text-slate-400 dark:text-slate-500 truncate">
                    socialhub.com/pages/@{{ previewSlug }}
                  </span>
                  <span class="text-slate-400 font-medium ml-auto">{{ form.username.length }}/50</span>
                </div>
              </div>

              <!-- Page Category Select -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                  Category <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <select
                    v-model="form.category"
                    @change="onCategoryChange"
                    :class="[
                      'w-full appearance-none px-4 py-2.5 rounded-xl border text-sm transition-all focus:outline-none focus:ring-2 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white pr-10',
                      errors.category
                        ? 'border-rose-400 focus:ring-rose-500/30'
                        : 'border-slate-300 dark:border-slate-700 focus:border-[#1877F2] focus:ring-[#1877F2]/20'
                    ]"
                  >
                    <option value="" disabled selected>Select a category</option>
                    <option
                      v-for="cat in availableCategories"
                      :key="cat.id || cat.name"
                      :value="cat.name"
                    >
                      {{ cat.name }}
                    </option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center px-3.5 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </div>
                </div>
                <span v-if="errors.category" class="text-xs text-rose-500 font-medium block">{{ errors.category }}</span>
              </div>

              <!-- Page Description Input -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                  Description <span class="text-rose-500">*</span>
                </label>
                <textarea
                  v-model="form.description"
                  rows="4"
                  maxlength="500"
                  placeholder="Tell people what your Page is about..."
                  :class="[
                    'w-full px-4 py-2.5 rounded-xl border text-sm transition-all focus:outline-none focus:ring-2 bg-slate-50/50 dark:bg-slate-800/50 dark:text-white',
                    errors.description
                      ? 'border-rose-400 focus:ring-rose-500/30'
                      : 'border-slate-300 dark:border-slate-700 focus:border-[#1877F2] focus:ring-[#1877F2]/20'
                  ]"
                ></textarea>
                <div class="flex items-center justify-between text-xs">
                  <span v-if="errors.description" class="text-rose-500 font-medium">{{ errors.description }}</span>
                  <span v-else class="text-slate-400 dark:text-slate-500">Provide details so visitors know what to expect.</span>
                  <span class="text-slate-400 font-medium ml-auto">{{ form.description.length }}/500</span>
                </div>
              </div>
            </div>

            <!-- 2. Page Privacy Section -->
            <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
              <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">
                  2. Page Privacy
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Choose who can see and interact with your Page.
                </p>
              </div>

              <div class="space-y-3">
                <!-- Option 1: Public -->
                <div
                  @click="form.visibility = 'public'"
                  :class="[
                    'flex items-center justify-between p-4 rounded-2xl border cursor-pointer transition-all select-none',
                    form.visibility === 'public'
                      ? 'border-[#1877F2] bg-blue-50/40 dark:bg-blue-950/20 shadow-sm'
                      : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                  ]"
                >
                  <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-[#1877F2]">
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                      </svg>
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-slate-900 dark:text-white">Public</h4>
                      <p class="text-xs text-slate-500 dark:text-slate-400">Anyone can see, like and follow your Page.</p>
                    </div>
                  </div>
                  <div class="relative flex items-center justify-center">
                    <div
                      :class="[
                        'w-5 h-5 rounded-full border flex items-center justify-center transition-all',
                        form.visibility === 'public'
                          ? 'border-[#1877F2] bg-[#1877F2]'
                          : 'border-slate-300 dark:border-slate-600'
                      ]"
                    >
                      <div v-if="form.visibility === 'public'" class="w-2 h-2 rounded-full bg-white"></div>
                    </div>
                  </div>
                </div>

                <!-- Option 2: Friends -->
                <div
                  @click="form.visibility = 'friends'"
                  :class="[
                    'flex items-center justify-between p-4 rounded-2xl border cursor-pointer transition-all select-none',
                    form.visibility === 'friends'
                      ? 'border-[#1877F2] bg-blue-50/40 dark:bg-blue-950/20 shadow-sm'
                      : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                  ]"
                >
                  <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300">
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-slate-900 dark:text-white">Friends</h4>
                      <p class="text-xs text-slate-500 dark:text-slate-400">Only your friends can see and interact with your Page.</p>
                    </div>
                  </div>
                  <div class="relative flex items-center justify-center">
                    <div
                      :class="[
                        'w-5 h-5 rounded-full border flex items-center justify-center transition-all',
                        form.visibility === 'friends'
                          ? 'border-[#1877F2] bg-[#1877F2]'
                          : 'border-slate-300 dark:border-slate-600'
                      ]"
                    >
                      <div v-if="form.visibility === 'friends'" class="w-2 h-2 rounded-full bg-white"></div>
                    </div>
                  </div>
                </div>

                <!-- Option 3: Private -->
                <div
                  @click="form.visibility = 'private'"
                  :class="[
                    'flex items-center justify-between p-4 rounded-2xl border cursor-pointer transition-all select-none',
                    form.visibility === 'private'
                      ? 'border-[#1877F2] bg-blue-50/40 dark:bg-blue-950/20 shadow-sm'
                      : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                  ]"
                >
                  <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300">
                      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                      </svg>
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-slate-900 dark:text-white">Private</h4>
                      <p class="text-xs text-slate-500 dark:text-slate-400">Only you can see and manage your Page.</p>
                    </div>
                  </div>
                  <div class="relative flex items-center justify-center">
                    <div
                      :class="[
                        'w-5 h-5 rounded-full border flex items-center justify-center transition-all',
                        form.visibility === 'private'
                          ? 'border-[#1877F2] bg-[#1877F2]'
                          : 'border-slate-300 dark:border-slate-600'
                      ]"
                    >
                      <div v-if="form.visibility === 'private'" class="w-2 h-2 rounded-full bg-white"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 1 Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-slate-100 dark:border-slate-800">
              <a
                href="/app"
                class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all"
              >
                Cancel
              </a>
              <button
                type="button"
                @click="validateStep1AndNext"
                class="px-6 py-2.5 rounded-xl bg-[#1877F2] hover:bg-blue-600 active:scale-95 text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center gap-2"
              >
                <span>Next</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </button>
            </div>
          </div>

          <!-- STEP 2: Profile & Cover Photos + Contact Details -->
          <div v-show="currentStep === 2" class="space-y-8 animate-fade-in">
            <!-- Profile Photo Section -->
            <div class="space-y-3">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Profile Photo</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  Add an image that helps people identify your Page in search results and posts.
                </p>
              </div>

              <div class="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-2xl bg-slate-50/70 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700">
                <div
                  @click="triggerAvatarPicker"
                  class="relative w-24 h-24 rounded-full overflow-hidden ring-4 ring-white dark:ring-slate-800 shadow-md shrink-0 bg-blue-50 dark:bg-blue-950 flex items-center justify-center cursor-pointer group"
                  title="Click to choose profile photo"
                >
                  <img
                    v-if="avatarPreview"
                    :src="avatarPreview"
                    alt="Profile Preview"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                  />
                  <div v-else class="text-[#1877F2] group-hover:scale-110 transition-transform">
                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                    </svg>
                  </div>
                  <!-- Uploading spinner -->
                  <div v-if="isUploadingAvatar" class="absolute inset-0 bg-black/50 flex items-center justify-center">
                    <svg class="animate-spin w-6 h-6 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                  </div>
                  <!-- Camera overlay icon -->
                  <div class="absolute bottom-0 inset-x-0 bg-black/40 py-0.5 text-center text-white text-[10px] opacity-0 group-hover:opacity-100 transition-opacity">
                    Edit
                  </div>
                </div>

                <div class="flex-1 space-y-2 text-center sm:text-left">
                  <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                    <button
                      type="button"
                      @click="triggerAvatarPicker"
                      class="px-4 py-2 rounded-xl bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 hover:border-[#1877F2] text-xs font-bold text-slate-700 dark:text-slate-200 cursor-pointer shadow-sm hover:shadow transition-all inline-flex items-center gap-1.5"
                    >
                      <svg class="w-4 h-4 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span>{{ avatarPreview ? 'Change Profile Photo' : 'Upload Profile Photo' }}</span>
                    </button>
                    <input
                      ref="avatarInputRef"
                      type="file"
                      accept="image/png,image/jpeg,image/webp,image/gif"
                      class="hidden"
                      @change="handleAvatarUpload"
                    />
                    <button
                      v-if="avatarPreview"
                      type="button"
                      @click="removeAvatar"
                      class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-all"
                    >
                      Remove
                    </button>
                  </div>
                  <p class="text-xs text-slate-400 dark:text-slate-500">
                    Recommended: Square format, PNG or JPG, max 10MB.
                  </p>
                </div>
              </div>
            </div>

            <!-- Cover Photo Section -->
            <div class="space-y-3 pt-4 border-t border-slate-100 dark:border-slate-800">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Cover Photo</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  Select a wide banner that displays your community or business background.
                </p>
              </div>

              <div class="space-y-3">
                <div class="relative w-full h-44 sm:h-52 rounded-2xl overflow-hidden border border-slate-200/80 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                  <img
                    :src="coverPreview || fallbackCover"
                    alt="Cover Preview"
                    class="w-full h-full object-cover"
                  />
                  <!-- Uploading spinner -->
                  <div v-if="isUploadingCover" class="absolute inset-0 bg-black/40 flex items-center justify-center">
                    <svg class="animate-spin w-8 h-8 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                  </div>
                  <!-- Overlay trigger button -->
                  <div class="absolute bottom-3 right-3 flex items-center gap-2">
                    <button
                      type="button"
                      @click="triggerCoverPicker"
                      class="px-3.5 py-2 rounded-xl bg-black/60 hover:bg-black/80 backdrop-blur text-white text-xs font-bold cursor-pointer transition-all inline-flex items-center gap-1.5 shadow-md"
                    >
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <span>{{ coverPreview ? 'Change Cover Photo' : 'Upload Cover Photo' }}</span>
                    </button>
                    <input
                      ref="coverInputRef"
                      type="file"
                      accept="image/png,image/jpeg,image/webp,image/gif"
                      class="hidden"
                      @change="handleCoverUpload"
                    />
                    <button
                      v-if="coverPreview"
                      type="button"
                      @click="removeCover"
                      class="px-3 py-2 rounded-xl bg-black/60 hover:bg-black/80 backdrop-blur text-rose-300 text-xs font-bold shadow-md transition-all"
                    >
                      Reset
                    </button>
                  </div>
                </div>
                <p class="text-xs text-slate-400 dark:text-slate-500">
                  Recommended: 1200x450 pixels or wide landscape photo.
                </p>
              </div>
            </div>

            <!-- Optional Contact Information Section -->
            <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Contact & Location (Optional)</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  Help people reach your business directly from your Page.
                </p>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Website -->
                <div class="space-y-1">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Website URL</label>
                  <input
                    v-model="form.website"
                    type="text"
                    placeholder="https://example.com"
                    class="w-full px-3.5 py-2.2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2]/20 focus:border-[#1877F2] dark:text-white"
                  />
                </div>

                <!-- Email -->
                <div class="space-y-1">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Contact Email</label>
                  <input
                    v-model="form.email"
                    type="email"
                    placeholder="contact@page.com"
                    class="w-full px-3.5 py-2.2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2]/20 focus:border-[#1877F2] dark:text-white"
                  />
                </div>

                <!-- Phone -->
                <div class="space-y-1">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Phone Number</label>
                  <input
                    v-model="form.phone"
                    type="tel"
                    placeholder="+880 1700 000000"
                    class="w-full px-3.5 py-2.2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2]/20 focus:border-[#1877F2] dark:text-white"
                  />
                </div>

                <!-- City -->
                <div class="space-y-1">
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">City / Location</label>
                  <input
                    v-model="form.city"
                    type="text"
                    placeholder="Dhaka, Bangladesh"
                    class="w-full px-3.5 py-2.2 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 text-sm focus:outline-none focus:ring-2 focus:ring-[#1877F2]/20 focus:border-[#1877F2] dark:text-white"
                  />
                </div>
              </div>
            </div>

            <!-- Step 2 Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-slate-100 dark:border-slate-800">
              <button
                type="button"
                @click="goToStep(1)"
                class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center gap-2"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Previous</span>
              </button>
              <button
                type="button"
                @click="goToStep(3)"
                class="px-6 py-2.5 rounded-xl bg-[#1877F2] hover:bg-blue-600 active:scale-95 text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center gap-2"
              >
                <span>Next</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
              </button>
            </div>
          </div>

          <!-- STEP 3: Review & Create -->
          <div v-show="currentStep === 3" class="space-y-6 animate-fade-in">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white">
                Review & Confirm
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Review your Page details before publishing. You can edit all details at any time after creation.
              </p>
            </div>

            <!-- Summary Card -->
            <div class="p-5 rounded-2xl bg-slate-50/80 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700 space-y-4">
              <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full overflow-hidden bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center shrink-0 ring-2 ring-blue-500/20">
                  <img
                    v-if="avatarPreview"
                    :src="avatarPreview"
                    alt="Avatar"
                    class="w-full h-full object-cover"
                  />
                  <svg v-else class="w-8 h-8 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ form.name || 'Untitled Page' }}</h3>
                  <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs text-[#1877F2] font-semibold">@{{ previewSlug }}</span>
                    <span class="text-slate-300 dark:text-slate-600">•</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 dark:bg-blue-950/60 text-[#1877F2]">
                      {{ form.category || 'General' }}
                    </span>
                    <span class="text-slate-300 dark:text-slate-600">•</span>
                    <span class="capitalize text-xs text-slate-500 dark:text-slate-400 font-medium">
                      {{ form.visibility }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="border-t border-slate-200/60 dark:border-slate-700/60 pt-3 text-xs text-slate-600 dark:text-slate-300">
                <p class="font-semibold text-slate-800 dark:text-slate-200 mb-1">About:</p>
                <p class="whitespace-pre-wrap leading-relaxed">{{ form.description || 'No description provided.' }}</p>
              </div>

              <!-- Contact Overview if provided -->
              <div v-if="form.website || form.email || form.phone || form.city" class="border-t border-slate-200/60 dark:border-slate-700/60 pt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600 dark:text-slate-400">
                <div v-if="form.website" class="flex items-center gap-2 truncate">
                  <span class="font-semibold text-slate-700 dark:text-slate-300">Website:</span>
                  <a :href="formattedWebsiteUrl" target="_blank" class="text-[#1877F2] hover:underline truncate">{{ form.website }}</a>
                </div>
                <div v-if="form.email" class="flex items-center gap-2 truncate">
                  <span class="font-semibold text-slate-700 dark:text-slate-300">Email:</span>
                  <span>{{ form.email }}</span>
                </div>
                <div v-if="form.phone" class="flex items-center gap-2 truncate">
                  <span class="font-semibold text-slate-700 dark:text-slate-300">Phone:</span>
                  <span>{{ form.phone }}</span>
                </div>
                <div v-if="form.city" class="flex items-center gap-2 truncate">
                  <span class="font-semibold text-slate-700 dark:text-slate-300">Location:</span>
                  <span>{{ form.city }}</span>
                </div>
              </div>
            </div>

            <!-- Agreement Disclaimer -->
            <div class="p-4 rounded-xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 text-xs text-slate-600 dark:text-slate-400 flex items-start gap-3">
              <svg class="w-5 h-5 text-[#1877F2] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <p>
                  By clicking <strong class="text-slate-900 dark:text-white">Create Page</strong>, you agree to the SocialHub Pages Terms and Community Standards. You will automatically receive the Owner and Administrator role for this Page.
                </p>
              </div>
            </div>

            <!-- Step 3 Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-slate-100 dark:border-slate-800">
              <button
                type="button"
                @click="goToStep(2)"
                class="px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center gap-2"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Previous</span>
              </button>

              <button
                type="button"
                @click="submitCreatePage"
                :disabled="isSubmitting"
                class="px-8 py-2.5 rounded-xl bg-[#1877F2] hover:bg-blue-600 active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center gap-2.5"
              >
                <svg v-if="isSubmitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>{{ isSubmitting ? 'Creating Page...' : 'Create Page' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Live Page Preview, Tips, and Help Center -->
      <div class="lg:col-span-5 xl:col-span-5 space-y-5">
        <!-- 1. Live Page Preview Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5 space-y-3">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white">Page Preview</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">This is how your Page will appear to others.</p>
            </div>
            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              <span>Live</span>
            </span>
          </div>

          <!-- The Live Interactive Preview Box -->
          <div class="rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden bg-white dark:bg-slate-900 shadow-sm">
            <!-- Cover Photo with 📷 Edit Button -->
            <div class="relative w-full h-36 sm:h-44 bg-slate-200 dark:bg-slate-800">
              <img
                :src="coverPreview || fallbackCover"
                alt="Page Cover"
                class="w-full h-full object-cover transition-all"
              />
              <button
                type="button"
                @click="onCoverEditClick"
                class="absolute bottom-2.5 right-2.5 px-3 py-1.5 rounded-lg bg-black/60 hover:bg-black/80 backdrop-blur text-white text-xs font-semibold shadow-md transition-all flex items-center gap-1.5 active:scale-95"
                title="Edit or change cover photo"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Edit</span>
              </button>
            </div>

            <!-- Header Info & Avatar Box -->
            <div class="px-5 pb-5 pt-0 relative">
              <!-- Overlapping Circular Avatar with Camera Badge -->
              <div class="relative -mt-12 mb-3 inline-block">
                <div
                  @click="onAvatarEditClick"
                  class="w-20 h-20 rounded-full border-4 border-white dark:border-slate-900 overflow-hidden shadow-md bg-blue-50 dark:bg-blue-950 flex items-center justify-center cursor-pointer group"
                  title="Click to edit avatar"
                >
                  <img
                    v-if="avatarPreview"
                    :src="avatarPreview"
                    alt="Page Avatar"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                  />
                  <div v-else class="text-[#1877F2] group-hover:scale-110 transition-transform">
                    <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                    </svg>
                  </div>
                </div>
                <button
                  type="button"
                  @click="onAvatarEditClick"
                  class="absolute bottom-0 right-0 w-6 h-6 rounded-full bg-slate-800 text-white flex items-center justify-center ring-2 ring-white dark:ring-slate-900 shadow hover:bg-slate-700 transition-all active:scale-90"
                  title="Change photo"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  </svg>
                </button>
              </div>

              <!-- Page Name and Handle -->
              <div class="space-y-0.5">
                <h4 class="text-lg font-bold text-slate-900 dark:text-white truncate">
                  {{ form.name || 'Your Page Name' }}
                </h4>
                <div class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                  <span>@{{ previewSlug }}</span>
                  <span v-if="form.category" class="text-slate-300 dark:text-slate-600">•</span>
                  <span v-if="form.category" class="font-medium text-[#1877F2] truncate">{{ form.category }}</span>
                </div>
              </div>

              <!-- Interactive Page Tabs -->
              <div class="flex items-center gap-4 sm:gap-5 mt-4 border-b border-slate-100 dark:border-slate-800 text-xs font-semibold relative">
                <button
                  type="button"
                  @click="previewActiveTab = 'posts'"
                  :class="[
                    'pb-2.5 transition-all',
                    previewActiveTab === 'posts'
                      ? 'border-b-2 border-[#1877F2] text-[#1877F2]'
                      : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
                  ]"
                >
                  Posts
                </button>
                <button
                  type="button"
                  @click="previewActiveTab = 'about'"
                  :class="[
                    'pb-2.5 transition-all',
                    previewActiveTab === 'about'
                      ? 'border-b-2 border-[#1877F2] text-[#1877F2]'
                      : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
                  ]"
                >
                  About
                </button>
                <button
                  type="button"
                  @click="previewActiveTab = 'photos'"
                  :class="[
                    'pb-2.5 transition-all',
                    previewActiveTab === 'photos'
                      ? 'border-b-2 border-[#1877F2] text-[#1877F2]'
                      : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
                  ]"
                >
                  Photos
                </button>
                <div class="relative">
                  <button
                    type="button"
                    @click="showMoreDropdown = !showMoreDropdown"
                    class="pb-2.5 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex items-center gap-1 cursor-pointer"
                  >
                    <span>More</span>
                    <svg class="w-3.5 h-3.5 transition-transform" :class="showMoreDropdown ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  <!-- More Dropdown Menu -->
                  <div
                    v-if="showMoreDropdown"
                    class="absolute top-full right-0 mt-1 w-32 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 py-1 text-xs z-30"
                  >
                    <button
                      type="button"
                      @click="previewActiveTab = 'videos'; showMoreDropdown = false"
                      class="w-full text-left px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200"
                    >
                      Videos
                    </button>
                    <button
                      type="button"
                      @click="previewActiveTab = 'events'; showMoreDropdown = false"
                      class="w-full text-left px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200"
                    >
                      Events
                    </button>
                    <button
                      type="button"
                      @click="previewActiveTab = 'community'; showMoreDropdown = false"
                      class="w-full text-left px-3 py-1.5 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200"
                    >
                      Community
                    </button>
                  </div>
                </div>
              </div>

              <!-- Interactive Tab Content Preview -->
              <div class="mt-3 text-xs">
                <!-- Posts Tab Preview -->
                <div v-if="previewActiveTab === 'posts'" class="space-y-2 py-1">
                  <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 flex items-center gap-2 text-slate-400">
                    <div class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-[#1877F2]">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                      </svg>
                    </div>
                    <span>Write something to your followers...</span>
                  </div>
                </div>

                <!-- About Tab Preview -->
                <div v-else-if="previewActiveTab === 'about'" class="space-y-2 py-1 text-slate-600 dark:text-slate-300">
                  <p class="font-medium text-slate-800 dark:text-slate-100">{{ form.description || 'No description added yet.' }}</p>
                  <div class="space-y-1 text-[11px] text-slate-500 dark:text-slate-400">
                    <p v-if="form.website">🌐 {{ form.website }}</p>
                    <p v-if="form.email">✉️ {{ form.email }}</p>
                    <p v-if="form.phone">📞 {{ form.phone }}</p>
                    <p v-if="form.city">📍 {{ form.city }}</p>
                  </div>
                </div>

                <!-- Photos Tab Preview -->
                <div v-else-if="previewActiveTab === 'photos'" class="grid grid-cols-3 gap-1.5 py-1">
                  <div class="aspect-square rounded-lg bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <img :src="coverPreview || fallbackCover" alt="Photo" class="w-full h-full object-cover" />
                  </div>
                  <div class="aspect-square rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                    <span>📷</span>
                  </div>
                  <div class="aspect-square rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                    <span>📷</span>
                  </div>
                </div>

                <!-- Other Tabs -->
                <div v-else class="py-2 text-center text-slate-400">
                  <span>No {{ previewActiveTab }} available yet.</span>
                </div>
              </div>

              <!-- Action Buttons (Like / Follow and More) -->
              <div class="flex items-center gap-2.5 mt-3 relative">
                <button
                  type="button"
                  @click="togglePreviewLike"
                  :class="[
                    'flex-1 py-2 rounded-xl text-xs font-bold shadow-sm transition-all flex items-center justify-center gap-2 active:scale-95',
                    previewLiked
                      ? 'bg-blue-600 text-white'
                      : 'bg-[#1877F2] hover:bg-blue-600 text-white'
                  ]"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" />
                  </svg>
                  <span>{{ previewLiked ? 'Liked (' + previewLikesCount + ')' : 'Like' }}</span>
                </button>

                <!-- Options Button with Functional Menu -->
                <div class="relative">
                  <button
                    type="button"
                    @click="showActionMenu = !showActionMenu"
                    class="w-10 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center transition-all active:scale-95"
                    title="More actions"
                  >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                    </svg>
                  </button>
                  <div
                    v-if="showActionMenu"
                    class="absolute bottom-full right-0 mb-1 w-36 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-200 dark:border-slate-700 py-1 text-xs z-30"
                  >
                    <button
                      type="button"
                      @click="copyPreviewLink"
                      class="w-full text-left px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center gap-2"
                    >
                      <span>🔗</span>
                      <span>{{ copiedLink ? 'Copied!' : 'Copy Link' }}</span>
                    </button>
                    <button
                      type="button"
                      @click="showActionMenu = false"
                      class="w-full text-left px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center gap-2"
                    >
                      <span>👥</span>
                      <span>Invite Friends</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Tips for a successful Page Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5 space-y-3.5">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-[#1877F2]">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
            </div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">
              Tips for a successful Page
            </h4>
          </div>

          <ul class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300">
            <li class="flex items-center gap-2.5">
              <span class="text-[#1877F2] font-bold">✓</span>
              <span>Use a clear and recognizable name</span>
            </li>
            <li class="flex items-center gap-2.5">
              <span class="text-[#1877F2] font-bold">✓</span>
              <span>Add a profile and cover photo</span>
            </li>
            <li class="flex items-center gap-2.5">
              <span class="text-[#1877F2] font-bold">✓</span>
              <span>Choose the right category</span>
            </li>
            <li class="flex items-center gap-2.5">
              <span class="text-[#1877F2] font-bold">✓</span>
              <span>Write a compelling description</span>
            </li>
            <li class="flex items-center gap-2.5">
              <span class="text-[#1877F2] font-bold">✓</span>
              <span>Set the appropriate privacy level</span>
            </li>
          </ul>
        </div>

        <!-- 3. Need help? Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-5 space-y-3">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-[#1877F2]">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">
              Need help?
            </h4>
          </div>

          <p class="text-xs text-slate-500 dark:text-slate-400">
            Learn more about creating and managing Pages in our Help Center.
          </p>

          <a
            href="/help"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 text-xs font-semibold text-slate-700 dark:text-slate-200 transition-all shadow-sm active:scale-95"
          >
            <span>Visit Help Center</span>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { User, SuggestedFriend, UpcomingEvent } from '../../types';

interface PageCategoryItem {
  id?: number;
  name: string;
  slug?: string;
  page_type_id?: number;
}

const props = withDefaults(
  defineProps<{
    categories?: PageCategoryItem[];
    pendingRequests?: any[];
    contacts?: User[];
    suggestions?: SuggestedFriend[];
    upcomingEvents?: UpcomingEvent[];
    unreadCount?: number;
    unreadMessagesCount?: number;
  }>(),
  {
    categories: () => [],
    pendingRequests: () => [],
    contacts: () => [],
    suggestions: () => [],
    upcomingEvents: () => [],
    unreadCount: 0,
    unreadMessagesCount: 0,
  }
);

// Fallback high-quality cover photo matching mockup mountain landscape
const fallbackCover = 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1200&q=80';

// Current active step: 1 (Details & Privacy), 2 (Media & Contact), 3 (Review & Confirm)
const currentStep = ref(1);

// Whether user manually edited the handle/username
const isCustomUsername = ref(false);

// Form Reactive State
const form = reactive({
  name: '',
  username: '',
  category: '',
  page_category_id: null as number | null,
  description: '',
  visibility: 'public' as 'public' | 'friends' | 'private',
  avatar_url: '',
  cover_image_url: '',
  website: '',
  email: '',
  phone: '',
  address: '',
  city: '',
});

// Validation Errors
const errors = reactive({
  name: '',
  username: '',
  category: '',
  description: '',
});

// DOM input refs for file selection
const avatarInputRef = ref<HTMLInputElement | null>(null);
const coverInputRef = ref<HTMLInputElement | null>(null);

// Image Upload Previews & States
const avatarPreview = ref<string>('');
const coverPreview = ref<string>('');
const isUploadingAvatar = ref(false);
const isUploadingCover = ref(false);
const isSubmitting = ref(false);
const submissionError = ref<string | null>(null);

// Interactive Preview Tabs & Actions
const previewActiveTab = ref<'posts' | 'about' | 'photos' | 'videos' | 'events' | 'community'>('posts');
const showMoreDropdown = ref(false);
const previewLiked = ref(false);
const previewLikesCount = ref(1);
const showActionMenu = ref(false);
const copiedLink = ref(false);

// Standard Category options (includes Content Creator, Business, Brand, Community, etc.)
const defaultCategories = [
  'Content Creator',
  'Business',
  'Brand',
  'Community',
  'Education',
  'Entertainment',
  'Food & Beverage',
  'Technology',
  'Public Figure',
  'Nonprofit',
  'Artist / Musician',
  'News & Media',
  'Health & Beauty',
  'Shopping & Retail',
];

const availableCategories = computed(() => {
  if (props.categories && props.categories.length > 0) {
    // If backend provided categories, ensure Content Creator is also present if not already
    const existing = [...props.categories];
    if (!existing.some(c => c.name.toLowerCase() === 'content creator')) {
      existing.unshift({ id: 9999, name: 'Content Creator', slug: 'content-creator' });
    }
    return existing;
  }
  return defaultCategories.map((name, index) => ({
    id: index + 1,
    name,
    slug: name.toLowerCase().replace(/[^a-z0-9]+/g, '-'),
  }));
});

// Slug generator from name
const generateSlugFromName = (name: string): string => {
  const cleaned = name
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
    .substring(0, 30);
  if (!cleaned) return '';
  return cleaned;
};

// Live Slug / Username Generator for Preview
const previewSlug = computed(() => {
  if (form.username && form.username.trim() !== '') {
    const sanitized = form.username.toLowerCase().replace(/[^a-z0-9_.-]/g, '');
    if (sanitized.length >= 3) {
      return sanitized;
    }
  }
  const fromName = generateSlugFromName(form.name);
  if (fromName && fromName.length >= 3) {
    return fromName;
  }
  if (fromName && fromName.length > 0) {
    return fromName + '_page';
  }
  return 'yourpagename';
});

// Formatted Website URL
const formattedWebsiteUrl = computed(() => {
  if (!form.website) return '#';
  const val = form.website.trim();
  if (val.startsWith('http://') || val.startsWith('https://')) {
    return val;
  }
  return 'https://' + val;
});

const onNameInput = () => {
  if (errors.name && form.name.trim().length >= 2) {
    errors.name = '';
  }
  // Auto-sync username as long as user hasn't explicitly typed a custom username
  if (!isCustomUsername.value) {
    const slug = generateSlugFromName(form.name);
    form.username = slug;
  }
};

const onUsernameInput = () => {
  isCustomUsername.value = true;
  form.username = form.username.toLowerCase().replace(/[^a-z0-9_.-]/g, '');
  if (errors.username && form.username.length >= 3) {
    errors.username = '';
  }
};

const resetUsernameToAuto = () => {
  isCustomUsername.value = false;
  form.username = generateSlugFromName(form.name);
};

const onCategoryChange = () => {
  if (errors.category) {
    errors.category = '';
  }
  const match = props.categories?.find(c => c.name === form.category);
  if (match && match.id) {
    form.page_category_id = match.id;
  }
};

// Navigation between steps
const goToStep = (step: number) => {
  if (step > currentStep.value) {
    if (currentStep.value === 1 && !validateStep1()) {
      return;
    }
  }
  currentStep.value = step;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Validation for Step 1
const validateStep1 = (): boolean => {
  let isValid = true;
  errors.name = '';
  errors.username = '';
  errors.category = '';
  errors.description = '';

  if (!form.name || form.name.trim().length < 2) {
    errors.name = 'Please provide a valid page name (at least 2 characters).';
    isValid = false;
  }

  // If user provided custom username, check that it's at least 3 chars
  if (form.username && form.username.trim().length > 0 && form.username.trim().length < 3) {
    errors.username = 'Username must be at least 3 characters long.';
    isValid = false;
  }

  if (!form.category) {
    errors.category = 'Please select a category for your Page.';
    isValid = false;
  }

  if (!form.description || form.description.trim().length < 5) {
    errors.description = 'Please provide a brief description (at least 5 characters).';
    isValid = false;
  }

  return isValid;
};

const validateStep1AndNext = () => {
  if (validateStep1()) {
    currentStep.value = 2;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

// Triggers for inputs
const triggerAvatarPicker = () => {
  avatarInputRef.value?.click();
};

const triggerCoverPicker = () => {
  coverInputRef.value?.click();
};

const onAvatarEditClick = () => {
  if (currentStep.value !== 2) {
    currentStep.value = 2;
  }
  setTimeout(() => {
    triggerAvatarPicker();
  }, 100);
};

const onCoverEditClick = () => {
  if (currentStep.value !== 2) {
    currentStep.value = 2;
  }
  setTimeout(() => {
    triggerCoverPicker();
  }, 100);
};

// Real Media Upload Handler for Avatar
const handleAvatarUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  // Local instant preview
  avatarPreview.value = URL.createObjectURL(file);
  isUploadingAvatar.value = true;
  submissionError.value = null;

  try {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('collection', 'page');

    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
    const response = await fetch('/api/v1/media/upload', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
      },
      body: formData,
    });

    const data = await response.json();
    if (response.ok && (data.success || data.data?.url)) {
      form.avatar_url = data.data.url;
      avatarPreview.value = data.data.url;
    }
  } catch (err: any) {
    console.error('Error uploading avatar:', err);
  } finally {
    isUploadingAvatar.value = false;
  }
};

const removeAvatar = () => {
  avatarPreview.value = '';
  form.avatar_url = '';
  if (avatarInputRef.value) {
    avatarInputRef.value.value = '';
  }
};

// Real Media Upload Handler for Cover
const handleCoverUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  // Local instant preview
  coverPreview.value = URL.createObjectURL(file);
  isUploadingCover.value = true;
  submissionError.value = null;

  try {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('collection', 'page');

    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
    const response = await fetch('/api/v1/media/upload', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
      },
      body: formData,
    });

    const data = await response.json();
    if (response.ok && (data.success || data.data?.url)) {
      form.cover_image_url = data.data.url;
      coverPreview.value = data.data.url;
    }
  } catch (err: any) {
    console.error('Error uploading cover:', err);
  } finally {
    isUploadingCover.value = false;
  }
};

const removeCover = () => {
  coverPreview.value = '';
  form.cover_image_url = '';
  if (coverInputRef.value) {
    coverInputRef.value.value = '';
  }
};

// Toggle like in live preview
const togglePreviewLike = () => {
  previewLiked.value = !previewLiked.value;
  previewLikesCount.value += previewLiked.value ? 1 : -1;
};

// Copy link in preview
const copyPreviewLink = () => {
  const url = `${window.location.origin}/pages/@${previewSlug.value}`;
  navigator.clipboard?.writeText(url);
  copiedLink.value = true;
  setTimeout(() => {
    copiedLink.value = false;
    showActionMenu.value = false;
  }, 1500);
};

// Final Real Submission
const submitCreatePage = async () => {
  if (!validateStep1()) {
    currentStep.value = 1;
    return;
  }

  isSubmitting.value = true;
  submissionError.value = null;

  // Ensure username is clean and at least 3 characters
  let cleanUsername = form.username.trim();
  if (cleanUsername.length < 3) {
    cleanUsername = previewSlug.value;
  }

  // Ensure website is formatted if provided
  let cleanWebsite = form.website.trim();
  if (cleanWebsite && !cleanWebsite.startsWith('http://') && !cleanWebsite.startsWith('https://')) {
    cleanWebsite = 'https://' + cleanWebsite;
  }

  const payload: Record<string, any> = {
    name: form.name.trim(),
    username: cleanUsername,
    category: form.category,
    description: form.description.trim(),
    short_description: form.description.trim().substring(0, 250),
    visibility: form.visibility,
  };

  if (form.page_category_id) {
    payload.page_category_id = form.page_category_id;
  }
  if (form.avatar_url) {
    payload.avatar_url = form.avatar_url;
  }
  if (form.cover_image_url) {
    payload.cover_image_url = form.cover_image_url;
  }
  if (cleanWebsite) {
    payload.website = cleanWebsite;
  }
  if (form.email && form.email.trim()) {
    payload.email = form.email.trim();
  }
  if (form.phone && form.phone.trim()) {
    payload.phone = form.phone.trim();
  }
  if (form.city && form.city.trim()) {
    payload.city = form.city.trim();
  }
  if (form.address && form.address.trim()) {
    payload.address = form.address.trim();
  }

  const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;

  try {
    // Attempt Atomic Registration via API v2
    let response = await fetch('/api/v2/pages/register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
      },
      body: JSON.stringify(payload),
    });

    let result = await response.json();

    // Fallback to v1 if v2 route not matching
    if (!response.ok && response.status === 404) {
      response = await fetch('/api/v1/pages', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        body: JSON.stringify(payload),
      });
      result = await response.json();
    }

    if (response.ok && (result.success || result.data)) {
      const pageData = result.data?.page || result.data;
      const targetSlug = pageData?.slug || cleanUsername;
      const redirectUrl = `/pages/${targetSlug}`;

      // Redirect directly to the newly created real Page profile
      window.location.href = redirectUrl;
    } else {
      const msg = result.message || result.error || 'Failed to create page. Please check the entered details.';
      submissionError.value = msg;
      if (result.errors) {
        const errorKeys = Object.keys(result.errors);
        if (errorKeys.length > 0) {
          submissionError.value = `${msg} (${result.errors[errorKeys[0]][0]})`;
        }
      }
    }
  } catch (err: any) {
    console.error('Page creation error:', err);
    submissionError.value = err.message || 'A network error occurred. Please try again.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in {
  animation: fadeIn 0.25s ease-out forwards;
}
</style>
