<template>
  <aside class="hidden lg:block w-72 xl:w-80 shrink-0 sticky top-20 self-start max-h-[calc(100vh-5.5rem)]">
    <!-- Professional Sidebar Navigation System -->
    <SidebarNavigation
      :user="user"
      :current-route="currentRoute"
      :unread-count="unreadCount"
      :unread-messages-count="unreadMessagesCount"
      :is-mobile="false"
      @close="toggleCollapse"
    />

    <!-- Collapsible Extended Section (Maintains Desktop Layout Parity) -->
    <div v-if="showMore" class="hidden">
      <a href="/messages">Messages</a>
      <a href="/notifications">Notifications</a>
      <a href="/settings">Settings</a>
      <a href="/terms">Terms</a>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import SidebarNavigation from './SidebarNavigation.vue';
import type { User } from '../types';

defineProps<{
  user: User | null;
  currentRoute?: string;
  unreadCount?: number;
  unreadMessagesCount?: number;
}>();

const isCollapsed = ref(false);
const showMore = ref(false);

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value;
  showMore.value = !showMore.value;
};
</script>
