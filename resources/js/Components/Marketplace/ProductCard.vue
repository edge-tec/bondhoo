<template>
  <div class="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-[0_2px_8px_rgba(0,0,0,0.04)] hover:shadow-lg transition-all duration-300 flex flex-col overflow-hidden relative">
    
    <!-- Product Image Container -->
    <div class="relative aspect-[4/3] w-full bg-slate-100 dark:bg-slate-800 overflow-hidden cursor-pointer" @click="goToDetail">
      <img
        :src="product.primary_image || (product.images && product.images[0]) || defaultImage"
        :alt="product.title"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
        loading="lazy"
        @error="handleImageError"
      />

      <!-- "Featured" Badge -->
      <div
        v-if="product.is_featured || product.is_boosted"
        class="absolute bottom-2.5 left-2.5 bg-[#1877F2] text-white text-[10px] sm:text-xs font-bold px-2.5 py-0.5 rounded-md shadow-md flex items-center gap-1 z-10"
      >
        <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20">
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
        <span>Featured</span>
      </div>

      <!-- Condition Tag (New / Refurbished) -->
      <div
        v-if="product.condition === 'new'"
        class="absolute top-2.5 left-2.5 bg-emerald-600/90 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-sm z-10"
      >
        Brand New
      </div>

      <!-- Favorite / Save Heart Button -->
      <button
        type="button"
        @click.stop="toggleSave"
        :aria-label="isSaved ? 'Remove from saved' : 'Save listing'"
        class="absolute top-2.5 right-2.5 w-8 h-8 rounded-full bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm flex items-center justify-center text-slate-600 dark:text-slate-300 hover:text-rose-600 hover:bg-white transition-all shadow-sm z-10"
      >
        <svg
          class="w-4 h-4 transition-transform active:scale-125"
          :class="isSaved ? 'text-rose-500 fill-current' : 'fill-none'"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
        </svg>
      </button>
    </div>

    <!-- Card Body -->
    <div class="p-3.5 flex-1 flex flex-col justify-between">
      <div>
        <!-- Title -->
        <h3
          @click="goToDetail"
          class="font-semibold text-sm text-slate-800 dark:text-slate-100 hover:text-[#1877F2] dark:hover:text-[#1877F2] transition-colors line-clamp-1 cursor-pointer"
          :title="product.title"
        >
          {{ product.title }}
        </h3>

        <!-- Price -->
        <div class="mt-1 flex items-baseline gap-1.5">
          <span class="text-base font-extrabold text-[#1877F2]">
            {{ product.formatted_price || ('$' + product.price) }}
          </span>
          <span v-if="product.is_negotiable" class="text-[11px] font-medium text-slate-400">
            (Negotiable)
          </span>
        </div>

        <!-- Location & Time -->
        <div class="mt-1 flex items-center gap-1 text-[11px] text-slate-400 truncate">
          <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <span class="truncate">{{ product.location || 'Dhaka, Bangladesh' }}</span>
          <span class="mx-0.5">•</span>
          <span class="shrink-0">{{ product.time_ago || 'Recently' }}</span>
        </div>
      </div>

      <!-- Seller Footer -->
      <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
        <a
          :href="sellerProfileUrl"
          class="flex items-center gap-2 group/seller min-w-0"
          @click.stop
        >
          <img
            :src="sellerAvatar"
            :alt="product.seller?.name || 'Seller'"
            class="w-6 h-6 rounded-full object-cover shrink-0 ring-1 ring-slate-200 dark:ring-slate-700"
            @error="handleAvatarError"
          />
          <div class="min-w-0">
            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 group-hover/seller:text-[#1877F2] truncate">
              {{ product.seller?.name || 'Community Seller' }}
            </p>
            <div class="flex items-center gap-1 text-[10px] text-amber-500 font-bold">
              <span>★ {{ sellerRatingFormatted }}</span>
              <span class="text-slate-400 font-normal">({{ sellerReviewsCount }})</span>
            </div>
          </div>
        </a>

        <!-- Chat Bubble Button -->
        <button
          type="button"
          @click.stop="$emit('contact', product)"
          class="w-8 h-8 rounded-full border border-slate-200 dark:border-slate-700 hover:border-[#1877F2] hover:bg-blue-50/50 dark:hover:bg-blue-900/30 text-slate-500 hover:text-[#1877F2] flex items-center justify-center transition-all shrink-0 ml-2"
          title="Message Seller"
          aria-label="Message Seller"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { MarketplaceProduct } from '../../types';

const props = defineProps<{
  product: MarketplaceProduct;
}>();

const emit = defineEmits<{
  (e: 'contact', product: MarketplaceProduct): void;
  (e: 'saved-toggle', isSaved: boolean): void;
}>();

const defaultImage = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80';
const isSaved = ref(Boolean(props.product.is_saved));

const sellerAvatar = computed(() => {
  return props.product.seller?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';
});

const sellerProfileUrl = computed(() => {
  return props.product.seller?.username ? `/profile/${props.product.seller.username}` : '#';
});

const sellerRatingFormatted = computed(() => {
  const rating = Number(props.product.seller?.rating || 4.8);
  return rating.toFixed(1);
});

const sellerReviewsCount = computed(() => {
  return props.product.seller?.reviews_count || 12;
});

const goToDetail = () => {
  window.location.href = `/marketplace/${props.product.id}`;
};

const handleImageError = (e: Event) => {
  const target = e.target as HTMLImageElement;
  target.src = defaultImage;
};

const handleAvatarError = (e: Event) => {
  const target = e.target as HTMLImageElement;
  target.src = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';
};

const toggleSave = async () => {
  isSaved.value = !isSaved.value;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/marketplace/products/${props.product.id}/favorite`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
    });
    if (res.ok) {
      const data = await res.json();
      if (typeof data.is_favorited === 'boolean') {
        isSaved.value = data.is_favorited;
      }
    }
  } catch (err) {
    console.error('Failed to toggle favorite', err);
  }
  emit('saved-toggle', isSaved.value);
};
</script>
