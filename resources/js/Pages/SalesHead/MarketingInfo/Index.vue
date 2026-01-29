<template>
  <SalesHeadLayout :title="t('marketing_info.title')">
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ t('marketing_info.title') }}
          </h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ t('marketing_info.description') }}
          </p>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="flex gap-4" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="switchTab(tab.key)"
            :class="[
              'px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
              activeTab === tab.key
                ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'
            ]"
          >
            <component :is="tab.icon" class="w-5 h-5 inline-block mr-2" />
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="mt-6">
        <!-- Dream Buyer Tab -->
        <div v-if="activeTab === 'dream-buyer'" class="space-y-6">
          <DreamBuyerList
            :dream-buyers="dreamBuyers"
            :panel-type="'saleshead'"
            :read-only="true"
          />
        </div>

        <!-- Offers Tab -->
        <div v-if="activeTab === 'offers'" class="space-y-6">
          <OffersList
            :offers="offers"
            :panel-type="'saleshead'"
            :read-only="true"
          />
        </div>

        <!-- Competitors Tab -->
        <div v-if="activeTab === 'competitors'" class="space-y-6">
          <CompetitorsList
            :competitors="competitors"
            :panel-type="'saleshead'"
            :read-only="true"
          />
        </div>
      </div>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { UserGroupIcon, TagIcon, ChartBarIcon } from '@heroicons/vue/24/outline';

// Simple list components - inline
import DreamBuyerList from './Tabs/DreamBuyerList.vue';
import OffersList from './Tabs/OffersList.vue';
import CompetitorsList from './Tabs/CompetitorsList.vue';

const { t } = useI18n();

const props = defineProps({
  dreamBuyers: { type: Array, default: () => [] },
  offers: { type: Array, default: () => [] },
  competitors: { type: Array, default: () => [] },
  activeTab: { type: String, default: 'dream-buyer' },
});

const activeTab = ref(props.activeTab);

const tabs = [
  { key: 'dream-buyer', label: 'Ideal Mijoz', icon: UserGroupIcon },
  { key: 'offers', label: 'Takliflar', icon: TagIcon },
  { key: 'competitors', label: 'Raqobatchilar', icon: ChartBarIcon },
];

function switchTab(key) {
  activeTab.value = key;
  // Update URL without full page reload
  router.get(route('sales-head.marketing-info.index'), { tab: key }, {
    preserveState: true,
    preserveScroll: true,
    only: [],
  });
}
</script>
