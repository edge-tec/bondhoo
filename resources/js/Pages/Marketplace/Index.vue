<template>
  <AppLayout
    current-route="marketplace"
    :unread-count="unreadCount"
    :unread-messages-count="unreadMessagesCount"
    :pending-requests="pendingRequests"
    :contacts="contacts"
    :suggestions="suggestions"
    :upcoming-events="upcomingEvents"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <div class="flex items-start gap-6">
      
      <!-- Center Main Content -->
      <div class="flex-1 min-w-0 space-y-6">
        
        <!-- Marketplace Header Banner Card -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-[#1877F2] text-white flex items-center justify-center shadow-lg shadow-blue-500/20 shrink-0">
              <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Marketplace
              </h1>
              <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed max-w-xl">
                Buy and sell locally, find great deals, and discover amazing products from people in your community.
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2 self-start sm:self-center">
            <!-- Mobile Filter Trigger -->
            <button
              type="button"
              @click="isMobileFilterOpen = true"
              class="xl:hidden inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold shadow-sm"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
              </svg>
              <span>Filters</span>
            </button>

            <!-- Create Listing Button -->
            <a
              href="/marketplace/create"
              class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs sm:text-sm font-bold shadow-md shadow-blue-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
              </svg>
              <span>Create Listing</span>
            </a>
          </div>
        </div>

        <!-- Category Pills Row -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-3 shadow-sm">
          <div class="flex items-center gap-2.5 overflow-x-auto no-scrollbar py-1">
            <!-- All Pill -->
            <button
              type="button"
              @click="handleCategorySelect('all')"
              class="flex flex-col items-center justify-center gap-1 min-w-[76px] px-3 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0"
              :class="activeCategory === 'all' || !activeCategory ? 'bg-[#1877F2] text-white shadow-md shadow-blue-500/20' : 'bg-slate-50 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100'"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
              <span>All</span>
            </button>

            <!-- Dynamic Category Pills -->
            <button
              v-for="cat in categories"
              :key="cat.id"
              type="button"
              @click="handleCategorySelect(cat.slug)"
              class="flex flex-col items-center justify-center gap-1 min-w-[80px] px-3 py-2.5 rounded-xl text-xs font-semibold transition-all shrink-0"
              :class="activeCategory === cat.slug ? 'bg-[#1877F2] text-white font-bold shadow-md shadow-blue-500/20' : 'bg-slate-50 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
            >
              <span class="text-base leading-none">
                {{ getCategoryEmoji(cat.slug) }}
              </span>
              <span class="truncate max-w-[84px]">{{ cat.name }}</span>
            </button>
          </div>
        </div>

        <!-- Featured Listings Section -->
        <section v-if="featuredListings && featuredListings.length > 0">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                Featured Listings
              </h2>
            </div>

            <!-- Sort Dropdown -->
            <div class="flex items-center gap-2 text-xs">
              <span class="text-slate-400 font-medium hidden sm:inline">Sort by:</span>
              <select
                v-model="currentSort"
                @change="handleSortChange"
                class="px-2.5 py-1.5 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1877F2] cursor-pointer"
              >
                <option value="newest">Newest</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="views_desc">Most Popular</option>
              </select>
            </div>
          </div>

          <!-- Featured Grid: 4 columns -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <ProductCard
              v-for="prod in featuredListings"
              :key="'featured-' + prod.id"
              :product="prod"
              @contact="openContactModal"
            />
          </div>
        </section>

        <!-- Latest Listings Section -->
        <section>
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
              </div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                Latest Listings
              </h2>
            </div>

            <span class="text-xs font-semibold text-slate-400">
              Showing {{ latestListings.data.length }} of {{ latestListings.total }} items
            </span>
          </div>

          <!-- Listings Grid -->
          <div v-if="latestListings.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <ProductCard
              v-for="prod in latestListings.data"
              :key="'latest-' + prod.id"
              :product="prod"
              @contact="openContactModal"
            />
          </div>

          <!-- Empty State -->
          <div
            v-else
            class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-12 text-center shadow-sm space-y-3"
          >
            <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center mx-auto">
              <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800 dark:text-white">No listings found</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
              No products match your current search or filter criteria. Try adjusting your filters or search keywords.
            </p>
            <button
              type="button"
              @click="resetFilters"
              class="inline-block mt-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 transition-colors"
            >
              Reset Filters
            </button>
          </div>

          <!-- Pagination Bar -->
          <div
            v-if="latestListings.last_page > 1"
            class="mt-8 flex items-center justify-center gap-2"
          >
            <button
              type="button"
              @click="goToPage(latestListings.current_page - 1)"
              :disabled="latestListings.current_page <= 1"
              class="px-3.5 py-2 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-800 transition-all text-slate-700 dark:text-slate-300"
            >
              ← Previous
            </button>

            <span class="text-xs font-semibold text-slate-500 px-3">
              Page {{ latestListings.current_page }} of {{ latestListings.last_page }}
            </span>

            <button
              type="button"
              @click="goToPage(latestListings.current_page + 1)"
              :disabled="latestListings.current_page >= latestListings.last_page"
              class="px-3.5 py-2 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-800 transition-all text-slate-700 dark:text-slate-300"
            >
              Next →
            </button>
          </div>
        </section>

      </div>

      <!-- Right Column: Filters & Confidence Cards (Desktop) -->
      <aside class="w-80 shrink-0 hidden xl:block">
        <FilterSidebar
          :categories="categories"
          :filters="currentFilters"
          @filter-change="handleFilterChange"
        />
      </aside>

    </div>

    <!-- Mobile Filters Slide-over / Modal -->
    <div v-if="isMobileFilterOpen" class="fixed inset-0 z-50 xl:hidden bg-slate-900/60 backdrop-blur-sm flex justify-end">
      <div class="w-full max-w-sm bg-white dark:bg-slate-900 h-full p-6 overflow-y-auto space-y-4 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <h3 class="font-bold text-base text-slate-800 dark:text-white">Filters</h3>
          <button @click="isMobileFilterOpen = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <FilterSidebar
          :categories="categories"
          :filters="currentFilters"
          @filter-change="(f) => { handleFilterChange(f); isMobileFilterOpen = false; }"
        />
      </div>
    </div>

    <!-- Contact Seller Modal -->
    <ContactSellerModal
      :is-open="isContactModalOpen"
      :product="selectedProduct"
      @close="isContactModalOpen = false"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ProductCard from '../../Components/Marketplace/ProductCard.vue';
import FilterSidebar from '../../Components/Marketplace/FilterSidebar.vue';
import ContactSellerModal from '../../Components/Marketplace/ContactSellerModal.vue';
import type { MarketplaceCategory, MarketplaceProduct, User, SuggestedFriend, UpcomingEvent } from '../../types';

const props = defineProps<{
  categories: MarketplaceCategory[];
  featuredListings: MarketplaceProduct[];
  latestListings: {
    data: MarketplaceProduct[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters: {
    category?: string;
    q?: string;
    min_price?: string | number | null;
    max_price?: string | number | null;
    location?: string;
    condition?: string;
    sort?: string;
  };
  pendingRequests?: any[];
  contacts?: User[];
  suggestions?: SuggestedFriend[];
  upcomingEvents?: UpcomingEvent[];
  unreadCount?: number;
  unreadMessagesCount?: number;
}>();

const activeCategory = ref(props.filters.category || 'all');
const currentSort = ref(props.filters.sort || 'newest');
const isMobileFilterOpen = ref(false);
const isContactModalOpen = ref(false);
const selectedProduct = ref<MarketplaceProduct | null>(null);

const currentFilters = reactive({
  ...props.filters,
});

const getCategoryEmoji = (slug: string) => {
  switch (slug) {
    case 'electronics': return '📱';
    case 'fashion': return '👕';
    case 'home-living': return '🏠';
    case 'sports': return '🏀';
    case 'books': return '📚';
    case 'vehicles': return '🚗';
    case 'jobs': return '💼';
    case 'services': return '🔧';
    case 'property': return '🏢';
    default: return '📦';
  }
};

const handleCategorySelect = (slug: string) => {
  activeCategory.value = slug;
  currentFilters.category = slug;
  executeSearch();
};

const handleSortChange = () => {
  currentFilters.sort = currentSort.value;
  executeSearch();
};

const handleFilterChange = (newFilters: any) => {
  Object.assign(currentFilters, newFilters);
  activeCategory.value = currentFilters.category || 'all';
  executeSearch();
};

const resetFilters = () => {
  activeCategory.value = 'all';
  currentFilters.category = 'all';
  currentFilters.q = '';
  currentFilters.min_price = '';
  currentFilters.max_price = '';
  currentFilters.location = '';
  currentFilters.condition = '';
  currentFilters.sort = 'newest';
  currentSort.value = 'newest';
  executeSearch();
};

const goToPage = (page: number) => {
  router.get('/marketplace', {
    ...cleanParams(currentFilters),
    page,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const executeSearch = () => {
  router.get('/marketplace', cleanParams(currentFilters), {
    preserveState: true,
    preserveScroll: true,
  });
};

const cleanParams = (params: any) => {
  const result: any = {};
  for (const [key, val] of Object.entries(params)) {
    if (val !== undefined && val !== null && val !== '' && val !== 'all') {
      result[key] = val;
    }
  }
  return result;
};

const openContactModal = (product: MarketplaceProduct) => {
  selectedProduct.value = product;
  isContactModalOpen.value = true;
};
</script>
