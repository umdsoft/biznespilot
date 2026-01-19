<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ t('integrations.title') }}</h1>
        <p class="text-sm text-gray-600 mt-1">
          {{ t('integrations.description') }}
        </p>
      </div>
      <button
        @click="refreshAll"
        :disabled="loading"
        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
      >
        <svg
          :class="{ 'animate-spin': loading }"
          class="w-4 h-4 mr-2"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        {{ t('integrations.refresh_all') }}
      </button>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ t('integrations.stats.total') }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ totalIntegrations }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ t('integrations.stats.connected') }}</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ connectedCount }}</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ t('integrations.stats.active') }}</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ activeCount }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">{{ t('integrations.stats.last_sync') }}</p>
            <p class="text-sm font-medium text-gray-900 mt-1">{{ lastSyncTime }}</p>
          </div>
          <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Tabs -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="category in categories"
          :key="category.id"
          @click="selectedCategory = category.id"
          :class="[
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors',
            selectedCategory === category.id
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          {{ category.name }}
          <span
            :class="[
              'ml-2 py-0.5 px-2 rounded-full text-xs',
              selectedCategory === category.id
                ? 'bg-primary-100 text-primary-600'
                : 'bg-gray-100 text-gray-600'
            ]"
          >
            {{ category.count }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Integrations Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <IntegrationCard
        v-for="integration in filteredIntegrations"
        :key="integration.id"
        :icon="integration.icon"
        :name="integration.name"
        :description="integration.description"
        :is-connected="integration.isConnected"
        :loading="integration.loading"
        :features="integration.features"
        :connection-info="integration.connectionInfo"
        :stats="integration.stats"
        @connect="handleConnect(integration)"
        @disconnect="handleDisconnect(integration)"
        @sync="handleSync(integration)"
        @settings="handleSettings(integration)"
      />
    </div>

    <!-- Empty State -->
    <div
      v-if="filteredIntegrations.length === 0"
      class="text-center py-12 bg-white rounded-lg border border-gray-200"
    >
      <div class="text-6xl mb-4">🔌</div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">
        {{ t('integrations.empty.title') }}
      </h3>
      <p class="text-gray-600">
        {{ t('integrations.empty.desc') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useToastStore } from '@/stores/toast';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';
import IntegrationCard from '@/components/Integrations/IntegrationCard.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();
const router = useRouter();
const toast = useToastStore();
const authStore = useAuthStore();

const loading = ref(false);
const selectedCategory = ref('all');
const integrations = ref([]);

// Categories
const categories = computed(() => [
  { id: 'all', name: t('integrations.categories.all'), count: integrations.value.length },
  { id: 'social', name: t('integrations.categories.social'), count: integrations.value.filter(i => i.category === 'social').length },
  { id: 'messaging', name: t('integrations.categories.messaging'), count: integrations.value.filter(i => i.category === 'messaging').length },
  { id: 'business', name: t('integrations.categories.business'), count: integrations.value.filter(i => i.category === 'business').length },
  { id: 'marketing', name: t('integrations.categories.marketing'), count: integrations.value.filter(i => i.category === 'marketing').length },
]);

// Filtered integrations
const filteredIntegrations = computed(() => {
  if (selectedCategory.value === 'all') {
    return integrations.value;
  }
  return integrations.value.filter(i => i.category === selectedCategory.value);
});

// Stats
const totalIntegrations = computed(() => integrations.value.length);
const connectedCount = computed(() => integrations.value.filter(i => i.isConnected).length);
const activeCount = computed(() => integrations.value.filter(i => i.isConnected && i.isActive).length);
const lastSyncTime = computed(() => {
  const syncs = integrations.value
    .filter(i => i.connectionInfo?.lastSync)
    .map(i => new Date(i.connectionInfo.lastSync));

  if (syncs.length === 0) return t('integrations.time.never');

  const latest = new Date(Math.max(...syncs));
  return formatRelativeTime(latest);
});

// Initialize integrations
const initializeIntegrations = () => {
  integrations.value = [
    // Social Media
    {
      id: 'instagram',
      category: 'social',
      icon: '📷',
      name: 'Instagram',
      description: t('integrations.instagram.desc'),
      isConnected: false,
      isActive: false,
      loading: false,
      features: t('integrations.instagram.features').split('|'),
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'facebook',
      category: 'social',
      icon: '👥',
      name: 'Facebook',
      description: t('integrations.facebook.desc'),
      isConnected: false,
      isActive: false,
      loading: false,
      features: t('integrations.facebook.features').split('|'),
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'telegram',
      category: 'messaging',
      icon: '✈️',
      name: 'Telegram',
      description: t('integrations.telegram.desc'),
      isConnected: false,
      isActive: false,
      loading: false,
      features: t('integrations.telegram.features').split('|'),
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'whatsapp',
      category: 'messaging',
      icon: '💬',
      name: 'WhatsApp Business',
      description: t('integrations.whatsapp.desc'),
      isConnected: false,
      isActive: false,
      loading: false,
      features: t('integrations.whatsapp.features').split('|'),
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'pos',
      category: 'business',
      icon: '💳',
      name: 'POS Sistema',
      description: t('integrations.pos.desc'),
      isConnected: false,
      isActive: false,
      loading: false,
      features: t('integrations.pos.features').split('|'),
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'google_analytics',
      category: 'marketing',
      icon: '📊',
      name: 'Google Analytics 4',
      description: t('integrations.google_analytics.desc'),
      isConnected: false,
      isActive: false,
      loading: false,
      features: t('integrations.google_analytics.features').split('|'),
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'yandex_metrica',
      category: 'marketing',
      icon: '📈',
      name: 'Yandex.Metrica',
      description: t('integrations.yandex_metrica.desc'),
      isConnected: false,
      isActive: false,
      loading: false,
      features: t('integrations.yandex_metrica.features').split('|'),
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'google_ads',
      category: 'marketing',
      icon: '🎯',
      name: 'Google Ads',
      description: t('integrations.google_ads.desc'),
      isConnected: false,
      isActive: false,
      loading: false,
      features: t('integrations.google_ads.features').split('|'),
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'yandex_direct',
      category: 'marketing',
      icon: '🎪',
      name: 'Yandex.Direct',
      description: t('integrations.yandex_direct.desc'),
      isConnected: false,
      isActive: false,
      loading: false,
      features: t('integrations.yandex_direct.features').split('|'),
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'email',
      category: 'marketing',
      icon: '📧',
      name: 'Email Marketing',
      description: t('integrations.email.desc'),
      isConnected: false,
      isActive: false,
      loading: false,
      features: t('integrations.email.features').split('|'),
      connectionInfo: null,
      stats: null,
    },
  ];
};

// Load integration status from API
const loadIntegrationStatus = async () => {
  loading.value = true;
  try {
    const businessId = authStore.currentBusiness?.id;
    if (!businessId) return;

    const response = await axios.get(`/api/integrations/status`, {
      params: { business_id: businessId },
    });

    // Update integrations with API data
    if (response.data.data) {
      integrations.value = integrations.value.map(integration => {
        const apiData = response.data.data[integration.id];
        if (apiData && apiData.isConnected) {
          return {
            ...integration,
            isConnected: apiData.isConnected || false,
            isActive: apiData.isActive || false,
            connectionInfo: {
              connectedAt: apiData.connectedAt,
              lastSync: apiData.lastSync,
              username: apiData.username || apiData.pageName || apiData.botName || apiData.phoneNumber || apiData.systemName || apiData.propertyName || apiData.counterName || apiData.accountName || apiData.websiteUrl,
            },
            stats: apiData.stats || null,
          };
        }
        return integration;
      });
    }
  } catch (error) {
    console.error('Failed to load integration status:', error);
  } finally {
    loading.value = false;
  }
};

// Handle connect
const handleConnect = async (integration) => {
  integration.loading = true;

  try {
    // Redirect to specific integration setup page
    const routes = {
      instagram: '/business/settings/instagram-ai',
      facebook: '/target-analysis',
      telegram: '/business/customer-bot/settings',
      whatsapp: '/business/settings/whatsapp-ai',
      pos: '/business/settings',
      google_analytics: '/business/settings',
      google_ads: '/target-analysis',
      email: '/business/settings',
    };

    const route = routes[integration.id];
    if (route) {
      router.push(route);
    } else {
      toast.info(t('integrations.toast.in_development', { name: integration.name }));
    }
  } catch (error) {
    toast.error(t('integrations.toast.connect_error', { name: integration.name }));
  } finally {
    integration.loading = false;
  }
};

// Handle disconnect
const handleDisconnect = async (integration) => {
  if (!confirm(t('integrations.confirm.disconnect', { name: integration.name }))) {
    return;
  }

  integration.loading = true;

  try {
    await axios.post(`/api/integrations/${integration.id}/disconnect`, {
      business_id: authStore.currentBusiness?.id,
    });

    integration.isConnected = false;
    integration.isActive = false;
    integration.connectionInfo = null;
    integration.stats = null;

    toast.success(t('integrations.toast.disconnected', { name: integration.name }));
  } catch (error) {
    toast.error(t('integrations.toast.disconnect_error', { name: integration.name }));
  } finally {
    integration.loading = false;
  }
};

// Handle sync
const handleSync = async (integration) => {
  integration.loading = true;

  try {
    await axios.post(`/api/integrations/${integration.id}/sync`, {
      business_id: authStore.currentBusiness?.id,
    });

    toast.success(t('integrations.toast.synced', { name: integration.name }));
    await loadIntegrationStatus();
  } catch (error) {
    toast.error(t('integrations.toast.sync_error', { name: integration.name }));
  } finally {
    integration.loading = false;
  }
};

// Handle settings
const handleSettings = (integration) => {
  // Open settings modal or page
  const routes = {
    instagram: '/business/settings/instagram-ai',
    facebook: '/target-analysis',
    telegram: '/business/customer-bot/settings',
    whatsapp: '/business/settings/whatsapp-ai',
  };

  const route = routes[integration.id];
  if (route) {
    router.push(route);
  }
};

// Refresh all
const refreshAll = async () => {
  await loadIntegrationStatus();
  toast.success(t('integrations.toast.refreshed'));
};

// Format relative time
const formatRelativeTime = (date) => {
  const now = new Date();
  const diff = Math.floor((now - date) / 1000);

  if (diff < 60) return t('integrations.time.just_now');
  if (diff < 3600) return t('integrations.time.minutes_ago', { count: Math.floor(diff / 60) });
  if (diff < 86400) return t('integrations.time.hours_ago', { count: Math.floor(diff / 3600) });
  if (diff < 604800) return t('integrations.time.days_ago', { count: Math.floor(diff / 86400) });

  return date.toLocaleDateString('uz-UZ');
};

onMounted(() => {
  initializeIntegrations();
  loadIntegrationStatus();
});
</script>
