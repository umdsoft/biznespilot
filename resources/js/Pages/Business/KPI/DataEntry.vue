<template>
  <BusinessLayout title="KPI Ma'lumot Kiritish">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            KPI Ma'lumot Kiritish
          </h2>
          <p class="mt-1 text-gray-600 dark:text-gray-400">
            Kunlik lidlar, sotuvlar va daromadni kiriting
          </p>
        </div>

        <!-- Tabs -->
        <div class="flex gap-2 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2',
              activeTab === tab.id
                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
            ]"
          >
            <!-- Edit Icon -->
            <svg v-if="tab.icon === 'edit'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <!-- Chart Icon -->
            <svg v-else-if="tab.icon === 'chart'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <!-- Filter Icon -->
            <svg v-else-if="tab.icon === 'filter'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <!-- Clock Icon -->
            <svg v-else-if="tab.icon === 'clock'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ tab.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State if no businessId -->
    <div v-if="!businessId" class="flex items-center justify-center py-12">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p class="text-gray-500 dark:text-gray-400">Yuklanmoqda...</p>
      </div>
    </div>

    <!-- Tab Content -->
    <template v-else>
      <div v-if="activeTab === 'quick'">
        <QuickEntry
          :business-id="businessId"
          @saved="onDataSaved"
          @error="onError"
        />
      </div>

      <div v-else-if="activeTab === 'dashboard'">
        <DataEntryDashboard
          :business-id="businessId"
          @view-all="activeTab = 'history'"
        />
      </div>

      <div v-else-if="activeTab === 'analysis'">
        <SourceAnalysis :business-id="businessId" />
      </div>

      <div v-else-if="activeTab === 'history'">
        <HistoryTable :business-id="businessId" />
      </div>
    </template>

    <!-- Toast Notification -->
    <div
      v-if="toast.show"
      class="fixed bottom-4 right-4 z-50"
    >
      <div
        :class="[
          'px-6 py-4 rounded-xl shadow-lg flex items-center gap-3',
          toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
        ]"
      >
        <svg v-if="toast.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <span>{{ toast.message }}</span>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import QuickEntry from '@/components/KPI/QuickEntry.vue';
import DataEntryDashboard from '@/components/KPI/DataEntryDashboard.vue';
import SourceAnalysis from '@/components/KPI/SourceAnalysis.vue';
import HistoryTable from '@/components/KPI/HistoryTable.vue';

const page = usePage();
const businessId = computed(() => page.props.business?.id || page.props.auth?.user?.current_business_id);

const activeTab = ref('quick');

const tabs = [
  { id: 'quick', label: 'Tezkor Kiritish', icon: 'edit' },
  { id: 'dashboard', label: 'Dashboard', icon: 'chart' },
  { id: 'analysis', label: 'Manba Tahlili', icon: 'filter' },
  { id: 'history', label: 'Tarix', icon: 'clock' },
];

// Toast notification
const toast = ref({
  show: false,
  type: 'success',
  message: ''
});

const showToast = (type, message) => {
  toast.value = { show: true, type, message };
  setTimeout(() => {
    toast.value.show = false;
  }, 3000);
};

const onDataSaved = (data) => {
  showToast('success', 'Ma\'lumotlar muvaffaqiyatli saqlandi!');
};

const onError = (message) => {
  showToast('error', message);
};
</script>
