<template>
  <AppLayout
    current-route="learning"
    :unread-count="unreadCount"
    :unread-messages-count="unreadMessagesCount"
    :upcoming-events="upcomingEvents"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <div class="max-w-6xl mx-auto space-y-6 pb-14">
      <!-- Header Banner -->
      <div class="bg-gradient-to-r from-[#0F172A] via-[#1E3A8A] to-[#1D4ED8] rounded-3xl p-6 sm:p-8 text-white shadow-xl flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="space-y-2 max-w-2xl">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 text-xs font-bold tracking-wide">
            🎓 Bondoo Learning &amp; Skill Academy
          </span>
          <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
            Advance Your Career with Verified Courses
          </h1>
          <p class="text-xs sm:text-sm text-blue-100 leading-relaxed">
            Enroll in free and premium courses, track lesson completion synchronized across Android and Web, and earn verifiable credentials.
          </p>
        </div>

        <!-- Learner Stats Strip (Authenticated) -->
        <div v-if="currentUser" class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 shrink-0">
          <div class="px-3.5 py-2.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
            <div class="text-[11px] text-blue-100">Enrolled</div>
            <div class="text-lg font-black">{{ liveStats.enrolled_count }}</div>
          </div>
          <div class="px-3.5 py-2.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
            <div class="text-[11px] text-blue-100">In Progress</div>
            <div class="text-lg font-black">{{ liveStats.in_progress_count }}</div>
          </div>
          <div class="px-3.5 py-2.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
            <div class="text-[11px] text-blue-100">Completed</div>
            <div class="text-lg font-black">{{ liveStats.completed_count }}</div>
          </div>
          <div class="px-3.5 py-2.5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
            <div class="text-[11px] text-blue-100">Wallet</div>
            <div class="text-lg font-black">${{ Number(liveStats.wallet_balance || 0).toFixed(2) }}</div>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-2 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar">
          <button
            type="button"
            @click="switchTab('explore')"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer shrink-0"
            :class="currentTab === 'explore' ? 'bg-[#1877F2] text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            📚 Explore Catalog ({{ courses.total }})
          </button>
          <button
            type="button"
            @click="switchTab('my_learning')"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer shrink-0 flex items-center gap-1.5"
            :class="currentTab === 'my_learning' ? 'bg-[#1877F2] text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            <span>🎯 My Learning</span>
            <span v-if="liveMyLearning.length > 0" class="px-1.5 py-0.5 rounded-full text-[10px] bg-emerald-500 text-white">
              {{ liveMyLearning.length }}
            </span>
          </button>
          <button
            type="button"
            @click="switchTab('certificates')"
            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer shrink-0 flex items-center gap-1.5"
            :class="currentTab === 'certificates' ? 'bg-[#1877F2] text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
          >
            <span>🏆 Certificates</span>
            <span v-if="liveCertificates.length > 0" class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-500 text-white">
              {{ liveCertificates.length }}
            </span>
          </button>
        </div>

        <!-- Search Input (Explore Tab) -->
        <div v-if="currentTab === 'explore'" class="flex items-center gap-2 w-full sm:w-auto">
          <input
            v-model="searchForm.q"
            @keyup.enter="applyFilters"
            type="text"
            placeholder="Search courses, skills..."
            class="w-full sm:w-64 px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs focus:outline-none focus:ring-2 focus:ring-[#1877F2]"
          />
          <button
            type="button"
            @click="applyFilters"
            class="px-3.5 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold cursor-pointer shrink-0"
          >
            Search
          </button>
        </div>
      </div>

      <!-- TAB 1: EXPLORE CATALOG -->
      <section v-if="currentTab === 'explore'" class="space-y-5">
        <!-- Category + Filter Bar -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm space-y-3">
          <!-- Parent & Subcategory Pills -->
          <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
            <button
              type="button"
              @click="selectCategory('')"
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all cursor-pointer"
              :class="!searchForm.category ? 'bg-[#1877F2] text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200'"
            >
              All Categories
            </button>
            <button
              v-for="cat in categories"
              :key="cat.id"
              type="button"
              @click="selectCategory(cat.slug)"
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all cursor-pointer"
              :class="searchForm.category === cat.slug ? 'bg-[#1877F2] text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200'"
            >
              {{ cat.name }} ({{ cat.courses_count }})
            </button>
          </div>

          <!-- Active Category Subcategories -->
          <div v-if="activeSubcategories.length > 0" class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pt-1 border-t border-slate-100 dark:border-slate-800">
            <span class="text-[11px] font-semibold text-slate-400 mr-1 shrink-0">Topics:</span>
            <button
              v-for="sub in activeSubcategories"
              :key="sub.id"
              type="button"
              @click="selectCategory(sub.slug)"
              class="px-2.5 py-1 rounded-lg text-[11px] font-semibold shrink-0 transition-all cursor-pointer"
              :class="searchForm.category === sub.slug ? 'bg-blue-100 text-[#1877F2] font-bold' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
            >
              {{ sub.name }}
            </button>
          </div>

          <!-- Level, Type & Sort Controls -->
          <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
            <div class="flex flex-wrap items-center gap-2">
              <select v-model="searchForm.type" @change="applyFilters" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-semibold">
                <option value="">All Pricing</option>
                <option value="free">Free Courses</option>
                <option value="paid">Paid Courses</option>
              </select>

              <select v-model="searchForm.level" @change="applyFilters" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-semibold">
                <option value="">All Levels</option>
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="expert">Expert</option>
              </select>

              <button
                v-if="searchForm.q || searchForm.category || searchForm.type || searchForm.level"
                type="button"
                @click="resetFilters"
                class="px-3 py-1.5 rounded-xl text-rose-500 hover:bg-rose-50 font-bold cursor-pointer"
              >
                Reset
              </button>
            </div>

            <div class="flex items-center gap-2">
              <span class="text-slate-400">Sort:</span>
              <select v-model="searchForm.sort" @change="applyFilters" class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 font-semibold">
                <option value="popular">Most Popular</option>
                <option value="newest">Newest</option>
                <option value="rating">Highest Rated</option>
                <option value="price_low">Price: Low to High</option>
                <option value="price_high">Price: High to Low</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Course Grid -->
        <div v-if="liveCourses.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="course in liveCourses"
            :key="course.id"
            class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm hover:shadow-lg transition-all flex flex-col"
          >
            <div class="relative aspect-video bg-slate-100 dark:bg-slate-800 cursor-pointer" @click="openCourseDetails(course)">
              <img
                :src="course.thumbnail_url || fallbackThumbnail"
                :alt="course.title"
                class="w-full h-full object-cover"
              />
              <span
                class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase shadow-sm"
                :class="course.type === 'free' ? 'bg-emerald-600 text-white' : 'bg-slate-900/90 text-white'"
              >
                {{ course.type === 'free' ? 'FREE' : ('$' + Number(course.effective_price).toFixed(2)) }}
              </span>
              <span
                v-if="course.is_enrolled"
                class="absolute top-3 right-3 px-2.5 py-1 rounded-lg bg-[#1877F2] text-white text-[10px] font-extrabold shadow-sm"
              >
                {{ course.enrollment_status === 'completed' ? '✓ Completed' : 'Enrolled' }}
              </span>
              <span
                v-else-if="course.is_instructor"
                class="absolute top-3 right-3 px-2.5 py-1 rounded-lg bg-indigo-600 text-white text-[10px] font-extrabold shadow-sm"
              >
                Instructor
              </span>
            </div>

            <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
              <div class="space-y-2">
                <div class="flex items-center justify-between text-[11px] font-bold text-[#1877F2]">
                  <span>{{ course.category?.name || 'Professional Skill' }}</span>
                  <span class="capitalize text-slate-400">{{ course.level || 'All levels' }}</span>
                </div>
                <h3
                  @click="openCourseDetails(course)"
                  class="font-extrabold text-base text-slate-900 dark:text-white hover:text-[#1877F2] line-clamp-2 cursor-pointer"
                >
                  {{ course.title }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                  {{ course.subtitle || course.description }}
                </p>
              </div>

              <div class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between text-xs text-slate-500">
                  <span>👤 {{ course.instructor?.name || 'Bondoo Instructor' }}</span>
                  <span>
                    <strong class="text-amber-500">★ {{ Number(course.rating_avg || 0).toFixed(1) }}</strong>
                    ({{ course.enrollments_count }} learners)
                  </span>
                </div>

                <!-- Price + Action Row -->
                <div class="flex items-center justify-between gap-2">
                  <div>
                    <span v-if="course.type === 'free'" class="text-base font-black text-emerald-600">Free</span>
                    <div v-else class="flex items-baseline gap-1.5">
                      <span class="text-base font-black text-slate-900 dark:text-white">${{ Number(course.effective_price).toFixed(2) }}</span>
                      <span v-if="course.discounted_price !== null && course.discounted_price < course.price" class="text-xs text-slate-400 line-through">
                        ${{ Number(course.price).toFixed(2) }}
                      </span>
                    </div>
                  </div>

                  <button
                    type="button"
                    @click="openCourseDetails(course)"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer"
                    :class="course.is_enrolled ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-[#1877F2] hover:bg-[#1666D8] text-white'"
                  >
                    {{ course.is_enrolled ? (course.enrollment_status === 'completed' ? 'Review Course' : 'Continue Learning') : (course.is_instructor ? 'Preview Course' : 'View & Enroll') }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center space-y-3">
          <div class="text-3xl">📘</div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">No matching courses found</h3>
          <p class="text-xs text-slate-500">Try clearing your search filters to explore all published courses.</p>
          <button type="button" @click="resetFilters" class="px-4 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold">Reset Filters</button>
        </div>

        <!-- Pagination -->
        <div v-if="courses.last_page > 1" class="flex items-center justify-center gap-3 pt-2">
          <button
            type="button"
            :disabled="courses.current_page <= 1"
            @click="goToPage(courses.current_page - 1)"
            class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold disabled:opacity-40"
          >
            ← Previous
          </button>
          <span class="text-xs font-semibold text-slate-500">Page {{ courses.current_page }} of {{ courses.last_page }}</span>
          <button
            type="button"
            :disabled="courses.current_page >= courses.last_page"
            @click="goToPage(courses.current_page + 1)"
            class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold disabled:opacity-40"
          >
            Next →
          </button>
        </div>
      </section>

      <!-- TAB 2: MY LEARNING -->
      <section v-else-if="currentTab === 'my_learning'" class="space-y-4">
        <div class="flex items-center gap-2">
          <button
            v-for="sub in ['all', 'in_progress', 'completed']"
            :key="sub"
            type="button"
            @click="myLearningFilter = sub"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold capitalize cursor-pointer"
            :class="myLearningFilter === sub ? 'bg-[#1877F2] text-white' : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300'"
          >
            {{ sub.replace('_', ' ') }}
          </button>
        </div>

        <div v-if="filteredMyLearning.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="item in filteredMyLearning"
            :key="item.id"
            class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm flex flex-col justify-between gap-4"
          >
            <div class="flex items-start gap-4">
              <img
                :src="item.course?.thumbnail_url || fallbackThumbnail"
                :alt="item.course?.title"
                class="w-24 h-16 rounded-2xl object-cover shrink-0"
              />
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span
                    class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase"
                    :class="item.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-[#1877F2]'"
                  >
                    {{ item.status }}
                  </span>
                  <span class="text-xs font-bold text-slate-500">{{ Math.round(Number(item.progress_percentage || 0)) }}% complete</span>
                </div>
                <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white mt-1 line-clamp-1">
                  {{ item.course?.title || 'Enrolled Course' }}
                </h3>
                <!-- Progress Bar -->
                <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 mt-2 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="item.status === 'completed' ? 'bg-emerald-500' : 'bg-[#1877F2]'"
                    :style="{ width: Math.min(100, Math.max(0, Number(item.progress_percentage || 0))) + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
              <a
                v-if="item.certificate_code"
                :href="'/verify/cert/' + item.certificate_code"
                class="text-xs font-bold text-amber-600 hover:underline"
              >
                🏆 Certificate: {{ item.certificate_code }}
              </a>
              <span v-else class="text-xs text-slate-400">
                {{ item.course?.total_lessons || 0 }} lessons
              </span>

              <button
                v-if="item.course"
                type="button"
                @click="openCourseDetails(item.course)"
                class="px-4 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold cursor-pointer"
              >
                {{ item.status === 'completed' ? 'Review Lessons' : 'Continue Course →' }}
              </button>
            </div>
          </div>
        </div>

        <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center space-y-3">
          <div class="text-3xl">🎯</div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">No courses in this list yet</h3>
          <p class="text-xs text-slate-500">Explore our catalog and enroll in your first course.</p>
          <button type="button" @click="switchTab('explore')" class="px-4 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold">Browse Catalog</button>
        </div>
      </section>

      <!-- TAB 3: CERTIFICATES -->
      <section v-else class="space-y-4">
        <div v-if="liveCertificates.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="cert in liveCertificates"
            :key="cert.id"
            class="bg-white dark:bg-slate-900 rounded-3xl border border-amber-200/80 dark:border-amber-900/40 p-6 shadow-sm space-y-3"
          >
            <div class="flex items-center justify-between">
              <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 text-xs font-extrabold">🏆 Verified Credential</span>
              <span class="font-mono text-xs text-slate-500">{{ cert.certificate_code }}</span>
            </div>
            <h3 class="text-base font-black text-slate-900 dark:text-white">{{ cert.course_title }}</h3>
            <p class="text-xs text-slate-500">Awarded to <strong>{{ cert.student_name }}</strong> • Instructor: {{ cert.instructor_name }}</p>
            <div class="flex items-center gap-2 pt-2">
              <a
                :href="'/verify/cert/' + cert.certificate_code"
                class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-xs font-bold hover:bg-slate-200"
              >
                Verify Credential
              </a>
              <a
                :href="'/api/v1/learning/certificates/' + cert.certificate_code + '/download'"
                target="_blank"
                class="px-3.5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold hover:bg-[#1666D8]"
              >
                Download Printable Certificate
              </a>
            </div>
          </div>
        </div>
        <div v-else class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center space-y-3">
          <div class="text-3xl">🏆</div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">No certificates earned yet</h3>
          <p class="text-xs text-slate-500">Complete 100% of lessons in any enrolled course to unlock your verified Bondoo Certificate.</p>
        </div>
      </section>
    </div>

    <!-- COURSE DETAILS, ENROLLMENT & CURRICULUM MODAL -->
    <div v-if="detailCourse" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/65 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-3xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden max-h-[88vh] flex flex-col">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <span class="text-[11px] font-bold uppercase text-[#1877F2]">{{ detailCourse.category?.name || 'Course Overview' }}</span>
            <h2 class="text-lg font-black text-slate-900 dark:text-white truncate">{{ detailCourse.title }}</h2>
          </div>
          <button type="button" @click="closeCourseDetails" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-800 flex items-center justify-center cursor-pointer">✕</button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto space-y-5 flex-1">
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
            {{ detailCourse.description || detailCourse.subtitle }}
          </p>

          <!-- Enrollment / Progress Banner -->
          <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200/80 dark:border-slate-700 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <div v-if="detailCourse.is_enrolled" class="space-y-1">
                  <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-emerald-600">
                    ✓ Enrolled ({{ Math.round(Number(detailCourse.progress_percentage || 0)) }}% Completed)
                  </span>
                  <p class="text-xs text-slate-500">Select any lesson below to watch and track your progress.</p>
                </div>
                <div v-else-if="detailCourse.is_instructor" class="space-y-1">
                  <span class="inline-flex items-center gap-1.5 text-xs font-extrabold text-indigo-600">
                    👨‍🏫 You are the Instructor of this course
                  </span>
                  <p class="text-xs text-slate-500">Instructors have full preview access and cannot self-enroll.</p>
                </div>
                <div v-else class="space-y-0.5">
                  <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-black text-[#1877F2]">
                      {{ detailCourse.type === 'free' ? 'FREE' : ('$' + Number(detailCourse.effective_price).toFixed(2)) }}
                    </span>
                    <span v-if="detailCourse.type !== 'free' && detailCourse.discounted_price !== null && detailCourse.discounted_price < detailCourse.price" class="text-xs text-slate-400 line-through">
                      ${{ Number(detailCourse.price).toFixed(2) }}
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-500">
                    {{ detailCourse.type === 'free' ? 'Instant full lifetime access.' : 'Paid securely from your Bondoo Wallet.' }}
                  </p>
                </div>
              </div>

              <!-- Action Button -->
              <div class="flex items-center gap-2">
                <button
                  v-if="detailCourse.is_enrolled && Number(detailCourse.progress_percentage || 0) >= 100"
                  type="button"
                  @click="claimCertificate(detailCourse.id)"
                  :disabled="isClaimingCert"
                  class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold cursor-pointer disabled:opacity-50"
                >
                  {{ isClaimingCert ? 'Issuing...' : '🏆 Claim / View Certificate' }}
                </button>
                <button
                  v-if="detailCourse.is_enrolled && firstAvailableLesson"
                  type="button"
                  @click="openLessonPlayer(firstAvailableLesson)"
                  class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer"
                >
                  ▶ Start / Continue Lesson
                </button>
                <button
                  v-else-if="!detailCourse.is_enrolled && !detailCourse.is_instructor"
                  type="button"
                  @click="enrollInCourse(detailCourse)"
                  :disabled="isEnrolling"
                  class="px-5 py-2.5 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold shadow-md shadow-blue-500/20 cursor-pointer disabled:opacity-50 flex items-center gap-2"
                >
                  <span v-if="isEnrolling" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                  <span>{{ isEnrolling ? 'Enrolling...' : (detailCourse.type === 'free' ? 'Enroll for Free' : 'Pay & Enroll Now') }}</span>
                </button>
              </div>
            </div>

            <!-- Optional Coupon Input for Paid Courses -->
            <div v-if="!detailCourse.is_enrolled && !detailCourse.is_instructor && detailCourse.type === 'paid'" class="flex items-center gap-2 pt-2 border-t border-slate-200/70 dark:border-slate-700">
              <input
                v-model="couponCode"
                type="text"
                placeholder="Have a coupon code? (e.g. BONDHOO50)"
                class="flex-1 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs uppercase font-mono"
              />
            </div>

            <!-- Honest Error & Retry Banner -->
            <div v-if="enrollError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center justify-between gap-3">
              <span>⚠️ {{ enrollError }}</span>
              <button
                type="button"
                @click="enrollInCourse(detailCourse)"
                :disabled="isEnrolling"
                class="px-3 py-1 rounded-lg bg-rose-600 text-white font-bold shrink-0 cursor-pointer"
              >
                Retry
              </button>
            </div>

            <div v-if="enrollFeedback" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
              {{ enrollFeedback }}
            </div>
          </div>

          <!-- Curriculum Sections -->
          <div class="space-y-3">
            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Course Curriculum</h3>
            <div v-if="isLoadingCurriculum" class="py-6 text-center text-xs text-slate-400">Loading curriculum...</div>
            <div v-else-if="detailSections.length === 0" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 text-xs text-slate-500">
              Curriculum lessons will be published soon.
            </div>
            <div v-else class="space-y-3">
              <div
                v-for="sec in detailSections"
                :key="sec.id"
                class="rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden"
              >
                <div class="px-4 py-2.5 bg-slate-50 dark:bg-slate-800/80 font-bold text-xs text-slate-800 dark:text-slate-200 flex items-center justify-between">
                  <span>{{ sec.title }}</span>
                  <span class="text-[11px] text-slate-400">{{ (sec.lessons || []).length }} lessons</span>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                  <div
                    v-for="lesson in (sec.lessons || [])"
                    :key="lesson.id"
                    class="px-4 py-3 flex items-center justify-between gap-3 text-xs"
                  >
                    <div class="flex items-center gap-2.5 min-w-0">
                      <span v-if="lesson.is_completed" class="text-emerald-600 font-bold">✓</span>
                      <span v-else-if="!lesson.is_locked" class="text-[#1877F2]">▶</span>
                      <span v-else class="text-slate-400">🔒</span>
                      <span class="font-semibold text-slate-800 dark:text-slate-200 truncate">{{ lesson.title }}</span>
                      <span v-if="lesson.is_preview && !detailCourse.is_enrolled" class="px-2 py-0.5 rounded bg-blue-50 text-[#1877F2] text-[10px] font-bold">
                        Preview
                      </span>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                      <span class="text-[11px] text-slate-400">{{ lesson.duration_minutes || 5 }} min</span>
                      <button
                        v-if="!lesson.is_locked"
                        type="button"
                        @click="openLessonPlayer(lesson)"
                        class="px-3 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-[#1877F2] font-bold cursor-pointer"
                      >
                        {{ lesson.is_completed ? 'Review' : 'Open' }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- LESSON PLAYER MODAL -->
    <div v-if="activeLesson && detailCourse" class="fixed inset-0 z-[60] overflow-y-auto bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
          <div>
            <span class="text-[11px] font-bold uppercase text-[#1877F2]">{{ detailCourse.title }}</span>
            <h3 class="text-base font-black text-slate-900 dark:text-white">{{ activeLesson.title }}</h3>
          </div>
          <button type="button" @click="activeLesson = null" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center cursor-pointer">✕</button>
        </div>

        <div v-if="activeLesson.video_url" class="rounded-2xl overflow-hidden bg-slate-950 aspect-video flex items-center justify-center">
          <video
            v-if="activeLesson.video_url.endsWith('.mp4') || activeLesson.video_url.includes('w3schools') || activeLesson.video_url.includes('storage')"
            :src="activeLesson.video_url"
            controls
            class="w-full h-full"
          ></video>
          <div v-else class="p-6 text-center text-white space-y-2">
            <div class="text-sm font-bold">External Video Resource</div>
            <a :href="activeLesson.video_url" target="_blank" class="inline-block px-4 py-2 rounded-xl bg-[#1877F2] text-xs font-bold">Open Video Stream ↗</a>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/70 text-xs sm:text-sm text-slate-700 dark:text-slate-200 whitespace-pre-line leading-relaxed max-h-60 overflow-y-auto">
          {{ activeLesson.content || 'Watch the lesson media above and mark complete when finished.' }}
        </div>

        <div v-if="progressFeedback" class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 text-xs font-semibold">{{ progressFeedback }}</div>
        <div v-if="progressError" class="p-2.5 rounded-xl bg-rose-50 text-rose-700 text-xs">{{ progressError }}</div>

        <div class="flex items-center justify-between pt-2">
          <span class="text-xs text-slate-500">Duration: {{ activeLesson.duration_minutes || 5 }} mins</span>
          <div class="flex items-center gap-2">
            <button type="button" @click="activeLesson = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500">Close</button>
            <button
              v-if="detailCourse.is_enrolled"
              type="button"
              @click="markLessonComplete(activeLesson)"
              :disabled="isUpdatingProgress || activeLesson.is_completed"
              class="px-5 py-2 rounded-xl text-xs font-bold text-white cursor-pointer disabled:opacity-60"
              :class="activeLesson.is_completed ? 'bg-emerald-600' : 'bg-[#1877F2] hover:bg-[#1666D8]'"
            >
              {{ activeLesson.is_completed ? '✓ Completed' : (isUpdatingProgress ? 'Saving...' : 'Mark as Complete') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { SharedProps, UpcomingEvent } from '../../types';

const props = defineProps<{
  categories: any[];
  courses: {
    data: any[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  myLearning: any[];
  certificates: any[];
  stats: {
    enrolled_count: number;
    in_progress_count: number;
    completed_count: number;
    certificates_count: number;
    wallet_balance: number;
  };
  selectedCourse?: any | null;
  activeTab?: string;
  filters: {
    q?: string;
    category_slug?: string;
    level?: string;
    type?: string;
    sort_by?: string;
  };
  upcomingEvents?: UpcomingEvent[];
  unreadCount?: number;
  unreadMessagesCount?: number;
}>();

const page = usePage<SharedProps>();
const currentUser = computed(() => page.props.auth?.user || null);
const fallbackThumbnail = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=800&q=80';

const currentTab = ref<'explore' | 'my_learning' | 'certificates'>(
  props.activeTab === 'my_learning' || props.activeTab === 'certificates' ? props.activeTab : 'explore'
);
const myLearningFilter = ref<'all' | 'in_progress' | 'completed'>('all');

const liveCourses = ref<any[]>([...props.courses.data]);
const liveMyLearning = ref<any[]>([...props.myLearning]);
const liveCertificates = ref<any[]>([...props.certificates]);
const liveStats = reactive({ ...props.stats });

const searchForm = reactive({
  q: props.filters.q || '',
  category: props.filters.category_slug || '',
  level: props.filters.level || '',
  type: props.filters.type || '',
  sort: props.filters.sort_by || 'popular',
});

const detailCourse = ref<any | null>(props.selectedCourse ? { ...props.selectedCourse } : null);
const detailSections = ref<any[]>(props.selectedCourse?.sections ? [...props.selectedCourse.sections] : []);
const isLoadingCurriculum = ref(false);
const couponCode = ref('');
const isEnrolling = ref(false);
const enrollError = ref('');
const enrollFeedback = ref('');
const isClaimingCert = ref(false);

const activeLesson = ref<any | null>(null);
const isUpdatingProgress = ref(false);
const progressFeedback = ref('');
const progressError = ref('');

const activeSubcategories = computed(() => {
  if (!searchForm.category) return [];
  const parent = props.categories.find(
    (c) => c.slug === searchForm.category || (c.children || []).some((sub: any) => sub.slug === searchForm.category)
  );
  return parent?.children || [];
});

const filteredMyLearning = computed(() => {
  if (myLearningFilter.value === 'completed') {
    return liveMyLearning.value.filter((e) => e.status === 'completed' || Number(e.progress_percentage || 0) >= 100);
  }
  if (myLearningFilter.value === 'in_progress') {
    return liveMyLearning.value.filter((e) => e.status !== 'completed' && Number(e.progress_percentage || 0) < 100);
  }
  return liveMyLearning.value;
});

const firstAvailableLesson = computed(() => {
  for (const sec of detailSections.value) {
    for (const lesson of sec.lessons || []) {
      if (!lesson.is_locked && !lesson.is_completed) {
        return lesson;
      }
    }
  }
  for (const sec of detailSections.value) {
    for (const lesson of sec.lessons || []) {
      if (!lesson.is_locked) {
        return lesson;
      }
    }
  }
  return null;
});

const switchTab = (tab: 'explore' | 'my_learning' | 'certificates') => {
  if ((tab === 'my_learning' || tab === 'certificates') && !currentUser.value) {
    window.location.href = '/login';
    return;
  }
  currentTab.value = tab;
};

const selectCategory = (slug: string) => {
  searchForm.category = slug;
  applyFilters();
};

const applyFilters = () => {
  const params: Record<string, any> = {};
  if (searchForm.q.trim()) params.q = searchForm.q.trim();
  if (searchForm.category) params.category = searchForm.category;
  if (searchForm.level) params.level = searchForm.level;
  if (searchForm.type) params.type = searchForm.type;
  if (searchForm.sort && searchForm.sort !== 'popular') params.sort = searchForm.sort;
  router.get('/learning', params, { preserveState: false });
};

const resetFilters = () => {
  searchForm.q = '';
  searchForm.category = '';
  searchForm.level = '';
  searchForm.type = '';
  searchForm.sort = 'popular';
  router.get('/learning', {}, { preserveState: false });
};

const goToPage = (pageNum: number) => {
  const params: Record<string, any> = { page: pageNum };
  if (searchForm.q.trim()) params.q = searchForm.q.trim();
  if (searchForm.category) params.category = searchForm.category;
  if (searchForm.level) params.level = searchForm.level;
  if (searchForm.type) params.type = searchForm.type;
  if (searchForm.sort && searchForm.sort !== 'popular') params.sort = searchForm.sort;
  router.get('/learning', params, { preserveState: false });
};

const openCourseDetails = async (course: any) => {
  detailCourse.value = { ...course };
  enrollError.value = '';
  enrollFeedback.value = '';
  couponCode.value = '';
  await loadCurriculum(course.id);
};

const closeCourseDetails = () => {
  detailCourse.value = null;
  detailSections.value = [];
  enrollError.value = '';
  enrollFeedback.value = '';
};

const loadCurriculum = async (courseId: number) => {
  isLoadingCurriculum.value = true;
  try {
    const res = await fetch(`/api/v1/learning/courses/${courseId}/curriculum`, {
      headers: { Accept: 'application/json' },
    });
    const payload = await res.json();
    if (res.ok && payload.data) {
      const completedSet = new Set<number>((payload.data.completed_lesson_ids || []).map((id: any) => Number(id)));
      const enrolled = Boolean(payload.data.is_enrolled || detailCourse.value?.is_enrolled);
      detailSections.value = (payload.data.sections || []).map((sec: any) => ({
        ...sec,
        lessons: (sec.lessons || []).map((l: any) => ({
          ...l,
          is_completed: completedSet.has(Number(l.id)) || Boolean(l.is_completed),
          is_locked: !(enrolled || detailCourse.value?.is_instructor || Boolean(l.is_preview)),
        })),
      }));
    }
  } catch (e) {
    console.error('Failed to load curriculum', e);
  } finally {
    isLoadingCurriculum.value = false;
  }
};

const refreshAuthoritativeLearningState = async () => {
  try {
    const res = await fetch('/api/v1/learning/my-learning', {
      headers: { Accept: 'application/json' },
    });
    const payload = await res.json();
    if (res.ok && payload.success) {
      const items = payload.data?.data || payload.data || [];
      if (Array.isArray(items)) {
        liveMyLearning.value = items;
      }
      if (payload.meta) {
        liveStats.enrolled_count = Number(payload.meta.enrolled_count ?? liveMyLearning.value.length);
        liveStats.in_progress_count = Number(payload.meta.in_progress_count ?? 0);
        liveStats.completed_count = Number(payload.meta.completed_count ?? 0);
        liveStats.certificates_count = Number(payload.meta.certificates_count ?? liveCertificates.value.length);
      }
    }
  } catch (_) {}
};

const enrollInCourse = async (course: any) => {
  if (!currentUser.value) {
    window.location.href = '/login';
    return;
  }
  if (isEnrolling.value) return;

  isEnrolling.value = true;
  enrollError.value = '';
  enrollFeedback.value = '';

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/learning/courses/${course.id}/enroll`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
      body: JSON.stringify({
        coupon_code: couponCode.value.trim() ? couponCode.value.trim() : null,
      }),
    });

    const payload = await res.json().catch(() => ({}));
    if (res.status === 401) {
      window.location.href = '/login';
      return;
    }

    if (res.ok && payload.success) {
      const enrollment = payload.data;
      detailCourse.value = {
        ...detailCourse.value,
        is_enrolled: true,
        enrollment_status: enrollment?.status || 'active',
        progress_percentage: Number(enrollment?.progress_percentage || 0),
      };
      const idx = liveCourses.value.findIndex((c) => c.id === course.id);
      if (idx !== -1) {
        liveCourses.value[idx] = {
          ...liveCourses.value[idx],
          is_enrolled: true,
          enrollment_status: enrollment?.status || 'active',
          enrollments_count: Number(liveCourses.value[idx].enrollments_count || 0) + (payload.already_enrolled ? 0 : 1),
        };
      }
      enrollFeedback.value = payload.message || 'Successfully enrolled! Full curriculum is now unlocked.';
      await Promise.all([loadCurriculum(course.id), refreshAuthoritativeLearningState()]);
    } else {
      enrollError.value = payload.message || 'Enrollment failed. Please verify eligibility or wallet balance.';
    }
  } catch (err: any) {
    enrollError.value = err.message || 'Network error while enrolling.';
  } finally {
    isEnrolling.value = false;
  }
};

const openLessonPlayer = (lesson: any) => {
  progressFeedback.value = '';
  progressError.value = '';
  activeLesson.value = lesson;
};

const markLessonComplete = async (lesson: any) => {
  if (!detailCourse.value || isUpdatingProgress.value) return;
  isUpdatingProgress.value = true;
  progressFeedback.value = '';
  progressError.value = '';

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/learning/lessons/${lesson.id}/progress`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
      body: JSON.stringify({
        is_completed: true,
        watch_time_seconds: Math.max(60, Number(lesson.duration_minutes || 5) * 60),
        last_position_seconds: 0,
      }),
    });
    const payload = await res.json().catch(() => ({}));
    if (res.ok && payload.success) {
      lesson.is_completed = true;
      const newPct = Number(payload.course_progress_percentage ?? detailCourse.value.progress_percentage ?? 0);
      detailCourse.value.progress_percentage = newPct;
      if (newPct >= 100) {
        detailCourse.value.enrollment_status = 'completed';
      }
      progressFeedback.value = newPct >= 100
        ? '🎉 Congratulations! Course 100% completed — your certificate is ready!'
        : `Lesson marked complete! Course progress: ${Math.round(newPct)}%`;
      await refreshAuthoritativeLearningState();
    } else {
      progressError.value = payload.message || 'Unable to save lesson progress.';
    }
  } catch (err: any) {
    progressError.value = err.message || 'Network error.';
  } finally {
    isUpdatingProgress.value = false;
  }
};

const claimCertificate = async (courseId: number) => {
  if (isClaimingCert.value) return;
  isClaimingCert.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/learning/courses/${courseId}/certificate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
    });
    const payload = await res.json().catch(() => ({}));
    if (res.ok && payload.data?.certificate_code) {
      window.location.href = `/verify/cert/${payload.data.certificate_code}`;
      return;
    }
    enrollError.value = payload.message || 'Complete all published lessons first to issue your certificate.';
  } catch (err: any) {
    enrollError.value = err.message || 'Failed to issue certificate.';
  } finally {
    isClaimingCert.value = false;
  }
};
</script>
