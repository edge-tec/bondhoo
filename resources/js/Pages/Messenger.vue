<template>
  <AppLayout
    current-route="messages"
    :unread-count="unreadCount"
    :unread-messages-count="totalUnreadCount"
    :pending-requests="pendingRequests"
    :contacts="contacts"
    :suggestions="suggestions"
    :upcoming-events="upcomingEvents"
    :hide-right-sidebar="true"
    :full-width="true"
  >
    <!-- Messenger Main Card (3-Column Desktop / Responsive Mobile) -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-[#E4E7EC] dark:border-slate-800 overflow-hidden flex h-[calc(100dvh-8.5rem)] md:h-[calc(100vh-125px)] min-h-[420px] md:min-h-[640px] relative antialiased">
      
      <!-- ========================================== -->
      <!-- COLUMN 1: CONVERSATIONS LIST               -->
      <!-- ========================================== -->
      <section
        :class="[
          'w-full md:w-[340px] lg:w-[360px] shrink-0 border-r border-[#E4E7EC] dark:border-slate-800 flex flex-col bg-white dark:bg-slate-900 transition-all duration-200 z-10',
          mobileView !== 'conversations' ? 'hidden md:flex' : 'flex'
        ]"
      >
        <!-- Header: Brand, Saved, Create Group, Settings, New Message -->
        <div class="p-3.5 sm:p-4 pb-3 flex items-center justify-between gap-1.5 sm:gap-2">
          <div class="flex items-center gap-2 min-w-0 shrink-0">
            <span class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#1877F2] to-violet-500 text-white flex items-center justify-center shadow-sm shrink-0">
              <svg class="w-4.5 h-4.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.91 1.19 5.44 3.14 7.17.16.14.26.35.27.57l.05 1.78c.02.57.6.94 1.12.71l1.98-.87c.17-.08.36-.09.53-.04.91.25 1.88.38 2.91.38 5.64 0 10-4.13 10-9.7S17.64 2 12 2zm6 7.46l-2.94 4.66c-.47.74-1.47.93-2.17.4l-2.34-1.75a.6.6 0 00-.72 0l-3.16 2.4c-.42.32-.97-.18-.69-.63l2.94-4.66c.47-.74 1.47-.93 2.17-.4l2.34 1.75a.6.6 0 00.72 0l3.16-2.4c.42-.32.97.18.69.63z"/></svg>
            </span>
            <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white tracking-tight shrink-0 whitespace-nowrap">
              Messages
            </h2>
            <span v-if="totalUnreadCount > 0" class="px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-bold shrink-0">{{ totalUnreadCount }}</span>
          </div>
          <div class="flex items-center gap-0.5 shrink-0">
            <button @click="openSavedMessages" type="button" title="Saved Messages" aria-label="Saved Messages" class="w-8 h-8 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:text-[#1877F2] hover:bg-blue-50 dark:hover:bg-slate-800 rounded-lg transition-all cursor-pointer">
              <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>
            </button>
            <button @click="openCreateGroup" type="button" title="Create Group" aria-label="Create Group" class="w-8 h-8 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:text-[#1877F2] hover:bg-blue-50 dark:hover:bg-slate-800 rounded-lg transition-all cursor-pointer">
              <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </button>
            <button @click="showSettingsModal = true" type="button" title="Messenger Settings" aria-label="Messenger Settings" class="w-8 h-8 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:text-[#1877F2] hover:bg-blue-50 dark:hover:bg-slate-800 rounded-lg transition-all cursor-pointer">
              <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            </button>
            <button
              @click="openNewMessageModal"
              type="button"
              title="New Message"
              aria-label="New Message"
              class="w-8 h-8 flex items-center justify-center text-white bg-[#1877F2] hover:bg-[#1666D8] rounded-lg transition-all cursor-pointer shadow-sm"
            >
              <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Search Conversations Input -->
        <div class="px-4 pb-3">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </span>
            <input
              v-model="conversationSearch"
              @input="onGlobalSearchInput"
              type="text"
              placeholder="Search conversations, contacts & messages..."
              class="w-full pl-10 pr-4 py-2 bg-[#F5F7FB] dark:bg-slate-800 text-xs sm:text-sm rounded-full border-none focus:ring-2 focus:ring-[#1877F2] text-slate-900 dark:text-slate-100 placeholder-slate-400 outline-none transition-all"
            />
          </div>
          <!-- Global Message Search Matches -->
          <div v-if="conversationSearch.trim().length >= 2 && globalSearchResults.length > 0" class="mt-2 max-h-44 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 divide-y divide-slate-100 dark:divide-slate-700">
            <p class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Messages matching "{{ conversationSearch.trim() }}"</p>
            <button
              v-for="hit in globalSearchResults"
              :key="'gs-' + hit.id"
              type="button"
              @click="openSearchHit(hit)"
              class="w-full px-3 py-2 text-left hover:bg-white dark:hover:bg-slate-800 flex items-start gap-2"
            >
              <span class="text-xs">💬</span>
              <span class="min-w-0 flex-1">
                <span class="block text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">{{ hit.sender?.name || 'Message' }}</span>
                <span class="block text-[11px] text-slate-500 dark:text-slate-300 truncate">{{ hit.body }}</span>
              </span>
            </button>
          </div>
        </div>

        <!-- Online Now / Quick-Start Contacts Strip -->
        <div v-if="quickContacts.length > 0" class="px-4 pb-3">
          <div class="flex items-center justify-between mb-2">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Active contacts</p>
            <button type="button" @click="activeFilter = 'contacts'" class="text-[11px] font-semibold text-[#1877F2] hover:underline">See all</button>
          </div>
          <div class="flex gap-3 overflow-x-auto no-scrollbar pb-1">
            <button
              v-for="friend in quickContacts"
              :key="'qc-' + friend.id"
              type="button"
              @click="startDirectChat(friend)"
              :title="'Chat with ' + friend.name"
              class="flex flex-col items-center gap-1 w-14 shrink-0 group cursor-pointer"
            >
              <span class="relative">
                <img :src="friend.avatar_url || getAvatarFallback({ title: friend.name })" class="w-12 h-12 rounded-full object-cover border-2 border-white dark:border-slate-800 shadow-sm group-hover:ring-2 group-hover:ring-[#1877F2] transition-all" :alt="friend.name" />
                <span v-if="friend.online" class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></span>
              </span>
              <span class="text-[10px] font-medium text-slate-600 dark:text-slate-300 truncate w-full text-center">{{ friend.name.split(' ')[0] }}</span>
            </button>
          </div>
        </div>

        <!-- Filter Tabs (All, Unread, Pinned, Groups, Requests, Archived, Starred, Calls, Contacts) -->
        <div class="px-4 pb-2.5 flex items-center gap-1.5 border-b border-[#F2F4F7] dark:border-slate-800/80 overflow-x-auto no-scrollbar" role="tablist">
          <button
            v-for="tab in filterTabs"
            :key="tab.key"
            @click="activeFilter = tab.key"
            type="button"
            role="tab"
            :aria-selected="activeFilter === tab.key"
            :class="[
              'px-3 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer shrink-0',
              activeFilter === tab.key
                ? 'bg-[#1877F2] text-white shadow-xs'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700'
            ]"
          >
            <span>{{ tab.label }}</span>
            <span
              v-if="tab.key === 'all' || (tab.key === 'unread' && totalUnreadCount > 0) || (tab.key === 'requests' && pendingRequestsCount > 0)"
              :class="[
                'text-[10px] px-1.5 py-0.2 rounded-full font-bold',
                activeFilter === tab.key ? 'bg-white/20 text-white' : (tab.key === 'requests' ? 'bg-rose-100 text-rose-600' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300')
              ]"
            >
              {{ tab.key === 'all' ? activeConversationsCount : (tab.key === 'unread' ? totalUnreadCount : pendingRequestsCount) }}
            </span>
          </button>
        </div>

        <!-- Starred / Calls / Contacts Views -->
        <div v-if="activeFilter === 'starred' || activeFilter === 'calls' || activeFilter === 'contacts'" class="flex-1 overflow-y-auto divide-y divide-slate-100/60 dark:divide-slate-800/40">
          <template v-if="activeFilter === 'starred'">
            <div
              v-for="item in savedMessages"
              :key="'sv-' + (item.id || item.message_id)"
              @click="selectConversation(conversationList.find((c) => c.id === (item.conversation_id || item.message?.conversation_id)) || activeConversation!)"
              class="p-3.5 flex items-start gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer"
            >
              <span class="text-amber-400 text-base">⭐</span>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">{{ item.sender?.name || item.message?.sender?.name || 'Message' }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">{{ item.body || item.message?.body || 'Attachment' }}</p>
              </div>
            </div>
            <p v-if="savedMessages.length === 0" class="p-8 text-center text-xs text-slate-400">No starred messages yet. Star a message to find it here.</p>
          </template>

          <template v-else-if="activeFilter === 'calls'">
            <div v-for="call in callHistory" :key="'call-' + call.id" class="p-3.5 flex items-center gap-3">
              <span :class="['w-10 h-10 rounded-full flex items-center justify-center text-base', call.outcome === 'missed' ? 'bg-rose-50 text-rose-500' : 'bg-emerald-50 text-emerald-600']">
                {{ (call.call_type || '').includes('video') ? '📹' : '📞' }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ call.conversation?.title || call.caller?.name || 'Call' }}</p>
                <p :class="['text-xs', call.outcome === 'missed' ? 'text-rose-500' : 'text-slate-400']">
                  {{ call.is_mine ? 'Outgoing' : 'Incoming' }} · {{ call.outcome || call.status }} · {{ formatCallTime(call) }}
                </p>
              </div>
              <button type="button" @click="openCallRoom(call)" class="p-2 text-[#1877F2] hover:bg-blue-50 dark:hover:bg-slate-800 rounded-full" title="Call again">📞</button>
            </div>
            <p v-if="callHistory.length === 0" class="p-8 text-center text-xs text-slate-400">No calls yet.</p>
          </template>

          <template v-else>
            <div
              v-for="friend in filteredContacts"
              :key="'ct-' + friend.id"
              @click="startDirectChat(friend)"
              class="p-3 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer"
            >
              <img :src="friend.avatar_url || getAvatarFallback({ title: friend.name })" class="w-10 h-10 rounded-full object-cover" :alt="friend.name" />
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ friend.name }}</p>
                <p class="text-xs text-slate-400 truncate">@{{ friend.username }}</p>
              </div>
              <span class="text-xs font-semibold text-[#1877F2]">Message</span>
            </div>
            <p v-if="filteredContacts.length === 0" class="p-8 text-center text-xs text-slate-400">No contacts found.</p>
          </template>
        </div>

        <!-- Scrollable Conversation List Items -->
        <div v-else class="flex-1 overflow-y-auto divide-y divide-slate-100/60 dark:divide-slate-800/40">
          <div
            v-for="conv in filteredConversations"
            :key="conv.id"
            @click="selectConversation(conv)"
            :class="[
              'p-3.5 flex items-center gap-3 cursor-pointer transition-all relative group',
              activeConversation?.id === conv.id
                ? 'bg-blue-50/70 dark:bg-blue-900/20'
                : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/50'
            ]"
          >
            <!-- Avatar with Online Status Indicator -->
            <div class="relative shrink-0">
              <img
                :src="conv.avatar_url || getAvatarFallback(conv)"
                class="w-11 h-11 rounded-full object-cover border border-slate-100 dark:border-slate-700 shadow-xs"
                :alt="conv.title"
              />
              <span
                v-if="isOnline(conv)"
                class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"
              ></span>
            </div>

            <!-- Details: Name & Snippet -->
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-1 mb-0.5">
                <div class="flex items-center gap-1.5 truncate">
                  <span v-if="conv.is_pinned" class="text-amber-500 text-xs shrink-0" title="Pinned Chat">📌</span>
                  <p
                    :class="[
                      'text-sm truncate font-semibold',
                      activeConversation?.id === conv.id ? 'text-[#1877F2]' : 'text-slate-900 dark:text-white'
                    ]"
                  >
                    {{ conv.title }}
                  </p>
                </div>
                <span class="text-[11px] text-slate-400 shrink-0 font-normal">
                  {{ formatTimestamp(conv.last_message?.sent_at || conv.last_message_at || conv.created_at) }}
                </span>
              </div>
              <div class="flex items-center justify-between gap-2">
                <p
                  :class="[
                    'text-xs truncate',
                    conv.unread_count > 0 ? 'text-slate-900 dark:text-slate-100 font-semibold' : 'text-slate-500 dark:text-slate-400'
                  ]"
                >
                  <span v-if="typingConversations[conv.id]" class="text-[#1877F2] font-medium flex items-center gap-1">
                    <span class="animate-pulse">Typing...</span>
                  </span>
                  <span v-else>
                    <span v-if="(conv as any).draft" class="text-rose-500 font-semibold">[Draft] </span>
                    <span v-else-if="(conv as any).is_request && (conv as any).request_status === 'pending'" class="text-amber-600 font-semibold">Message request · </span>
                    {{ (conv as any).draft || getLastMessageSnippet(conv) }}
                  </span>
                </p>
                <!-- Badges: Mute / Pin / Unread Counter -->
                <div class="flex items-center gap-1.5 shrink-0">
                  <span v-if="conv.is_muted" class="text-slate-400 text-xs" title="Muted">🔕</span>
                  <span
                    v-if="conv.unread_count > 0"
                    class="w-5 h-5 rounded-full bg-[#1877F2] text-white text-[11px] font-bold flex items-center justify-center shadow-xs"
                  >
                    {{ conv.unread_count > 9 ? '9+' : conv.unread_count }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Quick Action Options on Hover -->
            <div class="hidden group-hover:flex items-center gap-1 absolute right-2 top-2 bg-white/95 dark:bg-slate-800/95 backdrop-blur-xs p-1 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 z-10" @click.stop>
              <button
                @click="togglePinConversation(conv)"
                type="button"
                :title="conv.is_pinned ? 'Unpin Chat' : 'Pin Chat'"
                class="p-1 text-slate-500 hover:text-amber-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors text-xs"
              >
                {{ conv.is_pinned ? 'Unpin' : '📌' }}
              </button>
              <button
                @click="toggleArchiveConversation(conv)"
                type="button"
                :title="conv.is_archived ? 'Unarchive' : 'Archive'"
                class="p-1 text-slate-500 hover:text-[#1877F2] hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors text-xs"
              >
                {{ conv.is_archived ? 'Unarchive' : '📥' }}
              </button>
              <button
                @click="markConversationUnread(conv)"
                type="button"
                title="Mark as Unread"
                class="p-1 text-slate-500 hover:text-[#1877F2] hover:bg-slate-100 dark:hover:bg-slate-700 rounded transition-colors text-xs"
              >
                ●
              </button>
            </div>
          </div>

          <!-- Empty Conversations View -->
          <div v-if="filteredConversations.length === 0" class="p-8 text-center text-slate-400 flex flex-col items-center justify-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3 text-slate-400">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">No conversations found</p>
            <p class="text-xs text-slate-400 mt-1">Start chatting with your friends</p>
            <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
              <button
                @click="openNewMessageModal"
                type="button"
                class="px-4 py-2 bg-[#1877F2] text-white text-xs font-semibold rounded-xl hover:bg-[#1666D8] transition-all cursor-pointer shadow-xs"
              >
                Start Conversation
              </button>
              <button
                @click="openCreateGroup"
                type="button"
                class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl hover:bg-slate-200 transition-all cursor-pointer"
              >
                Create Group
              </button>
            </div>
            <div v-if="activeFilter === 'all' && quickContacts.length > 0" class="mt-6 w-full space-y-1 text-left">
              <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 text-center">Say hi to</p>
              <button
                v-for="friend in quickContacts.slice(0, 5)"
                :key="'es-' + friend.id"
                type="button"
                @click="startDirectChat(friend)"
                class="w-full p-2.5 flex items-center gap-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer"
              >
                <img :src="friend.avatar_url || getAvatarFallback({ title: friend.name })" class="w-9 h-9 rounded-full object-cover" :alt="friend.name" />
                <span class="flex-1 min-w-0">
                  <span class="block text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">{{ friend.name }}</span>
                  <span class="block text-[11px] text-slate-400 truncate">@{{ friend.username }}</span>
                </span>
                <span class="text-xs font-semibold text-[#1877F2]">👋 Wave</span>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- ========================================== -->
      <!-- COLUMN 2: ACTIVE CONVERSATION (CENTER)     -->
      <!-- ========================================== -->
      <main
        :class="[
          'flex-1 min-w-0 flex flex-col bg-white dark:bg-slate-900 border-r border-[#E4E7EC] dark:border-slate-800 transition-all duration-200',
          mobileView === 'conversations' ? 'hidden md:flex' : 'flex'
        ]"
      >
        <template v-if="activeConversation">
          <!-- Active Conversation Header -->
          <header class="h-16 px-4 sm:px-6 border-b border-[#E4E7EC] dark:border-slate-800 flex items-center justify-between shrink-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xs">
            <div class="flex items-center gap-3 min-w-0">
              <!-- Mobile Back Arrow Button -->
              <button
                @click="mobileView = 'conversations'"
                type="button"
                class="md:hidden p-1.5 -ml-1 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg cursor-pointer"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
              </button>

              <!-- Contact Avatar with Green Status Badge -->
              <div class="relative shrink-0 cursor-pointer" @click="toggleDetails">
                <img
                  :src="activeConversation.avatar_url || getAvatarFallback(activeConversation)"
                  class="w-10 h-10 rounded-full object-cover border border-slate-100 dark:border-slate-700"
                  :alt="activeConversation.title"
                />
                <span
                  v-if="isOnline(activeConversation)"
                  class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"
                ></span>
              </div>

              <!-- Name & Online Subtitle -->
              <div class="min-w-0 cursor-pointer" @click="toggleDetails">
                <div class="flex items-center gap-1.5">
                  <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white truncate">
                    {{ activeConversation.title }}
                  </h3>
                  <span v-if="activeConversation.is_pinned" class="text-amber-500 text-xs" title="Pinned Chat">📌</span>
                </div>
                <p class="text-xs font-medium flex items-center gap-1">
                  <span v-if="isTyping" class="text-[#1877F2] font-semibold animate-pulse">Typing...</span>
                  <span v-else :class="isOnline(activeConversation) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400'">
                    {{ presenceSubtitle }}
                  </span>
                </p>
              </div>
            </div>

            <!-- Header Action Controls: Audio Call, Video Call, Search, Details Toggle -->
            <div class="flex items-center gap-1 sm:gap-2">
              <!-- Audio Call Button -->
              <button
                @click="startCall('audio')"
                type="button"
                title="Start Audio Call"
                class="p-2 sm:p-2.5 text-slate-600 dark:text-slate-300 hover:text-[#1877F2] hover:bg-blue-50 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
              </button>

              <!-- Video Call Button -->
              <button
                @click="startCall('video')"
                type="button"
                title="Start Video Call"
                class="p-2 sm:p-2.5 text-slate-600 dark:text-slate-300 hover:text-[#1877F2] hover:bg-blue-50 dark:hover:bg-slate-800 rounded-xl transition-all cursor-pointer"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
              </button>

              <!-- Search in Conversation Button -->
              <button
                @click="toggleChatSearch"
                type="button"
                title="Search in Conversation"
                :class="[
                  'p-2 sm:p-2.5 rounded-xl transition-all cursor-pointer',
                  isSearchingInChat
                    ? 'text-[#1877F2] bg-blue-50 dark:bg-blue-900/30'
                    : 'text-slate-600 dark:text-slate-300 hover:text-[#1877F2] hover:bg-blue-50 dark:hover:bg-slate-800'
                ]"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </button>

              <!-- Multi-Select Toggle Button -->
              <button
                @click="toggleSelectionMode"
                type="button"
                title="Select Messages"
                aria-label="Select Messages"
                :class="[
                  'hidden sm:inline-flex p-2 sm:p-2.5 rounded-xl transition-all cursor-pointer',
                  selectionMode
                    ? 'text-[#1877F2] bg-blue-50 dark:bg-blue-900/30'
                    : 'text-slate-600 dark:text-slate-300 hover:text-[#1877F2] hover:bg-blue-50 dark:hover:bg-slate-800'
                ]"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </button>

              <!-- Details Panel Toggle Button -->
              <button
                @click="toggleDetails"
                type="button"
                title="Conversation Details"
                :class="[
                  'p-2 sm:p-2.5 rounded-xl transition-all cursor-pointer',
                  showDetailsPanel
                    ? 'text-[#1877F2] bg-blue-50 dark:bg-blue-900/30'
                    : 'text-slate-600 dark:text-slate-300 hover:text-[#1877F2] hover:bg-blue-50 dark:hover:bg-slate-800'
                ]"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
              </button>
            </div>
          </header>

          <!-- In-Conversation Live Search Bar (Expandable) -->
          <div v-if="isSearchingInChat" class="p-3 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2 animate-fade-in">
            <div class="relative flex-1">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </span>
              <input
                v-model="chatSearchQuery"
                type="text"
                placeholder="Find in conversation..."
                class="w-full pl-9 pr-8 py-1.5 bg-white dark:bg-slate-900 text-xs rounded-lg border border-slate-200 dark:border-slate-700 outline-none focus:ring-1 focus:ring-[#1877F2]"
              />
              <button
                v-if="chatSearchQuery"
                @click="chatSearchQuery = ''"
                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <span class="text-xs text-slate-500 whitespace-nowrap">
              {{ displayedMessages.length }} found
            </span>
            <button
              @click="isSearchingInChat = false; chatSearchQuery = ''"
              type="button"
              class="text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 px-2 py-1"
            >
              Done
            </button>
          </div>

          <!-- Message Request Banner -->
          <div
            v-if="(activeConversation as any).is_request && (activeConversation as any).request_status === 'pending'"
            class="px-4 py-3 bg-blue-50 dark:bg-blue-950/30 border-b border-blue-100 dark:border-blue-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
          >
            <p class="text-slate-700 dark:text-slate-200"><span class="font-bold">{{ activeConversation.title }}</span> wants to send you a message.</p>
            <div class="flex items-center gap-2">
              <button type="button" @click="handleMessageRequest('accept')" class="px-3 py-1.5 bg-[#1877F2] text-white font-semibold rounded-lg">Accept</button>
              <button type="button" @click="handleMessageRequest('delete')" class="px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-semibold rounded-lg">Decline</button>
              <button type="button" @click="handleMessageRequest('block')" class="px-3 py-1.5 text-rose-600 font-semibold rounded-lg hover:bg-rose-50">Block</button>
            </div>
          </div>

          <!-- Pinned Messages Banner (if any) -->
          <div
            v-if="currentPinned"
            class="px-4 py-2 bg-amber-50 dark:bg-amber-950/30 border-b border-amber-200/60 dark:border-amber-800/40 flex items-center justify-between text-xs animate-fade-in"
          >
            <button type="button" class="flex items-center gap-2 min-w-0 text-left" @click="jumpToMessage(currentPinned.id); cyclePinned()">
              <span class="text-amber-500 text-sm shrink-0">📌</span>
              <div class="min-w-0 truncate">
                <span class="font-bold text-amber-900 dark:text-amber-300">Pinned<span v-if="pinnedMessages.length > 1"> {{ (pinnedIndex % pinnedMessages.length) + 1 }} of {{ pinnedMessages.length }}</span>:</span>
                <span class="ml-1 text-amber-800 dark:text-amber-200 truncate">{{ currentPinned.body || 'Attachment' }}</span>
              </div>
            </button>
            <div class="flex items-center gap-2 shrink-0">
              <button
                @click="unpinMessage(currentPinned.id)"
                type="button"
                class="text-amber-700 dark:text-amber-400 hover:underline font-semibold text-[11px]"
              >
                Unpin
              </button>
            </div>
          </div>

          <!-- Multi-Select Bulk Action Bar -->
          <div v-if="selectionMode" class="px-4 py-2 bg-slate-900 text-white flex items-center justify-between text-xs">
            <span class="font-semibold">{{ selectedMessageIds.length }} selected</span>
            <div class="flex items-center gap-1">
              <button type="button" @click="forwardSelected" :disabled="!selectedMessageIds.length" class="px-2.5 py-1 rounded-lg hover:bg-white/10 disabled:opacity-40">Forward</button>
              <button type="button" @click="copySelected" :disabled="!selectedMessageIds.length" class="px-2.5 py-1 rounded-lg hover:bg-white/10 disabled:opacity-40">Copy</button>
              <button type="button" @click="bulkDeleteSelected" :disabled="!selectedMessageIds.length" class="px-2.5 py-1 rounded-lg text-rose-300 hover:bg-white/10 disabled:opacity-40">Delete</button>
              <button type="button" @click="toggleSelectionMode" class="px-2.5 py-1 rounded-lg hover:bg-white/10">Cancel</button>
            </div>
          </div>

          <!-- Message History Stream (Scrollable) -->
          <div
            ref="messagesContainerRef"
            class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 bg-white dark:bg-slate-900 relative"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDropFiles"
          >
            <!-- Drag & Drop Overlay -->
            <div v-if="isDragging" class="absolute inset-2 z-20 rounded-2xl border-2 border-dashed border-[#1877F2] bg-blue-50/80 dark:bg-slate-900/80 flex items-center justify-center text-sm font-semibold text-[#1877F2] pointer-events-none">
              Drop file to attach
            </div>

            <!-- Load Older Messages -->
            <div v-if="currentPage < lastPage" class="flex justify-center">
              <button type="button" @click="loadOlderMessages" :disabled="isLoadingOlder" class="px-3.5 py-1.5 text-xs font-semibold text-[#1877F2] bg-blue-50 dark:bg-slate-800 rounded-full hover:bg-blue-100 disabled:opacity-50">
                {{ isLoadingOlder ? 'Loading…' : 'Load older messages' }}
              </button>
            </div>

            <!-- Empty conversation hint -->
            <div v-if="displayedMessages.length === 0" class="flex flex-col items-center justify-center text-center py-12 text-slate-400">
              <img :src="activeConversation.avatar_url || getAvatarFallback(activeConversation)" class="w-16 h-16 rounded-full object-cover mb-3" :alt="activeConversation.title" />
              <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ activeConversation.title }}</p>
              <p class="text-xs mt-1">Say hello to start the conversation 👋</p>
            </div>

            <!-- Messages List -->
            <template v-for="(msg, msgIndex) in displayedMessages" :key="msg.id">
            <!-- Dynamic Date Separator -->
            <div v-if="dateLabelFor(msgIndex)" class="flex justify-center my-3">
              <span class="px-3.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-medium rounded-full shadow-2xs">
                {{ dateLabelFor(msgIndex) }}
              </span>
            </div>
            <div
              :id="'msg-' + msg.id"
              @click="selectionMode && toggleMessageSelected(msg.id)"
              :class="[
                'flex gap-2.5 max-w-[85%] sm:max-w-[70%] rounded-2xl transition-colors duration-500',
                msg.is_mine ? 'ml-auto justify-end' : 'mr-auto justify-start',
                highlightedMessageId === msg.id ? 'bg-amber-100/70 dark:bg-amber-900/30 ring-2 ring-amber-300' : '',
                selectionMode ? 'cursor-pointer' : ''
              ]"
            >
              <!-- Selection Checkbox -->
              <span v-if="selectionMode" :class="['mt-auto mb-2 w-5 h-5 rounded-full border-2 shrink-0 flex items-center justify-center text-[10px] text-white', selectedMessageIds.includes(msg.id) ? 'bg-[#1877F2] border-[#1877F2]' : 'border-slate-300']">
                <span v-if="selectedMessageIds.includes(msg.id)">✓</span>
              </span>
              <!-- Incoming Message Sender Avatar -->
              <img
                v-if="!msg.is_mine"
                :src="msg.sender?.avatar_url || getAvatarFallback({ title: msg.sender?.name || 'User' })"
                class="w-8 h-8 rounded-full object-cover shrink-0 mt-auto border border-slate-100 dark:border-slate-800"
                alt="Avatar"
              />

              <!-- Bubble & Metadata Container -->
              <div class="flex flex-col group relative" :class="msg.is_mine ? 'items-end' : 'items-start'">
                <!-- Group Sender Name -->
                <span v-if="!msg.is_mine && activeConversation.type === 'group'" class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 px-1 mb-0.5">{{ msg.sender?.name }}</span>

                <!-- Forwarded Badge -->
                <span v-if="msg.metadata?.forwarded || msg.metadata?.forwarded_from || (msg as any).is_forwarded" class="text-[10px] italic text-slate-400 px-1 mb-0.5">↪ Forwarded</span>

                <!-- Reply Context Quote if any (click to jump) -->
                <div
                  v-if="msg.reply_preview"
                  @click.stop="jumpToMessage(msg.reply_preview.id || (msg as any).reply_to_id)"
                  class="text-[11px] px-3 py-1 mb-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-l-2 border-[#1877F2] truncate max-w-xs cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700"
                >
                  <span class="font-bold">{{ msg.reply_preview.sender_name }}:</span> {{ msg.reply_preview.body }}
                </div>

                <!-- Shared Reel Card Preview -->
                <div
                  v-if="msg.type === 'reel' || msg.metadata?.reel_id || msg.metadata?.reel"
                  class="mb-2 w-64 rounded-2xl overflow-hidden bg-neutral-900 text-white shadow-xl border border-neutral-800 transition-all hover:scale-[1.02] group/reel cursor-pointer"
                  @click="openSharedReel(msg.metadata?.reel_id || msg.metadata?.reel?.id)"
                >
                  <div class="relative aspect-[9/14] w-full bg-black overflow-hidden flex items-center justify-center">
                    <img
                      v-if="msg.metadata?.reel?.thumbnail_url"
                      :src="msg.metadata?.reel?.thumbnail_url"
                      class="w-full h-full object-cover group-hover/reel:scale-105 transition-transform duration-300"
                      alt="Reel preview"
                    />
                    <div
                      v-else
                      class="w-full h-full flex flex-col items-center justify-center text-neutral-500 bg-neutral-950 p-4"
                    >
                      <svg class="w-10 h-10 text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
                      </svg>
                    </div>

                    <!-- Center Play Button Indicator -->
                    <div class="absolute inset-0 flex items-center justify-center bg-black/25 group-hover/reel:bg-black/10 transition-colors">
                      <div class="w-12 h-12 rounded-full bg-purple-600/90 text-white flex items-center justify-center shadow-2xl group-hover/reel:scale-110 transition-transform">
                        <svg class="w-6 h-6 fill-current ml-0.5" viewBox="0 0 24 24">
                          <path d="M8 5v14l11-7z" />
                        </svg>
                      </div>
                    </div>

                    <!-- Top Badge -->
                    <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-black/60 backdrop-blur-md text-[10px] font-bold text-white border border-white/10">
                      <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                      <span>Reel</span>
                    </div>

                    <!-- Bottom Details Overlay -->
                    <div class="absolute bottom-0 inset-x-0 p-3 bg-gradient-to-t from-black/95 via-black/65 to-transparent space-y-1">
                      <div class="flex items-center gap-1.5" v-if="msg.metadata?.reel?.creator">
                        <img
                          :src="msg.metadata.reel.creator.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
                          class="w-4 h-4 rounded-full object-cover ring-1 ring-white/50"
                        />
                        <span class="text-xs font-bold text-white truncate drop-shadow">
                          {{ msg.metadata.reel.creator.name }}
                        </span>
                      </div>
                      <p v-if="msg.metadata?.reel?.caption" class="text-xs text-white/95 line-clamp-2 leading-tight drop-shadow font-normal">
                        {{ msg.metadata.reel.caption }}
                      </p>
                    </div>
                  </div>

                  <!-- Card Action Footer -->
                  <div class="px-3 py-2 bg-neutral-800/90 hover:bg-neutral-800 flex items-center justify-between text-xs font-semibold text-purple-300">
                    <span>Watch Reel</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                  </div>
                </div>

                <!-- Attached Media Preview (Photos/Images) -->
                <div v-if="msg.media && msg.media.length > 0" class="mb-1.5 space-y-1">
                  <div
                    v-for="media in msg.media"
                    :key="media.id"
                    class="rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 max-w-xs cursor-pointer group/img relative"
                    @click="openLightbox(media.original_url || media.url)"
                  >
                    <!-- If Image -->
                    <img
                      v-if="isImageMedia(media)"
                      :src="media.thumbnail_url || media.original_url || media.url"
                      class="max-h-60 w-auto object-cover group-hover/img:scale-105 transition-transform duration-200"
                      alt="Attachment"
                    />
                    <!-- If Document / Non-Image File -->
                    <div
                      v-else
                      class="p-3 bg-slate-50 dark:bg-slate-800 flex items-center gap-3"
                      @click.stop="downloadAttachment(media)"
                    >
                      <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-[#1877F2] flex items-center justify-center font-bold text-sm">
                        📎
                      </div>
                      <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ media.file_name || 'Document' }}</p>
                        <p class="text-[10px] text-slate-400">{{ formatFileSize(media.file_size) }}</p>
                      </div>
                      <span class="text-xs text-[#1877F2] font-semibold hover:underline">Download</span>
                    </div>
                  </div>
                </div>

                <!-- Voice Note Audio Player -->
                <div
                  v-if="msg.type === 'voice' || msg.metadata?.audio_url"
                  class="p-3 rounded-2xl flex items-center gap-3 min-w-[220px]"
                  :class="msg.is_mine ? 'bg-[#1877F2] text-white' : 'bg-[#F0F2F5] dark:bg-slate-800 text-slate-900 dark:text-slate-100'"
                >
                  <button
                    @click="toggleAudioPlay(msg)"
                    type="button"
                    class="w-9 h-9 rounded-full bg-white/20 dark:bg-slate-700 flex items-center justify-center shrink-0 hover:scale-105 transition-transform"
                  >
                    <span v-if="playingAudioId === msg.id">⏸</span>
                    <span v-else>▶</span>
                  </button>
                  <div class="flex-1 min-w-0">
                    <div class="h-1.5 bg-white/30 dark:bg-slate-600 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-white dark:bg-[#1877F2] transition-all duration-100"
                        :style="{ width: (audioProgress[msg.id] || 0) + '%' }"
                      ></div>
                    </div>
                    <span class="text-[10px] mt-1 block opacity-80">
                      {{ formatAudioDuration(msg.metadata?.duration || 0) }}
                    </span>
                  </div>
                </div>

                <!-- Text Bubble or Inline Editor -->
                <div v-else-if="msg.body || msg.is_deleted">
                  <!-- Deleted Message Tombstone -->
                  <div
                    v-if="msg.is_deleted"
                    class="px-4 py-2.5 text-xs italic rounded-2xl bg-slate-100 dark:bg-slate-800/60 text-slate-400 border border-slate-200/50 dark:border-slate-700/50 flex items-center gap-1.5"
                  >
                    <svg class="w-3.5 h-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>This message was deleted</span>
                  </div>

                  <!-- Inline Message Editor Mode -->
                  <div
                    v-else-if="editingMessageId === msg.id"
                    class="p-2 bg-white dark:bg-slate-800 rounded-2xl border-2 border-[#1877F2] shadow-md w-72"
                  >
                    <textarea
                      v-model="editingMessageText"
                      rows="2"
                      class="w-full text-xs p-2 rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 outline-none resize-none border border-slate-200 dark:border-slate-700"
                      @keydown.enter.prevent="saveEditedMessage(msg.id)"
                    ></textarea>
                    <div class="flex items-center justify-end gap-1.5 mt-1.5">
                      <button
                        @click="cancelEditMessage"
                        type="button"
                        class="px-2.5 py-1 text-[11px] font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300"
                      >
                        Cancel
                      </button>
                      <button
                        @click="saveEditedMessage(msg.id)"
                        type="button"
                        class="px-3 py-1 bg-[#1877F2] text-white text-[11px] font-semibold rounded-lg hover:bg-[#1666D8]"
                      >
                        Save
                      </button>
                    </div>
                  </div>

                  <!-- Normal Message Bubble -->
                  <div
                    v-else
                    :class="[
                      'px-4 py-2.5 text-sm rounded-2xl relative shadow-2xs break-words',
                      msg.is_mine
                        ? 'bg-[#1877F2] text-white rounded-br-xs'
                        : 'bg-[#F0F2F5] dark:bg-slate-800 text-slate-900 dark:text-slate-100 rounded-bl-xs'
                    ]"
                  >
                    {{ msg.body }}

                    <!-- Reaction Badges Overlay on Bubble -->
                    <div
                      v-if="msg.reactions && msg.reactions.total > 0"
                      class="absolute -bottom-2.5 right-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-full px-1.5 py-0.5 text-[11px] shadow-sm flex items-center gap-1 cursor-pointer"
                      @click.stop="showReactors(msg)"
                    >
                      <span>{{ getTopReactionEmoji(msg) }}</span>
                      <span class="text-slate-700 dark:text-slate-300 font-bold text-[10px]">{{ msg.reactions.total }}</span>
                    </div>
                  </div>
                </div>

                <!-- Message Timestamp, Edited Tag & Read Receipt Checkmarks -->
                <div class="flex items-center gap-1.5 mt-1 px-1">
                  <span v-if="isMessagePinned(msg.id)" class="text-[10px]" title="Pinned">📌</span>
                  <span v-if="isMessageSaved(msg)" class="text-[10px]" title="Starred">⭐</span>
                  <span v-if="msg.is_edited" class="text-[10px] text-slate-400 italic">
                    (edited)
                  </span>
                  <span class="text-[10px] text-slate-400">
                    {{ formatMessageTime(msg.sent_at || msg.created_at) }}
                  </span>
                  <!-- Checkmark status for Sent/Delivered/Read Outgoing Messages -->
                  <span v-if="msg.is_mine" class="text-[10px] font-bold" :class="msg.delivery_status === 'read' ? 'text-[#1877F2]' : 'text-slate-400'">
                    <span v-if="msg.delivery_status === 'read'">✓✓</span>
                    <span v-else-if="msg.delivery_status === 'delivered'">✓✓</span>
                    <span v-else>✓</span>
                  </span>
                  <!-- Mobile: tap to open message actions -->
                  <button
                    v-if="!msg.is_deleted && !selectionMode"
                    type="button"
                    @click.stop="mobileMessageMenuId = mobileMessageMenuId === msg.id ? null : msg.id"
                    class="md:hidden ml-1 px-1.5 text-slate-400 hover:text-slate-700 text-xs leading-none"
                    aria-label="Message actions"
                  >⋯</button>
                </div>

                <!-- Quick Action Bar on Hover / Tap (React, Reply, Forward, Pin, Star, Copy, Edit, Select, Report, Delete) -->
                <div
                  v-if="!msg.is_deleted && !selectionMode"
                  :class="[
                    'items-center gap-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-full px-2 py-1 shadow-md absolute -top-4 z-10 flex-wrap max-w-[90vw]',
                    mobileMessageMenuId === msg.id ? 'flex' : 'hidden md:group-hover:flex',
                    msg.is_mine ? 'right-0' : 'left-0'
                  ]"
                >
                  <!-- Quick Reactions -->
                  <button @click="reactToMessage(msg.id, '❤️')" type="button" title="Love" class="hover:scale-125 transition-transform text-xs p-0.5">❤️</button>
                  <button @click="reactToMessage(msg.id, '👍')" type="button" title="Like" class="hover:scale-125 transition-transform text-xs p-0.5">👍</button>
                  <button @click="reactToMessage(msg.id, '😂')" type="button" title="Haha" class="hover:scale-125 transition-transform text-xs p-0.5">😂</button>
                  <button @click="reactToMessage(msg.id, '😮')" type="button" title="Wow" class="hover:scale-125 transition-transform text-xs p-0.5">😮</button>
                  <button @click="reactToMessage(msg.id, '😢')" type="button" title="Sad" class="hover:scale-125 transition-transform text-xs p-0.5">😢</button>
                  <button @click="reactToMessage(msg.id, '🔥')" type="button" title="Fire" class="hover:scale-125 transition-transform text-xs p-0.5">🔥</button>

                  <!-- Reply -->
                  <button
                    @click="setReply(msg)"
                    type="button"
                    title="Reply"
                    class="text-slate-500 hover:text-[#1877F2] p-1 ml-1 text-xs rounded hover:bg-slate-100 dark:hover:bg-slate-700"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                  </button>

                  <!-- Forward -->
                  <button
                    @click="openForwardModal(msg)"
                    type="button"
                    title="Forward Message"
                    class="text-slate-500 hover:text-[#1877F2] p-1 text-xs rounded hover:bg-slate-100 dark:hover:bg-slate-700"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                  </button>

                  <!-- Pin / Unpin -->
                  <button
                    @click="togglePinMessage(msg)"
                    type="button"
                    :title="isMessagePinned(msg.id) ? 'Unpin' : 'Pin'"
                    class="text-slate-500 hover:text-amber-500 p-1 text-xs rounded hover:bg-slate-100 dark:hover:bg-slate-700"
                  >
                    📌
                  </button>

                  <!-- Star / Save -->
                  <button @click="toggleSaveMessage(msg)" type="button" :title="isMessageSaved(msg) ? 'Unstar' : 'Star'" class="text-slate-500 hover:text-amber-500 p-1 text-xs rounded hover:bg-slate-100 dark:hover:bg-slate-700">
                    {{ isMessageSaved(msg) ? '★' : '☆' }}
                  </button>

                  <!-- Copy Text -->
                  <button v-if="msg.body" @click="copyMessageText(msg)" type="button" title="Copy Text" class="text-slate-500 hover:text-[#1877F2] p-1 text-xs rounded hover:bg-slate-100 dark:hover:bg-slate-700">⧉</button>

                  <!-- Copy Link -->
                  <button @click="copyMessageLink(msg)" type="button" title="Copy Link" class="text-slate-500 hover:text-[#1877F2] p-1 text-xs rounded hover:bg-slate-100 dark:hover:bg-slate-700">🔗</button>

                  <!-- Select -->
                  <button @click="selectionMode = true; selectedMessageIds = [msg.id]" type="button" title="Select" class="text-slate-500 hover:text-[#1877F2] p-1 text-xs rounded hover:bg-slate-100 dark:hover:bg-slate-700">☑</button>

                  <!-- Report (others' messages) -->
                  <button v-if="!msg.is_mine" @click="openReport('message', msg.id)" type="button" title="Report Message" class="text-slate-500 hover:text-rose-500 p-1 text-xs rounded hover:bg-slate-100 dark:hover:bg-slate-700">⚑</button>

                  <!-- Edit (if mine) -->
                  <button
                    v-if="msg.is_mine && msg.body"
                    @click="startEditMessage(msg)"
                    type="button"
                    title="Edit Message"
                    class="text-slate-500 hover:text-[#1877F2] p-1 text-xs rounded hover:bg-slate-100 dark:hover:bg-slate-700"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>

                  <!-- Delete -->
                  <button
                    @click="openDeleteModal(msg)"
                    type="button"
                    title="Delete Message"
                    class="text-slate-500 hover:text-rose-500 p-1 text-xs rounded hover:bg-slate-100 dark:hover:bg-slate-700"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            </template>

            <!-- Typing Indicator Dots when active -->
            <div v-if="isTyping" class="flex items-center gap-2 max-w-[70%] mr-auto animate-pulse">
              <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 shrink-0"></div>
              <div class="px-4 py-2.5 rounded-2xl rounded-bl-xs bg-[#F0F2F5] dark:bg-slate-800 flex items-center gap-1 text-slate-400">
                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce"></span>
                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                <span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
              </div>
            </div>
          </div>

          <!-- Reply / Attachment Preview Bar above Composer -->
          <div v-if="replyingTo || selectedAttachmentFile" class="px-4 py-2 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
            <div v-if="replyingTo" class="flex items-center gap-2 min-w-0">
              <span class="text-[#1877F2] font-bold">Replying to {{ replyingTo.sender?.name }}:</span>
              <span class="text-slate-500 truncate max-w-xs">{{ replyingTo.body }}</span>
            </div>
            <div v-else-if="selectedAttachmentFile" class="flex items-center gap-2 min-w-0">
              <span class="text-[#1877F2] font-bold">Attachment:</span>
              <span class="text-slate-600 dark:text-slate-300 truncate max-w-xs">{{ selectedAttachmentFile.name }} ({{ formatFileSize(selectedAttachmentFile.size) }})</span>
            </div>
            <button
              @click="clearComposerDrafts"
              type="button"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1"
            >
              ✕
            </button>
          </div>

          <!-- Voice Recording Bar (Active during recording) -->
          <div
            v-if="isRecordingVoice"
            class="p-3 sm:p-4 bg-rose-50 dark:bg-rose-950/30 border-t border-rose-200 dark:border-rose-900 shrink-0 flex items-center justify-between gap-3 animate-fade-in"
          >
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-rose-600 animate-ping"></span>
              <span class="text-xs font-bold text-rose-700 dark:text-rose-400">Recording Voice Note...</span>
              <span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">{{ formatRecordingDuration(recordingSeconds) }}</span>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="cancelVoiceRecording"
                type="button"
                class="px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-rose-600 transition-colors"
              >
                Cancel
              </button>
              <button
                @click="stopAndSendVoiceRecording"
                type="button"
                class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-full shadow-sm"
              >
                Send Voice
              </button>
            </div>
          </div>

          <!-- Message Composer (Input, Attachment, Emoji, Microphone, Send button) -->
          <footer
            v-else
            class="p-3 sm:p-4 bg-white dark:bg-slate-900 border-t border-[#E4E7EC] dark:border-slate-800 shrink-0 flex items-center gap-2 sm:gap-3"
          >
            <!-- Hidden File Input for Attachments -->
            <input
              ref="fileInputRef"
              type="file"
              class="hidden"
              accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.zip,.txt"
              @change="handleFileUpload"
            />

            <!-- Attachment Button (Paperclip) -->
            <button
              @click="triggerFileInput"
              type="button"
              title="Attach File or Photo"
              class="p-2 sm:p-2.5 text-slate-500 dark:text-slate-400 hover:text-[#1877F2] hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-all cursor-pointer"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
              </svg>
            </button>

            <!-- Message Text Input Field -->
            <div class="relative flex-1">
              <textarea
                v-model="composerMessage"
                @input="handleComposerTyping(); saveDraftDebounced()"
                @keydown="onComposerKeydown"
                rows="1"
                :placeholder="(activeConversation as any).is_request && (activeConversation as any).request_status === 'pending' ? 'Accept the request to reply…' : 'Type a message...'"
                :disabled="isSending"
                class="w-full pl-4 pr-10 py-2.5 bg-[#F5F7FB] dark:bg-slate-800 text-xs sm:text-sm rounded-3xl border-none focus:ring-2 focus:ring-[#1877F2] text-slate-900 dark:text-slate-100 placeholder-slate-400 outline-none transition-all disabled:opacity-60 resize-none max-h-32 leading-5 block"
                style="field-sizing: content;"
              ></textarea>

              <!-- Emoji Toggle Button inside input -->
              <button
                @click="showEmojiPicker = !showEmojiPicker"
                type="button"
                title="Insert Emoji"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-[#1877F2] cursor-pointer"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </button>

              <!-- Emoji / Sticker / GIF Popover Tray -->
              <div
                v-if="showEmojiPicker"
                class="absolute bottom-12 right-0 bg-white dark:bg-slate-800 border border-[#E4E7EC] dark:border-slate-700 rounded-2xl shadow-xl p-3 z-30 w-64 animate-fade-in"
              >
                <div class="flex items-center gap-1 mb-2 border-b border-slate-100 dark:border-slate-700 pb-1.5">
                  <button v-for="t in [{ k: 'emoji', l: '😊 Emoji' }, { k: 'stickers', l: '✨ Stickers' }, { k: 'gifs', l: 'GIF' }]" :key="t.k" type="button" @click="pickerTab = t.k as any" :class="['flex-1 py-1 rounded-lg text-[11px] font-bold', pickerTab === t.k ? 'bg-[#1877F2] text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700']">{{ t.l }}</button>
                </div>
                <div v-if="pickerTab === 'emoji'" class="grid grid-cols-6 gap-1.5">
                  <button
                    v-for="emoji in popularEmojis"
                    :key="emoji"
                    type="button"
                    @click="insertEmoji(emoji)"
                    class="text-xl p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-transform hover:scale-125"
                  >
                    {{ emoji }}
                  </button>
                </div>
                <div v-else-if="pickerTab === 'stickers'" class="grid grid-cols-4 gap-2">
                  <button
                    v-for="st in popularStickers"
                    :key="st"
                    type="button"
                    @click="sendQuickExpressive(st)"
                    class="text-3xl p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-transform hover:scale-110"
                  >{{ st }}</button>
                </div>
                <div v-else class="grid grid-cols-2 gap-1.5">
                  <button
                    v-for="gif in popularGifs"
                    :key="gif.label"
                    type="button"
                    @click="sendQuickExpressive(gif.text)"
                    class="p-2 rounded-xl bg-gradient-to-br from-blue-500/10 to-purple-500/10 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-[#1877F2]"
                  >{{ gif.label }}</button>
                </div>
              </div>
            </div>

            <!-- Voice Note Recording Trigger Button -->
            <button
              @click="startVoiceRecording"
              type="button"
              title="Record Voice Note"
              class="p-2 sm:p-2.5 text-slate-500 dark:text-slate-400 hover:text-[#1877F2] hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-all cursor-pointer"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
              </svg>
            </button>

            <!-- Send Button: Blue Circular Button with Paper Plane Icon -->
            <button
              @click="handleSendMessage"
              :disabled="(!composerMessage.trim() && !selectedAttachmentFile) || isSending"
              type="button"
              title="Send Message"
              class="w-10 h-10 rounded-full bg-[#1877F2] hover:bg-[#1666D8] disabled:opacity-40 text-white flex items-center justify-center shrink-0 shadow-md shadow-blue-500/20 active:scale-95 transition-all cursor-pointer"
            >
              <svg class="w-5 h-5 transform rotate-45 -translate-x-0.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
              </svg>
            </button>
          </footer>
        </template>

        <!-- No Active Conversation Empty State -->
        <div v-else class="flex-1 flex flex-col items-center justify-center p-8 text-center text-slate-400">
          <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-950/40 text-[#1877F2] flex items-center justify-center mb-4 shadow-sm">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Select a conversation</h3>
          <p class="text-xs text-slate-400 mt-1 max-w-xs">
            Choose from your existing conversations on the left or start a new message.
          </p>
          <button
            @click="openNewMessageModal"
            type="button"
            class="mt-4 px-5 py-2.5 bg-[#1877F2] text-white text-xs font-semibold rounded-xl hover:bg-[#1666D8] transition-all shadow-sm"
          >
            New Message
          </button>
        </div>
      </main>

      <!-- ========================================== -->
      <!-- COLUMN 3: CONVERSATION DETAILS (RIGHT)     -->
      <!-- ========================================== -->
      <aside
        v-if="showDetailsPanel && activeConversation"
        :class="[
          'w-full md:w-[300px] lg:w-[330px] shrink-0 border-l border-[#E4E7EC] dark:border-slate-800 flex flex-col bg-white dark:bg-slate-900 overflow-y-auto p-5 sm:p-6 transition-all duration-200 z-20',
          mobileView === 'details' ? 'fixed inset-0 md:static' : 'hidden lg:flex'
        ]"
      >
        <!-- Top Details Header with Close -->
        <div class="flex items-center justify-between pb-3">
          <button
            @click="showDetailsPanel = false; mobileView = 'chat'"
            type="button"
            class="lg:hidden p-1.5 text-slate-500 hover:bg-slate-100 rounded-lg"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
          <div class="ml-auto flex items-center gap-1">
            <button
              @click="togglePinConversation(activeConversation)"
              type="button"
              :title="activeConversation.is_pinned ? 'Unpin Conversation' : 'Pin Conversation'"
              class="p-1.5 text-slate-400 hover:text-amber-500 rounded-lg transition-colors"
            >
              📌
            </button>
          </div>
        </div>

        <!-- Contact Profile Card -->
        <div class="flex flex-col items-center text-center pb-5 border-b border-slate-100 dark:border-slate-800">
          <div class="relative">
            <img
              :src="activeConversation.avatar_url || getAvatarFallback(activeConversation)"
              class="w-20 h-20 rounded-full object-cover border-2 border-slate-100 dark:border-slate-700 shadow-sm"
              :alt="activeConversation.title"
            />
            <span
              v-if="isOnline(activeConversation)"
              class="absolute bottom-1 right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"
            ></span>
          </div>

          <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mt-3">
            {{ activeConversation.title }}
          </h4>
          <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">
            {{ isOnline(activeConversation) ? 'Online' : 'Offline' }}
          </p>

          <!-- Action Buttons: Add Friend & View Profile -->
          <div class="flex items-center gap-2 mt-4 w-full">
            <button
              @click="handleAddFriend"
              type="button"
              class="flex-1 py-2 px-3 bg-[#1877F2] hover:bg-[#1666D8] text-white text-xs font-semibold rounded-xl flex items-center justify-center gap-1.5 shadow-sm transition-all cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
              </svg>
              <span>{{ otherUserProfile?.relationship === 'accepted' ? 'Friends ✓' : 'Add Friend' }}</span>
            </button>

            <a
              :href="getProfileUrl()"
              class="flex-1 py-2 px-3 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl flex items-center justify-center gap-1.5 transition-all text-center"
            >
              View Profile
            </a>
          </div>
        </div>

        <!-- About Section -->
        <div class="py-4 border-b border-slate-100 dark:border-slate-800">
          <h5 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-3">
            About
          </h5>
          <div class="space-y-2.5 text-xs text-slate-600 dark:text-slate-300">
            <div class="flex items-center gap-2.5">
              <span class="text-slate-400 text-sm">💼</span>
              <span>{{ otherUserProfile?.occupation || 'Member of SocialHub' }}</span>
            </div>
            <div class="flex items-center gap-2.5">
              <span class="text-slate-400 text-sm">🏠</span>
              <span>{{ otherUserProfile?.location || 'Dhaka, Bangladesh' }}</span>
            </div>
            <div class="flex items-center gap-2.5">
              <span class="text-slate-400 text-sm">🕒</span>
              <span>{{ otherUserProfile?.joined || 'Joined SocialHub' }}</span>
            </div>
          </div>
        </div>

        <!-- Group Management -->
        <div v-if="activeConversation.type === 'group'" class="py-4 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center justify-between mb-3">
            <h5 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Members ({{ activeMembers.length }})</h5>
            <div v-if="isGroupAdmin" class="flex items-center gap-2">
              <button type="button" @click="editGroupInfo" class="text-xs text-slate-500 font-semibold hover:text-[#1877F2]">Edit</button>
              <button type="button" @click="addMembersSelection = []; showAddMembersModal = true" class="text-xs text-[#1877F2] font-semibold hover:underline">+ Add</button>
            </div>
          </div>
          <p v-if="(activeConversation as any).description" class="text-xs text-slate-500 mb-3">{{ (activeConversation as any).description }}</p>
          <div class="space-y-1 max-h-64 overflow-y-auto">
            <div v-for="member in activeMembers" :key="'mem-' + (member.user_id || member.id)" class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800">
              <img :src="member.avatar_url || getAvatarFallback({ title: member.name })" class="w-8 h-8 rounded-full object-cover" :alt="member.name" />
              <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">{{ member.name }}</p>
                <p class="text-[10px] text-slate-400 truncate">@{{ member.username }}</p>
              </div>
              <span v-if="member.role === 'admin'" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-50 text-[#1877F2]">Admin</span>
              <div v-if="isGroupAdmin && Number(member.user_id || member.id) !== Number(currentUserId)" class="flex items-center gap-1">
                <button type="button" @click="setMemberRole(member, member.role === 'admin' ? 'member' : 'admin')" class="text-[10px] font-semibold text-slate-500 hover:text-[#1877F2]">
                  {{ member.role === 'admin' ? 'Demote' : 'Make admin' }}
                </button>
                <button type="button" @click="removeMember(member)" class="text-[10px] font-semibold text-rose-500 hover:text-rose-600">Remove</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabbed Shared Content Explorer -->
        <div class="py-4 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-1 mb-3 overflow-x-auto no-scrollbar">
            <button
              v-for="tab in [{ k: 'media', l: 'Media' }, { k: 'files', l: 'Files' }, { k: 'links', l: 'Links' }, { k: 'voice', l: 'Voice' }, { k: 'pins', l: 'Pinned' }]"
              :key="tab.k"
              type="button"
              @click="detailsTab = tab.k as any"
              :class="['px-2.5 py-1 rounded-full text-[11px] font-semibold shrink-0', detailsTab === tab.k ? 'bg-[#1877F2] text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300']"
            >{{ tab.l }}</button>
          </div>

          <div v-if="detailsTab === 'media'">
            <div v-if="sharedMedia.length > 0" class="grid grid-cols-3 gap-2">
              <div
                v-for="(photo, idx) in displayedSharedMedia"
                :key="idx"
                @click="openLightbox(photo.url)"
                class="aspect-square rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 cursor-pointer group relative"
              >
                <img :src="photo.url" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200" alt="Shared Media" />
              </div>
            </div>
            <p v-else class="text-xs text-slate-400 text-center py-4">No shared photos or videos yet.</p>
          </div>

          <div v-else-if="detailsTab === 'files'" class="space-y-1.5">
            <button v-for="file in sharedFiles" :key="'f-' + file.id" type="button" @click="downloadAttachment(file)" class="w-full flex items-center gap-2.5 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-left">
              <span class="w-8 h-8 rounded-lg bg-blue-50 text-[#1877F2] flex items-center justify-center">📎</span>
              <span class="min-w-0 flex-1">
                <span class="block text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">{{ file.file_name || file.name || 'Document' }}</span>
                <span class="block text-[10px] text-slate-400">{{ formatFileSize(file.file_size || file.size) }}</span>
              </span>
            </button>
            <p v-if="sharedFiles.length === 0" class="text-xs text-slate-400 text-center py-4">No shared files yet.</p>
          </div>

          <div v-else-if="detailsTab === 'links'" class="space-y-1.5">
            <div v-for="link in sharedLinks" :key="'l-' + link.id" class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-200 break-all">
              {{ link.url || link.body }}
            </div>
            <p v-if="sharedLinks.length === 0" class="text-xs text-slate-400 text-center py-4">No shared links yet.</p>
          </div>

          <div v-else-if="detailsTab === 'voice'" class="space-y-1.5">
            <button v-for="vm in sharedVoice" :key="'v-' + vm.id" type="button" @click="jumpToMessage(vm.id)" class="w-full flex items-center gap-2 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 text-left text-xs">
              🎤 <span class="flex-1 text-slate-700 dark:text-slate-200">Voice message · {{ formatAudioDuration(vm.metadata?.duration || 0) }}</span>
              <span class="text-[10px] text-slate-400">{{ formatTimestamp(vm.sent_at || vm.created_at) }}</span>
            </button>
            <p v-if="sharedVoice.length === 0" class="text-xs text-slate-400 text-center py-4">No voice messages yet.</p>
          </div>

          <div v-else class="space-y-1.5">
            <button v-for="pin in pinnedMessages" :key="'p-' + pin.id" type="button" @click="jumpToMessage(pin.id)" class="w-full p-2 rounded-lg bg-amber-50 dark:bg-amber-950/30 text-left text-xs text-amber-900 dark:text-amber-200 truncate">
              📌 {{ pin.body || 'Attachment' }}
            </button>
            <p v-if="pinnedMessages.length === 0" class="text-xs text-slate-400 text-center py-4">No pinned messages.</p>
          </div>
        </div>

        <!-- More Options Section -->
        <div class="py-4 space-y-1">
          <h5 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-2">
            Options & Privacy
          </h5>

          <!-- Pin/Unpin Conversation -->
          <button
            @click="togglePinConversation(activeConversation)"
            type="button"
            class="w-full py-2 px-1 flex items-center gap-3 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-amber-500 transition-colors cursor-pointer"
          >
            <span class="text-sm">📌</span>
            <span>{{ activeConversation.is_pinned ? 'Unpin from Top' : 'Pin to Top' }}</span>
          </button>

          <!-- Archive / Unarchive -->
          <button
            @click="toggleArchiveConversation(activeConversation)"
            type="button"
            class="w-full py-2 px-1 flex items-center gap-3 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-[#1877F2] transition-colors cursor-pointer"
          >
            <span class="text-sm">📥</span>
            <span>{{ activeConversation.is_archived ? 'Unarchive Conversation' : 'Archive Conversation' }}</span>
          </button>

          <!-- Mute Notifications with Switch -->
          <div class="py-2 px-1 flex items-center justify-between text-xs font-medium text-slate-700 dark:text-slate-300">
            <div class="flex items-center gap-3">
              <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span>Mute Notifications</span>
            </div>
            <button
              @click="toggleMuteConversation"
              type="button"
              :class="[
                'w-10 h-5 flex items-center rounded-full p-0.5 transition-colors cursor-pointer',
                isMuted ? 'bg-[#1877F2]' : 'bg-slate-300 dark:bg-slate-700'
              ]"
            >
              <div
                :class="[
                  'bg-white w-4 h-4 rounded-full shadow-md transform transition-transform duration-200',
                  isMuted ? 'translate-x-5' : 'translate-x-0'
                ]"
              ></div>
            </button>
          </div>

          <!-- Mute Duration Options -->
          <div class="pl-8 pb-1">
            <button type="button" @click="showMuteOptions = !showMuteOptions" class="text-[11px] font-semibold text-[#1877F2] hover:underline">
              {{ showMuteOptions ? 'Hide mute durations' : 'Mute for…' }}
            </button>
            <div v-if="showMuteOptions" class="flex flex-wrap gap-1.5 mt-2">
              <button v-for="d in ['1h', '8h', '24h', 'forever']" :key="'mute-' + d" type="button" @click="muteConversation(d as any)" class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-blue-50">
                {{ d === 'forever' ? 'Until I unmute' : d }}
              </button>
              <button v-if="isMuted" type="button" @click="muteConversation('unmute')" class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-emerald-50 text-emerald-700">Unmute</button>
            </div>
          </div>

          <!-- Mark as Unread -->
          <button
            @click="markConversationUnread(activeConversation)"
            type="button"
            class="w-full py-2 px-1 flex items-center gap-3 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-[#1877F2] transition-colors cursor-pointer"
          >
            <span class="text-sm">●</span>
            <span>Mark as Unread</span>
          </button>

          <!-- Report Conversation -->
          <button
            @click="openReport('conversation', activeConversation.id)"
            type="button"
            class="w-full py-2 px-1 flex items-center gap-3 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-rose-600 transition-colors cursor-pointer"
          >
            <span class="text-sm">⚑</span>
            <span>Report Conversation</span>
          </button>

          <!-- Leave Group (if group conversation) -->
          <button
            v-if="activeConversation.type === 'group'"
            @click="confirmLeaveGroup"
            type="button"
            class="w-full py-2 px-1 flex items-center gap-3 text-xs font-medium text-amber-600 hover:text-amber-700 transition-colors cursor-pointer"
          >
            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span>Leave Group</span>
          </button>

          <!-- Clear Chat History -->
          <button
            @click="confirmClearHistory"
            type="button"
            class="w-full py-2 px-1 flex items-center gap-3 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-amber-600 transition-colors cursor-pointer"
          >
            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>Clear Chat History</span>
          </button>

          <!-- Delete Conversation -->
          <button
            @click="confirmDeleteConversation"
            type="button"
            class="w-full py-2 px-1 flex items-center gap-3 text-xs font-medium text-rose-500 hover:text-rose-600 transition-colors cursor-pointer"
          >
            <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>Delete Conversation</span>
          </button>

          <!-- Block User (Red Icon & Text) -->
          <button
            v-if="otherUserProfile?.id"
            @click="confirmBlockUser"
            type="button"
            class="w-full py-2 px-1 flex items-center gap-3 text-xs font-bold text-rose-600 hover:text-rose-700 transition-colors cursor-pointer"
          >
            <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
            <span>Block User</span>
          </button>
        </div>
      </aside>
    </div>

    <!-- ========================================== -->
    <!-- FORWARD MESSAGE MODAL                      -->
    <!-- ========================================== -->
    <div
      v-if="forwardingMessage"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fade-in"
      @click="forwardingMessage = null"
    >
      <div
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full overflow-hidden"
        @click.stop
      >
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="font-bold text-base text-slate-900 dark:text-white">Forward Message</h3>
          <button @click="forwardingMessage = null" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="p-4">
          <p class="text-xs text-slate-500 mb-3 bg-slate-50 dark:bg-slate-800 p-2.5 rounded-xl border border-slate-100 dark:border-slate-700 truncate">
            "{{ forwardingMessage.body || 'Attachment' }}"
          </p>

          <input v-model="forwardQuery" type="text" placeholder="Search chats…" class="w-full mb-2 px-3 py-2 bg-slate-50 dark:bg-slate-800 text-xs rounded-xl border border-slate-200 dark:border-slate-700 outline-none focus:ring-1 focus:ring-[#1877F2]" />
          <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Select one or more conversations:</p>
          <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
            <label
              v-for="conv in forwardCandidates"
              :key="conv.id"
              class="p-2.5 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl cursor-pointer"
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <img :src="conv.avatar_url || getAvatarFallback(conv)" class="w-8 h-8 rounded-full object-cover" />
                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ conv.title }}</span>
              </div>
              <input type="checkbox" :value="conv.id" v-model="forwardTargets" class="w-4 h-4 accent-[#1877F2]" />
            </label>
            <p v-if="forwardCandidates.length === 0" class="p-4 text-center text-xs text-slate-400">No conversations to forward to.</p>
          </div>
          <button
            type="button"
            @click="forwardToConversation()"
            :disabled="forwardTargets.length === 0"
            class="mt-3 w-full py-2.5 bg-[#1877F2] text-white text-xs font-semibold rounded-xl hover:bg-[#1666D8] disabled:opacity-40"
          >
            Send to {{ forwardTargets.length || '' }} chat{{ forwardTargets.length === 1 ? '' : 's' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- DELETE MESSAGE CHOICE MODAL                -->
    <!-- ========================================== -->
    <div
      v-if="deletingMessage"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-fade-in"
      @click="deletingMessage = null"
    >
      <div
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-sm w-full p-5 space-y-4"
        @click.stop
      >
        <h3 class="font-bold text-base text-slate-900 dark:text-white">Delete Message?</h3>
        <p class="text-xs text-slate-500">Who would you like to delete this message for?</p>

        <div class="space-y-2 pt-2">
          <!-- Delete for everyone (only for sender) -->
          <button
            v-if="deletingMessage.is_mine"
            @click="executeDeleteMessage(true)"
            type="button"
            class="w-full py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl text-center shadow-xs transition-colors"
          >
            Delete for Everyone
          </button>

          <!-- Delete for me -->
          <button
            @click="executeDeleteMessage(false)"
            type="button"
            class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl text-center transition-colors"
          >
            Delete for Me
          </button>

          <button
            @click="deletingMessage = null"
            type="button"
            class="w-full py-2 text-xs font-medium text-slate-400 hover:text-slate-600"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- NEW MESSAGE / CONTACT SELECTOR MODAL       -->
    <!-- ========================================== -->
    <div
      v-if="showNewMessageModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs animate-fade-in"
      @click="showNewMessageModal = false"
    >
      <div
        class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 max-w-md w-full overflow-hidden"
        @click.stop
      >
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <h3 class="font-bold text-base text-slate-900 dark:text-white">New Message</h3>
          <button @click="showNewMessageModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="p-4">
          <input
            v-model="friendFilterQuery"
            type="text"
            placeholder="Search friends and contacts..."
            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 text-xs rounded-xl border border-slate-200 dark:border-slate-700 outline-none focus:ring-1 focus:ring-[#1877F2]"
          />

          <div class="mt-3 max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
            <div
              v-for="friend in eligibleFriends"
              :key="friend.id"
              @click="startDirectChat(friend)"
              class="p-2.5 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl cursor-pointer"
            >
              <img
                :src="friend.avatar_url || getAvatarFallback({ title: friend.name })"
                class="w-9 h-9 rounded-full object-cover"
                alt="Avatar"
              />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ friend.name }}</p>
                <p class="text-xs text-slate-400 truncate">@{{ friend.username }}</p>
              </div>
            </div>

            <div v-if="eligibleFriends.length === 0" class="p-4 text-center text-xs text-slate-400">
              No contacts found
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- IMAGE LIGHTBOX PREVIEW MODAL               -->
    <!-- ========================================== -->
    <div
      v-if="lightboxImageUrl"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md animate-fade-in"
      @click="lightboxImageUrl = null"
    >
      <div class="relative max-w-4xl max-h-[90vh]">
        <img
          :src="lightboxImageUrl"
          class="max-w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl"
          alt="Enlarged view"
        />
        <button
          @click="lightboxImageUrl = null"
          class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-white text-slate-800 font-bold flex items-center justify-center shadow-lg hover:bg-slate-100"
        >
          ✕
        </button>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- CREATE GROUP MODAL                         -->
    <!-- ========================================== -->
    <div v-if="showCreateGroupModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @click="showCreateGroupModal = false">
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 space-y-3" @click.stop>
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-base text-slate-900 dark:text-white">Create Group Chat</h3>
          <button type="button" @click="showCreateGroupModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <input v-model="groupForm.title" type="text" placeholder="Group name" class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 outline-none focus:ring-1 focus:ring-[#1877F2]" />
        <input v-model="groupForm.description" type="text" placeholder="Group description (optional)" class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 outline-none focus:ring-1 focus:ring-[#1877F2]" />
        <input v-model="groupForm.query" type="text" placeholder="Search friends to add…" class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 outline-none focus:ring-1 focus:ring-[#1877F2]" />
        <div class="max-h-48 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
          <label v-for="friend in groupCandidates" :key="'gm-' + friend.id" class="p-2 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl cursor-pointer">
            <span class="flex items-center gap-2.5 min-w-0">
              <img :src="friend.avatar_url || getAvatarFallback({ title: friend.name })" class="w-8 h-8 rounded-full object-cover" />
              <span class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">{{ friend.name }}</span>
            </span>
            <input type="checkbox" :checked="groupForm.memberIds.includes(friend.id)" @change="toggleGroupMember(friend.id)" class="w-4 h-4 accent-[#1877F2]" />
          </label>
        </div>
        <button type="button" @click="submitCreateGroup" class="w-full py-2.5 bg-[#1877F2] text-white text-xs font-semibold rounded-xl hover:bg-[#1666D8]">Create Group ({{ groupForm.memberIds.length }})</button>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- ADD GROUP MEMBERS MODAL                    -->
    <!-- ========================================== -->
    <div v-if="showAddMembersModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @click="showAddMembersModal = false">
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-5 space-y-3" @click.stop>
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-base text-slate-900 dark:text-white">Add Members</h3>
          <button type="button" @click="showAddMembersModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <div class="max-h-56 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
          <label v-for="friend in friends" :key="'am-' + friend.id" class="p-2 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl cursor-pointer">
            <span class="flex items-center gap-2.5 min-w-0">
              <img :src="friend.avatar_url || getAvatarFallback({ title: friend.name })" class="w-8 h-8 rounded-full object-cover" />
              <span class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate">{{ friend.name }}</span>
            </span>
            <input type="checkbox" :value="friend.id" v-model="addMembersSelection" class="w-4 h-4 accent-[#1877F2]" />
          </label>
        </div>
        <button type="button" @click="submitAddMembers" :disabled="addMembersSelection.length === 0" class="w-full py-2.5 bg-[#1877F2] text-white text-xs font-semibold rounded-xl hover:bg-[#1666D8] disabled:opacity-40">Add Selected</button>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- REPORT MODAL                               -->
    <!-- ========================================== -->
    <div v-if="reportingTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @click="reportingTarget = null">
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-sm w-full p-5 space-y-3" @click.stop>
        <h3 class="font-bold text-base text-slate-900 dark:text-white">Report {{ reportingTarget.kind }}</h3>
        <select v-model="reportForm.reason" class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
          <option value="spam">Spam</option>
          <option value="harassment">Harassment</option>
          <option value="abuse">Abuse</option>
          <option value="fraud">Scam or fraud</option>
          <option value="other">Other</option>
        </select>
        <textarea v-model="reportForm.details" rows="3" placeholder="Additional details (optional)" class="w-full px-3 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700"></textarea>
        <div class="flex gap-2">
          <button type="button" @click="reportingTarget = null" class="flex-1 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-slate-800">Cancel</button>
          <button type="button" @click="submitReport" class="flex-1 py-2 text-xs font-semibold rounded-xl bg-rose-600 text-white">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- REACTORS MODAL                             -->
    <!-- ========================================== -->
    <div v-if="reactorsList" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @click="reactorsList = null">
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-xs w-full p-4 space-y-2" @click.stop>
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-bold text-slate-900 dark:text-white">Reactions</h4>
          <button type="button" @click="reactorsList = null" class="text-slate-400">✕</button>
        </div>
        <div class="max-h-56 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
          <div v-for="(r, idx) in reactorsList" :key="idx" class="py-2 flex items-center justify-between text-xs">
            <span class="font-medium text-slate-700 dark:text-slate-200">{{ r.user?.name || r.name || 'User' }}</span>
            <span class="text-base">{{ r.reaction || r.emoji }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- MESSENGER SETTINGS MODAL                   -->
    <!-- ========================================== -->
    <div v-if="showSettingsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @click="showSettingsModal = false">
      <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-sm w-full p-5 space-y-4" @click.stop>
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-base text-slate-900 dark:text-white">Messenger Preferences</h3>
          <button type="button" @click="showSettingsModal = false" class="text-slate-400">✕</button>
        </div>
        <label class="flex items-center justify-between text-xs font-medium text-slate-700 dark:text-slate-200">
          <span>Press Enter to send message</span>
          <input type="checkbox" v-model="sendOnEnter" class="w-4 h-4 accent-[#1877F2]" />
        </label>
        <label class="flex items-center justify-between text-xs font-medium text-slate-700 dark:text-slate-200">
          <span>Show when you're active</span>
          <input type="checkbox" v-model="activeStatusVisible" class="w-4 h-4 accent-[#1877F2]" />
        </label>
        <label class="flex items-center justify-between text-xs font-medium text-slate-700 dark:text-slate-200">
          <span>Notification & call sounds</span>
          <input type="checkbox" v-model="soundEnabled" class="w-4 h-4 accent-[#1877F2]" />
        </label>
        <button type="button" @click="savePreferences" class="w-full py-2.5 bg-[#1877F2] text-white text-xs font-semibold rounded-xl">Save Preferences</button>
      </div>
    </div>

    <!-- ========================================== -->
    <!-- OUTGOING / INCOMING CALL OVERLAY           -->
    <!-- ========================================== -->
    <div v-if="activeCall || incomingCall" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="bg-slate-900 text-white rounded-3xl p-6 max-w-xs w-full text-center space-y-4 shadow-2xl border border-white/10">
        <template v-if="incomingCall">
          <img :src="incomingCall.caller?.avatar_url || getAvatarFallback({ title: incomingCall.caller?.name || 'Caller' })" class="w-20 h-20 rounded-full mx-auto object-cover ring-4 ring-emerald-500/40 animate-pulse" />
          <div>
            <p class="text-base font-bold">{{ incomingCall.caller?.name || 'Incoming call' }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ (incomingCall.call_type || '').includes('video') ? 'Incoming video call…' : 'Incoming audio call…' }}</p>
          </div>
          <div class="flex items-center justify-center gap-4 pt-2">
            <button type="button" @click="respondIncomingCall('reject')" class="px-5 py-2.5 rounded-full bg-rose-600 hover:bg-rose-700 text-xs font-bold">Decline</button>
            <button type="button" @click="respondIncomingCall('accept')" class="px-5 py-2.5 rounded-full bg-emerald-500 hover:bg-emerald-600 text-xs font-bold">Accept</button>
          </div>
        </template>
        <template v-else-if="activeCall">
          <img :src="activeCall.conversation?.avatar_url || getAvatarFallback(activeCall.conversation || { title: 'Call' })" class="w-20 h-20 rounded-full mx-auto object-cover ring-4 ring-[#1877F2]/40 animate-pulse" />
          <div>
            <p class="text-base font-bold">{{ activeCall.conversation?.title }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ callStatus === 'calling' ? 'Connecting…' : 'Ringing…' }}</p>
          </div>
          <div class="flex items-center justify-center gap-3 pt-2">
            <button type="button" @click="openCallRoom(activeCall)" class="px-4 py-2 rounded-full bg-[#1877F2] text-xs font-bold">Open Call Room</button>
            <button type="button" @click="endOutgoingCall" class="px-4 py-2 rounded-full bg-rose-600 text-xs font-bold">Cancel</button>
          </div>
        </template>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="toastMessage" class="fixed bottom-20 md:bottom-6 right-6 z-50 px-4 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl">
      {{ toastMessage }}
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import type { User, SuggestedFriend, UpcomingEvent } from '../types';

interface MessageItem {
  id: number;
  conversation_id: number;
  sender_id?: number;
  sender?: {
    id: number;
    name: string;
    username: string;
    avatar_url?: string | null;
  };
  body: string;
  type?: string;
  metadata?: any;
  delivery_status?: string;
  sent_at?: string;
  created_at?: string;
  is_mine?: boolean;
  is_edited?: boolean;
  is_deleted?: boolean;
  is_pinned?: boolean;
  reply_preview?: any;
  media?: any[];
  reactions?: {
    total: number;
    counts?: Record<string, number>;
    my_reaction?: string | null;
  };
}

interface ConversationItem {
  id: number;
  type: string;
  title: string;
  avatar_url?: string | null;
  last_message_at?: string;
  unread_count: number;
  is_muted?: boolean;
  is_pinned?: boolean;
  is_archived?: boolean;
  other_user?: {
    id: number;
    name: string;
    username: string;
    avatar_url?: string | null;
    bio?: string | null;
  } | null;
  last_message?: {
    id: number;
    body: string;
    sent_at?: string;
    type?: string;
  } | null;
  created_at?: string;
}

const props = withDefaults(
  defineProps<{
    conversations?: ConversationItem[];
    initialActiveConversation?: ConversationItem | null;
    initialMessages?: MessageItem[];
    initialSharedMedia?: any[];
    initialSharedFiles?: any[];
    initialSharedLinks?: any[];
    initialPinnedMessages?: any[];
    initialSavedMessages?: any[];
    initialCallHistory?: any[];
    soundPreferences?: Record<string, any> | null;
    apiToken?: string | null;
    otherUserProfile?: any | null;
    friends?: User[];
    unreadCount?: number;
    unreadMessagesCount?: number;
    pendingRequests?: any[];
    contacts?: User[];
    suggestions?: SuggestedFriend[];
    upcomingEvents?: UpcomingEvent[];
  }>(),
  {
    conversations: () => [],
    initialActiveConversation: null,
    initialMessages: () => [],
    initialSharedMedia: () => [],
    initialSharedFiles: () => [],
    initialSharedLinks: () => [],
    initialPinnedMessages: () => [],
    initialSavedMessages: () => [],
    initialCallHistory: () => [],
    soundPreferences: null,
    apiToken: null,
    otherUserProfile: null,
    friends: () => [],
    unreadCount: 0,
    unreadMessagesCount: 0,
    pendingRequests: () => [],
    contacts: () => [],
    suggestions: () => [],
    upcomingEvents: () => [],
  }
);

const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id || 0);

// State
const conversationList = ref<ConversationItem[]>([...props.conversations]);
const activeConversation = ref<ConversationItem | null>(props.initialActiveConversation);
const messages = ref<MessageItem[]>([...props.initialMessages]);
const sharedMedia = ref<any[]>([...props.initialSharedMedia]);
const otherUserProfile = ref<any>(props.otherUserProfile);
const pinnedMessages = ref<MessageItem[]>([...(props.initialPinnedMessages as MessageItem[])]);
const sharedFiles = ref<any[]>([...props.initialSharedFiles]);
const sharedLinks = ref<any[]>([...props.initialSharedLinks]);
const sharedVoice = ref<any[]>([]);
const savedMessages = ref<any[]>([...props.initialSavedMessages]);
const callHistory = ref<any[]>([...props.initialCallHistory]);
const detailsTab = ref<'media' | 'files' | 'links' | 'voice' | 'pins'>('media');
const pinnedIndex = ref(0);
const currentPage = ref(1);
const lastPage = ref(1);
const isLoadingOlder = ref(false);
const highlightedMessageId = ref<number | null>(null);

// Multi-select
const selectionMode = ref(false);
const selectedMessageIds = ref<number[]>([]);

// Modals (Phase 8)
const showCreateGroupModal = ref(false);
const groupForm = ref({ title: '', description: '', memberIds: [] as number[], query: '' });
const showAddMembersModal = ref(false);
const addMembersSelection = ref<number[]>([]);
const showSettingsModal = ref(false);
const forwardTargets = ref<number[]>([]);
const forwardQuery = ref('');
const reportingTarget = ref<{ kind: 'message' | 'conversation'; id: number } | null>(null);
const reportForm = ref({ reason: 'spam', details: '' });
const reactorsList = ref<any[] | null>(null);
const mobileMessageMenuId = ref<number | null>(null);
const showMuteOptions = ref(false);
const toastMessage = ref('');
let toastTimer: any = null;

// Calling (Phase 8)
const activeCall = ref<any | null>(null);
const incomingCall = ref<any | null>(null);
const callStatus = ref<'idle' | 'calling' | 'ringing' | 'connected'>('idle');

// Preferences
const sendOnEnter = ref(localStorage.getItem('jj_send_on_enter') !== '0');
const activeStatusVisible = ref(localStorage.getItem('jj_active_status') !== '0');
const soundEnabled = ref(props.soundPreferences?.sounds_enabled ?? props.soundPreferences?.enabled ?? true);

/**
 * Resilient API fetch helper: CSRF + session cookies + Bearer token fallback.
 */
const apiFetch = (url: string, options: RequestInit & { json?: any } = {}): Promise<Response> => {
  const headers: Record<string, string> = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
    ...((options.headers as Record<string, string>) || {}),
  };
  const token = props.apiToken || localStorage.getItem('bondhoo_token');
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }
  let body = options.body;
  if (options.json !== undefined) {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(options.json);
  }
  return fetch(url, { ...options, headers, body, credentials: 'same-origin' });
};

const showToast = (msg: string) => {
  toastMessage.value = msg;
  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => (toastMessage.value = ''), 2600);
};

// Responsive views
const mobileView = ref<'conversations' | 'chat' | 'details'>(
  props.initialActiveConversation ? 'chat' : 'conversations'
);
const showDetailsPanel = ref(true);

// Filters & Search
type MessengerFilter = 'all' | 'unread' | 'pinned' | 'groups' | 'requests' | 'archived' | 'starred' | 'calls' | 'contacts';
const activeFilter = ref<MessengerFilter>('all');
const filterTabs: { key: MessengerFilter; label: string }[] = [
  { key: 'all', label: 'All' },
  { key: 'unread', label: 'Unread' },
  { key: 'pinned', label: 'Pinned' },
  { key: 'groups', label: 'Groups' },
  { key: 'requests', label: 'Requests' },
  { key: 'archived', label: 'Archived' },
  { key: 'starred', label: 'Starred' },
  { key: 'calls', label: 'Calls' },
  { key: 'contacts', label: 'Contacts' },
];
const conversationSearch = ref('');
const isSearchingInChat = ref(false);
const chatSearchQuery = ref('');

// Composer
const composerMessage = ref('');
const isSending = ref(false);
const isTyping = ref(false);
const typingConversations = ref<Record<number, boolean>>({});
const replyingTo = ref<MessageItem | null>(null);
const selectedAttachmentFile = ref<File | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const messagesContainerRef = ref<HTMLElement | null>(null);
const showEmojiPicker = ref(false);
const showNewMessageModal = ref(false);
const friendFilterQuery = ref('');
const lightboxImageUrl = ref<string | null>(null);
const showAllMediaModal = ref(false);

// Editing & Deleting & Forwarding modals
const editingMessageId = ref<number | null>(null);
const editingMessageText = ref('');
const deletingMessage = ref<MessageItem | null>(null);
const forwardingMessage = ref<MessageItem | null>(null);

// Voice Recording
const isRecordingVoice = ref(false);
const recordingSeconds = ref(0);
let mediaRecorder: MediaRecorder | null = null;
let audioChunks: Blob[] = [];
let recordingTimer: any = null;

// Voice audio playback
const playingAudioId = ref<number | null>(null);
const audioProgress = ref<Record<number, number>>({});
let activeAudioElement: HTMLAudioElement | null = null;

const popularEmojis = ['😊', '😂', '❤️', '👍', '🔥', '🎉', '👏', '😍', '😮', '😢', '🙏', '🚀', '💯', '🥳', '✨', '🙌', '🤝', '⭐'];
const pickerTab = ref<'emoji' | 'stickers' | 'gifs'>('emoji');
const popularStickers = ['🥳🎉', '❤️🔥', '🙌✨', '😂🤣', '👍💯', '🚀🌙', '🤝💼', '☕💻'];
const popularGifs = [
  { label: '🎉 Celebrate', text: '🎉🥳 Woohoo!' },
  { label: '👏 Applause', text: '👏👏 Bravo!' },
  { label: '🔥 High Five', text: '🙌🔥 High five!' },
  { label: '💯 Agreed', text: '💯 Spot on!' },
];
const globalSearchResults = ref<any[]>([]);
let globalSearchTimer: any = null;

const onGlobalSearchInput = () => {
  const q = conversationSearch.value.trim();
  if (globalSearchTimer) clearTimeout(globalSearchTimer);
  if (q.length < 2) {
    globalSearchResults.value = [];
    return;
  }
  globalSearchTimer = setTimeout(async () => {
    try {
      const r = await apiFetch(`/api/v1/messenger/search?q=${encodeURIComponent(q)}`);
      if (r.ok) {
        const d = await r.json();
        globalSearchResults.value = d.data?.messages || d.data || [];
      }
    } catch (e) {
      // ignore
    }
  }, 280);
};

const openSearchHit = async (hit: any) => {
  const convId = Number(hit.conversation_id);
  const existing = conversationList.value.find((c) => c.id === convId);
  if (existing) {
    await selectConversation(existing);
    setTimeout(() => jumpToMessage(hit.id), 250);
  }
};

const sendQuickExpressive = (content: string) => {
  showEmojiPicker.value = false;
  composerMessage.value = content;
  handleSendMessage();
};

// Default Photos fallback
const defaultSharedPhotos = [
  { url: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=300&q=80' },
  { url: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80' },
  { url: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=300&q=80' },
  { url: 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=300&q=80' },
  { url: 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?auto=format&fit=crop&w=300&q=80' },
  { url: 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=300&q=80' },
];

const displayedSharedMedia = computed(() => {
  if (sharedMedia.value.length > 0) {
    return sharedMedia.value.slice(0, 6).map((m: any) => ({
      url: m.thumbnail_url || m.original_url || m.url || m,
    }));
  }
  return defaultSharedPhotos;
});

const isMuted = computed(() => {
  return !!activeConversation.value?.is_muted;
});

const totalUnreadCount = computed(() => {
  return conversationList.value.filter((c) => (c.unread_count || 0) > 0).length;
});

const activeConversationsCount = computed(() => {
  return conversationList.value.filter((c) => !c.is_archived).length;
});

// Filtered conversations with Pinning priority
const filteredConversations = computed(() => {
  let list = [...conversationList.value];

  if (activeFilter.value === 'unread') {
    list = list.filter((c) => (c.unread_count || 0) > 0 && !(c as any).is_request);
  } else if (activeFilter.value === 'pinned') {
    list = list.filter((c) => !!c.is_pinned && !c.is_archived);
  } else if (activeFilter.value === 'groups') {
    list = list.filter((c) => c.type === 'group');
  } else if (activeFilter.value === 'requests') {
    list = list.filter((c) => !!(c as any).is_request && (c as any).request_status !== 'accepted');
  } else if (activeFilter.value === 'archived') {
    list = list.filter((c) => !!c.is_archived);
  } else {
    // 'all' excludes archived and pending message requests
    list = list.filter((c) => !c.is_archived && !((c as any).is_request && (c as any).request_status === 'pending'));
  }

  if (conversationSearch.value.trim()) {
    const q = conversationSearch.value.toLowerCase().trim();
    list = list.filter(
      (c) =>
        c.title.toLowerCase().includes(q) ||
        c.last_message?.body?.toLowerCase().includes(q) ||
        c.other_user?.username?.toLowerCase().includes(q)
    );
  }

  // Sort pinned conversations to the very top, then by last message timestamp
  list.sort((a, b) => {
    if (a.is_pinned && !b.is_pinned) return -1;
    if (!a.is_pinned && b.is_pinned) return 1;
    const timeA = new Date(a.last_message?.sent_at || a.last_message_at || a.created_at || 0).getTime();
    const timeB = new Date(b.last_message?.sent_at || b.last_message_at || b.created_at || 0).getTime();
    return timeB - timeA;
  });

  return list;
});

// In-chat search filtered messages
const displayedMessages = computed(() => {
  if (!chatSearchQuery.value.trim()) {
    return messages.value;
  }
  const q = chatSearchQuery.value.toLowerCase().trim();
  return messages.value.filter((m) => m.body && m.body.toLowerCase().includes(q));
});

// Eligible friends for New Message
const pendingRequestsCount = computed(() =>
  conversationList.value.filter((c: any) => c.is_request && c.request_status === 'pending').length
);

// Online / quick-start contacts strip (works even when no conversations exist)
const quickContacts = computed(() => {
  const online: Set<number> | undefined = (window as any).jugajugOnlineUsers;
  return [...props.friends]
    .map((f: any) => ({ ...f, online: online ? online.has(Number(f.id)) : !!f.is_online }))
    .sort((a: any, b: any) => Number(b.online) - Number(a.online))
    .slice(0, 20);
});

const filteredContacts = computed(() => {
  const q = conversationSearch.value.toLowerCase().trim();
  return props.friends.filter(
    (f) => !q || f.name.toLowerCase().includes(q) || (f.username || '').toLowerCase().includes(q)
  );
});

const groupCandidates = computed(() => {
  const q = groupForm.value.query.toLowerCase().trim();
  return props.friends.filter(
    (f) => !q || f.name.toLowerCase().includes(q) || (f.username || '').toLowerCase().includes(q)
  );
});

const forwardCandidates = computed(() => {
  const q = forwardQuery.value.toLowerCase().trim();
  return conversationList.value.filter((c) => !q || c.title.toLowerCase().includes(q));
});

const activeMembers = computed<any[]>(() => (activeConversation.value as any)?.members || []);
const isGroupAdmin = computed(() => {
  const conv: any = activeConversation.value;
  if (!conv || conv.type !== 'group') return false;
  if (Number(conv.creator_id) === Number(currentUserId.value)) return true;
  return activeMembers.value.some((m) => Number(m.user_id || m.id) === Number(currentUserId.value) && m.role === 'admin');
});

const currentPinned = computed<MessageItem | null>(() => {
  if (pinnedMessages.value.length === 0) return null;
  return pinnedMessages.value[pinnedIndex.value % pinnedMessages.value.length];
});

/**
 * Returns a human-friendly date label when a message starts a new day.
 */
const dateLabelFor = (index: number): string | null => {
  const list = displayedMessages.value;
  const cur = list[index];
  if (!cur) return null;
  const curDate = new Date(cur.sent_at || cur.created_at || Date.now());
  if (index > 0) {
    const prev = list[index - 1];
    const prevDate = new Date(prev.sent_at || prev.created_at || Date.now());
    if (prevDate.toDateString() === curDate.toDateString()) return null;
  }
  const today = new Date();
  const yesterday = new Date();
  yesterday.setDate(today.getDate() - 1);
  if (curDate.toDateString() === today.toDateString()) return 'Today';
  if (curDate.toDateString() === yesterday.toDateString()) return 'Yesterday';
  return curDate.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
};

const presenceSubtitle = computed(() => {
  const conv: any = activeConversation.value;
  if (!conv) return '';
  if (conv.type === 'group') return `${conv.participants_count || activeMembers.value.length || 0} members`;
  if (conv.type === 'saved') return 'Your personal space';
  if (isOnline(conv)) return 'Active now';
  const lastSeen = conv.other_user?.last_seen_at;
  return lastSeen ? `Last seen ${formatTimestamp(lastSeen)}` : 'Offline';
});

const eligibleFriends = computed(() => {
  if (!friendFilterQuery.value.trim()) {
    return props.friends;
  }
  const q = friendFilterQuery.value.toLowerCase().trim();
  return props.friends.filter(
    (f) => f.name.toLowerCase().includes(q) || f.username.toLowerCase().includes(q)
  );
});

// Helpers
const getCsrfToken = (): string => {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
};

/**
 * Cryptographically secure client message ID generator for offline outbox,
 * retry deduplication, and idempotency protection.
 * Follows RFC 4122 v4 UUID via Web Cryptography API with secure fallback.
 */
const generateSecureClientMessageId = (): string => {
  if (typeof window !== 'undefined' && window.crypto) {
    if (typeof window.crypto.randomUUID === 'function') {
      return `msg_${window.crypto.randomUUID()}`;
    }
    if (typeof window.crypto.getRandomValues === 'function') {
      const bytes = new Uint8Array(16);
      window.crypto.getRandomValues(bytes);
      bytes[6] = (bytes[6] & 0x0f) | 0x40;
      bytes[8] = (bytes[8] & 0x3f) | 0x80;
      const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
      return `msg_${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
    }
  }
  return `msg_${Date.now()}_fallback`;
};

const getAvatarFallback = (item: { title?: string; name?: string }): string => {
  const initial = (item.title || item.name || 'U').charAt(0).toUpperCase();
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(initial)}&background=1877F2&color=fff&size=128`;
};

const isOnline = (conv: ConversationItem): boolean => {
  if (conv.type !== 'direct') return false;
  if (conv.other_user?.id && (window as any).jugajugOnlineUsers) {
    return (window as any).jugajugOnlineUsers.has(Number(conv.other_user.id));
  }
  return !!(conv.other_user as any)?.is_online;
};

const formatTimestamp = (dateStr?: string): string => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const now = new Date();
  if (d.toDateString() === now.toDateString()) {
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }
  return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
};

const formatMessageTime = (dateStr?: string): string => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatFileSize = (bytes?: number): string => {
  if (!bytes) return 'File';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
};

const formatRecordingDuration = (sec: number): string => {
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
};

const formatAudioDuration = (sec: number): string => {
  if (!sec) return '00:00';
  const m = Math.floor(sec / 60);
  const s = Math.round(sec % 60);
  return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
};

const isImageMedia = (media: any): boolean => {
  const url = media.original_url || media.url || '';
  const mime = media.mime_type || '';
  return mime.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp|svg)/i.test(url);
};

const getTopReactionEmoji = (msg: MessageItem): string => {
  if (msg.reactions?.counts) {
    const keys = Object.keys(msg.reactions.counts);
    if (keys.length > 0) return keys[0];
  }
  return '❤️';
};

const openSharedReel = (reelId: number | string | undefined): void => {
  if (!reelId) return;
  router.visit(`/reels?reel=${reelId}`);
};

const getLastMessageSnippet = (conv: ConversationItem): string => {
  if (conv.last_message?.body) {
    return conv.last_message.body;
  }
  if (conv.last_message?.type === 'media') {
    return 'Shared a photo';
  }
  if (conv.last_message?.type === 'voice') {
    return '🎤 Voice message';
  }
  return 'No messages yet';
};

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainerRef.value) {
      messagesContainerRef.value.scrollTop = messagesContainerRef.value.scrollHeight;
    }
  });
};

const toggleDetails = () => {
  showDetailsPanel.value = !showDetailsPanel.value;
  if (window.innerWidth < 1024 && showDetailsPanel.value) {
    mobileView.value = 'details';
  }
};

const toggleChatSearch = () => {
  isSearchingInChat.value = !isSearchingInChat.value;
  if (!isSearchingInChat.value) {
    chatSearchQuery.value = '';
  }
};

const openLightbox = (url: string) => {
  lightboxImageUrl.value = url;
};

const setReply = (msg: MessageItem) => {
  replyingTo.value = msg;
};

const clearComposerDrafts = () => {
  replyingTo.value = null;
  selectedAttachmentFile.value = null;
};

const insertEmoji = (emoji: string) => {
  composerMessage.value += emoji;
  showEmojiPicker.value = false;
};

const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const handleFileUpload = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    selectedAttachmentFile.value = target.files[0];
  }
};

const downloadAttachment = (media: any) => {
  const downloadUrl = `/api/v1/messages/attachments/${media.id}/download`;
  window.open(downloadUrl, '_blank');
};

const getProfileUrl = (): string => {
  if (otherUserProfile.value?.username) {
    return `/profile/${otherUserProfile.value.username}`;
  }
  if (activeConversation.value?.other_user?.username) {
    return `/profile/${activeConversation.value.other_user.username}`;
  }
  return '#';
};

// Selection & Navigation
const selectConversation = async (conv: ConversationItem) => {
  activeConversation.value = conv;
  mobileView.value = 'chat';
  selectionMode.value = false;
  selectedMessageIds.value = [];
  isSearchingInChat.value = false;
  chatSearchQuery.value = '';
  composerMessage.value = localStorage.getItem(`jj_draft_${conv.id}`) || (conv as any).draft || '';
  conv.unread_count = 0;
  if (typeof window !== 'undefined') {
    window.dispatchEvent(
      new CustomEvent('jugajug:messages-unread-updated', {
        detail: { count: totalUnreadCount.value },
      })
    );
  }

  try {
    // Mark as read on backend
    apiFetch(`/api/v1/conversations/${conv.id}/read`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    }).catch(() => {});

    // Fetch conversation messages
    const res = await apiFetch(`/api/v1/conversations/${conv.id}/messages?per_page=50`, {
      headers: { 'Accept': 'application/json' },
    });
    if (res.ok) {
      const data = await res.json();
      const loadedMsgs = data.data || [];
      messages.value = Array.isArray(loadedMsgs) ? [...loadedMsgs].reverse() : [];
      currentPage.value = data.meta?.current_page || 1;
      lastPage.value = data.meta?.last_page || 1;
      scrollToBottom();
    }

    // Fetch pinned messages
    fetchPinnedMessages(conv.id);
    pinnedIndex.value = 0;

    // Fetch conversation media
    const mediaRes = await apiFetch(`/api/v1/conversations/${conv.id}/media`, {
      headers: { 'Accept': 'application/json' },
    });
    if (mediaRes.ok) {
      const mediaData = await mediaRes.json();
      sharedMedia.value = mediaData.data || [];
    }
    loadSharedContent(conv.id);

    // Update other user profile
    if (conv.other_user) {
      otherUserProfile.value = {
        ...conv.other_user,
        occupation: 'Member of SocialHub',
        location: 'Dhaka, Bangladesh',
        joined: 'Joined SocialHub',
        is_online: true,
      };
    }
  } catch (err) {
    console.error('Failed to load conversation details:', err);
  }
};

// Shared files / links / voice for the details explorer
const loadSharedContent = async (convId: number) => {
  const load = async (type: string): Promise<any[]> => {
    try {
      const r = await apiFetch(`/api/v1/conversations/${convId}/media?type=${type}`);
      if (r.ok) {
        const d = await r.json();
        return d.data || [];
      }
    } catch (e) {
      // ignore
    }
    return [];
  };
  const [files, links, voice] = await Promise.all([load('files'), load('links'), load('voice')]);
  sharedFiles.value = files;
  sharedLinks.value = links;
  sharedVoice.value = voice;
};

// Pinned messages management
const fetchPinnedMessages = async (convId: number) => {
  try {
    const res = await apiFetch(`/api/v1/conversations/${convId}/pins`, {
      headers: { 'Accept': 'application/json' },
    });
    if (res.ok) {
      const data = await res.json();
      pinnedMessages.value = data.data || [];
    }
  } catch (err) {
    // Silent
  }
};

const isMessagePinned = (msgId: number): boolean => {
  return pinnedMessages.value.some((p) => p.id === msgId);
};

const togglePinMessage = async (msg: MessageItem) => {
  if (!activeConversation.value) return;
  const convId = activeConversation.value.id;
  const isPinned = isMessagePinned(msg.id);

  try {
    if (isPinned) {
      await apiFetch(`/api/v1/conversations/${convId}/messages/${msg.id}/pin`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
      });
      pinnedMessages.value = pinnedMessages.value.filter((p) => p.id !== msg.id);
    } else {
      await apiFetch(`/api/v1/conversations/${convId}/messages/${msg.id}/pin`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
      });
      pinnedMessages.value.unshift(msg);
    }
  } catch (err) {
    console.error('Pin toggle failed:', err);
  }
};

const unpinMessage = async (msgId: number) => {
  if (!activeConversation.value) return;
  await togglePinMessage({ id: msgId } as MessageItem);
};

// Message Sending
const handleSendMessage = async () => {
  const text = composerMessage.value.trim();
  const file = selectedAttachmentFile.value;

  if ((!text && !file) || !activeConversation.value || isSending.value) {
    return;
  }

  isSending.value = true;
  const convId = activeConversation.value.id;

  try {
    let mediaIds: number[] = [];

    // If an attachment file is selected, upload it first
    if (file) {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('collection', 'message');

      const uploadRes = await apiFetch('/api/v1/messages/attachments', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: formData,
      });

      if (uploadRes.ok) {
        const uploadJson = await uploadRes.json();
        if (uploadJson.data?.id) {
          mediaIds.push(uploadJson.data.id);
        }
      }
    }

    // Send the message
    const payload: Record<string, any> = {
      body: text || (mediaIds.length > 0 ? 'Sent an attachment' : ''),
      type: mediaIds.length > 0 ? 'media' : 'text',
      media_ids: mediaIds,
      client_message_id: generateSecureClientMessageId(),
    };

    if (replyingTo.value) {
      payload.reply_to_id = replyingTo.value.id;
    }

    const res = await apiFetch(`/api/v1/conversations/${convId}/messages`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify(payload),
    });

    if (res.ok) {
      const data = await res.json();
      const newMsg = data.data;
      if (newMsg) {
        newMsg.is_mine = true;
        // Check for duplicates
        if (!messages.value.some((m) => m.id === newMsg.id)) {
          messages.value.push(newMsg);
        }

        // Update conversation preview in sidebar
        const convIndex = conversationList.value.findIndex((c) => c.id === convId);
        if (convIndex !== -1) {
          conversationList.value[convIndex].last_message = {
            id: newMsg.id,
            body: newMsg.body,
            sent_at: newMsg.sent_at || newMsg.created_at,
          };
          const [moved] = conversationList.value.splice(convIndex, 1);
          conversationList.value.unshift(moved);
        }
      }

      composerMessage.value = '';
      localStorage.removeItem(`jj_draft_${convId}`);
      clearComposerDrafts();
      scrollToBottom();
    } else {
      const err = await res.json().catch(() => ({}));
      showToast(err.message || 'Message failed to send. Tap send to retry.');
    }
  } catch (err) {
    console.error('Failed to send message:', err);
  } finally {
    isSending.value = false;
  }
};

// Typing emission
let typingDebounceTimer: any = null;
const handleComposerTyping = () => {
  if (!activeConversation.value || typingDebounceTimer) return;
  const convId = activeConversation.value.id;

  typingDebounceTimer = setTimeout(() => {
    typingDebounceTimer = null;
  }, 2000);

  apiFetch('/api/v1/presence/typing', {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    body: JSON.stringify({
      conversation_id: convId,
      is_typing: true,
    }),
  }).catch(() => {});
};

// Message Editing
const startEditMessage = (msg: MessageItem) => {
  editingMessageId.value = msg.id;
  editingMessageText.value = msg.body;
};

const cancelEditMessage = () => {
  editingMessageId.value = null;
  editingMessageText.value = '';
};

const saveEditedMessage = async (msgId: number) => {
  const newBody = editingMessageText.value.trim();
  if (!newBody) return;

  try {
    const res = await apiFetch(`/api/v1/messages/${msgId}`, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ body: newBody }),
    });

    if (res.ok) {
      const data = await res.json();
      const updated = data.data;
      const target = messages.value.find((m) => m.id === msgId);
      if (target) {
        target.body = updated?.body || newBody;
        target.is_edited = true;
      }
      cancelEditMessage();
    }
  } catch (err) {
    console.error('Failed to edit message:', err);
  }
};

// Message Deleting
const openDeleteModal = (msg: MessageItem) => {
  deletingMessage.value = msg;
};

const executeDeleteMessage = async (forEveryone: boolean) => {
  if (!deletingMessage.value) return;
  const msgId = deletingMessage.value.id;

  try {
    const endpoint = forEveryone
      ? `/api/v1/messages/${msgId}/delete-for-everyone`
      : `/api/v1/messages/${msgId}/delete-for-me`;

    const res = await apiFetch(endpoint, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });

    if (res.ok) {
      if (forEveryone) {
        const target = messages.value.find((m) => m.id === msgId);
        if (target) {
          target.is_deleted = true;
          target.body = '';
          target.media = [];
        }
      } else {
        messages.value = messages.value.filter((m) => m.id !== msgId);
      }
    }
  } catch (err) {
    console.error('Failed to delete message:', err);
  } finally {
    deletingMessage.value = null;
  }
};

// Message Forwarding
const openForwardModal = (msg: MessageItem) => {
  forwardTargets.value = [];
  forwardQuery.value = '';
  forwardingMessage.value = msg;
};

const forwardToConversation = async (targetConvId?: number) => {
  if (!forwardingMessage.value) return;
  const msgId = forwardingMessage.value.id;
  const targets = targetConvId ? [targetConvId] : [...forwardTargets.value];
  if (targets.length === 0) return;

  try {
    const res = await apiFetch(`/api/v1/messages/${msgId}/forward`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({
        target_conversation_ids: targets,
      }),
    });

    if (res.ok) {
      forwardingMessage.value = null;
      forwardTargets.value = [];
      selectionMode.value = false;
      showToast(`Forwarded to ${targets.length} chat${targets.length > 1 ? 's' : ''}`);
    }
  } catch (err) {
    console.error('Failed to forward message:', err);
  }
};

// Reactions
const reactToMessage = async (messageId: number, reaction: string) => {
  try {
    const res = await apiFetch(`/api/v1/messages/${messageId}/reactions`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ reaction }),
    });

    if (res.ok) {
      const data = await res.json();
      const targetMsg = messages.value.find((m) => m.id === messageId);
      if (targetMsg) {
        if (!targetMsg.reactions) {
          targetMsg.reactions = { total: 0, counts: {} };
        }
        targetMsg.reactions.total = (targetMsg.reactions.total || 0) + 1;
        if (!targetMsg.reactions.counts) targetMsg.reactions.counts = {};
        targetMsg.reactions.counts[reaction] = (targetMsg.reactions.counts[reaction] || 0) + 1;
      }
    }
  } catch (err) {
    console.error('Reaction failed:', err);
  }
};

// Voice Recording Handlers
const startVoiceRecording = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    mediaRecorder = new MediaRecorder(stream);
    audioChunks = [];

    mediaRecorder.ondataavailable = (event) => {
      if (event.data.size > 0) {
        audioChunks.push(event.data);
      }
    };

    mediaRecorder.start();
    isRecordingVoice.value = true;
    recordingSeconds.value = 0;

    recordingTimer = setInterval(() => {
      recordingSeconds.value++;
    }, 1000);
  } catch (err) {
    alert('Microphone access is required to record voice messages.');
  }
};

const cancelVoiceRecording = () => {
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop();
  }
  if (recordingTimer) clearInterval(recordingTimer);
  isRecordingVoice.value = false;
  audioChunks = [];
};

const stopAndSendVoiceRecording = async () => {
  if (!mediaRecorder || !activeConversation.value) return;
  const duration = Math.max(1, recordingSeconds.value);
  const convId = activeConversation.value.id;

  mediaRecorder.onstop = async () => {
    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
    const formData = new FormData();
    formData.append('audio', audioBlob, 'voicenote.webm');
    formData.append('duration', duration.toString());

    try {
      const res = await apiFetch(`/api/v1/conversations/${convId}/voice`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: formData,
      });

      if (res.ok) {
        const data = await res.json();
        if (data.data) {
          data.data.is_mine = true;
          messages.value.push(data.data);
          scrollToBottom();
        }
      }
    } catch (err) {
      console.error('Failed to send voice message:', err);
    }
  };

  mediaRecorder.stop();
  if (recordingTimer) clearInterval(recordingTimer);
  isRecordingVoice.value = false;
};

// Voice Audio Playback
const toggleAudioPlay = (msg: MessageItem) => {
  const audioUrl = msg.metadata?.audio_url || (msg.media && msg.media[0]?.original_url);
  if (!audioUrl) return;

  if (playingAudioId.value === msg.id && activeAudioElement) {
    activeAudioElement.pause();
    playingAudioId.value = null;
    return;
  }

  if (activeAudioElement) {
    activeAudioElement.pause();
  }

  activeAudioElement = new Audio(audioUrl);
  playingAudioId.value = msg.id;

  activeAudioElement.ontimeupdate = () => {
    if (activeAudioElement && activeAudioElement.duration) {
      audioProgress.value[msg.id] = (activeAudioElement.currentTime / activeAudioElement.duration) * 100;
    }
  };

  activeAudioElement.onended = () => {
    playingAudioId.value = null;
    audioProgress.value[msg.id] = 0;
  };

  activeAudioElement.play().catch(() => {
    playingAudioId.value = null;
  });
};

// Calling Integration (Audio / Video)
// Creates the call record first (POST /api/v1/calls) so the recipient receives
// the `call.incoming` broadcast and rings, then opens the WebRTC call room.
const startCall = async (type: 'audio' | 'video') => {
  if (!activeConversation.value) return;
  const conv = activeConversation.value;
  const callType = conv.type === 'group' ? (`group_${type}` as const) : type;
  callStatus.value = 'calling';
  activeCall.value = { type, conversation: conv, id: null };
  try {
    const res = await apiFetch('/api/v1/calls', {
      method: 'POST',
      json: { conversation_id: conv.id, call_type: callType },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      callStatus.value = 'idle';
      activeCall.value = null;
      showToast(data.message || 'Unable to start the call.');
      return;
    }
    activeCall.value = { ...data.data, type, conversation: conv };
    callStatus.value = 'ringing';
    callHistory.value.unshift(data.data);
  } catch (err) {
    callStatus.value = 'idle';
    activeCall.value = null;
    showToast('Network error while starting the call.');
  }
};

const openCallRoom = (call: any) => {
  const convId = call?.conversation_id || call?.conversation?.id || activeConversation.value?.id;
  if (!convId) return;
  const type = (call?.call_type || call?.type || 'audio').replace('group_', '');
  const params = new URLSearchParams({ type });
  if (call?.id) params.set('call_id', String(call.id));
  window.open(`/call/${convId}?${params.toString()}`, '_blank', 'width=1024,height=720,menubar=no,toolbar=no');
};

const endOutgoingCall = async () => {
  const call = activeCall.value;
  activeCall.value = null;
  callStatus.value = 'idle';
  if (call?.id) {
    apiFetch(`/api/v1/calls/${call.id}/leave`, { method: 'POST' }).catch(() => {});
  }
};

const respondIncomingCall = async (action: 'accept' | 'reject') => {
  const call = incomingCall.value;
  incomingCall.value = null;
  if (!call?.id) return;
  try {
    await apiFetch(`/api/v1/calls/${call.id}/respond`, { method: 'POST', json: { action } });
  } catch (e) {
    // ignore
  }
  if (action === 'accept') {
    openCallRoom(call);
  }
};

const onIncomingCall = (e: any) => {
  const d = e.detail || {};
  const call = d.call || d.payload?.call || d.payload || d;
  if (!call || Number(call.caller_id || call.caller?.id) === Number(currentUserId.value)) return;
  incomingCall.value = call;
};

// Multi-select
const toggleSelectionMode = () => {
  selectionMode.value = !selectionMode.value;
  selectedMessageIds.value = [];
};

const toggleMessageSelected = (id: number) => {
  const idx = selectedMessageIds.value.indexOf(id);
  if (idx === -1) selectedMessageIds.value.push(id);
  else selectedMessageIds.value.splice(idx, 1);
};

const bulkDeleteSelected = async () => {
  if (selectedMessageIds.value.length === 0) return;
  if (!confirm(`Delete ${selectedMessageIds.value.length} message(s) for you?`)) return;
  const ids = [...selectedMessageIds.value];
  const res = await apiFetch('/api/v1/messages/bulk-delete', { method: 'POST', json: { message_ids: ids } });
  if (res.ok) {
    messages.value = messages.value.filter((m) => !ids.includes(m.id));
    showToast('Messages deleted');
  }
  toggleSelectionMode();
};

const copySelected = async () => {
  const text = messages.value
    .filter((m) => selectedMessageIds.value.includes(m.id))
    .map((m) => m.body)
    .filter(Boolean)
    .join('\n');
  await navigator.clipboard?.writeText(text).catch(() => {});
  showToast('Copied to clipboard');
  toggleSelectionMode();
};

const forwardSelected = () => {
  const first = messages.value.find((m) => selectedMessageIds.value.includes(m.id));
  if (first) openForwardModal(first);
};

// Load older messages (pagination)
const loadOlderMessages = async () => {
  if (!activeConversation.value || isLoadingOlder.value || currentPage.value >= lastPage.value) return;
  isLoadingOlder.value = true;
  const container = messagesContainerRef.value;
  const prevHeight = container?.scrollHeight || 0;
  try {
    const res = await apiFetch(`/api/v1/conversations/${activeConversation.value.id}/messages?per_page=50&page=${currentPage.value + 1}`);
    if (res.ok) {
      const data = await res.json();
      const older = (data.data || []).slice().reverse();
      const known = new Set(messages.value.map((m) => m.id));
      messages.value = [...older.filter((m: any) => !known.has(m.id)), ...messages.value];
      currentPage.value = data.meta?.current_page || currentPage.value + 1;
      lastPage.value = data.meta?.last_page || lastPage.value;
      nextTick(() => {
        if (container) container.scrollTop = container.scrollHeight - prevHeight;
      });
    }
  } finally {
    isLoadingOlder.value = false;
  }
};

// Jump to and highlight a message (reply quote, pinned banner, search)
const jumpToMessage = (id?: number | null) => {
  if (!id) return;
  const el = document.getElementById(`msg-${id}`);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    highlightedMessageId.value = id;
    setTimeout(() => (highlightedMessageId.value = null), 1800);
  } else {
    showToast('Original message is not loaded yet');
  }
};

const cyclePinned = () => {
  if (pinnedMessages.value.length > 1) {
    pinnedIndex.value = (pinnedIndex.value + 1) % pinnedMessages.value.length;
  }
};

// Star / Save message
const isMessageSaved = (msg: any): boolean => !!msg.is_saved || savedMessages.value.some((s: any) => (s.message_id || s.id) === msg.id);

const toggleSaveMessage = async (msg: any) => {
  const saved = isMessageSaved(msg);
  const res = await apiFetch(`/api/v1/messages/${msg.id}/save`, { method: saved ? 'DELETE' : 'POST' });
  if (res.ok) {
    msg.is_saved = !saved;
    if (saved) {
      savedMessages.value = savedMessages.value.filter((s: any) => (s.message_id || s.id) !== msg.id);
    } else {
      savedMessages.value.unshift({ ...msg, message_id: msg.id });
    }
    showToast(saved ? 'Removed from starred' : 'Message starred');
  }
};

const copyMessageText = async (msg: MessageItem) => {
  await navigator.clipboard?.writeText(msg.body || '').catch(() => {});
  showToast('Copied');
};

const copyMessageLink = async (msg: MessageItem) => {
  const url = `${window.location.origin}/messages?conversation_id=${msg.conversation_id}#msg-${msg.id}`;
  await navigator.clipboard?.writeText(url).catch(() => {});
  showToast('Link copied');
};

// Reactions viewer
const showReactors = async (msg: MessageItem) => {
  const res = await apiFetch(`/api/v1/messages/${msg.id}/reactions`);
  if (res.ok) {
    const data = await res.json();
    reactorsList.value = Array.isArray(data.data) ? data.data : data.data?.reactions || [];
  }
};

// Report
const openReport = (kind: 'message' | 'conversation', id: number) => {
  reportForm.value = { reason: 'spam', details: '' };
  reportingTarget.value = { kind, id };
};

const submitReport = async () => {
  const target = reportingTarget.value;
  if (!target) return;
  let messageId = target.id;
  if (target.kind === 'conversation') {
    const last = [...messages.value].reverse().find((m) => !m.is_mine);
    if (!last) {
      showToast('No messages from this contact to report yet.');
      reportingTarget.value = null;
      return;
    }
    messageId = last.id;
  }
  const res = await apiFetch(`/api/v1/messages/${messageId}/report`, { method: 'POST', json: reportForm.value });
  reportingTarget.value = null;
  showToast(res.ok ? 'Report submitted. Thank you.' : 'Unable to submit report');
};

// Message request handling
const handleMessageRequest = async (action: 'accept' | 'delete' | 'block') => {
  const conv: any = activeConversation.value;
  if (!conv) return;
  const res = await apiFetch(`/api/v1/conversations/${conv.id}/request`, { method: 'POST', json: { action } });
  if (res.ok) {
    if (action === 'accept') {
      conv.is_request = false;
      conv.request_status = 'accepted';
      showToast('Request accepted');
    } else {
      conversationList.value = conversationList.value.filter((c) => c.id !== conv.id);
      activeConversation.value = null;
      messages.value = [];
      mobileView.value = 'conversations';
    }
  }
};

// Mute with duration
const muteConversation = async (duration: '1h' | '8h' | '24h' | 'forever' | 'unmute') => {
  const conv = activeConversation.value;
  if (!conv) return;
  showMuteOptions.value = false;
  const res = await apiFetch(`/api/v1/conversations/${conv.id}/mute`, { method: 'POST', json: { duration } });
  if (res.ok) {
    conv.is_muted = duration !== 'unmute';
    showToast(duration === 'unmute' ? 'Notifications unmuted' : `Muted ${duration === 'forever' ? 'until you unmute' : 'for ' + duration}`);
  }
};

const markConversationUnread = async (conv: ConversationItem) => {
  const res = await apiFetch(`/api/v1/conversations/${conv.id}/unread`, { method: 'POST' });
  if (res.ok) {
    conv.unread_count = Math.max(1, conv.unread_count || 0);
    showToast('Marked as unread');
  }
};

// Saved messages (self chat)
const openSavedMessages = async () => {
  const res = await apiFetch('/api/v1/conversations', { method: 'POST', json: { type: 'saved' } });
  if (res.ok) {
    const data = await res.json();
    const conv = data.data;
    if (conv) {
      if (!conversationList.value.some((c) => c.id === conv.id)) conversationList.value.unshift(conv);
      selectConversation(conv);
    }
  }
};

// Group creation & management
const openCreateGroup = () => {
  groupForm.value = { title: '', description: '', memberIds: [], query: '' };
  showCreateGroupModal.value = true;
};

const toggleGroupMember = (id: number) => {
  const list = groupForm.value.memberIds;
  const idx = list.indexOf(id);
  if (idx === -1) list.push(id);
  else list.splice(idx, 1);
};

const submitCreateGroup = async () => {
  const f = groupForm.value;
  if (!f.title.trim() || f.memberIds.length === 0) {
    showToast('Add a group name and at least one member');
    return;
  }
  const res = await apiFetch('/api/v1/conversations', {
    method: 'POST',
    json: { type: 'group', title: f.title.trim(), description: f.description || null, participant_ids: f.memberIds },
  });
  const data = await res.json().catch(() => ({}));
  if (res.ok && data.data) {
    showCreateGroupModal.value = false;
    conversationList.value.unshift(data.data);
    selectConversation(data.data);
    showToast('Group created');
  } else {
    showToast(data.message || 'Unable to create group');
  }
};

const refreshActiveConversation = async () => {
  const conv = activeConversation.value;
  if (!conv) return;
  const res = await apiFetch(`/api/v1/conversations/${conv.id}`);
  if (res.ok) {
    const data = await res.json();
    if (data.data) Object.assign(conv, data.data);
  }
};

const editGroupInfo = async () => {
  const conv: any = activeConversation.value;
  if (!conv) return;
  const title = prompt('Group name', conv.title);
  if (title === null) return;
  const description = prompt('Group description', conv.description || '') ?? conv.description;
  const res = await apiFetch(`/api/v1/conversations/${conv.id}/group-info`, { method: 'PUT', json: { title, description } });
  if (res.ok) {
    conv.title = title;
    conv.description = description;
    showToast('Group updated');
  }
};

const submitAddMembers = async () => {
  const conv = activeConversation.value;
  if (!conv || addMembersSelection.value.length === 0) return;
  const res = await apiFetch(`/api/v1/conversations/${conv.id}/members`, {
    method: 'POST',
    json: { user_ids: addMembersSelection.value },
  });
  showAddMembersModal.value = false;
  addMembersSelection.value = [];
  if (res.ok) {
    await refreshActiveConversation();
    showToast('Members added');
  }
};

const setMemberRole = async (member: any, role: 'admin' | 'member') => {
  const conv = activeConversation.value;
  if (!conv) return;
  const uid = member.user_id || member.id;
  const res = await apiFetch(`/api/v1/conversations/${conv.id}/members/${uid}/role`, { method: 'PUT', json: { role } });
  if (res.ok) {
    member.role = role;
    showToast(role === 'admin' ? 'Promoted to admin' : 'Admin removed');
  }
};

const removeMember = async (member: any) => {
  const conv: any = activeConversation.value;
  if (!conv || !confirm(`Remove ${member.name} from the group?`)) return;
  const uid = member.user_id || member.id;
  const res = await apiFetch(`/api/v1/conversations/${conv.id}/members/${uid}`, { method: 'DELETE' });
  if (res.ok) {
    conv.members = activeMembers.value.filter((m) => (m.user_id || m.id) !== uid);
    showToast('Member removed');
  }
};

// Preferences
const savePreferences = async () => {
  localStorage.setItem('jj_send_on_enter', sendOnEnter.value ? '1' : '0');
  localStorage.setItem('jj_active_status', activeStatusVisible.value ? '1' : '0');
  apiFetch('/api/v1/presence/visibility', { method: 'POST', json: { appear_offline: !activeStatusVisible.value } }).catch(() => {});
  apiFetch('/api/v1/settings/messenger-sounds', { method: 'PUT', json: { sounds_enabled: soundEnabled.value } }).catch(() => {});
  showSettingsModal.value = false;
  showToast('Preferences saved');
};

// Composer keyboard handling (Send-on-Enter preference)
const onComposerKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Enter' && !e.shiftKey && sendOnEnter.value) {
    e.preventDefault();
    handleSendMessage();
  }
};

// Draft auto-save
let draftTimer: any = null;
const saveDraftDebounced = () => {
  const conv = activeConversation.value;
  if (!conv) return;
  localStorage.setItem(`jj_draft_${conv.id}`, composerMessage.value);
  if (draftTimer) clearTimeout(draftTimer);
  draftTimer = setTimeout(() => {
    apiFetch(`/api/v1/conversations/${conv.id}/draft`, { method: 'POST', json: { draft: composerMessage.value } }).catch(() => {});
  }, 1200);
};

// Drag & drop
const isDragging = ref(false);
const onDropFiles = (e: DragEvent) => {
  isDragging.value = false;
  const file = e.dataTransfer?.files?.[0];
  if (file) selectedAttachmentFile.value = file;
};

const formatCallTime = (call: any): string => formatTimestamp(call.started_at || call.created_at);

// Start Direct Chat from New Message Modal
const startDirectChat = async (friend: User) => {
  showNewMessageModal.value = false;
  try {
    const res = await apiFetch('/api/v1/conversations', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({
        recipient_id: friend.id,
      }),
    });

    if (res.ok) {
      const data = await res.json();
      const newConv = data.data;
      if (newConv) {
        const existingIdx = conversationList.value.findIndex((c) => c.id === newConv.id);
        if (existingIdx === -1) {
          conversationList.value.unshift(newConv);
        }
        selectConversation(newConv);
      }
    }
  } catch (err) {
    console.error('Failed to start direct conversation:', err);
  }
};

// Pin conversation
const togglePinConversation = async (conv: ConversationItem) => {
  try {
    const res = await apiFetch(`/api/v1/conversations/${conv.id}/pin`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });

    if (res.ok) {
      const data = await res.json();
      conv.is_pinned = data.data?.is_pinned ?? !conv.is_pinned;
    }
  } catch (err) {
    console.error('Pin conversation failed:', err);
  }
};

// Archive conversation
const toggleArchiveConversation = async (conv: ConversationItem) => {
  try {
    const res = await apiFetch(`/api/v1/conversations/${conv.id}/archive`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });

    if (res.ok) {
      const data = await res.json();
      conv.is_archived = data.data?.is_archived ?? !conv.is_archived;
    }
  } catch (err) {
    console.error('Archive conversation failed:', err);
  }
};

// Add Friend action
const handleAddFriend = async () => {
  if (!otherUserProfile.value?.id) return;
  try {
    const res = await apiFetch(`/api/v1/friends/${otherUserProfile.value.id}/request`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    if (res.ok) {
      otherUserProfile.value.relationship = 'pending';
    }
  } catch (err) {
    console.error('Friend request failed:', err);
  }
};

// Mute conversation
const toggleMuteConversation = async () => {
  if (!activeConversation.value) return;
  const convId = activeConversation.value.id;
  try {
    const res = await apiFetch(`/api/v1/conversations/${convId}/mute`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    if (res.ok) {
      activeConversation.value.is_muted = !activeConversation.value.is_muted;
    }
  } catch (err) {
    console.error('Failed to toggle mute:', err);
  }
};

// Clear chat history
const confirmClearHistory = async () => {
  if (!activeConversation.value) return;
  if (!confirm('Are you sure you want to clear your chat history for this conversation? This cannot be undone.')) {
    return;
  }
  const convId = activeConversation.value.id;
  try {
    const res = await apiFetch(`/api/v1/conversations/${convId}/clear`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    if (res.ok) {
      messages.value = [];
      if (activeConversation.value.last_message) {
        activeConversation.value.last_message.body = '';
      }
    }
  } catch (err) {
    console.error('Failed to clear conversation history:', err);
  }
};

// Delete conversation
const confirmDeleteConversation = async () => {
  if (!activeConversation.value) return;
  if (!confirm('Are you sure you want to delete this conversation completely?')) {
    return;
  }
  const convId = activeConversation.value.id;
  try {
    const res = await apiFetch(`/api/v1/conversations/${convId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    if (res.ok) {
      conversationList.value = conversationList.value.filter((c) => c.id !== convId);
      activeConversation.value = null;
      messages.value = [];
    }
  } catch (err) {
    console.error('Failed to delete conversation:', err);
  }
};

// Leave group
const confirmLeaveGroup = async () => {
  if (!activeConversation.value) return;
  if (!confirm('Are you sure you want to leave this group?')) {
    return;
  }
  const convId = activeConversation.value.id;
  try {
    const res = await apiFetch(`/api/v1/conversations/${convId}/leave`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    if (res.ok) {
      conversationList.value = conversationList.value.filter((c) => c.id !== convId);
      activeConversation.value = null;
      messages.value = [];
    }
  } catch (err) {
    console.error('Failed to leave group:', err);
  }
};

// Block user
const confirmBlockUser = async () => {
  if (!otherUserProfile.value?.id) return;
  if (!confirm(`Are you sure you want to block ${activeConversation.value?.title || 'this user'}?`)) {
    return;
  }
  try {
    const res = await apiFetch(`/api/v1/users/${otherUserProfile.value.id}/block`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
    if (res.ok) {
      alert('User has been blocked.');
      window.location.reload();
    }
  } catch (err) {
    console.error('Failed to block user:', err);
  }
};

const openNewMessageModal = () => {
  friendFilterQuery.value = '';
  showNewMessageModal.value = true;
};

// Real-Time Event Handlers from realtime-listener & WebSocket
const onRealtimeMessageCreated = (e: any) => {
  const detail = e.detail || {};
  const p = detail.payload || {};
  const convId = Number(detail.event?.conversation_id || p.conversation_id);

  // If in current active conversation, append message
  if (activeConversation.value && Number(activeConversation.value.id) === convId) {
    const newMsg: MessageItem = p.message || p;
    newMsg.is_mine = Number(newMsg.sender_id || newMsg.sender?.id) === Number(currentUserId.value);

    // Prevent duplicate appending
    if (!messages.value.some((m) => m.id === newMsg.id)) {
      messages.value.push(newMsg);
      scrollToBottom();

      // Play chime if incoming from another user
      if (!newMsg.is_mine && (window as any).bondhooSoundManager) {
        (window as any).bondhooSoundManager.playIncomingMessageTone(newMsg.id);
      }

      // Mark read
      apiFetch(`/api/v1/conversations/${convId}/read`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
      }).catch(() => {});
    }
  }

  // Update conversation preview in sidebar
  const convIdx = conversationList.value.findIndex((c) => c.id === convId);
  if (convIdx !== -1) {
    const conv = conversationList.value[convIdx];
    conv.last_message = {
      id: p.message?.id || p.id,
      body: p.message?.body || p.body || 'New message',
      sent_at: p.message?.sent_at || p.sent_at || new Date().toISOString(),
      type: p.message?.type || p.type,
    };
    if (!activeConversation.value || Number(activeConversation.value.id) !== convId) {
      conv.unread_count = (conv.unread_count || 0) + 1;
    }
    const [moved] = conversationList.value.splice(convIdx, 1);
    conversationList.value.unshift(moved);
  }
};

const onRealtimeMessageUpdated = (e: any) => {
  const detail = e.detail || {};
  const p = detail.payload || {};
  const msgId = Number(p.message_id || p.id);
  const target = messages.value.find((m) => m.id === msgId);
  if (target) {
    target.body = p.body || target.body;
    target.is_edited = true;
  }
};

const onRealtimeMessageDeleted = (e: any) => {
  const detail = e.detail || {};
  const p = detail.payload || {};
  const msgId = Number(p.message_id || p.id);
  const forEveryone = p.for_everyone ?? true;

  if (forEveryone) {
    const target = messages.value.find((m) => m.id === msgId);
    if (target) {
      target.is_deleted = true;
      target.body = '';
      target.media = [];
    }
  } else {
    messages.value = messages.value.filter((m) => m.id !== msgId);
  }
};

const onRealtimeReaction = (e: any) => {
  const detail = e.detail || {};
  const p = detail.payload || {};
  const msgId = Number(p.message_id || p.id);
  const target = messages.value.find((m) => m.id === msgId);
  if (target) {
    if (!target.reactions) target.reactions = { total: 0, counts: {} };
    target.reactions.total = (target.reactions.total || 0) + 1;
    if (p.reaction) {
      if (!target.reactions.counts) target.reactions.counts = {};
      target.reactions.counts[p.reaction] = (target.reactions.counts[p.reaction] || 0) + 1;
    }
  }
};

let typingResetTimer: any = null;
const onRealtimeTyping = (e: any) => {
  const detail = e.detail || {};
  const p = detail.payload || {};
  const convId = Number(p.conversation_id);
  const senderId = Number(p.user_id);

  if (senderId !== Number(currentUserId.value)) {
    if (activeConversation.value && Number(activeConversation.value.id) === convId) {
      isTyping.value = true;
      if (typingResetTimer) clearTimeout(typingResetTimer);
      typingResetTimer = setTimeout(() => {
        isTyping.value = false;
      }, 3000);
    }
    typingConversations.value[convId] = true;
    setTimeout(() => {
      delete typingConversations.value[convId];
    }, 3000);
  }
};

const onRealtimeMessageStatus = (e: any) => {
  const detail = e.detail || {};
  const p = detail.payload || {};
  const msgId = Number(p.message_id || p.id);
  const status = p.delivery_status || (detail.event?.event_type === 'message.read' ? 'read' : 'delivered');
  const target = messages.value.find((m) => m.id === msgId);
  if (target) {
    target.delivery_status = status;
  }
};

// Background Polling fallback (every 4s)
let pollInterval: any = null;
let presenceInterval: any = null;

const pollActiveMessages = async () => {
  if (!activeConversation.value || document.hidden) return;
  try {
    const res = await apiFetch(`/api/v1/conversations/${activeConversation.value.id}/messages?per_page=30`, {
      headers: { 'Accept': 'application/json' },
    });
    if (res.ok) {
      const data = await res.json();
      const serverMsgs = (data.data || []).reverse();
      if (serverMsgs.length > 0) {
        const lastLocalId = messages.value[messages.value.length - 1]?.id || 0;
        const newOnes = serverMsgs.filter((m: any) => m.id > lastLocalId);
        if (newOnes.length > 0) {
          messages.value.push(...newOnes);
          scrollToBottom();
        }
      }
    }
  } catch (e) {
    // Silent
  }
};

const sendPresenceHeartbeat = async () => {
  try {
    await apiFetch('/api/v1/presence/heartbeat', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    });
  } catch (e) {
    // Ignore presence errors
  }
};

onMounted(() => {
  scrollToBottom();
  if (activeConversation.value) {
    fetchPinnedMessages(activeConversation.value.id);
  }

  // Register real-time sync listeners
  window.addEventListener('jugajug:message-created', onRealtimeMessageCreated);
  window.addEventListener('jugajug:message-updated', onRealtimeMessageUpdated);
  window.addEventListener('jugajug:message-deleted', onRealtimeMessageDeleted);
  window.addEventListener('jugajug:message-reaction', onRealtimeReaction);
  window.addEventListener('jugajug:typing', onRealtimeTyping);
  window.addEventListener('jugajug:message-status', onRealtimeMessageStatus);
  window.addEventListener('jugajug:incoming-call', onIncomingCall);

  if (activeConversation.value) {
    loadSharedContent(activeConversation.value.id);
  }

  pollInterval = setInterval(pollActiveMessages, 4000);
  presenceInterval = setInterval(sendPresenceHeartbeat, 30000);
  callPollInterval = setInterval(pollActiveCall, 5000);
});

let callPollInterval: any = null;
const pollActiveCall = async () => {
  if (document.hidden) return;
  try {
    // Outgoing: track ringing call until accepted / rejected / missed
    if (callStatus.value === 'ringing' && activeCall.value?.id) {
      const r = await apiFetch(`/api/v1/calls/${activeCall.value.id}`);
      if (r.ok) {
        const c = (await r.json()).data;
        if (c?.status === 'active' || c?.status === 'ongoing' || c?.status === 'connected') {
          openCallRoom(c);
          activeCall.value = null;
          callStatus.value = 'idle';
        } else if (c && !['ringing', 'initiated', 'pending'].includes(c.status)) {
          showToast(c.outcome === 'missed' ? 'No answer' : 'Call ended');
          activeCall.value = null;
          callStatus.value = 'idle';
        }
      }
    }

    // Incoming: show ringing modal
    if (incomingCall.value) return;
    const res = await apiFetch('/api/v1/calls/active');
    if (!res.ok) return;
    const call = (await res.json()).data;
    if (call && !call.is_mine && call.status === 'ringing') {
      incomingCall.value = call;
    }
  } catch (e) {
    // ignore
  }
};

onUnmounted(() => {
  window.removeEventListener('jugajug:message-created', onRealtimeMessageCreated);
  window.removeEventListener('jugajug:message-updated', onRealtimeMessageUpdated);
  window.removeEventListener('jugajug:message-deleted', onRealtimeMessageDeleted);
  window.removeEventListener('jugajug:message-reaction', onRealtimeReaction);
  window.removeEventListener('jugajug:typing', onRealtimeTyping);
  window.removeEventListener('jugajug:message-status', onRealtimeMessageStatus);
  window.removeEventListener('jugajug:incoming-call', onIncomingCall);

  if (pollInterval) clearInterval(pollInterval);
  if (presenceInterval) clearInterval(presenceInterval);
  if (callPollInterval) clearInterval(callPollInterval);
  if (activeAudioElement) activeAudioElement.pause();
});
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.98); }
  to { opacity: 1; transform: scale(1); }
}

.animate-fade-in {
  animation: fadeIn 0.15s ease-out forwards;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
