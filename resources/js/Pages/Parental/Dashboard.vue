<template>
  <AppLayout :current-user="user" current-route="parental">
    <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6">
      
      <!-- Top Header & Family Selector -->
      <div class="bg-white dark:bg-slate-900 shadow-sm border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </span>
            <div>
              <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                {{ family ? family.name : 'Parental Safety & Family Controls' }}
              </h1>
              <p class="text-sm text-slate-500 dark:text-slate-400">
                Transparent child supervision, screen-time management and digital well-being.
              </p>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-3">
          <button
            v-if="family"
            @click="openPairingModal"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-2xl shadow-sm transition"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Pair New Device
          </button>
          <button
            v-else
            @click="createFamilyModal = true"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-2xl shadow-sm transition"
          >
            Create Family Group
          </button>
        </div>
      </div>

      <!-- Overview Stats Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Children</p>
          <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ dashboardStats.total_children }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Devices</p>
          <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ dashboardStats.connected_devices }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Offline Devices</p>
          <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ dashboardStats.offline_devices }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Rules</p>
          <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">{{ dashboardStats.active_policies_count }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Consent Requests</p>
          <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ dashboardStats.pending_consents_count }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Security Alerts</p>
          <p :class="['text-2xl font-bold mt-1', dashboardStats.active_alerts_count > 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white']">
            {{ dashboardStats.active_alerts_count }}
          </p>
        </div>
      </div>

      <!-- Main Tabs Navigation -->
      <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex gap-6">
          <button
            v-for="tab in ['children', 'policies', 'consent_requests', 'alerts', 'activity']"
            :key="tab"
            @click="activeTab = tab"
            :class="[
              'pb-3 text-sm font-semibold capitalize transition border-b-2',
              activeTab === tab
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400'
            ]"
          >
            {{ tab.replace('_', ' ') }}
          </button>
        </nav>
      </div>

      <!-- Tab 1: Children & Devices -->
      <div v-if="activeTab === 'children'" class="space-y-4">
        <div v-if="family && family.members && family.members.filter(m => m.role === 'child').length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="child in family.members.filter(m => m.role === 'child')"
            :key="child.id"
            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm flex flex-col justify-between"
          >
            <div>
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center font-bold text-indigo-600 dark:text-indigo-400 text-lg">
                    {{ child.name.charAt(0) }}
                  </div>
                  <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ child.name }}</h3>
                    <p class="text-xs text-slate-500">Child Profile</p>
                  </div>
                </div>
                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">
                  Protected
                </span>
              </div>

              <!-- Enrolled Devices List -->
              <div class="mt-6 border-t border-slate-100 dark:border-slate-800 pt-4 space-y-3">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Connected Devices</p>
                <div v-if="child.devices && child.devices.length > 0" class="space-y-2">
                  <div
                    v-for="dev in child.devices"
                    :key="dev.id"
                    class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl flex items-center justify-between text-xs"
                  >
                    <div>
                      <p class="font-semibold text-slate-800 dark:text-slate-200">{{ dev.device_name }}</p>
                      <p class="text-slate-400 capitalize">{{ dev.platform }} • Status: {{ dev.device_status }}</p>
                    </div>
                    <button
                      v-if="dev.device_status === 'active'"
                      @click="sendLockCommand(dev.id)"
                      class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-medium transition"
                    >
                      Quick Lock
                    </button>
                  </div>
                </div>
                <div v-else class="text-xs text-slate-400 italic">No devices paired yet.</div>
              </div>
            </div>

            <!-- Footer Action -->
            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
              <button
                @click="generateCodeForChild(child.id)"
                class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline"
              >
                + Pair Device
              </button>
            </div>
          </div>
        </div>

        <div v-else class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center">
          <p class="text-slate-500">No child profiles added yet. Add a child to begin monitoring.</p>
          <button
            @click="addChildModal = true"
            class="mt-4 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-2xl"
          >
            Add Child Profile
          </button>
        </div>
      </div>

      <!-- Tab 2: Policy Restrictions -->
      <div v-if="activeTab === 'policies'" class="space-y-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Active Family Restrictions</h3>
          <p class="text-xs text-slate-500 mt-1">Configured curfews, daily limits, and application restrictions.</p>

          <div v-if="family && family.restrictions && family.restrictions.length > 0" class="mt-6 divide-y divide-slate-100 dark:divide-slate-800">
            <div
              v-for="res in family.restrictions"
              :key="res.id"
              class="py-4 flex items-center justify-between"
            >
              <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-white capitalize">
                  {{ res.restriction_type.replace('_', ' ') }}
                </p>
                <p class="text-xs text-slate-400">
                  Version: {{ res.policy_version }} • {{ res.application_package || 'Device-wide' }}
                </p>
              </div>
              <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full', res.enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600']">
                {{ res.enabled ? 'Active' : 'Paused' }}
              </span>
            </div>
          </div>
          <div v-else class="text-sm text-slate-400 py-6 text-center italic">No active restrictions configured.</div>
        </div>
      </div>

      <!-- Tab 3: Pending Consent Requests -->
      <div v-if="activeTab === 'consent_requests'" class="space-y-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Child Consent Requests</h3>
          <p class="text-xs text-slate-500 mt-1">Requests for additional screen-time or app unblocking.</p>

          <div v-if="family && family.consent_requests && family.consent_requests.length > 0" class="mt-6 divide-y divide-slate-100 dark:divide-slate-800">
            <div
              v-for="req in family.consent_requests"
              :key="req.id"
              class="py-4 flex items-center justify-between"
            >
              <div>
                <p class="text-sm font-semibold text-slate-900 dark:text-white capitalize">
                  {{ req.request_type.replace('_', ' ') }}: {{ req.requested_value }} mins
                </p>
                <p class="text-xs text-slate-500">Reason: {{ req.request_reason || 'No note provided' }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Status: <span class="capitalize font-medium">{{ req.request_status }}</span></p>
              </div>
              <div v-if="req.request_status === 'pending'" class="flex items-center gap-2">
                <button
                  @click="respondConsent(req.id, 'approved')"
                  class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl"
                >
                  Approve
                </button>
                <button
                  @click="respondConsent(req.id, 'rejected')"
                  class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl"
                >
                  Reject
                </button>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-slate-400 py-6 text-center italic">No pending consent requests.</div>
        </div>
      </div>

      <!-- Tab 4: Security Alerts -->
      <div v-if="activeTab === 'alerts'" class="space-y-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Security & Tamper Alerts</h3>
          <div v-if="family && family.security_alerts && family.security_alerts.length > 0" class="mt-6 divide-y divide-slate-100 dark:divide-slate-800">
            <div
              v-for="alert in family.security_alerts"
              :key="alert.id"
              class="py-4 flex items-center justify-between"
            >
              <div>
                <span class="px-2 py-0.5 text-xs font-bold uppercase rounded bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400">
                  {{ alert.severity }}
                </span>
                <p class="text-sm font-semibold text-slate-900 dark:text-white mt-1">{{ alert.alert_type }}</p>
                <p class="text-xs text-slate-500">{{ alert.description }}</p>
              </div>
              <span class="text-xs text-slate-400">{{ alert.alert_status }}</span>
            </div>
          </div>
          <div v-else class="text-sm text-slate-400 py-6 text-center italic">No active security alerts. All systems healthy.</div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps<{
  user: any;
  family: any;
  dashboardStats: any;
}>();

const activeTab = ref('children');
const createFamilyModal = ref(false);
const addChildModal = ref(false);

const openPairingModal = () => {
  alert('Please select a child profile to generate a single-use pairing code.');
};

const generateCodeForChild = async (childId: number) => {
  if (!props.family) return;
  try {
    const res = await fetch(`/api/v1/parental/families/${props.family.id}/pairing-codes`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ child_member_id: childId }),
    });
    const data = await res.json();
    if (data.success) {
      alert(`6-Digit Pairing Code: ${data.pairing_code}\nExpires at: ${data.expires_at}\nEnter this code in the Connectly Child Agent.`);
    } else {
      alert(`Error: ${data.message}`);
    }
  } catch (err: any) {
    alert(`Request failed: ${err.message}`);
  }
};

const sendLockCommand = async (deviceId: number) => {
  try {
    const res = await fetch(`/api/v1/parental/devices/${deviceId}/commands`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        command_type: 'device_lock',
        payload: { message: 'Parent requested immediate device lock.' },
      }),
    });
    const data = await res.json();
    if (data.success) {
      alert('Lock command dispatched successfully!');
    }
  } catch (err: any) {
    alert(`Failed to send command: ${err.message}`);
  }
};

const respondConsent = async (requestId: number, status: 'approved' | 'rejected') => {
  try {
    const res = await fetch(`/api/v1/parental/consent-requests/${requestId}/respond`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ status }),
    });
    const data = await res.json();
    if (data.success) {
      alert(`Consent request marked as ${status}.`);
      window.location.reload();
    }
  } catch (err: any) {
    alert(`Failed to update consent request: ${err.message}`);
  }
};
</script>

