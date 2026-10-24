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
    <div class="max-w-6xl mx-auto space-y-6 pb-12">
      
      <!-- Breadcrumbs & Back Button -->
      <div class="flex items-center justify-between text-xs sm:text-sm">
        <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
          <a href="/marketplace" class="hover:text-[#1877F2] flex items-center gap-1 font-semibold">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to Marketplace</span>
          </a>
          <span>/</span>
          <a :href="'/marketplace?category=' + (product.category?.slug || '')" class="hover:text-[#1877F2] truncate max-w-[150px]">
            {{ product.category?.name || 'General' }}
          </a>
          <span>/</span>
          <span class="text-slate-800 dark:text-slate-200 font-bold truncate max-w-[200px]">
            {{ product.title }}
          </span>
        </div>

        <button
          type="button"
          @click="toggleSave"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold transition-all shadow-sm"
          :class="isSaved ? 'text-rose-500 border-rose-200' : 'text-slate-700 dark:text-slate-300 hover:text-rose-500'"
        >
          <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
            <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" />
          </svg>
          <span>{{ isSaved ? 'Saved' : 'Save Item' }}</span>
        </button>
      </div>

      <!-- Main Product View: 2 Columns (Gallery on Left, Info on Right) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Column 1: Image Gallery (7 cols) -->
        <div class="lg:col-span-7 space-y-3">
          <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="relative aspect-[4/3] w-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
              <img
                :src="selectedImage || product.primary_image || defaultImage"
                :alt="product.title"
                class="w-full h-full object-cover transition-all duration-300"
              />
              <div
                v-if="product.is_featured || product.is_boosted"
                class="absolute top-4 left-4 bg-[#1877F2] text-white text-xs font-bold px-3 py-1 rounded-lg shadow-md flex items-center gap-1.5"
              >
                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span>Featured Listing</span>
              </div>
            </div>

            <!-- Thumbnail Carousel / Strip -->
            <div v-if="product.images && product.images.length > 1" class="p-3 bg-slate-50 dark:bg-slate-800/50 flex items-center gap-2 overflow-x-auto">
              <button
                v-for="(img, idx) in product.images"
                :key="idx"
                type="button"
                @click="selectedImage = img"
                class="w-16 h-16 rounded-xl overflow-hidden border-2 transition-all shrink-0"
                :class="selectedImage === img ? 'border-[#1877F2] ring-2 ring-blue-500/20' : 'border-transparent opacity-70 hover:opacity-100'"
              >
                <img :src="img" :alt="product.title + ' preview'" class="w-full h-full object-cover" />
              </button>
            </div>
          </div>

          <!-- Product Details / Description Card -->
          <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <h3 class="font-bold text-base text-slate-800 dark:text-white">
              Description &amp; Specifications
            </h3>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line leading-relaxed">
              {{ product.description || 'No description provided.' }}
            </p>

            <!-- Specification Badges -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-3 gap-3">
              <div v-if="product.brand" class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Brand</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ product.brand }}</p>
              </div>

              <div v-if="product.model" class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Model</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5 truncate">{{ product.model }}</p>
              </div>

              <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Condition</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5 capitalize">{{ product.condition || 'Used' }}</p>
              </div>

              <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Delivery</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5 capitalize">{{ product.delivery_type || 'Pickup' }}</p>
              </div>

              <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Price Negotiable</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ product.is_negotiable ? 'Yes' : 'Fixed Price' }}</p>
              </div>

              <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Views</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ product.views_count }} views</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Column 2: Buy & Seller Info (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
          
          <!-- Price & Purchase Action Card -->
          <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <div>
              <span class="text-xs font-bold text-[#1877F2] uppercase tracking-wider">
                {{ product.category?.name || 'Item' }}
              </span>
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 leading-snug">
                {{ product.title }}
              </h1>
              
              <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-[#1877F2]">
                  {{ product.formatted_price || ('$' + product.price) }}
                </span>
                <span v-if="product.is_negotiable" class="text-xs font-semibold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-md">
                  Negotiable
                </span>
              </div>

              <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-400">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ product.location || 'Dhaka, Bangladesh' }}</span>
                <span>• Listed {{ product.time_ago || 'recently' }}</span>
              </div>
            </div>

            <!-- Primary CTAs: Message Seller & Buy with Escrow -->
            <div class="pt-2 space-y-2.5">
              <button
                type="button"
                @click="isContactModalOpen = true"
                class="w-full py-3 px-4 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center gap-2 hover:scale-[1.01] active:scale-[0.99]"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>Message Seller</span>
              </button>

              <button
                type="button"
                @click="openOrderModal"
                class="w-full py-3 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2 hover:scale-[1.01] active:scale-[0.99]"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>Buy Now with Escrow</span>
              </button>
            </div>

            <!-- Escrow Safety Notice Box -->
            <div class="p-3.5 rounded-2xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 space-y-1.5">
              <div class="flex items-center gap-2 text-xs font-bold text-[#1877F2]">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>এসক্রো সুরক্ষিত — SocialHub Escrow Payment Hold</span>
              </div>
              <p class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                আপনার অর্থ নিরাপদ পেমেন্ট হোল্ডে সংরক্ষিত থাকবে। পণ্য হাতে পেয়ে নিশ্চিত করার পরেই বিক্রেতার ফান্ড আনলক হবে।
              </p>
            </div>
          </div>

          <!-- Seller Information Card -->
          <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <h3 class="font-bold text-sm text-slate-800 dark:text-white">
              Seller Information
            </h3>

            <div class="flex items-center gap-3.5">
              <img
                :src="sellerAvatar"
                :alt="product.seller?.name || 'Seller'"
                class="w-12 h-12 rounded-full object-cover ring-2 ring-blue-500/20 shrink-0"
              />
              <div class="min-w-0 flex-1">
                <a
                  :href="sellerProfileUrl"
                  class="font-bold text-sm text-slate-900 dark:text-white hover:text-[#1877F2] truncate block"
                >
                  {{ product.seller?.name || 'Community Seller' }}
                </a>
                <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-500">
                  <span class="text-amber-500 font-bold">★ {{ Number(product.seller?.rating || 4.8).toFixed(1) }}</span>
                  <span>({{ product.seller?.reviews_count || 12 }} reviews)</span>
                </div>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-xs space-y-2 text-slate-600 dark:text-slate-300">
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Location:</span>
                <span class="font-semibold">{{ product.location || 'Dhaka, Bangladesh' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Response Rate:</span>
                <span class="font-semibold text-emerald-600">~100% within 1 hour</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Identity:</span>
                <span class="font-semibold text-[#1877F2] flex items-center gap-1">
                  <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  Verified User
                </span>
              </div>
            </div>

            <div class="pt-2">
              <button
                type="button"
                @click="isReportModalOpen = true"
                class="w-full text-center text-xs font-semibold text-rose-500 hover:text-rose-600 hover:underline"
              >
                Report this listing
              </button>
            </div>
          </div>

        </div>

      </div>

      <!-- Related Listings Grid -->
      <section v-if="product.related_products && product.related_products.length > 0" class="pt-8 border-t border-slate-200 dark:border-slate-800">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">
          Similar Products in {{ product.category?.name || 'this category' }}
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <ProductCard
            v-for="rel in product.related_products"
            :key="'related-' + rel.id"
            :product="rel"
            @contact="openContactModal"
          />
        </div>
      </section>

    </div>

    <!-- Contact Seller Modal -->
    <ContactSellerModal
      :is-open="isContactModalOpen"
      :product="product"
      @close="isContactModalOpen = false"
    />

    <!-- Buy / Secure Payment Hold Order Placement Modal -->
    <div v-if="isOrderModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <span>SocialHub Secure Checkout</span>
            </h3>
            <p class="text-[11px] text-slate-500">Marketplace Payment Hold Protection</p>
          </div>
          <button @click="isOrderModalOpen = false" class="text-slate-400 hover:text-slate-600">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
          <!-- Product Summary -->
          <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/50">
            <img
              :src="product.images?.[0] || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=100'"
              :alt="product.title"
              class="w-14 h-14 rounded-xl object-cover shrink-0"
            />
            <div class="min-w-0 flex-1">
              <h4 class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ product.title }}</h4>
              <p class="text-[11px] text-slate-500">Unit Price: {{ product.formatted_price || ('৳ ' + product.price) }}</p>
            </div>
            <!-- Quantity Selector -->
            <div class="flex items-center gap-1.5 bg-white dark:bg-slate-900 px-2 py-1 rounded-xl border border-slate-200 dark:border-slate-700">
              <button
                type="button"
                @click="orderQuantity = Math.max(1, orderQuantity - 1)"
                class="w-6 h-6 flex items-center justify-center text-slate-500 hover:text-slate-900 text-xs font-bold"
              >
                -
              </button>
              <span class="text-xs font-bold w-4 text-center">{{ orderQuantity }}</span>
              <button
                type="button"
                @click="orderQuantity = Math.min(20, orderQuantity + 1)"
                class="w-6 h-6 flex items-center justify-center text-slate-500 hover:text-slate-900 text-xs font-bold"
              >
                +
              </button>
            </div>
          </div>

          <!-- Delivery Type Selection -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              Fulfillment Method:
            </label>
            <div class="grid grid-cols-3 gap-2">
              <button
                type="button"
                @click="orderDeliveryType = 'standard'"
                class="p-2.5 rounded-xl border text-xs font-bold text-center transition-all"
                :class="orderDeliveryType === 'standard' ? 'border-[#1877F2] bg-blue-50/80 text-[#1877F2]' : 'border-slate-200 text-slate-600'"
              >
                <div>Standard</div>
                <div class="text-[10px] opacity-75 font-normal">৳ {{ product.delivery_charge || 0 }}</div>
              </button>
              <button
                type="button"
                @click="orderDeliveryType = 'express'"
                class="p-2.5 rounded-xl border text-xs font-bold text-center transition-all"
                :class="orderDeliveryType === 'express' ? 'border-[#1877F2] bg-blue-50/80 text-[#1877F2]' : 'border-slate-200 text-slate-600'"
              >
                <div>Express</div>
                <div class="text-[10px] opacity-75 font-normal">৳ {{ Math.round((Number(product.delivery_charge || 0) * 1.5) + 50) }}</div>
              </button>
              <button
                type="button"
                @click="orderDeliveryType = 'pickup'"
                class="p-2.5 rounded-xl border text-xs font-bold text-center transition-all"
                :class="orderDeliveryType === 'pickup' ? 'border-[#1877F2] bg-blue-50/80 text-[#1877F2]' : 'border-slate-200 text-slate-600'"
              >
                <div>Pickup</div>
                <div class="text-[10px] opacity-75 font-normal">Free (৳ 0)</div>
              </button>
            </div>
          </div>

          <!-- Recipient Name & Phone -->
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Recipient Name:
              </label>
              <input
                v-model="orderRecipientName"
                type="text"
                placeholder="Full name"
                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Contact Phone:
              </label>
              <input
                v-model="orderRecipientPhone"
                type="tel"
                placeholder="017xxxxxxxx"
                class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2]"
              />
            </div>
          </div>

          <!-- Shipping Address -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Delivery / Meeting Location:
            </label>
            <textarea
              v-model="orderAddress"
              rows="2"
              placeholder="House #, Road #, Area, City..."
              class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-[#1877F2] resize-none"
            ></textarea>
          </div>

          <!-- Payment Method Selection -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
              Payment Gateway:
            </label>
            <div class="grid grid-cols-3 gap-2">
              <button
                type="button"
                @click="orderPaymentMethod = 'bkash'"
                class="p-2 rounded-xl border text-xs font-bold transition-all text-center flex flex-col items-center gap-1"
                :class="orderPaymentMethod === 'bkash' ? 'border-[#E2136E] bg-pink-50 text-[#E2136E]' : 'border-slate-200 text-slate-600'"
              >
                <span>bKash</span>
                <span class="text-[9px] font-normal">Wallet / QR</span>
              </button>
              <button
                type="button"
                @click="orderPaymentMethod = 'nagad'"
                class="p-2 rounded-xl border text-xs font-bold transition-all text-center flex flex-col items-center gap-1"
                :class="orderPaymentMethod === 'nagad' ? 'border-[#F7941D] bg-orange-50 text-[#F7941D]' : 'border-slate-200 text-slate-600'"
              >
                <span>Nagad</span>
                <span class="text-[9px] font-normal">Direct Gateway</span>
              </button>
              <button
                type="button"
                @click="orderPaymentMethod = 'card'"
                class="p-2 rounded-xl border text-xs font-bold transition-all text-center flex flex-col items-center gap-1"
                :class="orderPaymentMethod === 'card' ? 'border-[#1877F2] bg-blue-50 text-[#1877F2]' : 'border-slate-200 text-slate-600'"
              >
                <span>Card / Bank</span>
                <span class="text-[9px] font-normal">Visa, Master</span>
              </button>
            </div>
          </div>

          <!-- Financial Calculation Breakdown -->
          <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700 text-xs space-y-1.5">
            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
              <span>Items Subtotal ({{ orderQuantity }}x):</span>
              <span class="font-semibold">৳ {{ calculatedSubtotal.toFixed(2) }}</span>
            </div>
            <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
              <span>Delivery Charge ({{ orderDeliveryType }}):</span>
              <span class="font-semibold">৳ {{ calculatedDeliveryCharge.toFixed(2) }}</span>
            </div>
            <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between font-extrabold text-sm text-slate-900 dark:text-white">
              <span>Total Payable:</span>
              <span class="text-[#1877F2]">৳ {{ calculatedTotal.toFixed(2) }}</span>
            </div>
          </div>

          <div v-if="orderFeedback" class="p-3 rounded-xl bg-emerald-50 text-emerald-800 text-xs">
            {{ orderFeedback }}
          </div>
          <div v-if="orderError" class="p-3 rounded-xl bg-rose-50 text-rose-800 text-xs">
            {{ orderError }}
          </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
          <button
            type="button"
            @click="isOrderModalOpen = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="placeEscrowOrder"
            :disabled="isPlacingOrder || !orderAddress.trim()"
            class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 flex items-center gap-1.5"
          >
            <span v-if="isPlacingOrder" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ isPlacingOrder ? 'Processing...' : 'Confirm Order & Pay' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Report Modal -->
    <div v-if="isReportModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
        <h3 class="font-bold text-base text-slate-800 dark:text-white">Report Listing</h3>
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Reason:</label>
          <select v-model="reportReason" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
            <option value="spam">Spam or Misleading</option>
            <option value="scam">Fraud or Scam</option>
            <option value="prohibited">Prohibited item</option>
            <option value="inappropriate">Inappropriate content</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Details:</label>
          <textarea v-model="reportDetails" rows="2" placeholder="Describe the issue..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs"></textarea>
        </div>
        <div class="flex justify-end gap-2">
          <button @click="isReportModalOpen = false" class="px-3 py-1.5 text-xs text-slate-500">Cancel</button>
          <button @click="submitReport" class="px-4 py-1.5 rounded-xl bg-rose-600 text-white text-xs font-bold">Submit Report</button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ProductCard from '../../Components/Marketplace/ProductCard.vue';
import ContactSellerModal from '../../Components/Marketplace/ContactSellerModal.vue';
import type { MarketplaceProduct, User, SuggestedFriend, UpcomingEvent } from '../../types';

const props = defineProps<{
  product: MarketplaceProduct;
  pendingRequests?: any[];
  contacts?: User[];
  suggestions?: SuggestedFriend[];
  upcomingEvents?: UpcomingEvent[];
  unreadCount?: number;
  unreadMessagesCount?: number;
}>();

const defaultImage = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80';
const selectedImage = ref(props.product.primary_image || (props.product.images && props.product.images[0]) || defaultImage);
const isSaved = ref(Boolean(props.product.is_saved));
const isContactModalOpen = ref(false);
const isOrderModalOpen = ref(false);
const isReportModalOpen = ref(false);

const orderAddress = ref('Dhaka, Bangladesh');
const orderRecipientName = ref('');
const orderRecipientPhone = ref('');
const orderPaymentMethod = ref('bkash');
const orderDeliveryType = ref('standard');
const orderQuantity = ref(1);

const isPlacingOrder = ref(false);
const orderFeedback = ref('');
const orderError = ref('');

const reportReason = ref('spam');
const reportDetails = ref('');

const calculatedSubtotal = computed(() => {
  const price = Number(props.product.price || 0);
  return price * orderQuantity.value;
});

const calculatedDeliveryCharge = computed(() => {
  if (orderDeliveryType.value === 'pickup') return 0;
  const baseCharge = Number(props.product.delivery_charge || 0);
  if (orderDeliveryType.value === 'express') {
    return Math.round(baseCharge * 1.5 + 50);
  }
  return baseCharge;
});

const calculatedTotal = computed(() => {
  return calculatedSubtotal.value + calculatedDeliveryCharge.value;
});

const sellerAvatar = computed(() => {
  return props.product.seller?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';
});

const sellerProfileUrl = computed(() => {
  return props.product.seller?.username ? `/profile/${props.product.seller.username}` : '#';
});

const toggleSave = async () => {
  isSaved.value = !isSaved.value;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    await fetch(`/api/v1/marketplace/products/${props.product.id}/favorite`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
    });
  } catch (e) {
    console.error(e);
  }
};

const openOrderModal = () => {
  isOrderModalOpen.value = true;
  orderFeedback.value = '';
  orderError.value = '';
};

const placeEscrowOrder = async () => {
  if (!orderAddress.value.trim() || isPlacingOrder.value) return;

  isPlacingOrder.value = true;
  orderError.value = '';
  orderFeedback.value = '';

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v2/marketplace/products/${props.product.id}/order`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        shipping_address: orderAddress.value.trim(),
        shipping_name: orderRecipientName.value.trim() || undefined,
        shipping_phone: orderRecipientPhone.value.trim() || undefined,
        delivery_type: orderDeliveryType.value,
        quantity: orderQuantity.value,
        payment_method: orderPaymentMethod.value,
      }),
    });

    const data = await res.json();
    if (res.ok && data.status === 'success') {
      const order = data.data;
      orderFeedback.value = 'Order placed successfully! Redirecting to secure payment...';

      // If online payment method, initiate checkout session
      if (['bkash', 'nagad', 'card', 'sslcommerz'].includes(orderPaymentMethod.value)) {
        try {
          const checkoutRes = await fetch(`/api/v2/marketplace/orders/${order.id}/checkout`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json',
            },
            body: JSON.stringify({ gateway: orderPaymentMethod.value }),
          });
          const checkoutData = await checkoutRes.json();
          if (checkoutData.data?.checkout_url && !checkoutData.data.checkout_url.startsWith('/')) {
            window.location.href = `/marketplace/orders/${order.id}`;
            return;
          }
        } catch (_) {}
      }

      setTimeout(() => {
        window.location.href = `/marketplace/orders?tab=purchases`;
      }, 1000);
    } else {
      orderError.value = data.message || 'Failed to place order. Please ensure you are logged in.';
    }
  } catch (err: any) {
    orderError.value = err.message || 'Network error.';
  } finally {
    isPlacingOrder.value = false;
  }
};

const submitReport = async () => {
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    await fetch(`/api/v1/marketplace/products/${props.product.id}/report`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        reason: reportReason.value,
        details: reportDetails.value,
      }),
    });
    alert('Thank you for reporting. Our moderation team will investigate.');
    isReportModalOpen.value = false;
  } catch (e) {
    console.error(e);
  }
};

const openContactModal = () => {
  isContactModalOpen.value = true;
};
</script>
