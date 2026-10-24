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
      
      <!-- Studio Header -->
      <div class="flex items-center justify-between">
        <div>
          <a href="/marketplace" class="text-xs font-bold text-slate-500 hover:text-[#1877F2] flex items-center gap-1 mb-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to Marketplace</span>
          </a>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
            Create Product Listing
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            List your item to reach thousands of buyers across the SocialHub community.
          </p>
        </div>

        <a
          href="/marketplace/my-listings"
          class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50"
        >
          My Listings
        </a>
      </div>

      <!-- Main Studio Grid (Form on Left, Live Card Preview on Right) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Form Wizard (7 cols) -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
          
          <!-- Stepper Indicator -->
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800 text-xs font-bold">
            <span class="text-[#1877F2]">Step {{ currentStep }} of 4</span>
            <span class="text-slate-400 font-normal">
              {{ currentStep === 1 ? 'Basic Information' : currentStep === 2 ? 'Price & Delivery' : currentStep === 3 ? 'Upload Photos' : 'Description & Review' }}
            </span>
          </div>

          <!-- STEP 1: Basic Information -->
          <div v-show="currentStep === 1" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                Listing Title <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.title"
                type="text"
                placeholder="e.g. MacBook Air M2 2022 (Midnight 256GB)"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-[#1877F2]"
              />
              <span v-if="errors.title" class="text-xs text-rose-500 mt-1 block">{{ errors.title }}</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                  Category <span class="text-rose-500">*</span>
                </label>
                <select
                  v-model="form.category_id"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-[#1877F2]"
                >
                  <option value="" disabled>Select a category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                  Condition <span class="text-rose-500">*</span>
                </label>
                <select
                  v-model="form.condition"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-[#1877F2]"
                >
                  <option value="new">Brand New</option>
                  <option value="used">Used (Normal wear)</option>
                  <option value="refurbished">Refurbished / Like New</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                  Brand (Optional)
                </label>
                <input
                  v-model="form.brand"
                  type="text"
                  placeholder="e.g. Apple, Sony, Nike"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-[#1877F2]"
                />
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                  Model / Spec (Optional)
                </label>
                <input
                  v-model="form.model"
                  type="text"
                  placeholder="e.g. 8GB Unified, 256GB SSD"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-[#1877F2]"
                />
              </div>
            </div>
          </div>

          <!-- STEP 2: Price & Delivery -->
          <div v-show="currentStep === 2" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                  Price ($) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold">$</span>
                  <input
                    v-model="form.price"
                    type="number"
                    min="0"
                    step="0.01"
                    placeholder="850"
                    class="w-full pl-8 pr-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-[#1877F2]"
                  />
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                  Location (City / Area) <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="form.location"
                  type="text"
                  placeholder="Dhaka, Bangladesh"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-[#1877F2]"
                />
              </div>
            </div>

            <div class="flex items-center gap-3 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700">
              <input
                id="negotiable"
                type="checkbox"
                v-model="form.is_negotiable"
                class="w-4 h-4 text-[#1877F2] rounded border-slate-300 focus:ring-[#1877F2]"
              />
              <label for="negotiable" class="text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                Price is negotiable
                <span class="block text-[11px] font-normal text-slate-400">Allows buyers to make reasonable offers.</span>
              </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                  Delivery Method
                </label>
                <select
                  v-model="form.delivery_type"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-[#1877F2]"
                >
                  <option value="pickup">Local Meetup / Pickup Only</option>
                  <option value="delivery">Courier / Shipping Delivery</option>
                  <option value="both">Both Pickup &amp; Delivery</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                  Delivery Charge ($)
                </label>
                <input
                  v-model="form.delivery_charge"
                  type="number"
                  min="0"
                  step="0.01"
                  placeholder="0 (Free Delivery)"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-[#1877F2]"
                />
              </div>
            </div>
          </div>

          <!-- STEP 3: Photos -->
          <div v-show="currentStep === 3" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                Upload Product Photos (Up to 8 photos)
              </label>
              
              <!-- Dropzone -->
              <div
                @click="triggerFileInput"
                class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-[#1877F2] rounded-3xl p-6 text-center cursor-pointer transition-all bg-slate-50/50 dark:bg-slate-800/30 group"
              >
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                  <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <p class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300">
                  Click to select images or drag and drop
                </p>
                <p class="text-[11px] text-slate-400 mt-0.5">
                  PNG, JPG, WEBP up to 15MB each
                </p>
                <input
                  ref="fileInputRef"
                  type="file"
                  multiple
                  accept="image/*"
                  class="hidden"
                  @change="handleFileUpload"
                />
              </div>
            </div>

            <!-- Uploaded Photos Strip -->
            <div v-if="uploadedImages.length > 0" class="grid grid-cols-4 gap-2 pt-2">
              <div
                v-for="(img, idx) in uploadedImages"
                :key="idx"
                class="relative aspect-[4/3] rounded-xl overflow-hidden border-2 group/thumb"
                :class="primaryImageIndex === idx ? 'border-[#1877F2] ring-2 ring-blue-500/20' : 'border-slate-200'"
              >
                <img :src="img" class="w-full h-full object-cover" />
                <button
                  type="button"
                  @click="removeImage(idx)"
                  class="absolute top-1 right-1 w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center opacity-0 group-hover/thumb:opacity-100 transition-opacity"
                >
                  ×
                </button>
                <button
                  type="button"
                  @click="primaryImageIndex = idx"
                  class="absolute bottom-1 left-1 px-1.5 py-0.5 rounded text-[9px] font-bold"
                  :class="primaryImageIndex === idx ? 'bg-[#1877F2] text-white' : 'bg-black/60 text-white hover:bg-[#1877F2]'"
                >
                  {{ primaryImageIndex === idx ? 'Cover' : 'Set Cover' }}
                </button>
              </div>
            </div>

            <!-- Online image URL fallback -->
            <div class="pt-2">
              <span class="text-xs text-slate-400 block mb-1">Or add image by URL:</span>
              <div class="flex gap-2">
                <input
                  v-model="imageUrlInput"
                  type="url"
                  placeholder="https://images.unsplash.com/photo-..."
                  class="flex-1 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs bg-white dark:bg-slate-800"
                />
                <button
                  type="button"
                  @click="addImageUrl"
                  class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-700"
                >
                  Add
                </button>
              </div>
            </div>
          </div>

          <!-- STEP 4: Description & Publish -->
          <div v-show="currentStep === 4" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                Detailed Item Description
              </label>
              <textarea
                v-model="form.description"
                rows="5"
                placeholder="Include details about item age, accessories included, cosmetic flaws, warranty status, and reason for selling..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-[#1877F2] resize-none"
              ></textarea>
            </div>

            <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/40 text-xs space-y-1">
              <span class="font-bold text-[#1877F2] block">Marketplace Seller Standards:</span>
              <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
                By publishing this listing, you confirm that you have physical possession of the product, all photos accurately represent the current condition, and your price complies with fair trade practices.
              </p>
            </div>

            <div v-if="submissionError" class="p-3 rounded-xl bg-rose-50 text-rose-700 text-xs">
              {{ submissionError }}
            </div>
          </div>

          <!-- Wizard Navigation Buttons -->
          <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <button
              v-if="currentStep > 1"
              type="button"
              @click="currentStep--"
              class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100"
            >
              ← Back
            </button>
            <div v-else></div>

            <button
              v-if="currentStep < 4"
              type="button"
              @click="validateAndNext"
              class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1877F2] hover:bg-[#1666D8] shadow-md shadow-blue-500/20"
            >
              Continue →
            </button>

            <button
              v-else
              type="button"
              @click="submitListing"
              :disabled="isSubmitting"
              class="px-7 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-lg shadow-emerald-600/20 disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="isSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
              <span>{{ isSubmitting ? 'Publishing...' : 'Publish Listing' }}</span>
            </button>
          </div>

        </div>

        <!-- Live Product Card Preview (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
          <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-bold text-xs uppercase tracking-wider text-slate-400">
                Live Card Preview
              </h3>
              <span class="text-[10px] bg-blue-100 dark:bg-blue-900 text-[#1877F2] font-bold px-2 py-0.5 rounded-md">
                Buyer View
              </span>
            </div>

            <!-- Preview Card -->
            <div class="w-full max-w-sm mx-auto">
              <ProductCard
                :product="previewProduct"
                @contact="() => {}"
              />
            </div>

            <p class="text-[11px] text-slate-400 text-center mt-4">
              This is how your item will appear in search results, feeds, and the category catalog.
            </p>
          </div>
        </div>

      </div>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ProductCard from '../../Components/Marketplace/ProductCard.vue';
import type { MarketplaceCategory, MarketplaceProduct, User, SuggestedFriend, UpcomingEvent } from '../../types';

const props = defineProps<{
  categories: MarketplaceCategory[];
  pendingRequests?: any[];
  contacts?: User[];
  suggestions?: SuggestedFriend[];
  upcomingEvents?: UpcomingEvent[];
  unreadCount?: number;
  unreadMessagesCount?: number;
}>();

const currentStep = ref(1);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isSubmitting = ref(false);
const submissionError = ref('');
const imageUrlInput = ref('');
const uploadedImages = ref<string[]>([
  'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&auto=format&fit=crop&q=80',
]);
const primaryImageIndex = ref(0);

const form = reactive({
  category_id: props.categories[0]?.id || '',
  title: '',
  description: '',
  price: '',
  condition: 'used',
  brand: '',
  model: '',
  is_negotiable: true,
  delivery_type: 'both',
  delivery_charge: '0',
  location: 'Dhaka, Bangladesh',
});

const errors = reactive<Record<string, string>>({});

const previewProduct = computed<MarketplaceProduct>(() => {
  const chosenCat = props.categories.find(c => c.id === Number(form.category_id)) || props.categories[0];
  const images = uploadedImages.value.length > 0 ? uploadedImages.value : ['https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80'];
  const primary = images[primaryImageIndex.value] || images[0];

  return {
    id: 9999,
    title: form.title || 'Your Product Title Goes Here',
    description: form.description || 'Item description will appear here...',
    price: Number(form.price) || 850,
    currency: 'USD',
    currency_symbol: '$',
    formatted_price: '$' + (Number(form.price) || 850),
    condition: form.condition,
    brand: form.brand || null,
    model: form.model || null,
    is_negotiable: form.is_negotiable,
    delivery_type: form.delivery_type,
    delivery_charge: Number(form.delivery_charge) || 0,
    location: form.location || 'Dhaka, Bangladesh',
    status: 'active',
    images,
    primary_image: primary,
    is_boosted: false,
    is_featured: false,
    is_saved: false,
    views_count: 0,
    time_ago: 'Just now',
    category: {
      id: chosenCat?.id || 1,
      name: chosenCat?.name || 'Electronics',
      slug: chosenCat?.slug || 'electronics',
    },
    seller: {
      id: 1,
      name: 'You (Current User)',
      username: 'you',
      avatar_url: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80',
      rating: 5.0,
      reviews_count: 1,
      verified: true,
    },
  };
});

const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const handleFileUpload = async (e: Event) => {
  const files = (e.target as HTMLInputElement).files;
  if (!files || files.length === 0) return;

  for (let i = 0; i < files.length; i++) {
    const file = files[i];
    const formData = new FormData();
    formData.append('image', file);

    try {
      const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
      const res = await fetch('/api/v1/marketplace/upload-media', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
        },
        body: formData,
      });
      if (res.ok) {
        const data = await res.json();
        if (data.url) {
          uploadedImages.value.push(data.url);
        }
      }
    } catch (err) {
      console.error('File upload error', err);
    }
  }
};

const addImageUrl = () => {
  if (imageUrlInput.value.trim()) {
    uploadedImages.value.push(imageUrlInput.value.trim());
    imageUrlInput.value = '';
  }
};

const removeImage = (index: number) => {
  uploadedImages.value.splice(index, 1);
  if (primaryImageIndex.value >= uploadedImages.value.length) {
    primaryImageIndex.value = Math.max(0, uploadedImages.value.length - 1);
  }
};

const validateAndNext = () => {
  delete errors.title;
  if (currentStep.value === 1) {
    if (!form.title.trim() || form.title.trim().length < 3) {
      errors.title = 'Title must be at least 3 characters.';
      return;
    }
  }
  currentStep.value++;
};

const submitListing = async () => {
  isSubmitting.value = true;
  submissionError.value = '';

  const reorderedImages = [...uploadedImages.value];
  if (primaryImageIndex.value > 0 && reorderedImages[primaryImageIndex.value]) {
    const primary = reorderedImages.splice(primaryImageIndex.value, 1)[0];
    reorderedImages.unshift(primary);
  }

  const payload = {
    category_id: form.category_id,
    title: form.title,
    description: form.description,
    price: Number(form.price) || 0,
    currency: 'USD',
    condition: form.condition,
    brand: form.brand || null,
    model: form.model || null,
    is_negotiable: form.is_negotiable,
    delivery_type: form.delivery_type,
    delivery_charge: Number(form.delivery_charge) || 0,
    location: form.location,
    images: reorderedImages,
  };

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch('/api/v1/marketplace/products', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json();
    if (res.ok && data.data?.id) {
      window.location.href = `/marketplace/${data.data.id}`;
    } else {
      submissionError.value = data.message || 'Failed to publish listing. Please check your inputs.';
    }
  } catch (err: any) {
    submissionError.value = err.message || 'Network error.';
  } finally {
    isSubmitting.value = false;
  }
};
</script>
