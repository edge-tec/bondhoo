<template>
  <AppLayout
    current-route="notifications"
    :unread-count="liveUnreadCount"
    :pending-requests="pendingRequests"
    :contacts="contacts"
  >
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-[#E4E7EC] dark:border-slate-800 p-6 shadow-sm mb-6">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-[#E4E7EC] dark:border-slate-800 pb-4 mb-4">
        <div>
          <h1 class="text-2xl font-bold text-[#172033] dark:text-slate-100">Notifications</h1>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Stay updated with activities, reactions, and friend requests.</p>
        </div>

        <button
          v-if="hasUnread"
          @click="markAllAsRead"
          :disabled="isMarkingAll"
          class="text-xs font-bold text-[#1877F2] hover:text-[#1666D8] dark:text-blue-400 p-2 rounded-xl hover:bg-[#E7F3FF] dark:hover:bg-blue-950/40 transition-colors"
        >
          {{ isMarkingAll ? 'Marking...' : 'Mark all as read' }}
        </button>
      </div>

      <!-- Filter Tabs -->
      <div class="flex items-center gap-2 mb-4">
        <button
          @click="activeFilter = 'all'"
          :class="[
            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all',
            activeFilter === 'all'
              ? 'bg-[#1877F2] text-white shadow-sm'
              : 'bg-[#F5F7FB] dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
          ]"
        >
          All
        </button>
        <button
          @click="activeFilter = 'unread'"
          :class="[
            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all',
            activeFilter === 'unread'
              ? 'bg-[#1877F2] text-white shadow-sm'
              : 'bg-[#F5F7FB] dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'
          ]"
        >
          Unread
        </button>
      </div>

      <!-- Notifications List -->
      <div v-if="filteredNotifications.length > 0" class="space-y-2">
        <div
          v-for="item in filteredNotifications"
          :key="item.id"
          @click="markAsRead(item)"
          :class="[
            'flex items-start gap-3.5 p-3.5 rounded-2xl transition-all cursor-pointer border',
            !item.read_at
              ? 'bg-blue-50/60 dark:bg-blue-950/20 border-blue-200/60 dark:border-blue-900/40 hover:bg-blue-50 dark:hover:bg-blue-950/30'
              : 'bg-white dark:bg-slate-900 border-transparent hover:bg-slate-50 dark:hover:bg-slate-800/60'
          ]"
        >
          <!-- Notification Icon or Sender Avatar -->
          <div class="relative shrink-0">
            <img
              :src="item.data?.sender?.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
              alt="Avatar"
              class="w-10 h-10 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-800 shadow-xs"
            />
            <span
              class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] text-white shadow-sm"
              :class="getIconBadgeClass(item.type)"
            >
              <span v-if="item.type.includes('reaction')">❤️</span>
              <span v-else-if="item.type.includes('comment')">💬</span>
              <span v-else-if="item.type.includes('friend')">👥</span>
              <span v-else>🔔</span>
            </span>
          </div>

          <!-- Notification Body -->
          <div class="flex-1 min-w-0">
            <p class="text-xs text-slate-800 dark:text-slate-200 leading-relaxed">
              <span class="font-semibold text-slate-900 dark:text-slate-100">{{ item.data?.sender?.name || 'Someone' }}</span>
              {{ formatNotificationText(item) }}
            </p>
            <p class="text-[11px] text-slate-400 mt-1">
              {{ formatDate(item.created_at) }}
            </p>
          </div>

          <!-- Unread Dot -->
          <div v-if="!item.read_at" class="shrink-0 self-center">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 block"></span>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <EmptyState
        v-else
        title="No Notifications"
        :description="activeFilter === 'unread' ? 'You have no unread notifications.' : 'You are completely caught up!'"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import EmptyState from '../Components/EmptyState.vue';
import type { NotificationItem, User } from '../types';

interface NotificationsProps {
  notifications?: {
    data: NotificationItem[];
  } | NotificationItem[];
  unreadCount?: number;
  pendingRequests?: any[];
  contacts?: User[];
}

const props = withDefaults(defineProps<NotificationsProps>(), {
  unreadCount: 0,
  pendingRequests: () => [],
  contacts: () => [],
});

const initialList = Array.isArray(props.notifications)
  ? props.notifications
  : (props.notifications?.data || []);

const list = ref<NotificationItem[]>([...initialList]);
const activeFilter = ref<'all' | 'unread'>('all');
const isMarkingAll = ref(false);

const filteredNotifications = computed(() => {
  if (activeFilter.value === 'unread') {
    return list.value.filter((n) => !n.read_at);
  }
  return list.value;
});

const hasUnread = computed(() => {
  return list.value.some((n) => !n.read_at);
});

const liveUnreadCount = computed(() => {
  return list.value.filter((n) => !n.read_at).length;
});

const syncUnreadBadge = () => {
  if (typeof window !== 'undefined') {
    window.dispatchEvent(
      new CustomEvent('jugajug:unread-updated', {
        detail: { count: liveUnreadCount.value },
      })
    );
  }
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return 'Just now';
  const d = new Date(dateStr);
  return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getIconBadgeClass = (type: string) => {
  if (type.includes('reaction')) return 'bg-rose-500';
  if (type.includes('comment')) return 'bg-emerald-500';
  if (type.includes('friend')) return 'bg-blue-500';
  return 'bg-indigo-500';
};

const formatNotificationText = (item: NotificationItem) => {
  if (item.message) return ` ${item.message}`;
  if (item.data?.message) return ` ${item.data.message}`;
  if (item.type.includes('reaction')) return ' reacted to your post.';
  if (item.type.includes('comment')) return ' commented on your post.';
  if (item.type.includes('friend_request_accepted')) return ' accepted your friend request.';
  if (item.type.includes('friend_request')) return ' sent you a friend request.';
  return ' interacted with your profile.';
};

const markAsRead = async (item: NotificationItem) => {
  if (item.read_at) return;
  item.read_at = new Date().toISOString();
  syncUnreadBadge();

  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    await fetch(`/api/v1/notifications/${item.id}/read`, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
  } catch (err) {
    console.error(err);
  }
};

const markAllAsRead = async () => {
  isMarkingAll.value = true;
  try {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const res = await fetch('/api/v1/notifications/mark-all-read', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });

    if (res.ok) {
      list.value.forEach((n) => {
        n.read_at = new Date().toISOString();
      });
      syncUnreadBadge();
    }
  } catch (err) {
    console.error(err);
  } finally {
    isMarkingAll.value = false;
  }
};
</script>
