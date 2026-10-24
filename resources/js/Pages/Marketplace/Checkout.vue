<template>
  <AppLayout
    current-route="marketplace"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <div class="max-w-6xl mx-auto px-4 py-8">
      <!-- Breadcrumb Navigation -->
      <nav class="flex items-center gap-2 text-sm text-slate-500 mb-6 font-medium">
        <a href="/marketplace" class="hover:text-blue-600 transition flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
          মার্কেটপ্লেস
        </a>
        <span>/</span>
        <a :href="`/marketplace/${product.id}`" class="hover:text-blue-600 truncate max-w-xs transition">
          {{ product.title }}
        </a>
        <span>/</span>
        <span class="text-slate-800 font-semibold">নিরাপদ চেকআউট</span>
      </nav>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Main Column: Product Gallery, Options, Shipping Details (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
          <!-- Listing Summary Card -->
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 overflow-hidden">
            <div class="flex flex-col sm:flex-row gap-5">
              <!-- Product Image Thumbnail -->
              <div class="w-full sm:w-44 h-44 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0 relative border border-slate-200/60">
                <img
                  :src="product.images?.[0] || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=600&q=80'"
                  :alt="product.title"
                  class="w-full h-full object-cover"
                />
                <span class="absolute top-2 left-2 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-white/95 text-slate-800 shadow-sm border border-slate-200/60">
                  {{ product.condition === 'new' ? 'নতুন (Brand New)' : 'ব্যবহৃত (Used)' }}
                </span>
              </div>

              <!-- Product Key Metadata -->
              <div class="flex-1 min-w-0 flex flex-col justify-between">
                <div>
                  <div class="flex items-center gap-2 mb-1.5 text-xs font-semibold text-blue-600 uppercase tracking-wide">
                    <span>{{ product.category?.name || 'জেনারেল' }}</span>
                    <span>•</span>
                    <span class="text-slate-500">আইডি: #{{ product.id }}</span>
                  </div>
                  <h1 class="text-xl font-bold text-slate-900 leading-snug line-clamp-2">
                    {{ product.title }}
                  </h1>
                  <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                    {{ product.description }}
                  </p>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-2">
                  <div class="text-2xl font-black text-slate-900">
                    ৳ {{ formatMoney(product.price) }}
                    <span class="text-xs font-normal text-slate-500">/ প্রতিটি</span>
                  </div>

                  <!-- Location -->
                  <div class="text-xs text-slate-500 flex items-center gap-1 font-medium bg-slate-50 px-2.5 py-1.5 rounded-lg border border-slate-100">
                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ product.location || 'ঢাকা, বাংলাদেশ' }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Verified Seller Profile & Messenger Card -->
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3.5">
              <img
                :src="product.seller?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                :alt="product.seller?.name"
                class="w-12 h-12 rounded-full object-cover ring-2 ring-blue-500/20"
              />
              <div>
                <div class="flex items-center gap-1.5">
                  <span class="font-bold text-slate-900 text-sm">{{ product.seller?.name || 'বিশ্বস্ত বিক্রেতা' }}</span>
                  <span v-if="product.seller?.is_verified" class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    <svg class="w-3 h-3 text-blue-600 fill-current" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    ভেরিফাইড সেলার
                  </span>
                </div>
                <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                  <span class="flex items-center text-amber-500 font-semibold">
                    ★ {{ product.seller?.rating || '৫.০' }}
                  </span>
                  <span>•</span>
                  <span>{{ product.seller?.orders_count || 1 }} টি সফল অর্ডার সম্পন্ন</span>
                </div>
              </div>
            </div>

            <!-- Contact Seller via Messenger Button -->
            <a
              :href="`/messages?user=${product.seller?.id}`"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 transition border border-blue-200/60"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
              </svg>
              বিক্রেতাকে মেসেজ দিন
            </a>
          </div>

          <!-- Step 1: Order Configuration (Quantity & Fulfillment) -->
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
              <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">১</span>
              পরিমাণ ও ডেলিভারি পদ্ধতি নির্বাচন করুন
            </h2>

            <!-- Quantity Selector -->
            <div class="mb-5 pb-5 border-b border-slate-100 flex items-center justify-between">
              <div>
                <label class="text-sm font-semibold text-slate-800 block">পণের পরিমাণ (Quantity)</label>
                <span class="text-xs text-slate-500">এক অর্ডারে সর্বোচ্চ ১০টি নেওয়া যাবে</span>
              </div>
              <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden shadow-sm bg-slate-50">
                <button
                  type="button"
                  @click="decrementQty"
                  :disabled="quantity <= 1"
                  class="w-10 h-10 flex items-center justify-center text-slate-600 hover:bg-slate-200/80 disabled:opacity-40 transition font-bold text-lg"
                >
                  −
                </button>
                <span class="w-12 text-center font-bold text-slate-800 text-sm bg-white h-10 flex items-center justify-center border-x border-slate-200">
                  {{ quantity }}
                </span>
                <button
                  type="button"
                  @click="incrementQty"
                  :disabled="quantity >= 10"
                  class="w-10 h-10 flex items-center justify-center text-slate-600 hover:bg-slate-200/80 disabled:opacity-40 transition font-bold text-lg"
                >
                  +
                </button>
              </div>
            </div>

            <!-- Fulfillment Options -->
            <div>
              <label class="text-sm font-semibold text-slate-800 block mb-2.5">ডেলিভারি মাধ্যম</label>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Standard -->
                <label
                  :class="[
                    'border rounded-xl p-3.5 cursor-pointer transition flex flex-col justify-between relative',
                    deliveryType === 'standard' ? 'border-blue-600 bg-blue-50/50 ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-slate-300 bg-white'
                  ]"
                >
                  <input type="radio" v-model="deliveryType" value="standard" class="sr-only" />
                  <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-xs uppercase text-slate-800">স্ট্যান্ডার্ড হোম ডেলিভারি</span>
                    <span class="text-xs font-black text-blue-600">৳ {{ formatMoney(standardFee) }}</span>
                  </div>
                  <p class="text-[11px] text-slate-500 leading-relaxed">
                    সেলার-ম্যানেজড কুরিয়ার (২-৪ কার্যদিবস)
                  </p>
                </label>

                <!-- Express -->
                <label
                  :class="[
                    'border rounded-xl p-3.5 cursor-pointer transition flex flex-col justify-between relative',
                    deliveryType === 'express' ? 'border-blue-600 bg-blue-50/50 ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-slate-300 bg-white'
                  ]"
                >
                  <input type="radio" v-model="deliveryType" value="express" class="sr-only" />
                  <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-xs uppercase text-slate-800">এক্সপ্রেস ডেলিভারি</span>
                    <span class="text-xs font-black text-blue-600">৳ {{ formatMoney(expressFee) }}</span>
                  </div>
                  <p class="text-[11px] text-slate-500 leading-relaxed">
                    জরুরি দ্রুত ডেলিভারি (২৪ ঘণ্টার মধ্যে)
                  </p>
                </label>

                <!-- Store Pickup -->
                <label
                  :class="[
                    'border rounded-xl p-3.5 cursor-pointer transition flex flex-col justify-between relative',
                    deliveryType === 'pickup' ? 'border-blue-600 bg-blue-50/50 ring-2 ring-blue-500/20' : 'border-slate-200 hover:border-slate-300 bg-white'
                  ]"
                >
                  <input type="radio" v-model="deliveryType" value="pickup" class="sr-only" />
                  <div class="flex items-center justify-between mb-2">
                    <span class="font-bold text-xs uppercase text-slate-800">সরাসরি পিকআপ</span>
                    <span class="text-xs font-black text-emerald-600">ফ্রি (৳ ০)</span>
                  </div>
                  <p class="text-[11px] text-slate-500 leading-relaxed">
                    বিক্রেতার পয়েন্ট থেকে নিজে সংগ্রহ করুন
                  </p>
                </label>
              </div>
            </div>
          </div>

          <!-- Step 2: Recipient & Address Details -->
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
              <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">২</span>
              {{ deliveryType === 'pickup' ? 'পিকআপ গ্রহীতার তথ্য' : 'ডেলিভারি ঠিকানা ও প্রাপক' }}
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Recipient Name -->
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">প্রাপকের নাম *</label>
                <input
                  v-model="shippingForm.name"
                  type="text"
                  placeholder="যেমন: মো: করিম আহমেদ"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 text-sm"
                  required
                />
              </div>

              <!-- Recipient Phone -->
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">মোবাইল নম্বর (যোগাযোগের জন্য) *</label>
                <input
                  v-model="shippingForm.phone"
                  type="tel"
                  placeholder="017XXXXXXXX"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 text-sm"
                  required
                />
              </div>

              <!-- Full Shipping Address (if not pickup) -->
              <div class="sm:col-span-2" v-if="deliveryType !== 'pickup'">
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">পূর্ণাঙ্গ শিপিং ঠিকানা (রোড, বাড়ি, এলাকা) *</label>
                <textarea
                  v-model="shippingForm.address"
                  rows="2.5"
                  placeholder="যেমন: বাড়ি #১২, ফ্ল্যাট #৪বি, রোড #৭, সেক্টর #৩, উত্তরা, ঢাকা"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 text-sm resize-none"
                  required
                ></textarea>
              </div>

              <!-- Pickup Info Note if pickup -->
              <div class="sm:col-span-2 bg-slate-50 border border-slate-200/80 rounded-xl p-3.5 text-xs text-slate-600 flex items-start gap-2.5" v-else>
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                  <span class="font-bold text-slate-800">পিকআপ পয়েন্ট: </span>
                  {{ product.location || 'ঢাকা, বাংলাদেশ' }} — অর্ডার সম্পন্ন হলে সেলার প্রস্তুত করবেন এবং মেসেঞ্জারে বিস্তারিত পিকআপ নির্দেশনা দেওয়া হবে।
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Payment Method Selection -->
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
              <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center">৩</span>
              নিরাপদ পেমেন্ট মেথড নির্বাচন করুন
            </h2>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <!-- bKash -->
              <label
                :class="[
                  'border rounded-xl p-3.5 cursor-pointer transition flex flex-col items-center justify-center text-center gap-2',
                  selectedPaymentMethod === 'bkash' ? 'border-pink-500 bg-pink-50/40 ring-2 ring-pink-400/20' : 'border-slate-200 hover:border-slate-300'
                ]"
              >
                <input type="radio" v-model="selectedPaymentMethod" value="bkash" class="sr-only" />
                <div class="w-10 h-10 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center font-black text-sm">
                  bK
                </div>
                <span class="text-xs font-bold text-slate-800">বিকাশ (bKash)</span>
                <span class="text-[10px] text-pink-600 font-semibold">অফিসিয়াল গেটওয়ে</span>
              </label>

              <!-- Nagad -->
              <label
                :class="[
                  'border rounded-xl p-3.5 cursor-pointer transition flex flex-col items-center justify-center text-center gap-2',
                  selectedPaymentMethod === 'nagad' ? 'border-orange-500 bg-orange-50/40 ring-2 ring-orange-400/20' : 'border-slate-200 hover:border-slate-300'
                ]"
              >
                <input type="radio" v-model="selectedPaymentMethod" value="nagad" class="sr-only" />
                <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-black text-sm">
                  নগদ
                </div>
                <span class="text-xs font-bold text-slate-800">নগদ (Nagad)</span>
                <span class="text-[10px] text-orange-600 font-semibold">অফিসিয়াল গেটওয়ে</span>
              </label>

              <!-- Card / SSLCommerz -->
              <label
                :class="[
                  'border rounded-xl p-3.5 cursor-pointer transition flex flex-col items-center justify-center text-center gap-2',
                  selectedPaymentMethod === 'sslcommerz' ? 'border-blue-500 bg-blue-50/40 ring-2 ring-blue-400/20' : 'border-slate-200 hover:border-slate-300'
                ]"
              >
                <input type="radio" v-model="selectedPaymentMethod" value="sslcommerz" class="sr-only" />
                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-black text-xs">
                  💳
                </div>
                <span class="text-xs font-bold text-slate-800">কার্ড / ইন্টারনেট</span>
                <span class="text-[10px] text-blue-600 font-semibold">SSLCommerz</span>
              </label>

              <!-- Sandbox (Test Environment) -->
              <label
                :class="[
                  'border rounded-xl p-3.5 cursor-pointer transition flex flex-col items-center justify-center text-center gap-2',
                  selectedPaymentMethod === 'sandbox' ? 'border-purple-500 bg-purple-50/40 ring-2 ring-purple-400/20' : 'border-slate-200 hover:border-slate-300'
                ]"
              >
                <input type="radio" v-model="selectedPaymentMethod" value="sandbox" class="sr-only" />
                <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center font-black text-sm">
                  ⚡
                </div>
                <span class="text-xs font-bold text-slate-800">স্যান্ডবক্স টেস্ট</span>
                <span class="text-[10px] text-purple-600 font-semibold">ইনস্ট্যান্ট ভেরিফাই</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Right Column: Order Summary & Place Order Action (4 cols) -->
        <div class="lg:col-span-4 space-y-5 sticky top-20">
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-md p-6">
            <h3 class="text-base font-extrabold text-slate-900 border-b border-slate-100 pb-3 mb-4">
              অর্ডার বিবরণী (Order Summary)
            </h3>

            <div class="space-y-3 text-sm">
              <div class="flex justify-between text-slate-600">
                <span>একক মূল্য (Unit Price):</span>
                <span class="font-semibold text-slate-900">৳ {{ formatMoney(product.price) }}</span>
              </div>
              <div class="flex justify-between text-slate-600">
                <span>পরিমাণ (Qty):</span>
                <span class="font-semibold text-slate-900">× {{ quantity }}</span>
              </div>
              <div class="flex justify-between text-slate-600">
                <span>সাব-টোটাল (Subtotal):</span>
                <span class="font-semibold text-slate-900">৳ {{ formatMoney(subtotal) }}</span>
              </div>
              <div class="flex justify-between text-slate-600">
                <span>ডেলিভারি ফি (Delivery):</span>
                <span class="font-semibold" :class="deliveryCharge === 0 ? 'text-emerald-600' : 'text-slate-900'">
                  {{ deliveryCharge === 0 ? 'ফ্রি' : `৳ ${formatMoney(deliveryCharge)}` }}
                </span>
              </div>

              <div class="pt-3 border-t border-slate-200 flex justify-between items-baseline">
                <div>
                  <span class="text-sm font-bold text-slate-900">সর্বমোট প্রদেয়:</span>
                  <div class="text-[11px] text-slate-400">সকল ট্যাক্স ও চার্জ অন্তর্ভুক্ত</div>
                </div>
                <span class="text-2xl font-black text-blue-600">
                  ৳ {{ formatMoney(calculatedTotal) }}
                </span>
              </div>
            </div>

            <!-- Error Banner -->
            <div v-if="errorMessage" class="mt-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold">
              ⚠️ {{ errorMessage }}
            </div>

            <!-- Place Order / Proceed to Payment Button -->
            <button
              type="button"
              @click="submitOrder"
              :disabled="isSubmitting"
              class="w-full mt-5 py-3.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-extrabold text-sm shadow-md shadow-blue-500/25 transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            >
              <svg v-if="isSubmitting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ isSubmitting ? 'অর্ডার প্রসেস হচ্ছে...' : 'অর্ডার নিশ্চিত করে পেমেন্টে যান' }}</span>
              <svg v-if="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
              </svg>
            </button>

            <!-- Accurate Trust & Payment Hold Disclosure -->
            <div class="mt-5 pt-4 border-t border-slate-100 space-y-2.5 text-xs text-slate-500">
              <div class="flex items-start gap-2">
                <span class="text-blue-600 font-bold text-sm">🛡️</span>
                <div>
                  <span class="font-bold text-slate-800">বন্ডহু সিকিউর পেমেন্ট হোল্ড:</span>
                  আপনার টাকা সরাসরি সেলারকে দেওয়া হবে না। পণ্য সঠিকভাবে হাতে পাওয়া পর্যন্ত পেমেন্ট প্ল্যাটফর্ম এসক্রো তহবিলে নিরাপদ থাকবে।
                </div>
              </div>
              <div class="flex items-start gap-2">
                <span class="text-emerald-600 font-bold text-sm">🔄</span>
                <div>
                  <span class="font-bold text-slate-800">সহজ বিরোধ ও রিফান্ড:</span>
                  পণ্য বিবরণের সাথে অমিল থাকলে ৭ দিনের মধ্যে ডিসপিউট ফাইল করতে পারবেন।
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

interface ProductData {
  id: number
  title: string
  description: string
  price: number
  condition: string
  location: string
  images: string[]
  delivery_charge?: number
  category?: { name: string }
  seller?: {
    id: number
    name: string
    avatar_url?: string
    is_verified?: boolean
    rating?: number
    orders_count?: number
  }
}

interface CurrentUser {
  id: number
  name: string
  username: string
  phone?: string
  address?: string
}

const props = defineProps<{
  product: ProductData
  currentUser?: CurrentUser
}>()

const quantity = ref(1)
const deliveryType = ref<'standard' | 'express' | 'pickup'>('standard')
const selectedPaymentMethod = ref('bkash')
const isSubmitting = ref(false)
const errorMessage = ref('')

const shippingForm = ref({
  name: props.currentUser?.name || '',
  phone: props.currentUser?.phone || '',
  address: props.currentUser?.address || '',
})

const standardFee = computed(() => {
  return Number(props.product.delivery_charge ?? 60.00)
})

const expressFee = computed(() => {
  return standardFee.value > 0 ? standardFee.value * 2 : 120.00
})

const deliveryCharge = computed(() => {
  if (deliveryType.value === 'pickup') return 0.00
  if (deliveryType.value === 'express') return expressFee.value
  return standardFee.value
})

const subtotal = computed(() => {
  return Number(props.product.price) * quantity.value
})

const calculatedTotal = computed(() => {
  return subtotal.value + deliveryCharge.value
})

const formatMoney = (val: number | string) => {
  const num = Number(val) || 0
  return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

const incrementQty = () => {
  if (quantity.value < 10) quantity.value++
}

const decrementQty = () => {
  if (quantity.value > 1) quantity.value--
}

const submitOrder = async () => {
  errorMessage.value = ''

  if (!shippingForm.value.name.trim()) {
    errorMessage.value = 'অনুগ্রহ করে প্রাপকের নাম প্রদান করুন।'
    return
  }
  if (!shippingForm.value.phone.trim()) {
    errorMessage.value = 'অনুগ্রহ করে মোবাইল নম্বর প্রদান করুন।'
    return
  }
  if (deliveryType.value !== 'pickup' && !shippingForm.value.address.trim()) {
    errorMessage.value = 'অনুগ্রহ করে শিপিং ঠিকানা প্রদান করুন।'
    return
  }

  isSubmitting.value = true

  try {
    const idempotencyKey = `CHECKOUT_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`

    // Step 1: Create Order on server
    const orderRes = await fetch(`/api/v2/marketplace/products/${props.product.id}/order`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
      body: JSON.stringify({
        quantity: quantity.value,
        delivery_type: deliveryType.value,
        shipping_name: shippingForm.value.name,
        shipping_phone: shippingForm.value.phone,
        shipping_address: deliveryType.value === 'pickup'
          ? `Store Pickup — ${props.product.location || 'Dhaka'}`
          : shippingForm.value.address,
        payment_method: selectedPaymentMethod.value,
        idempotency_key: idempotencyKey,
      }),
    })

    const orderData = await orderRes.json()

    if (!orderRes.ok || !orderData.data) {
      throw new Error(orderData.message || 'অর্ডার তৈরি করা সম্ভব হয়নি।')
    }

    const order = orderData.data

    // Step 2: Initiate Payment Gateway Session
    const checkoutRes = await fetch(`/api/v2/marketplace/orders/${order.id}/checkout`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
      body: JSON.stringify({
        gateway: selectedPaymentMethod.value,
      }),
    })

    const checkoutData = await checkoutRes.json()

    if (!checkoutRes.ok || !checkoutData.data) {
      throw new Error(checkoutData.message || 'পেমেন্ট গেটওয়ে সেশন আরম্ভ করা যায়নি।')
    }

    const session = checkoutData.data

    // Step 3: Handle redirection or sandbox verification
    if (selectedPaymentMethod.value === 'sandbox') {
      // In sandbox mode, immediately verify payment
      const verifyRes = await fetch(`/api/v2/marketplace/orders/${order.id}/verify-payment`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
        },
        body: JSON.stringify({
          gateway: 'sandbox',
          transaction_id: session.transaction_id,
          amount: session.amount,
          currency: session.currency,
          payment_status: 'COMPLETED',
          signature: session.signature,
        }),
      })

      if (verifyRes.ok) {
        window.location.href = `/marketplace/orders/${order.id}`
        return
      }
    }

    // Official gateway redirection
    if (session.checkout_url) {
      window.location.href = session.checkout_url
    } else {
      window.location.href = `/marketplace/orders/${order.id}`
    }
  } catch (err: any) {
    errorMessage.value = err.message || 'দুঃখিত, কোনো ত্রুটি হয়েছে। পুনরায় চেষ্টা করুন।'
  } finally {
    isSubmitting.value = false
  }
}
</script>
