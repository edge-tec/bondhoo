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
      
      <!-- Breadcrumbs & Top Actions -->
      <div class="flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm">
        <div class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
          <a href="/marketplace" class="hover:text-[#1877F2] flex items-center gap-1 font-semibold">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to Marketplace</span>
          </a>
          <span>/</span>
          <a :href="'/marketplace?category=' + (liveProduct.category?.slug || '')" class="hover:text-[#1877F2] truncate max-w-[150px]">
            {{ liveProduct.category?.name || 'General' }}
          </a>
          <span>/</span>
          <span class="text-slate-800 dark:text-slate-200 font-bold truncate max-w-[200px]">
            {{ liveProduct.title }}
          </span>
        </div>

        <div class="flex items-center gap-2">
          <!-- Share Button (Available to everyone) -->
          <button
            type="button"
            @click="shareListing"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-[#1877F2] transition-all shadow-sm cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
            </svg>
            <span>{{ shareCopied ? 'Link Copied!' : 'Share' }}</span>
          </button>

          <!-- Owner Badge vs Non-Owner Save Button -->
          <span
            v-if="isOwner"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 text-xs font-bold text-[#1877F2]"
          >
            🏷️ Your Listing ({{ liveProduct.status }})
          </span>
          <button
            v-else
            type="button"
            @click="toggleSave"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs font-bold transition-all shadow-sm cursor-pointer"
            :class="isSaved ? 'text-rose-500 border-rose-200' : 'text-slate-700 dark:text-slate-300 hover:text-rose-500'"
          >
            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
              <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" />
            </svg>
            <span>{{ isSaved ? 'Saved' : 'Save Item' }}</span>
          </button>
        </div>
      </div>

      <!-- Main Product View: 2 Columns (Gallery on Left, Info on Right) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Column 1: Image Gallery (7 cols) -->
        <div class="lg:col-span-7 space-y-3">
          <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="relative aspect-[4/3] w-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
              <img
                :src="selectedImage || liveProduct.primary_image || defaultImage"
                :alt="liveProduct.title"
                class="w-full h-full object-cover transition-all duration-300"
              />
              <div
                v-if="liveProduct.is_featured || liveProduct.is_boosted"
                class="absolute top-4 left-4 bg-[#1877F2] text-white text-xs font-bold px-3 py-1 rounded-lg shadow-md flex items-center gap-1.5"
              >
                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span>Featured Listing</span>
              </div>
              <div
                v-if="liveProduct.status !== 'active'"
                class="absolute top-4 right-4 bg-slate-900/90 text-white text-xs font-extrabold uppercase px-3 py-1 rounded-lg shadow-md"
              >
                {{ liveProduct.status }}
              </div>
            </div>

            <!-- Thumbnail Carousel / Strip -->
            <div v-if="liveProduct.images && liveProduct.images.length > 1" class="p-3 bg-slate-50 dark:bg-slate-800/50 flex items-center gap-2 overflow-x-auto">
              <button
                v-for="(img, idx) in liveProduct.images"
                :key="idx"
                type="button"
                @click="selectedImage = img"
                class="w-16 h-16 rounded-xl overflow-hidden border-2 transition-all shrink-0"
                :class="selectedImage === img ? 'border-[#1877F2] ring-2 ring-blue-500/20' : 'border-transparent opacity-70 hover:opacity-100'"
              >
                <img :src="img" :alt="liveProduct.title + ' preview'" class="w-full h-full object-cover" />
              </button>
            </div>
          </div>

          <!-- Product Details / Description Card -->
          <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <h3 class="font-bold text-base text-slate-800 dark:text-white">
              Description &amp; Specifications
            </h3>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line leading-relaxed">
              {{ liveProduct.description || 'No description provided.' }}
            </p>

            <!-- Specification Badges -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 sm:grid-cols-3 gap-3">
              <div v-if="liveProduct.brand" class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Brand</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ liveProduct.brand }}</p>
              </div>

              <div v-if="liveProduct.model" class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Model</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5 truncate">{{ liveProduct.model }}</p>
              </div>

              <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Condition</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5 capitalize">{{ liveProduct.condition || 'Used' }}</p>
              </div>

              <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Delivery</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5 capitalize">{{ liveProduct.delivery_type || 'Pickup' }}</p>
              </div>

              <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Price Negotiable</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ liveProduct.is_negotiable ? 'Yes' : 'Fixed Price' }}</p>
              </div>

              <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Views</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ liveProduct.views_count }} views</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Column 2: Owner Controls OR Buyer Purchase & Seller Info (5 cols) -->
        <div class="lg:col-span-5 space-y-4">
          
          <!-- Price & Action Card -->
          <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm space-y-4">
            <div>
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold text-[#1877F2] uppercase tracking-wider">
                  {{ liveProduct.category?.name || 'Item' }}
                </span>
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                  :class="liveProduct.status === 'active' ? 'bg-emerald-50 text-emerald-700' : liveProduct.status === 'sold' ? 'bg-slate-200 text-slate-700' : 'bg-amber-50 text-amber-700'"
                >
                  {{ liveProduct.status }}
                </span>
              </div>
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 leading-snug">
                {{ liveProduct.title }}
              </h1>
              
              <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-[#1877F2]">
                  {{ liveProduct.formatted_price || ('৳ ' + liveProduct.price) }}
                </span>
                <span v-if="liveProduct.is_negotiable" class="text-xs font-semibold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-md">
                  Negotiable
                </span>
              </div>

              <div class="mt-2 flex items-center gap-1.5 text-xs text-slate-400">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ liveProduct.location || 'Dhaka, Bangladesh' }}</span>
                <span>• Listed {{ liveProduct.time_ago || 'recently' }}</span>
              </div>
            </div>

            <!-- OWNER CONTROLS (Strict Ownership Separation) -->
            <div v-if="isOwner" class="pt-2 space-y-2.5">
              <div class="p-3 rounded-2xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 text-xs text-slate-700 dark:text-slate-300">
                <strong class="text-[#1877F2]">Seller Management:</strong> You own this listing. Edit details, pause visibility, mark as sold, or manage incoming orders and offers.
              </div>

              <button
                type="button"
                @click="openEditModal"
                class="w-full py-3 px-4 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-md shadow-blue-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>✏️ Edit Listing</span>
              </button>

              <div class="grid grid-cols-2 gap-2">
                <button
                  v-if="liveProduct.status === 'active'"
                  type="button"
                  @click="changeListingStatus('inactive')"
                  :disabled="isOwnerActionBusy"
                  class="py-2.5 px-3 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-800 text-xs font-bold transition-all cursor-pointer disabled:opacity-50"
                >
                  ⏸ Pause Listing
                </button>
                <button
                  v-else
                  type="button"
                  @click="changeListingStatus('active')"
                  :disabled="isOwnerActionBusy"
                  class="py-2.5 px-3 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 text-xs font-bold transition-all cursor-pointer disabled:opacity-50"
                >
                  ▶ Activate Listing
                </button>

                <button
                  v-if="liveProduct.status !== 'sold'"
                  type="button"
                  @click="changeListingStatus('sold')"
                  :disabled="isOwnerActionBusy"
                  class="py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-800 text-xs font-bold transition-all cursor-pointer disabled:opacity-50"
                >
                  ✓ Mark as Sold
                </button>
                <a
                  v-else
                  href="/marketplace/orders?tab=sales"
                  class="py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-800 text-xs font-bold text-center transition-all"
                >
                  📦 View Sale Orders
                </a>
              </div>

              <a
                href="/marketplace/orders?tab=offers_received"
                class="w-full py-2.5 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 text-slate-700 dark:text-slate-200 text-xs font-bold flex items-center justify-center gap-1.5 transition-all"
              >
                <span>🤝 Manage Seller Orders &amp; Offers</span>
              </a>

              <button
                type="button"
                @click="isDeleteConfirmOpen = true"
                :disabled="isOwnerActionBusy"
                class="w-full py-2.5 px-4 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-600 text-xs font-bold transition-all cursor-pointer disabled:opacity-50"
              >
                🗑️ Delete My Listing
              </button>
            </div>

            <!-- NON-OWNER BUYER CONTROLS -->
            <div v-else class="pt-2 space-y-2.5">
              <button
                type="button"
                @click="requireAuthOr(() => { isContactModalOpen = true })"
                class="w-full py-3 px-4 rounded-2xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-sm font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span>Message Seller</span>
              </button>

              <template v-if="liveProduct.status === 'active'">
                <button
                  type="button"
                  @click="requireAuthOr(openOfferModal)"
                  class="w-full py-2.5 px-4 rounded-2xl border border-[#1877F2] bg-blue-50/60 dark:bg-blue-950/20 hover:bg-blue-100/70 text-[#1877F2] text-sm font-bold transition-all flex items-center justify-center gap-2 cursor-pointer"
                >
                  <span>🤝 Make an Offer</span>
                </button>

                <button
                  type="button"
                  @click="requireAuthOr(openOrderModal)"
                  class="w-full py-3 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer"
                >
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                  <span>Buy Now with Escrow</span>
                </button>
              </template>
              <div
                v-else
                class="w-full py-3 px-4 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 text-xs font-bold text-center border border-slate-200 dark:border-slate-700"
              >
                This item is currently {{ liveProduct.status }} and unavailable for purchase.
              </div>
            </div>

            <!-- Escrow Safety Notice Box -->
            <div class="p-3.5 rounded-2xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 space-y-1.5">
              <div class="flex items-center gap-2 text-xs font-bold text-[#1877F2]">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>এসক্রো সুরক্ষিত — Bondoo Escrow Payment Hold</span>
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
                :alt="liveProduct.seller?.name || 'Seller'"
                class="w-12 h-12 rounded-full object-cover ring-2 ring-blue-500/20 shrink-0"
              />
              <div class="min-w-0 flex-1">
                <a
                  :href="sellerProfileUrl"
                  class="font-bold text-sm text-slate-900 dark:text-white hover:text-[#1877F2] truncate block"
                >
                  {{ liveProduct.seller?.name || 'Community Seller' }}
                </a>
                <div v-if="sellerReviewsCount > 0" class="flex items-center gap-2 mt-0.5 text-xs text-slate-500">
                  <span class="text-amber-500 font-bold">★ {{ sellerRatingFormatted }}</span>
                  <span>({{ sellerReviewsCount }} reviews)</span>
                </div>
                <div v-else class="mt-0.5 text-xs text-slate-400">
                  New Seller • No ratings yet
                </div>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 text-xs space-y-2 text-slate-600 dark:text-slate-300">
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Location:</span>
                <span class="font-semibold">{{ liveProduct.location || 'Dhaka, Bangladesh' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Identity:</span>
                <span v-if="isSellerVerified" class="font-semibold text-[#1877F2] flex items-center gap-1">
                  <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  Verified Seller
                </span>
                <span v-else class="font-semibold text-slate-500">
                  Community Member
                </span>
              </div>
            </div>

            <div v-if="!isOwner" class="pt-2">
              <button
                type="button"
                @click="requireAuthOr(() => { isReportModalOpen = true })"
                class="w-full text-center text-xs font-semibold text-rose-500 hover:text-rose-600 hover:underline cursor-pointer"
              >
                Report this listing
              </button>
            </div>
          </div>

        </div>

      </div>

      <!-- Related Listings Grid -->
      <section v-if="liveProduct.related_products && liveProduct.related_products.length > 0" class="pt-8 border-t border-slate-200 dark:border-slate-800">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">
          Similar Products in {{ liveProduct.category?.name || 'this category' }}
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <ProductCard
            v-for="rel in liveProduct.related_products"
            :key="'related-' + rel.id"
            :product="rel"
            @contact="openRelatedContactModal"
          />
        </div>
      </section>

    </div>

    <!-- Contact Seller Modal -->
    <ContactSellerModal
      :is-open="isContactModalOpen"
      :product="contactTargetProduct"
      @close="isContactModalOpen = false"
    />

    <!-- Make an Offer Modal (Non-Owner) -->
    <div v-if="isOfferModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-base text-slate-900 dark:text-white">Make an Offer</h3>
          <button type="button" @click="isOfferModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <p class="text-xs text-slate-500">
          Listed price: <strong>{{ liveProduct.formatted_price || ('৳ ' + liveProduct.price) }}</strong>. Submit a reasonable offer to the seller.
        </p>
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Your Offer Amount ({{ liveProduct.currency || 'BDT' }}):</label>
          <input
            v-model.number="offerAmount"
            type="number"
            min="1"
            step="1"
            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-bold"
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Note to Seller (Optional):</label>
          <textarea
            v-model="offerMessage"
            rows="2"
            placeholder="e.g. Ready for immediate pickup today..."
            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs resize-none"
          ></textarea>
        </div>
        <div v-if="offerFeedback" class="p-3 rounded-xl bg-emerald-50 text-emerald-800 text-xs">{{ offerFeedback }}</div>
        <div v-if="offerError" class="p-3 rounded-xl bg-rose-50 text-rose-800 text-xs">{{ offerError }}</div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="isOfferModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500">Cancel</button>
          <button
            type="button"
            @click="submitOffer"
            :disabled="isSubmittingOffer || !offerAmount || offerAmount <= 0"
            class="px-5 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold disabled:opacity-50"
          >
            {{ isSubmittingOffer ? 'Sending...' : 'Send Offer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Owner Edit Listing Modal -->
    <div v-if="isEditModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Listing</h3>
          <button type="button" @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Title</label>
            <input v-model="editForm.title" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Price ({{ liveProduct.currency || 'BDT' }})</label>
              <input v-model.number="editForm.price" type="number" min="0" step="0.01" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Condition</label>
              <select v-model="editForm.condition" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                <option value="new">Brand New</option>
                <option value="like_new">Used - Like New</option>
                <option value="used">Used - Good</option>
                <option value="refurbished">Refurbished</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div v-if="categories && categories.length > 0">
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Category</label>
              <select v-model.number="editForm.category_id" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Location</label>
              <input v-model="editForm.location" type="text" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs" />
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Description</label>
            <textarea v-model="editForm.description" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs resize-none"></textarea>
          </div>
          <div v-if="editError" class="p-2.5 rounded-xl bg-rose-50 text-rose-700 text-xs">{{ editError }}</div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="isEditModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500">Cancel</button>
          <button
            type="button"
            @click="submitEditListing"
            :disabled="isOwnerActionBusy || !editForm.title.trim()"
            class="px-5 py-2 rounded-xl bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-bold disabled:opacity-50"
          >
            {{ isOwnerActionBusy ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Owner Delete Confirmation Modal -->
    <div v-if="isDeleteConfirmOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
        <h3 class="text-base font-bold text-rose-600">Delete Listing?</h3>
        <p class="text-xs text-slate-600 dark:text-slate-300">
          Are you sure you want to permanently remove <strong>"{{ liveProduct.title }}"</strong> from Marketplace discovery? Existing completed orders will remain safe in order history.
        </p>
        <div v-if="deleteError" class="p-2.5 rounded-xl bg-rose-50 text-rose-700 text-xs">{{ deleteError }}</div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" @click="isDeleteConfirmOpen = false" :disabled="isOwnerActionBusy" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500">Cancel</button>
          <button
            type="button"
            @click="confirmDeleteListing"
            :disabled="isOwnerActionBusy"
            class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold disabled:opacity-50"
          >
            {{ isOwnerActionBusy ? 'Deleting...' : 'Delete Listing' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Buy / Secure Payment Hold Order Placement Modal -->
    <div v-if="isOrderModalOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold text-slate-800 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-[#1877F2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <span>Bondoo Secure Checkout</span>
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
              :src="liveProduct.images?.[0] || liveProduct.primary_image || defaultImage"
              :alt="liveProduct.title"
              class="w-14 h-14 rounded-xl object-cover shrink-0"
            />
            <div class="min-w-0 flex-1">
              <h4 class="font-bold text-xs text-slate-900 dark:text-white truncate">{{ liveProduct.title }}</h4>
              <p class="text-[11px] text-slate-500">Unit Price: {{ liveProduct.formatted_price || ('৳ ' + liveProduct.price) }}</p>
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
                @click="orderQuantity = Math.min(maxOrderQuantity, orderQuantity + 1)"
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
                <div class="text-[10px] opacity-75 font-normal">৳ {{ Number(liveProduct.delivery_charge || 0) }}</div>
              </button>
              <button
                type="button"
                @click="orderDeliveryType = 'express'"
                class="p-2.5 rounded-xl border text-xs font-bold text-center transition-all"
                :class="orderDeliveryType === 'express' ? 'border-[#1877F2] bg-blue-50/80 text-[#1877F2]' : 'border-slate-200 text-slate-600'"
              >
                <div>Express</div>
                <div class="text-[10px] opacity-75 font-normal">৳ {{ expressDeliveryFee }}</div>
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
                @click="orderPaymentMethod = 'cod'"
                class="p-2 rounded-xl border text-xs font-bold transition-all text-center flex flex-col items-center gap-1"
                :class="orderPaymentMethod === 'cod' ? 'border-[#1877F2] bg-blue-50 text-[#1877F2]' : 'border-slate-200 text-slate-600'"
              >
                <span>Cash / COD</span>
                <span class="text-[9px] font-normal">Pay on Delivery</span>
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
            <span>{{ isPlacingOrder ? 'Processing...' : 'Confirm Order' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Report Modal (Non-Owner Only) -->
    <div v-if="isReportModalOpen && !isOwner" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
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
        <div v-if="reportFeedback" class="p-2.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs">{{ reportFeedback }}</div>
        <div v-if="reportError" class="p-2.5 rounded-xl bg-rose-50 text-rose-700 text-xs">{{ reportError }}</div>
        <div class="flex justify-end gap-2">
          <button @click="isReportModalOpen = false" class="px-3 py-1.5 text-xs text-slate-500">Cancel</button>
          <button @click="submitReport" class="px-4 py-1.5 rounded-xl bg-rose-600 text-white text-xs font-bold">Submit Report</button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ProductCard from '../../Components/Marketplace/ProductCard.vue';
import ContactSellerModal from '../../Components/Marketplace/ContactSellerModal.vue';
import type { MarketplaceCategory, MarketplaceProduct, User, SuggestedFriend, UpcomingEvent, SharedProps } from '../../types';

const props = defineProps<{
  product: MarketplaceProduct;
  categories?: MarketplaceCategory[];
  pendingRequests?: any[];
  contacts?: User[];
  suggestions?: SuggestedFriend[];
  upcomingEvents?: UpcomingEvent[];
  unreadCount?: number;
  unreadMessagesCount?: number;
}>();

const page = usePage<SharedProps>();
const currentUser = computed(() => page.props.auth?.user || null);

const liveProduct = ref<MarketplaceProduct>({ ...props.product });
const defaultImage = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80';
const selectedImage = ref(liveProduct.value.primary_image || (liveProduct.value.images && liveProduct.value.images[0]) || defaultImage);
const isSaved = ref(Boolean(liveProduct.value.is_favorited ?? liveProduct.value.is_saved));
const shareCopied = ref(false);

const isOwner = computed(() => {
  if (typeof liveProduct.value.is_owner === 'boolean') {
    return liveProduct.value.is_owner;
  }
  if (!currentUser.value) return false;
  const sellerId = Number(liveProduct.value.seller?.id ?? liveProduct.value.seller_id ?? 0);
  return sellerId > 0 && Number(currentUser.value.id) === sellerId;
});

const isContactModalOpen = ref(false);
const contactTargetProduct = ref<MarketplaceProduct | null>(liveProduct.value);
const isOrderModalOpen = ref(false);
const isOfferModalOpen = ref(false);
const isReportModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteConfirmOpen = ref(false);
const isOwnerActionBusy = ref(false);

const editError = ref('');
const deleteError = ref('');
const editForm = reactive({
  title: '',
  price: 0,
  condition: 'used',
  category_id: 0,
  location: '',
  description: '',
});

const offerAmount = ref<number>(Math.max(1, Math.round(Number(liveProduct.value.price || 0) * 0.9)));
const offerMessage = ref('');
const isSubmittingOffer = ref(false);
const offerFeedback = ref('');
const offerError = ref('');

const orderAddress = ref('Dhaka, Bangladesh');
const orderRecipientName = ref('');
const orderRecipientPhone = ref('');
const orderPaymentMethod = ref('cod');
const orderDeliveryType = ref('standard');
const orderQuantity = ref(1);
const orderIdempotencyKey = ref('');

const isPlacingOrder = ref(false);
const orderFeedback = ref('');
const orderError = ref('');

const reportReason = ref('spam');
const reportDetails = ref('');
const reportFeedback = ref('');
const reportError = ref('');

const maxOrderQuantity = computed(() => {
  const stock = liveProduct.value.stock;
  if (typeof stock === 'number' && stock > 0) {
    return Math.min(20, stock);
  }
  return 1;
});

const expressDeliveryFee = computed(() => {
  const baseCharge = Number(liveProduct.value.delivery_charge || 0);
  return baseCharge > 0 ? baseCharge * 2 : 120;
});

const calculatedSubtotal = computed(() => {
  const price = Number(liveProduct.value.price || 0);
  return price * orderQuantity.value;
});

const calculatedDeliveryCharge = computed(() => {
  if (orderDeliveryType.value === 'pickup') return 0;
  const baseCharge = Number(liveProduct.value.delivery_charge || 0);
  if (orderDeliveryType.value === 'express') {
    return expressDeliveryFee.value;
  }
  return baseCharge;
});

const calculatedTotal = computed(() => {
  return calculatedSubtotal.value + calculatedDeliveryCharge.value;
});

const sellerAvatar = computed(() => {
  return liveProduct.value.seller?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80';
});

const sellerProfileUrl = computed(() => {
  return liveProduct.value.seller?.username ? `/profile/${liveProduct.value.seller.username}` : '#';
});

const sellerReviewsCount = computed(() => Number(liveProduct.value.seller?.reviews_count || 0));
const sellerRatingFormatted = computed(() => Number(liveProduct.value.seller?.rating || 0).toFixed(1));
const isSellerVerified = computed(() => Boolean(liveProduct.value.seller?.is_verified ?? liveProduct.value.seller?.verified));

const requireAuthOr = (fn: () => void) => {
  if (!currentUser.value) {
    window.location.href = '/login';
    return;
  }
  fn();
};

const shareListing = async () => {
  const url = window.location.href;
  try {
    if (navigator.share) {
      await navigator.share({ title: liveProduct.value.title, url });
      return;
    }
    await navigator.clipboard.writeText(url);
    shareCopied.value = true;
    setTimeout(() => {
      shareCopied.value = false;
    }, 2000);
  } catch (_) {}
};

const toggleSave = async () => {
  if (!currentUser.value) {
    window.location.href = '/login';
    return;
  }
  if (isOwner.value) return;

  const previous = isSaved.value;
  isSaved.value = !previous;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/marketplace/products/${liveProduct.value.id}/favorite`, {
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
    } else {
      isSaved.value = previous;
    }
  } catch (e) {
    isSaved.value = previous;
    console.error(e);
  }
};

const openEditModal = () => {
  editError.value = '';
  editForm.title = liveProduct.value.title;
  editForm.price = Number(liveProduct.value.price || 0);
  editForm.condition = liveProduct.value.condition || 'used';
  editForm.category_id = Number(liveProduct.value.category?.id || liveProduct.value.category_id || (props.categories?.[0]?.id ?? 0));
  editForm.location = liveProduct.value.location || '';
  editForm.description = liveProduct.value.description || '';
  isEditModalOpen.value = true;
};

const submitEditListing = async () => {
  if (isOwnerActionBusy.value || !editForm.title.trim()) return;
  isOwnerActionBusy.value = true;
  editError.value = '';
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/marketplace/products/${liveProduct.value.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        title: editForm.title.trim(),
        price: Number(editForm.price),
        condition: editForm.condition,
        category_id: editForm.category_id || undefined,
        location: editForm.location.trim(),
        description: editForm.description.trim(),
      }),
    });
    const payload = await res.json();
    if (res.ok && payload.data) {
      liveProduct.value = { ...liveProduct.value, ...payload.data, is_owner: true };
      isEditModalOpen.value = false;
    } else {
      editError.value = payload.message || 'Failed to update listing.';
    }
  } catch (err: any) {
    editError.value = err.message || 'Network error.';
  } finally {
    isOwnerActionBusy.value = false;
  }
};

const changeListingStatus = async (newStatus: string) => {
  if (isOwnerActionBusy.value) return;
  isOwnerActionBusy.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/marketplace/products/${liveProduct.value.id}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ status: newStatus }),
    });
    if (res.ok) {
      liveProduct.value.status = newStatus;
    }
  } catch (e) {
    console.error(e);
  } finally {
    isOwnerActionBusy.value = false;
  }
};

const confirmDeleteListing = async () => {
  if (isOwnerActionBusy.value) return;
  isOwnerActionBusy.value = true;
  deleteError.value = '';
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/marketplace/products/${liveProduct.value.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
    });
    if (res.ok || res.status === 204) {
      window.location.href = '/marketplace/my-listings';
      return;
    }
    const payload = await res.json().catch(() => ({}));
    deleteError.value = payload.message || 'Failed to delete listing.';
  } catch (err: any) {
    deleteError.value = err.message || 'Network error while deleting listing.';
  } finally {
    isOwnerActionBusy.value = false;
  }
};

const openOfferModal = () => {
  offerAmount.value = Math.max(1, Math.round(Number(liveProduct.value.price || 0) * 0.9));
  offerMessage.value = '';
  offerFeedback.value = '';
  offerError.value = '';
  isOfferModalOpen.value = true;
};

const submitOffer = async () => {
  if (isSubmittingOffer.value || !offerAmount.value || offerAmount.value <= 0) return;
  isSubmittingOffer.value = true;
  offerFeedback.value = '';
  offerError.value = '';
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v2/marketplace/products/${liveProduct.value.id}/offers`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        amount: Number(offerAmount.value),
        message: offerMessage.value.trim() || undefined,
      }),
    });
    const payload = await res.json();
    if (res.ok && (payload.status === 'success' || payload.success)) {
      offerFeedback.value = 'Offer sent to seller! You can track responses in Orders & Offers.';
      setTimeout(() => {
        isOfferModalOpen.value = false;
      }, 1400);
    } else {
      offerError.value = payload.message || 'Unable to submit offer.';
    }
  } catch (err: any) {
    offerError.value = err.message || 'Network error.';
  } finally {
    isSubmittingOffer.value = false;
  }
};

const openOrderModal = () => {
  if (isOwner.value || liveProduct.value.status !== 'active') return;
  orderFeedback.value = '';
  orderError.value = '';
  orderIdempotencyKey.value = `ord_${liveProduct.value.id}_${Date.now()}`;
  isOrderModalOpen.value = true;
};

const placeEscrowOrder = async () => {
  if (!orderAddress.value.trim() || isPlacingOrder.value) return;

  isPlacingOrder.value = true;
  orderError.value = '';
  orderFeedback.value = '';

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v2/marketplace/products/${liveProduct.value.id}/order`, {
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
        idempotency_key: orderIdempotencyKey.value || `ord_${liveProduct.value.id}_${Date.now()}`,
      }),
    });

    const data = await res.json();
    if (res.ok && data.status === 'success') {
      const order = data.data;
      orderFeedback.value = 'Order placed successfully! Redirecting to order tracking...';

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
        window.location.href = `/marketplace/orders/${order.id}`;
      }, 900);
    } else {
      if (res.status === 409) {
        liveProduct.value.status = 'sold';
      }
      orderError.value = data.message || 'Failed to place order.';
    }
  } catch (err: any) {
    orderError.value = err.message || 'Network error.';
  } finally {
    isPlacingOrder.value = false;
  }
};

const submitReport = async () => {
  reportFeedback.value = '';
  reportError.value = '';
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v1/marketplace/products/${liveProduct.value.id}/report`, {
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
    const payload = await res.json().catch(() => ({}));
    if (res.ok) {
      reportFeedback.value = 'Thank you for reporting. Our moderation team will investigate.';
      setTimeout(() => {
        isReportModalOpen.value = false;
      }, 1000);
    } else {
      reportError.value = payload.message || 'Unable to submit report.';
    }
  } catch (e: any) {
    reportError.value = e.message || 'Network error.';
  }
};

const openRelatedContactModal = (prod: MarketplaceProduct) => {
  contactTargetProduct.value = prod;
  isContactModalOpen.value = true;
};
</script>
