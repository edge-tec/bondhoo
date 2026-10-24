<template>
  <AppLayout
    title="Orders & Commerce — SocialHub Marketplace"
    current-route="marketplace"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">

      <!-- Header Section -->
      <div class="bg-gradient-to-r from-blue-600 via-[#1877F2] to-indigo-600 rounded-3xl p-6 md:p-8 text-white shadow-xl shadow-blue-500/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-semibold mb-2">
            <span>🛡️ SocialHub Secure Payment Hold</span>
          </div>
          <h1 class="text-2xl md:text-3xl font-black tracking-tight">
            অর্ডার ও বাণিজ্য ড্যাশবোর্ড
          </h1>
          <p class="text-sm text-blue-100 mt-1 max-w-xl">
            আপনার সকল কেনাকাটা এবং বিক্রয়ের লেনদেন নিরাপদ পেমেন্ট হোল্ডে সংরক্ষিত। পণ্য বুঝে পেয়ে নিশ্চিত করার পরেই কেবল বিক্রেতার ফান্ড আনলক হয়।
          </p>
        </div>

        <div class="flex items-center gap-3">
          <a
            href="/marketplace"
            class="px-4 py-2.5 rounded-2xl bg-white text-[#1877F2] font-bold text-xs hover:bg-blue-50 transition-all shadow-sm flex items-center gap-2"
          >
            <span>🛍️ মার্কেটপ্লেস</span>
          </a>
          <a
            href="/marketplace/my-listings"
            class="px-4 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition-all backdrop-blur-md flex items-center gap-2 border border-white/20"
          >
            <span>🏷️ আমার লিস্টিং</span>
          </a>
        </div>
      </div>

      <!-- Seller Analytics Summary Card (When on Sales Tab) -->
      <div v-if="activeTab === 'sales' && sellerSummary" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-sm">
          <div class="text-xs text-slate-500 font-semibold">Gross Sales</div>
          <div class="text-xl font-extrabold text-slate-900 dark:text-white mt-1">৳ {{ Number(sellerSummary.gross_sales || 0).toLocaleString() }}</div>
          <div class="text-[11px] text-slate-400 mt-0.5">{{ sellerSummary.total_orders || 0 }} total orders</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-emerald-200/80 dark:border-emerald-800/40 shadow-sm bg-emerald-50/20">
          <div class="text-xs text-emerald-700 dark:text-emerald-400 font-semibold">Net Earnings (Released)</div>
          <div class="text-xl font-extrabold text-emerald-600 mt-1">৳ {{ Number(sellerSummary.net_earnings || 0).toLocaleString() }}</div>
          <div class="text-[11px] text-emerald-600/80 mt-0.5">{{ sellerSummary.completed_orders || 0 }} completed</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-blue-200/80 dark:border-blue-800/40 shadow-sm bg-blue-50/20">
          <div class="text-xs text-[#1877F2] font-semibold">In Payment Hold</div>
          <div class="text-xl font-extrabold text-[#1877F2] mt-1">৳ {{ Number(sellerSummary.pending_settlements || 0).toLocaleString() }}</div>
          <div class="text-[11px] text-blue-600/80 mt-0.5">{{ sellerSummary.active_orders || 0 }} active in transit</div>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-rose-200/80 dark:border-rose-800/40 shadow-sm bg-rose-50/20">
          <div class="text-xs text-rose-700 dark:text-rose-400 font-semibold">Refunded Amount</div>
          <div class="text-xl font-extrabold text-rose-600 mt-1">৳ {{ Number(sellerSummary.refunded_amount || 0).toLocaleString() }}</div>
          <div class="text-[11px] text-rose-600/80 mt-0.5">{{ sellerSummary.cancelled_orders || 0 }} cancelled / refunded</div>
        </div>
      </div>

      <!-- Main Tabs and Status Filter -->
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Role Tabs -->
        <div class="flex items-center gap-2 p-1 rounded-2xl bg-slate-100 dark:bg-slate-800/60 w-fit">
          <button
            type="button"
            @click="switchTab('purchases')"
            class="px-5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
            :class="activeTab === 'purchases' ? 'bg-white dark:bg-slate-900 text-[#1877F2] shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
          >
            <span>🛍️ আমার ক্রয় (Purchases)</span>
          </button>
          <button
            type="button"
            @click="switchTab('sales')"
            class="px-5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2"
            :class="activeTab === 'sales' ? 'bg-white dark:bg-slate-900 text-[#1877F2] shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
          >
            <span>💼 আমার বিক্রয় (Sales)</span>
          </button>
        </div>

        <!-- Status Filter Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 scrollbar-none text-xs">
          <button
            v-for="st in statusFilters"
            :key="st.key"
            @click="filterByStatus(st.key)"
            class="px-3 py-1.5 rounded-full font-bold transition-all shrink-0"
            :class="currentStatus === st.key ? 'bg-[#1877F2] text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'"
          >
            {{ st.label }}
          </button>
        </div>
      </div>

      <!-- Orders List Container -->
      <div v-if="isLoading" class="py-16 text-center">
        <div class="w-8 h-8 border-3 border-[#1877F2] border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="text-xs text-slate-400 font-semibold mt-3">অর্ডার লোড হচ্ছে...</p>
      </div>

      <div v-else-if="orders.length === 0" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-12 text-center shadow-sm">
        <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center text-3xl mx-auto mb-3">
          📭
        </div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white">
          {{ activeTab === 'purchases' ? 'আপনার কোনো কেনাকাটার অর্ডার নেই' : 'আপনার কোনো বিক্রয়ের অর্ডার পাওয়া যায়নি' }}
        </h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1">
          {{ activeTab === 'purchases' ? 'মার্কেটপ্লেসে দারুণ সব পণ্য খুঁজুন এবং নিরাপদ পেমেন্ট হোল্ড দিয়ে অর্ডার করুন।' : 'নতুন পণ্যের বিজ্ঞাপন দিন এবং সহজে সারাদেশে কেনাবেচা করুন।' }}
        </p>
        <div class="mt-4">
          <a
            :href="activeTab === 'purchases' ? '/marketplace' : '/marketplace/create'"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-[#1877F2] text-white text-xs font-bold hover:bg-blue-600 transition-all shadow-md shadow-blue-500/20"
          >
            <span>{{ activeTab === 'purchases' ? '🛍️ মার্কেটপ্লেস ব্রাউজ করুন' : '➕ নতুন বিজ্ঞাপন পোস্ট করুন' }}</span>
          </a>
        </div>
      </div>

      <!-- Order Cards Grid -->
      <div v-else class="space-y-4">
        <div
          v-for="order in orders"
          :key="'order-' + order.id"
          class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-sm space-y-4 hover:border-slate-300 dark:hover:border-slate-700 transition-all"
        >
          <!-- Card Header -->
          <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3.5">
            <div class="flex items-center gap-3">
              <span class="font-extrabold text-sm text-slate-900 dark:text-white">
                #{{ order.order_number || ('ORD-' + order.id) }}
              </span>
              <span class="text-xs text-slate-400">
                {{ formatDate(order.created_at) }}
              </span>
            </div>

            <!-- Badges -->
            <div class="flex items-center gap-2">
              <!-- Order Status Badge -->
              <span
                class="px-2.5 py-1 rounded-full text-[11px] font-extrabold tracking-wide uppercase"
                :class="getStatusBadgeClass(order.status)"
              >
                {{ formatStatus(order.status) }}
              </span>

              <!-- Escrow Status Badge -->
              <span
                class="px-2.5 py-1 rounded-full text-[11px] font-bold"
                :class="order.escrow_status === 'released' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-[#1877F2] border border-blue-200'"
              >
                {{ order.escrow_status === 'released' ? '🛡️ পেমেন্ট রিলিজড' : '🛡️ পেমেন্ট হোল্ড' }}
              </span>
            </div>
          </div>

          <!-- Card Body: Product info, delivery, price -->
          <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
            <!-- Product Thumbnail & Title -->
            <div class="md:col-span-6 flex items-center gap-3.5">
              <img
                :src="getOrderThumbnail(order)"
                :alt="order.product?.title || 'Product'"
                class="w-16 h-16 rounded-2xl object-cover shrink-0 bg-slate-100 dark:bg-slate-800 border border-slate-200/60 dark:border-slate-700"
              />
              <div class="min-w-0 flex-1">
                <a
                  :href="'/marketplace/' + (order.product_id || order.product?.id)"
                  class="font-bold text-sm text-slate-900 dark:text-white hover:text-[#1877F2] truncate block"
                >
                  {{ order.product?.title || order.product_snapshot?.title || 'Marketplace Item' }}
                </a>
                <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                  <span>পরিমাণ: <strong>{{ order.quantity || 1 }} টি</strong></span>
                  <span>•</span>
                  <span>গেটওয়ে: <strong class="uppercase text-slate-700 dark:text-slate-300">{{ order.payment_method }}</strong></span>
                </div>
                <div v-if="order.tracking_number" class="mt-1 flex items-center gap-1.5 text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold">
                  <span>🚚 ট্র্যাকিং ({{ order.tracking_carrier || 'Courier' }}):</span>
                  <span class="font-mono bg-indigo-50 dark:bg-indigo-950/40 px-1.5 py-0.5 rounded">{{ order.tracking_number }}</span>
                </div>
              </div>
            </div>

            <!-- Price Breakdown -->
            <div class="md:col-span-3 text-left md:text-right">
              <div class="text-base font-extrabold text-[#1877F2]">
                ৳ {{ Number(order.amount).toLocaleString() }}
              </div>
              <div class="text-[11px] text-slate-400 mt-0.5">
                (ডেলিভারি চার্জ: ৳ {{ Number(order.delivery_charge || 0) }})
              </div>
              <div class="text-[11px] font-semibold text-slate-500 mt-0.5">
                {{ activeTab === 'purchases' ? 'বিক্রেতা: ' + (order.seller?.name || 'Seller') : 'ক্রেতা: ' + (order.buyer?.name || 'Buyer') }}
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="md:col-span-3 flex flex-col gap-2 justify-end">
              <!-- Invoice Link -->
              <a
                :href="'/marketplace/orders/' + order.id"
                class="px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold text-center transition-all"
              >
                📄 ইনভয়েস ও ট্র্যাকিং
              </a>

              <!-- Buyer Actions -->
              <template v-if="activeTab === 'purchases'">
                <!-- Release Escrow Button -->
                <button
                  v-if="order.escrow_status === 'held' && ['shipped', 'delivered', 'processing'].includes(order.status)"
                  type="button"
                  @click="releaseEscrow(order)"
                  :disabled="actionInProgress === order.id"
                  class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold text-center shadow-sm transition-all"
                >
                  {{ actionInProgress === order.id ? 'অপেক্ষা করুন...' : '✅ পণ্য পেয়েছি (রিলিজ)' }}
                </button>

                <!-- Review Button (if delivered) -->
                <button
                  v-if="['delivered', 'completed'].includes(order.status) && !order.has_reviewed"
                  type="button"
                  @click="openReviewModal(order)"
                  class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold text-center shadow-sm transition-all"
                >
                  ⭐ রিভিউ লিখুন
                </button>

                <!-- Dispute Button -->
                <button
                  v-if="!['cancelled', 'refunded'].includes(order.status) && order.status !== 'disputed'"
                  type="button"
                  @click="openDisputeModal(order)"
                  class="px-3 py-1 text-[11px] font-semibold text-rose-500 hover:text-rose-600 text-center"
                >
                  বিরোধ (Dispute) ফাইল করুন
                </button>
              </template>

              <!-- Seller Actions -->
              <template v-else>
                <!-- Ship Order Button -->
                <button
                  v-if="['processing', 'confirmed', 'paid', 'pending'].includes(order.status)"
                  type="button"
                  @click="openShipModal(order)"
                  class="px-3 py-1.5 rounded-xl bg-[#1877F2] hover:bg-blue-600 text-white text-xs font-bold text-center shadow-sm transition-all"
                >
                  🚚 পণ্য শিপ করুন
                </button>

                <!-- Mark Delivered Button -->
                <button
                  v-if="order.status === 'shipped'"
                  type="button"
                  @click="markDelivered(order)"
                  :disabled="actionInProgress === order.id"
                  class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold text-center shadow-sm transition-all"
                >
                  📦 ডেলিভারি সম্পন্ন
                </button>
              </template>

              <!-- Cancel Button (if permitted) -->
              <button
                v-if="['pending', 'processing'].includes(order.status)"
                type="button"
                @click="openCancelModal(order)"
                class="px-3 py-1 text-[11px] font-semibold text-slate-500 hover:text-rose-600 text-center"
              >
                অর্ডার বাতিল করুন
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Ship Order Modal (Seller) -->
    <div v-if="shipModalOrder" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
        <h3 class="font-bold text-base text-slate-900 dark:text-white">
          অর্ডার শিপমেন্ট ট্র্যাকিং যুক্ত করুন
        </h3>
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">কুরিয়ার / ক্যারিয়ার:</label>
          <input
            v-model="trackingCarrier"
            type="text"
            placeholder="Steadfast / Pathao / RedX / Paperfly..."
            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs"
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">ট্র্যাকিং নম্বর / চালান নং:</label>
          <input
            v-model="trackingNumber"
            type="text"
            placeholder="e.g. TRK-84920491"
            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-mono"
          />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button @click="shipModalOrder = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500">বাতিল</button>
          <button @click="submitShipOrder" class="px-5 py-2 rounded-xl bg-[#1877F2] text-white text-xs font-bold hover:bg-blue-600">শিপ নিশ্চিত করুন</button>
        </div>
      </div>
    </div>

    <!-- Cancel Order Modal -->
    <div v-if="cancelModalOrder" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
        <h3 class="font-bold text-base text-rose-600">
          অর্ডার বাতিল নিশ্চিতকরণ
        </h3>
        <p class="text-xs text-slate-500">
          অর্ডার #{{ cancelModalOrder.order_number || cancelModalOrder.id }} বাতিল করার কারণ উল্লেখ করুন:
        </p>
        <textarea
          v-model="cancelReason"
          rows="3"
          placeholder="বাতিল করার সুনির্দিষ্ট কারণ লিখুন..."
          class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs"
        ></textarea>
        <div class="flex justify-end gap-2 pt-2">
          <button @click="cancelModalOrder = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500">ফিরে যান</button>
          <button @click="submitCancelOrder" class="px-5 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700">অর্ডার বাতিল করুন</button>
        </div>
      </div>
    </div>

    <!-- Dispute Modal -->
    <div v-if="disputeModalOrder" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
        <h3 class="font-bold text-base text-slate-900 dark:text-white">
          অর্ডার বিরোধ (Dispute) ফাইল করুন
        </h3>
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">বিরোধের প্রধান কারণ:</label>
          <select v-model="disputeReason" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs">
            <option value="Item not received">পণ্য হাতে পাইনি (Item not received)</option>
            <option value="Item damaged or defective">পণ্যটি ক্ষতিগ্রস্ত বা অচল (Damaged/Defective)</option>
            <option value="Different from description">বর্ণনার সাথে মিল নেই (Different from description)</option>
            <option value="Fraudulent seller">সেলার কোনো সাড়া দিচ্ছেন না (Seller unresponsive)</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">বিস্তারিত বিবরণ:</label>
          <textarea
            v-model="disputeDescription"
            rows="3"
            placeholder="প্রমাণের বিস্তারিত তথ্য দিন..."
            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs"
          ></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button @click="disputeModalOrder = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500">বাতিল</button>
          <button @click="submitDisputeOrder" class="px-5 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700">বিরোধ জমা দিন</button>
        </div>
      </div>
    </div>

    <!-- Review Modal -->
    <div v-if="reviewModalOrder" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-4">
        <h3 class="font-bold text-base text-slate-900 dark:text-white">
          ভেরিফায়েড ক্রয়ের অভিজ্ঞতা ও রিভিউ
        </h3>
        <p class="text-xs text-slate-500">পণ্যটি পেয়ে আপনার অভিজ্ঞতা কেমন ছিল? স্টার রেটিং দিন:</p>
        <div class="flex items-center gap-2 text-2xl cursor-pointer">
          <span
            v-for="star in 5"
            :key="'star-' + star"
            @click="reviewRating = star"
            :class="star <= reviewRating ? 'text-amber-500' : 'text-slate-300 dark:text-slate-700'"
          >
            ★
          </span>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">মন্তব্য:</label>
          <textarea
            v-model="reviewComment"
            rows="3"
            placeholder="পণ্যের গুণগত মান, ডেলিভারি গতি ও বিক্রেতার ব্যবহার সম্পর্কে লিখুন..."
            class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs"
          ></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button @click="reviewModalOrder = null" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500">বাতিল</button>
          <button @click="submitReview" class="px-5 py-2 rounded-xl bg-amber-500 text-white text-xs font-bold hover:bg-amber-600">রিভিউ প্রকাশ করুন</button>
        </div>
      </div>
    </div>

  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps<{
  activeTab?: string;
  purchases?: any;
  sales?: any;
}>();

const activeTab = ref(props.activeTab || 'purchases');
const currentStatus = ref<string | null>(null);
const orders = ref<any[]>([]);
const sellerSummary = ref<any>(null);
const isLoading = ref(false);
const actionInProgress = ref<number | null>(null);

// Modals state
const shipModalOrder = ref<any>(null);
const trackingCarrier = ref('');
const trackingNumber = ref('');

const cancelModalOrder = ref<any>(null);
const cancelReason = ref('');

const disputeModalOrder = ref<any>(null);
const disputeReason = ref('Item not received');
const disputeDescription = ref('');

const reviewModalOrder = ref<any>(null);
const reviewRating = ref(5);
const reviewComment = ref('');

const statusFilters = [
  { key: null, label: 'সকল অর্ডার' },
  { key: 'processing', label: 'প্রসেসিং' },
  { key: 'shipped', label: 'শিপড' },
  { key: 'delivered', label: 'ডেলিভার্ড' },
  { key: 'cancelled', label: 'বাতিল' },
  { key: 'disputed', label: 'বিরোধ' },
];

const fetchOrders = async () => {
  isLoading.value = true;
  try {
    const role = activeTab.value === 'sales' ? 'seller' : 'buyer';
    let url = `/api/v2/marketplace/orders?role=${role}`;
    if (currentStatus.value) {
      url += `&status=${currentStatus.value}`;
    }

    const res = await fetch(url, {
      headers: { Accept: 'application/json' },
    });
    const data = await res.json();
    if (data.status === 'success') {
      orders.value = data.data.data || data.data || [];
    }

    if (activeTab.value === 'sales') {
      fetchSellerSummary();
    }
  } catch (e) {
    console.error(e);
  } finally {
    isLoading.value = false;
  }
};

const fetchSellerSummary = async () => {
  try {
    const res = await fetch('/api/v2/marketplace/seller/summary', {
      headers: { Accept: 'application/json' },
    });
    const data = await res.json();
    if (data.status === 'success') {
      sellerSummary.value = data.data;
    }
  } catch (e) {
    console.error(e);
  }
};

const switchTab = (tab: string) => {
  activeTab.value = tab;
  currentStatus.value = null;
  fetchOrders();
};

const filterByStatus = (statusKey: string | null) => {
  currentStatus.value = statusKey;
  fetchOrders();
};

const releaseEscrow = async (order: any) => {
  if (!confirm('আপনি কি পণ্য হাতে পেয়েছেন এবং বিক্রেতাকে পেমেন্ট ছাড়তে চান?')) return;
  actionInProgress.value = order.id;

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v2/marketplace/orders/${order.id}/release-escrow`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert('পেমেন্ট সফলভাবে রিলিজ হয়েছে!');
      fetchOrders();
    } else {
      alert(data.message || 'ব্যর্থ হয়েছে।');
    }
  } catch (e: any) {
    alert(e.message || 'Network error');
  } finally {
    actionInProgress.value = null;
  }
};

const markDelivered = async (order: any) => {
  actionInProgress.value = order.id;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v2/marketplace/orders/${order.id}/confirm-delivery`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      fetchOrders();
    }
  } catch (e: any) {
    alert(e.message || 'Network error');
  } finally {
    actionInProgress.value = null;
  }
};

const openShipModal = (order: any) => {
  shipModalOrder.value = order;
  trackingCarrier.value = '';
  trackingNumber.value = '';
};

const submitShipOrder = async () => {
  if (!shipModalOrder.value) return;
  const orderId = shipModalOrder.value.id;

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v2/marketplace/orders/${orderId}/ship`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
      body: JSON.stringify({
        carrier: trackingCarrier.value.trim() || undefined,
        tracking_number: trackingNumber.value.trim() || undefined,
      }),
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      shipModalOrder.value = null;
      fetchOrders();
    } else {
      alert(data.message || 'Failed to update shipping.');
    }
  } catch (e: any) {
    alert(e.message || 'Network error');
  }
};

const openCancelModal = (order: any) => {
  cancelModalOrder.value = order;
  cancelReason.value = '';
};

const submitCancelOrder = async () => {
  if (!cancelModalOrder.value || !cancelReason.value.trim()) return;
  const orderId = cancelModalOrder.value.id;

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v2/marketplace/orders/${orderId}/cancel`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
      body: JSON.stringify({ reason: cancelReason.value.trim() }),
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      cancelModalOrder.value = null;
      fetchOrders();
    } else {
      alert(data.message || 'অর্ডার বাতিল করা সম্ভব হয়নি।');
    }
  } catch (e: any) {
    alert(e.message || 'Network error');
  }
};

const openDisputeModal = (order: any) => {
  disputeModalOrder.value = order;
  disputeReason.value = 'Item not received';
  disputeDescription.value = '';
};

const submitDisputeOrder = async () => {
  if (!disputeModalOrder.value) return;
  const orderId = disputeModalOrder.value.id;

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v2/marketplace/orders/${orderId}/dispute`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
      body: JSON.stringify({
        reason: disputeReason.value,
        description: disputeDescription.value,
      }),
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert('বিরোধ সফলভাবে দায়ের করা হয়েছে। আমাদের টিম এটি পর্যালোচনা করছে।');
      disputeModalOrder.value = null;
      fetchOrders();
    } else {
      alert(data.message || 'বিরোধ ফাইল করা যায়নি।');
    }
  } catch (e: any) {
    alert(e.message || 'Network error');
  }
};

const openReviewModal = (order: any) => {
  reviewModalOrder.value = order;
  reviewRating.value = 5;
  reviewComment.value = '';
};

const submitReview = async () => {
  if (!reviewModalOrder.value) return;
  const orderId = reviewModalOrder.value.id;

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch(`/api/v2/marketplace/orders/${orderId}/review`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
      },
      body: JSON.stringify({
        rating: reviewRating.value,
        comment: reviewComment.value,
      }),
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert('ধন্যবাদ! আপনার রিভিউ সফলভাবে প্রকাশিত হয়েছে।');
      reviewModalOrder.value = null;
      fetchOrders();
    } else {
      alert(data.message || 'রিভিউ জমা দেওয়া সম্ভব হয়নি।');
    }
  } catch (e: any) {
    alert(e.message || 'Network error');
  }
};

const getOrderThumbnail = (order: any) => {
  if (order.product?.images && order.product.images.length > 0) {
    return order.product.images[0];
  }
  if (order.product_snapshot?.images && order.product_snapshot.images.length > 0) {
    return order.product_snapshot.images[0];
  }
  return 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=100';
};

const formatDate = (dateString?: string) => {
  if (!dateString) return 'সম্প্রতি';
  try {
    const d = new Date(dateString);
    return d.toLocaleDateString('bn-BD', { day: 'numeric', month: 'short', year: 'numeric' });
  } catch (_) {
    return dateString;
  }
};

const formatStatus = (st: string) => {
  const map: Record<string, string> = {
    pending: 'পেন্ডিং',
    processing: 'প্রসেসিং',
    confirmed: 'কনফার্মড',
    shipped: 'শিপড',
    delivered: 'ডেলিভার্ড',
    completed: 'সম্পন্ন',
    cancelled: 'বাতিল',
    disputed: 'বিরোধপূর্ণ',
    refunded: 'রিফান্ডেড',
  };
  return map[st] || st;
};

const getStatusBadgeClass = (st: string) => {
  const map: Record<string, string> = {
    pending: 'bg-amber-50 text-amber-700 border border-amber-200',
    processing: 'bg-blue-50 text-blue-700 border border-blue-200',
    confirmed: 'bg-indigo-50 text-indigo-700 border border-indigo-200',
    shipped: 'bg-purple-50 text-purple-700 border border-purple-200',
    delivered: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
    completed: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
    cancelled: 'bg-rose-50 text-rose-700 border border-rose-200',
    disputed: 'bg-orange-50 text-orange-700 border border-orange-200',
    refunded: 'bg-slate-100 text-slate-700 border border-slate-300',
  };
  return map[st] || 'bg-slate-100 text-slate-700';
};

onMounted(() => {
  fetchOrders();
});
</script>
