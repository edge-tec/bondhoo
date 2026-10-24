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
    <div class="max-w-5xl mx-auto space-y-6 pb-12">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <a href="/marketplace" class="text-xs font-bold text-slate-500 hover:text-[#1877F2] flex items-center gap-1 mb-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to Marketplace</span>
          </a>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
            My Marketplace Listings
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            Manage your active items, mark products as sold, or update details.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <a
            href="/marketplace/orders?tab=sales"
            class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 shadow-sm"
          >
            Escrow Sales &amp; Orders
          </a>
          <a
            href="/marketplace/create"
            class="px-4 py-2.5 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold shadow-md shadow-blue-500/20"
          >
            + Create Listing
          </a>
        </div>
      </div>

      <!-- Status Filter Tabs -->
      <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button
          type="button"
          @click="filterStatus('')"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all relative"
          :class="!selectedStatus ? 'text-[#1877F2]' : 'text-slate-500 hover:bg-slate-100'"
        >
          All ({{ listings.total }})
          <span v-if="!selectedStatus" class="absolute bottom-[-9px] left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
        </button>

        <button
          type="button"
          @click="filterStatus('active')"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all relative"
          :class="selectedStatus === 'active' ? 'text-[#1877F2]' : 'text-slate-500 hover:bg-slate-100'"
        >
          Active
          <span v-if="selectedStatus === 'active'" class="absolute bottom-[-9px] left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
        </button>

        <button
          type="button"
          @click="filterStatus('sold')"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all relative"
          :class="selectedStatus === 'sold' ? 'text-[#1877F2]' : 'text-slate-500 hover:bg-slate-100'"
        >
          Sold
          <span v-if="selectedStatus === 'sold'" class="absolute bottom-[-9px] left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
        </button>

        <button
          type="button"
          @click="filterStatus('inactive')"
          class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all relative"
          :class="selectedStatus === 'inactive' ? 'text-[#1877F2]' : 'text-slate-500 hover:bg-slate-100'"
        >
          Inactive / Draft
          <span v-if="selectedStatus === 'inactive'" class="absolute bottom-[-9px] left-2 right-2 h-0.5 bg-[#1877F2] rounded-full"></span>
        </button>
      </div>

      <!-- Listings Table / Cards -->
      <div v-if="items.length > 0" class="space-y-3">
        <div
          v-for="item in items"
          :key="item.id"
          class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all hover:shadow-md"
        >
          <div class="flex items-center gap-4 min-w-0">
            <img
              :src="item.primary_image || defaultImage"
              :alt="item.title"
              class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700"
            />
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span
                  class="px-2 py-0.5 text-[10px] font-bold rounded-md uppercase"
                  :class="item.status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : item.status === 'sold' ? 'bg-slate-100 text-slate-700' : 'bg-amber-50 text-amber-700'"
                >
                  {{ item.status }}
                </span>
                <span class="text-xs text-slate-400">
                  {{ item.time_ago }}
                </span>
              </div>

              <a
                :href="'/marketplace/' + item.id"
                class="font-bold text-sm sm:text-base text-slate-800 dark:text-white hover:text-[#1877F2] truncate block mt-0.5"
              >
                {{ item.title }}
              </a>

              <div class="flex items-center gap-3 text-xs mt-1">
                <span class="font-extrabold text-[#1877F2]">
                  {{ item.formatted_price || ('$' + item.price) }}
                </span>
                <span class="text-slate-400">• {{ item.views_count }} views</span>
                <span class="text-slate-400">• {{ item.location }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
            <a
              :href="'/marketplace/' + item.id"
              class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-xs font-semibold text-slate-700 dark:text-slate-200"
            >
              View
            </a>

            <!-- Status Changer -->
            <button
              v-if="item.status === 'active'"
              type="button"
              @click="updateStatus(item, 'sold')"
              class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-700"
            >
              Mark as Sold
            </button>
            <button
              v-else-if="item.status === 'sold'"
              type="button"
              @click="updateStatus(item, 'active')"
              class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-semibold hover:bg-emerald-100"
            >
              Re-activate
            </button>

            <!-- Delete -->
            <button
              type="button"
              @click="deleteItem(item)"
              class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors"
              title="Delete Listing"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else
        class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center shadow-sm space-y-3"
      >
        <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center mx-auto">
          <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
        <h3 class="text-base font-bold text-slate-800 dark:text-white">You have no listings here</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto">
          Start decluttering and reach verified buyers on SocialHub Marketplace.
        </p>
        <a
          href="/marketplace/create"
          class="inline-block mt-2 px-5 py-2.5 rounded-xl bg-[#1877F2] text-white text-xs font-bold shadow-md shadow-blue-500/20"
        >
          Create First Listing
        </a>
      </div>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { MarketplaceProduct, User, SuggestedFriend, UpcomingEvent } from '../../types';

const props = defineProps<{
  listings: {
    data: MarketplaceProduct[];
    current_page: number;
    last_page: number;
    total: number;
  };
  currentStatus?: string;
  pendingRequests?: any[];
  contacts?: User[];
  suggestions?: SuggestedFriend[];
  upcomingEvents?: UpcomingEvent[];
  unreadCount?: number;
  unreadMessagesCount?: number;
}>();

const items = ref<MarketplaceProduct[]>([...props.listings.data]);
const selectedStatus = ref(props.currentStatus || '');
const defaultImage = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80';

const filterStatus = (status: string) => {
  selectedStatus.value = status;
  router.get('/marketplace/my-listings', status ? { status } : {}, {
    preserveState: true,
  });
};

const updateStatus = async (item: MarketplaceProduct, newStatus: string) => {
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/marketplace/products/${item.id}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ status: newStatus }),
    });
    if (res.ok) {
      item.status = newStatus;
    }
  } catch (e) {
    console.error('Failed to update status', e);
  }
};

const deleteItem = async (item: MarketplaceProduct) => {
  if (!confirm(`Are you sure you want to delete "${item.title}"?`)) return;

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/marketplace/products/${item.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
    });
    if (res.ok) {
      items.value = items.value.filter(i => i.id !== item.id);
    }
  } catch (e) {
    console.error('Failed to delete listing', e);
  }
};
</script>
