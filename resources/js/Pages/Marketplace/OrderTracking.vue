<template>
  <AppLayout
    current-route="marketplace"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <div class="max-w-5xl mx-auto px-4 py-8">
      <!-- Top Action Bar -->
      <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
        <div class="flex items-center gap-3">
          <a href="/marketplace/orders" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </a>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl font-black text-slate-900">
                অর্ডার #{{ order.order_number || ('ORD-' + order.id) }}
              </h1>
              <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold border uppercase tracking-wider', statusBadgeClass(order.status)]">
                {{ formatStatus(order.status) }}
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
              অর্ডারের তারিখ: {{ formatDate(order.timestamps?.created_at || order.created_at) }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2.5">
          <button
            type="button"
            @click="printInvoice"
            class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs shadow-sm transition flex items-center gap-1.5 cursor-pointer"
          >
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            ইনভয়েস প্রিন্ট
          </button>

          <a
            :href="`/messages?user=${isBuyer ? order.seller?.id : order.buyer?.id}`"
            class="px-3.5 py-2 rounded-xl bg-blue-50 border border-blue-200 text-blue-600 hover:bg-blue-100 font-bold text-xs shadow-sm transition flex items-center gap-1.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            {{ isBuyer ? 'বিক্রেতার সাথে চ্যাট' : 'ক্রেতার সাথে চ্যাট' }}
          </a>
        </div>
      </div>

      <!-- Main Tracking Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left: Status Timeline, Item Details & Shipment (8 cols) -->
        <div class="lg:col-span-8 space-y-6">
          <!-- Lifecycle Progress Timeline Card -->
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wide mb-6 flex items-center gap-2">
              <span>📍</span> অর্ডারের অগ্রগতি ও ট্র্যাক রেকর্ড (Progress Timeline)
            </h2>

            <div class="relative pl-6 space-y-8 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
              <!-- Step 1: Order Placed -->
              <div class="relative">
                <div class="absolute -left-6 top-0 w-5 h-5 rounded-full border-2 border-white bg-emerald-500 shadow-sm flex items-center justify-center text-white text-[10px] font-bold">
                  ✓
                </div>
                <div>
                  <h3 class="text-sm font-bold text-slate-900">অর্ডার প্লেস করা হয়েছে</h3>
                  <p class="text-xs text-slate-500 mt-0.5">
                    {{ formatDate(order.timestamps?.created_at || order.created_at) }}
                  </p>
                  <p class="text-xs text-slate-600 mt-1">অর্ডার নম্বর: {{ order.order_number }}</p>
                </div>
              </div>

              <!-- Step 2: Payment Verified & Hold -->
              <div class="relative">
                <div :class="[
                  'absolute -left-6 top-0 w-5 h-5 rounded-full border-2 border-white shadow-sm flex items-center justify-center text-white text-[10px] font-bold',
                  isStepPassed(['processing', 'confirmed', 'shipped', 'delivered', 'completed']) ? 'bg-emerald-500' : (order.status === 'pending' ? 'bg-blue-600' : 'bg-slate-300')
                ]">
                  {{ isStepPassed(['processing', 'confirmed', 'shipped', 'delivered', 'completed']) ? '✓' : '২' }}
                </div>
                <div>
                  <h3 class="text-sm font-bold text-slate-900">
                    {{ order.payment_status === 'paid' ? 'পেমেন্ট সম্পন্ন ও ফান্ড হোল্ড' : 'পেমেন্ট প্রক্রিয়াধীন' }}
                  </h3>
                  <p class="text-xs text-slate-500 mt-0.5" v-if="order.timestamps?.paid_at">
                    {{ formatDate(order.timestamps.paid_at) }}
                  </p>
                  <div class="inline-flex items-center gap-1.5 mt-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
                    <span>🛡️ বন্ডহু সিকিউর পেমেন্ট ফান্ডে টাকা সুরক্ষিত</span>
                  </div>
                </div>
              </div>

              <!-- Step 3: Shipped / In Transit -->
              <div class="relative">
                <div :class="[
                  'absolute -left-6 top-0 w-5 h-5 rounded-full border-2 border-white shadow-sm flex items-center justify-center text-white text-[10px] font-bold',
                  isStepPassed(['shipped', 'delivered', 'completed']) ? 'bg-emerald-500' : (order.status === 'confirmed' || order.status === 'processing' ? 'bg-blue-600' : 'bg-slate-300')
                ]">
                  {{ isStepPassed(['shipped', 'delivered', 'completed']) ? '✓' : '৩' }}
                </div>
                <div>
                  <h3 class="text-sm font-bold text-slate-900">
                    {{ order.delivery_type === 'pickup' ? 'পিকআপের জন্য প্রস্তুত' : 'শিপিং ও কুরিয়ার ট্রানজিট' }}
                  </h3>
                  <p class="text-xs text-slate-500 mt-0.5" v-if="order.timestamps?.shipped_at">
                    {{ formatDate(order.timestamps.shipped_at) }}
                  </p>
                  <div v-if="order.tracking_number" class="mt-2 p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-xs">
                    <div class="font-bold text-slate-800">
                      কুরিয়ার / মাধ্যম: {{ order.tracking_carrier || 'সেলার-ম্যানেজড ডেলিভারি' }}
                    </div>
                    <div class="text-slate-600 font-mono mt-0.5">
                      ট্র্যাকিং নম্বর: <span class="text-blue-600 font-bold">{{ order.tracking_number }}</span>
                    </div>
                  </div>
                  <p v-else class="text-xs text-slate-400 mt-1">
                    {{ order.delivery_type === 'pickup' ? 'বিক্রেতা পণ্য প্রস্তুত করছেন' : 'বিক্রেতা পার্সেল কুরিয়ারে হস্তান্তরের অপেক্ষায়' }}
                  </p>
                </div>
              </div>

              <!-- Step 4: Delivered & Released -->
              <div class="relative">
                <div :class="[
                  'absolute -left-6 top-0 w-5 h-5 rounded-full border-2 border-white shadow-sm flex items-center justify-center text-white text-[10px] font-bold',
                  order.status === 'delivered' || order.status === 'completed' ? 'bg-emerald-500' : 'bg-slate-300'
                ]">
                  {{ order.status === 'delivered' || order.status === 'completed' ? '✓' : '৪' }}
                </div>
                <div>
                  <h3 class="text-sm font-bold text-slate-900">ডেলিভারি সম্পন্ন ও এসক্রো রিলিজ</h3>
                  <p class="text-xs text-slate-500 mt-0.5" v-if="order.timestamps?.delivered_at">
                    {{ formatDate(order.timestamps.delivered_at) }}
                  </p>
                  <p class="text-xs text-slate-400 mt-1">
                    ক্রেতা পণ্য প্রাপ্তি নিশ্চিত করলে তহবিল বিক্রেতাকে রিলিজ করা হবে।
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Purchased Items Snapshot Card -->
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
            <h2 class="text-sm font-extrabold text-slate-800 uppercase tracking-wide mb-4">
              ক্রয়কৃত পণ্যের বিবরণ (Product Snapshot)
            </h2>

            <div class="flex flex-col sm:flex-row gap-4 items-start pb-4 border-b border-slate-100">
              <img
                :src="order.product?.primary_image || order.product?.images?.[0] || 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=300&q=80'"
                :alt="order.product?.title"
                class="w-20 h-20 rounded-xl object-cover bg-slate-100 border border-slate-200/60 flex-shrink-0"
              />
              <div class="flex-1 min-w-0">
                <h3 class="font-bold text-slate-900 text-base leading-snug">
                  {{ order.product?.title || 'মার্কেটপ্লেস আইটেম' }}
                </h3>
                <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                  <span>অবস্থা: {{ order.product?.condition === 'new' ? 'নতুন' : 'ব্যবহৃত' }}</span>
                  <span>•</span>
                  <span>লোকেশন: {{ order.product?.location || 'বাংলাদেশ' }}</span>
                </div>
                <div class="mt-2 text-sm font-bold text-slate-900">
                  ৳ {{ formatMoney(order.unit_price || order.amount) }} × {{ order.quantity || 1 }} টি = ৳ {{ formatMoney(order.subtotal || order.amount) }}
                </div>
              </div>
            </div>

            <!-- Shipping / Delivery Information Box -->
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/60">
                <span class="font-bold text-slate-700 block mb-1">📍 ডেলিভারি ঠিকানা ও প্রাপক</span>
                <p class="font-semibold text-slate-900">{{ order.shipping_name || order.buyer?.name }}</p>
                <p class="text-slate-600 mt-0.5">{{ order.shipping_phone }}</p>
                <p class="text-slate-600 mt-1 leading-relaxed">{{ order.shipping_address || 'ঠিকানা দেওয়া হয়নি' }}</p>
              </div>

              <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/60">
                <span class="font-bold text-slate-700 block mb-1">🚚 ডেলিভারি পদ্ধতি</span>
                <p class="font-semibold text-slate-900 uppercase">
                  {{ order.delivery_type === 'express' ? 'এক্সপ্রেস ডেলিভারি (২৪ ঘণ্টা)' : (order.delivery_type === 'pickup' ? 'সরাসরি পিকআপ' : 'স্ট্যান্ডার্ড হোম ডেলিভারি') }}
                </p>
                <p class="text-slate-600 mt-1">
                  ফি: {{ order.delivery_charge === 0 ? 'ফ্রি' : `৳ ${formatMoney(order.delivery_charge)}` }}
                </p>
                <p class="text-[11px] text-slate-500 mt-1">
                  {{ order.tracking_carrier ? `কুরিয়ার: ${order.tracking_carrier}` : 'সেলার-ম্যানেজড ডেলিভারি' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Financial Breakdown & Interactive Actions (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
          <!-- Financial Ledger Card -->
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-md p-6">
            <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wide border-b border-slate-100 pb-3 mb-4">
              পেমেন্ট ও হিসাব বিবরণী
            </h3>

            <div class="space-y-2.5 text-xs text-slate-600">
              <div class="flex justify-between">
                <span>পণ্যের সাব-টোটাল:</span>
                <span class="font-bold text-slate-800">৳ {{ formatMoney(order.subtotal || order.amount) }}</span>
              </div>
              <div class="flex justify-between">
                <span>ডেলিভারি ফি:</span>
                <span class="font-bold text-slate-800">৳ {{ formatMoney(order.delivery_charge || 0) }}</span>
              </div>
              <div class="flex justify-between" v-if="order.discount_amount > 0">
                <span>ডিসকাউন্ট:</span>
                <span class="font-bold text-emerald-600">- ৳ {{ formatMoney(order.discount_amount) }}</span>
              </div>
              <div class="pt-3 border-t border-slate-200 flex justify-between items-baseline text-sm">
                <span class="font-extrabold text-slate-900">সর্বমোট প্রদেয়:</span>
                <span class="text-xl font-black text-blue-600">৳ {{ formatMoney(order.amount) }}</span>
              </div>
            </div>

            <!-- Payment Badge & Details -->
            <div class="mt-4 pt-4 border-t border-slate-100 text-xs space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">পেমেন্ট মেথড:</span>
                <span class="font-bold text-slate-800 uppercase">{{ order.payment_method || 'bKash' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">পেমেন্ট স্ট্যাটাস:</span>
                <span :class="['px-2 py-0.5 rounded font-bold uppercase text-[10px]', order.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                  {{ order.payment_status === 'paid' ? 'পরিশোধিত (PAID)' : 'অপরিশোধিত (UNPAID)' }}
                </span>
              </div>
              <div class="flex items-center justify-between" v-if="order.transaction_id">
                <span class="text-slate-500">লেনদেন আইডি:</span>
                <span class="font-mono text-slate-700 text-[11px] truncate max-w-[140px]">{{ order.transaction_id }}</span>
              </div>
            </div>

            <!-- Buyer Fulfillment / Confirm Delivery Action -->
            <div class="mt-6 pt-5 border-t border-slate-200 space-y-2.5">
              <button
                v-if="isBuyer && (order.status === 'shipped' || order.escrow_status === 'held')"
                type="button"
                @click="confirmDelivery"
                :disabled="actionLoading"
                class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
              >
                <span>✅ পণ্য হাতে পেয়েছি — রিলিজ করুন</span>
              </button>

              <!-- Write Review Button (if delivered and not reviewed) -->
              <button
                v-if="isBuyer && (order.status === 'delivered' || order.status === 'completed') && !hasReviewed"
                type="button"
                @click="showReviewModal = true"
                class="w-full py-2.5 px-4 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-bold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <span>⭐ রিভিউ ও রেটিং দিন</span>
              </button>

              <!-- Cancel Order Button (if eligible) -->
              <button
                v-if="canCancel"
                type="button"
                @click="showCancelModal = true"
                class="w-full py-2.5 px-4 rounded-xl bg-slate-50 hover:bg-red-50 text-red-600 border border-slate-200 hover:border-red-200 font-bold text-xs transition flex items-center justify-center gap-1 cursor-pointer"
              >
                <span>✕ অর্ডার বাতিল করুন</span>
              </button>

              <!-- Raise Dispute Button -->
              <button
                v-if="isBuyer && (order.status === 'shipped' || order.status === 'delivered') && !order.dispute_status"
                type="button"
                @click="showDisputeModal = true"
                class="w-full py-2.5 px-4 rounded-xl bg-slate-50 hover:bg-amber-50 text-amber-700 border border-slate-200 hover:border-amber-200 font-bold text-xs transition flex items-center justify-center gap-1 cursor-pointer"
              >
                <span>⚠️ সমস্যা / বিরোধ দায়ের করুন (Dispute)</span>
              </button>
            </div>
          </div>

          <!-- Counterparty Card -->
          <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
            <span class="text-xs font-bold text-slate-400 uppercase block mb-3">
              {{ isBuyer ? 'বিক্রেতার পরিচিতি' : 'ক্রেতার পরিচিতি' }}
            </span>
            <div class="flex items-center gap-3">
              <img
                :src="(isBuyer ? order.seller?.avatar_url : order.buyer?.avatar_url) || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                alt="Counterparty"
                class="w-11 h-11 rounded-full object-cover ring-2 ring-slate-100"
              />
              <div>
                <h4 class="font-bold text-slate-900 text-sm">
                  {{ isBuyer ? (order.seller?.name || 'সেলার') : (order.buyer?.name || 'ক্রেতা') }}
                </h4>
                <p class="text-xs text-slate-500">
                  @{{ isBuyer ? order.seller?.username : order.buyer?.username }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Review Modal -->
    <div v-if="showReviewModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <h3 class="text-base font-extrabold text-slate-900 mb-1">পণ্যটির রিভিউ দিন</h3>
        <p class="text-xs text-slate-500 mb-4">আপনার মূল্যবান মতামত অন্যান্য ক্রেতাদের সঠিক পণ্য বেছে নিতে সাহায্য করবে।</p>

        <!-- Stars -->
        <div class="flex items-center gap-2 mb-4 justify-center py-3 bg-slate-50 rounded-xl">
          <button
            v-for="star in 5"
            :key="star"
            type="button"
            @click="reviewRating = star"
            class="text-2xl transition hover:scale-110 cursor-pointer"
            :class="star <= reviewRating ? 'text-amber-400' : 'text-slate-300'"
          >
            ★
          </button>
        </div>

        <textarea
          v-model="reviewComment"
          rows="3"
          placeholder="পণ্যের গুণমান ও প্যাকেজিং সম্পর্কে লিখুন..."
          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/30 resize-none mb-4"
        ></textarea>

        <div class="flex justify-end gap-2.5">
          <button
            type="button"
            @click="showReviewModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 cursor-pointer"
          >
            বাতিল
          </button>
          <button
            type="button"
            @click="submitReview"
            :disabled="actionLoading"
            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm cursor-pointer disabled:opacity-50"
          >
            {{ actionLoading ? 'জমা হচ্ছে...' : 'রিভিউ সাবমিট করুন' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Dispute Modal -->
    <div v-if="showDisputeModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <h3 class="text-base font-extrabold text-slate-900 mb-1">অর্ডার বিরোধ দায়ের করুন (Dispute)</h3>
        <p class="text-xs text-slate-500 mb-4">পণ্য না পেলে বা বিবরণের সাথে বড় ধরনের অমিল থাকলে অভিযোগ দিন।</p>

        <div class="space-y-3 mb-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">বিরোধের কারণ *</label>
            <select v-model="disputeReason" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs">
              <option value="Item not as described">পণ্য বিবরণের সাথে মিল নেই (Not as described)</option>
              <option value="Item damaged or defective">পণ্যটি ক্ষতিগ্রস্ত বা ত্রুটিযুক্ত (Damaged)</option>
              <option value="Item never received">পণ্য হাতে পাইনি (Never received)</option>
              <option value="Other">অন্যান্য কারণ (Other)</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">বিস্তারিত বিবরণ *</label>
            <textarea
              v-model="disputeNotes"
              rows="3"
              placeholder="সমস্যার বিস্তারিত বর্ণনা দিন..."
              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs resize-none"
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-2.5">
          <button
            type="button"
            @click="showDisputeModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 cursor-pointer"
          >
            বাতিল
          </button>
          <button
            type="button"
            @click="submitDispute"
            :disabled="actionLoading || !disputeNotes.trim()"
            class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm cursor-pointer disabled:opacity-50"
          >
            {{ actionLoading ? 'ফাইল হচ্ছে...' : 'বিরোধ ফাইল করুন' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Cancellation Modal -->
    <div v-if="showCancelModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <h3 class="text-base font-extrabold text-slate-900 mb-1">অর্ডার বাতিল নিশ্চিত করুন</h3>
        <p class="text-xs text-slate-500 mb-4">অর্ডারটি বাতিল করলে প্রদেয় অর্থ নিয়মানুযায়ী রিফান্ড তালিকায় চলে যাবে।</p>

        <textarea
          v-model="cancelReason"
          rows="2.5"
          placeholder="বাতিলের কারণ লিখুন..."
          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs resize-none mb-4"
        ></textarea>

        <div class="flex justify-end gap-2.5">
          <button
            type="button"
            @click="showCancelModal = false"
            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 cursor-pointer"
          >
            না, ফিরে যান
          </button>
          <button
            type="button"
            @click="submitCancel"
            :disabled="actionLoading"
            class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm cursor-pointer disabled:opacity-50"
          >
            {{ actionLoading ? 'বাতিল হচ্ছে...' : 'হ্যাঁ, অর্ডার বাতিল করুন' }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

interface OrderData {
  id: number
  order_number: string
  amount: number
  subtotal?: number
  unit_price?: number
  quantity?: number
  delivery_type?: string
  delivery_charge?: number
  discount_amount?: number
  currency: string
  status: string
  escrow_status: string
  payment_status: string
  payment_method: string
  transaction_id?: string
  shipping_name?: string
  shipping_phone?: string
  shipping_address?: string
  tracking_carrier?: string
  tracking_number?: string
  dispute_status?: string
  timestamps?: Record<string, string>
  created_at?: string
  buyer?: { id: number; name: string; username: string; avatar_url?: string }
  seller?: { id: number; name: string; username: string; avatar_url?: string }
  product?: {
    id: number
    title: string
    condition?: string
    location?: string
    primary_image?: string
    images?: string[]
  }
}

const props = defineProps<{
  order: OrderData
  currentUser?: { id: number; name: string; username: string; is_admin?: boolean }
  isBuyer?: boolean
  isSeller?: boolean
}>()

const showReviewModal = ref(false)
const showDisputeModal = ref(false)
const showCancelModal = ref(false)
const actionLoading = ref(false)

const reviewRating = ref(5)
const reviewComment = ref('')
const hasReviewed = ref(false)

const disputeReason = ref('Item not as described')
const disputeNotes = ref('')
const cancelReason = ref('ক্রেতার অনুরোধে বাতিল')

const canCancel = computed(() => {
  return in_array(props.order.status, ['pending', 'processing', 'confirmed'])
})

function in_array(needle: string, haystack: string[]): boolean {
  return haystack.includes(needle)
}

const isStepPassed = (states: string[]) => {
  return states.includes(props.order.status)
}

const formatMoney = (val: number | string | undefined) => {
  const num = Number(val) || 0
  return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
}

const formatDate = (dateStr?: string) => {
  if (!dateStr) return 'তারিখ পাওয়া যায়নি'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('bn-BD', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return dateStr
  }
}

const formatStatus = (st: string) => {
  const map: Record<string, string> = {
    pending: 'পেন্ডিং',
    processing: 'প্রসেসিং',
    confirmed: 'কনফার্মড',
    shipped: 'শিপড',
    delivered: 'ডেলিভার্ড',
    completed: 'সম্পন্ন',
    cancelled: 'বাতিল',
    refunded: 'রিফান্ডেড',
    disputed: 'বিরোধপূর্ণ',
  }
  return map[st] || st
}

const statusBadgeClass = (st: string) => {
  const map: Record<string, string> = {
    pending: 'bg-amber-50 text-amber-700 border-amber-200',
    processing: 'bg-blue-50 text-blue-700 border-blue-200',
    confirmed: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    shipped: 'bg-purple-50 text-purple-700 border-purple-200',
    delivered: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    completed: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    cancelled: 'bg-red-50 text-red-700 border-red-200',
    refunded: 'bg-rose-50 text-rose-700 border-rose-200',
    disputed: 'bg-orange-50 text-orange-700 border-orange-200',
  }
  return map[st] || 'bg-slate-50 text-slate-700 border-slate-200'
}

const confirmDelivery = async () => {
  if (!confirm('আপনি কি নিশ্চিত যে পণ্য হাতে পেয়ে সন্তুষ্ট হয়েছেন এবং পেমেন্ট রিলিজ করতে চান?')) return

  actionLoading.value = true
  try {
    const res = await fetch(`/api/v2/marketplace/orders/${props.order.id}/confirm-delivery`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
    })
    const data = await res.json()
    if (res.ok) {
      window.location.reload()
    } else {
      alert(data.message || 'ডেলিভারি কনফার্ম করা সম্ভব হয়নি।')
    }
  } catch (err: any) {
    alert(err.message || 'ত্রুটি ঘটেছে।')
  } finally {
    actionLoading.value = false
  }
}

const submitReview = async () => {
  actionLoading.value = true
  try {
    const res = await fetch(`/api/v2/marketplace/orders/${props.order.id}/review`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
      body: JSON.stringify({
        rating: reviewRating.value,
        comment: reviewComment.value,
      }),
    })
    const data = await res.json()
    if (res.ok) {
      hasReviewed.value = true
      showReviewModal.value = false
      alert('আপনার রিভিউ সফলভাবে প্রকাশ করা হয়েছে!')
    } else {
      alert(data.message || 'রিভিউ সাবমিট করা যায়নি।')
    }
  } catch (err: any) {
    alert(err.message || 'ত্রুটি ঘটেছে।')
  } finally {
    actionLoading.value = false
  }
}

const submitDispute = async () => {
  actionLoading.value = true
  try {
    const res = await fetch(`/api/v2/marketplace/orders/${props.order.id}/dispute`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
      body: JSON.stringify({
        reason: disputeReason.value,
        description: disputeNotes.value,
      }),
    })
    const data = await res.json()
    if (res.ok) {
      showDisputeModal.value = false
      window.location.reload()
    } else {
      alert(data.message || 'ডিসপিউট ফাইল করা সম্ভব হয়নি।')
    }
  } catch (err: any) {
    alert(err.message || 'ত্রুটি ঘটেছে।')
  } finally {
    actionLoading.value = false
  }
}

const submitCancel = async () => {
  actionLoading.value = true
  try {
    const res = await fetch(`/api/v2/marketplace/orders/${props.order.id}/cancel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      },
      body: JSON.stringify({
        reason: cancelReason.value || 'ক্রেতার অনুরোধে বাতিল',
      }),
    })
    const data = await res.json()
    if (res.ok) {
      showCancelModal.value = false
      window.location.reload()
    } else {
      alert(data.message || 'অর্ডার বাতিল করা সম্ভব হয়নি।')
    }
  } catch (err: any) {
    alert(err.message || 'ত্রুটি ঘটেছে।')
  } finally {
    actionLoading.value = false
  }
}

const printInvoice = () => {
  window.print()
}
</script>
