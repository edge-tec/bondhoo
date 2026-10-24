<template>
  <div class="space-y-4">
    <!-- Filters Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-base text-slate-800 dark:text-white">
          Filters
        </h3>
        <button
          type="button"
          @click="clearFilters"
          class="text-xs font-semibold text-[#1877F2] hover:underline"
        >
          Clear All
        </button>
      </div>

      <!-- Search Input -->
      <div class="mb-4">
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </span>
          <input
            v-model="localFilters.q"
            type="text"
            placeholder="Search marketplace..."
            @keyup.enter="applyFilters"
            class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#1877F2] transition-all"
          />
        </div>
      </div>

      <!-- Category Select -->
      <div class="mb-4">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
          Category
        </label>
        <div class="relative">
          <select
            v-model="localFilters.category"
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1877F2] appearance-none cursor-pointer pr-8"
          >
            <option value="all">All Categories</option>
            <option
              v-for="cat in categories"
              :key="cat.id"
              :value="cat.slug"
            >
              {{ cat.name }}
            </option>
          </select>
          <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Price Range -->
      <div class="mb-4">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
          Price Range
        </label>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <span class="text-[10px] text-slate-400 uppercase font-semibold">Min</span>
            <div class="relative mt-0.5">
              <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-xs font-bold">$</span>
              <input
                v-model="localFilters.min_price"
                type="number"
                min="0"
                placeholder="0"
                class="w-full pl-6 pr-2 py-1.5 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1877F2]"
              />
            </div>
          </div>
          <div>
            <span class="text-[10px] text-slate-400 uppercase font-semibold">Max</span>
            <div class="relative mt-0.5">
              <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-xs font-bold">$</span>
              <input
                v-model="localFilters.max_price"
                type="number"
                min="0"
                placeholder="10,000+"
                class="w-full pl-6 pr-2 py-1.5 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1877F2]"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Location -->
      <div class="mb-4">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
          Location
        </label>
        <div class="relative">
          <select
            v-model="localFilters.location"
            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 text-xs sm:text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-[#1877F2] appearance-none cursor-pointer pr-8"
          >
            <option value="">All Locations</option>
            <option value="Dhaka, Bangladesh">Dhaka, Bangladesh</option>
            <option value="Chittagong, Bangladesh">Chittagong, Bangladesh</option>
            <option value="Sylhet, Bangladesh">Sylhet, Bangladesh</option>
            <option value="Rajshahi, Bangladesh">Rajshahi, Bangladesh</option>
            <option value="Khulna, Bangladesh">Khulna, Bangladesh</option>
          </select>
          <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Condition -->
      <div class="mb-5">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">
          Condition
        </label>
        <div class="space-y-2">
          <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300">
            <input
              type="radio"
              name="condition"
              value=""
              v-model="localFilters.condition"
              class="w-4 h-4 text-[#1877F2] rounded focus:ring-[#1877F2] border-slate-300"
            />
            <span>All Conditions</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300">
            <input
              type="radio"
              name="condition"
              value="new"
              v-model="localFilters.condition"
              class="w-4 h-4 text-[#1877F2] rounded focus:ring-[#1877F2] border-slate-300"
            />
            <span>New</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-700 dark:text-slate-300">
            <input
              type="radio"
              name="condition"
              value="used"
              v-model="localFilters.condition"
              class="w-4 h-4 text-[#1877F2] rounded focus:ring-[#1877F2] border-slate-300"
            />
            <span>Used</span>
          </label>
        </div>
      </div>

      <!-- Apply Button -->
      <button
        type="button"
        @click="applyFilters"
        class="w-full py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-[#1877F2] hover:bg-[#1666D8] transition-all shadow-md shadow-blue-500/20 active:scale-[0.98]"
      >
        Apply Filters
      </button>
    </div>

    <!-- Buy & Sell with Confidence Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm text-center">
      <div class="flex items-center justify-center gap-2 mb-3">
        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center shadow-inner">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center shadow-inner">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
      </div>

      <h4 class="font-bold text-sm text-slate-800 dark:text-white">
        Buy &amp; Sell with Confidence
      </h4>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
        Safe transactions, trusted sellers, and a great community.
      </p>

      <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/80 space-y-2 text-left">
        <div class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300">
          <div class="w-4 h-4 rounded-full bg-blue-100 dark:bg-blue-900/50 text-[#1877F2] flex items-center justify-center shrink-0">
            <svg class="w-2.5 h-2.5 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <span>Verified Sellers</span>
        </div>

        <div class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300">
          <div class="w-4 h-4 rounded-full bg-blue-100 dark:bg-blue-900/50 text-[#1877F2] flex items-center justify-center shrink-0">
            <svg class="w-2.5 h-2.5 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <span>Secure Payments</span>
        </div>

        <div class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300">
          <div class="w-4 h-4 rounded-full bg-blue-100 dark:bg-blue-900/50 text-[#1877F2] flex items-center justify-center shrink-0">
            <svg class="w-2.5 h-2.5 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <span>Community Protection</span>
        </div>
      </div>
    </div>

    <!-- Top Categories Card -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm">
      <h4 class="font-bold text-sm text-slate-800 dark:text-white mb-3">
        Top Categories
      </h4>
      <div class="space-y-1.5">
        <button
          v-for="cat in topCategories"
          :key="cat.id"
          type="button"
          @click="selectCategory(cat.slug)"
          class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/80 transition-colors text-left group"
          :class="localFilters.category === cat.slug ? 'bg-blue-50/70 dark:bg-blue-950/30' : ''"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <span class="text-sm shrink-0">
              {{ getCategoryEmoji(cat.slug) }}
            </span>
            <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 group-hover:text-[#1877F2] truncate" :class="localFilters.category === cat.slug ? 'text-[#1877F2] font-bold' : ''">
              {{ cat.name }}
            </span>
          </div>
          <span class="text-[11px] font-bold text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-200 shrink-0">
            {{ formatCount(cat.products_count) }}
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed, watch } from 'vue';
import type { MarketplaceCategory } from '../../types';

const props = defineProps<{
  categories: MarketplaceCategory[];
  filters: {
    category?: string;
    q?: string;
    min_price?: string | number | null;
    max_price?: string | number | null;
    location?: string;
    condition?: string;
    sort?: string;
  };
}>();

const emit = defineEmits<{
  (e: 'filter-change', filters: any): void;
}>();

const localFilters = reactive({
  category: props.filters.category || 'all',
  q: props.filters.q || '',
  min_price: props.filters.min_price || '',
  max_price: props.filters.max_price || '',
  location: props.filters.location || '',
  condition: props.filters.condition || '',
  sort: props.filters.sort || 'newest',
});

watch(
  () => props.filters,
  (newVal) => {
    Object.assign(localFilters, newVal);
  },
  { deep: true }
);

const topCategories = computed(() => {
  return props.categories.slice(0, 5);
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

const formatCount = (count?: number) => {
  if (!count) return '0';
  if (count >= 1000) {
    return (count / 1000).toFixed(1) + 'k';
  }
  return String(count);
};

const selectCategory = (slug: string) => {
  localFilters.category = slug;
  applyFilters();
};

const applyFilters = () => {
  emit('filter-change', { ...localFilters });
};

const clearFilters = () => {
  localFilters.category = 'all';
  localFilters.q = '';
  localFilters.min_price = '';
  localFilters.max_price = '';
  localFilters.location = '';
  localFilters.condition = '';
  localFilters.sort = 'newest';
  emit('filter-change', { ...localFilters });
};
</script>
