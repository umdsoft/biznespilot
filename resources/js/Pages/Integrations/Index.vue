<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Integratsiyalar</h1>
        <p class="text-sm text-gray-600 mt-1">
          Biznesingizni turli platformalar bilan birlashtiring
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
        Hammasini Yangilash
      </button>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">Jami Integratsiyalar</p>
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
            <p class="text-sm text-gray-600">Ulangan</p>
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
            <p class="text-sm text-gray-600">Faol</p>
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
            <p class="text-sm text-gray-600">Oxirgi Sinxronlash</p>
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
        Bu kategoriyada integratsiya topilmadi
      </h3>
      <p class="text-gray-600">
        Boshqa kategoriyani tanlang yoki barcha integratsiyalarni ko'ring
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

const router = useRouter();
const toast = useToastStore();
const authStore = useAuthStore();

const loading = ref(false);
const selectedCategory = ref('all');
const integrations = ref([]);

// Categories
const categories = computed(() => [
  { id: 'all', name: 'Barchasi', count: integrations.value.length },
  { id: 'social', name: 'Ijtimoiy Tarmoqlar', count: integrations.value.filter(i => i.category === 'social').length },
  { id: 'messaging', name: 'Xabarlar', count: integrations.value.filter(i => i.category === 'messaging').length },
  { id: 'business', name: 'Biznes Tizimlari', count: integrations.value.filter(i => i.category === 'business').length },
  { id: 'marketing', name: 'Marketing', count: integrations.value.filter(i => i.category === 'marketing').length },
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

  if (syncs.length === 0) return 'Hech qachon';

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
      description: 'Instagram akkauntingizni ulang va postlaringiz, audiensiya statistikasini kuzating',
      isConnected: false,
      isActive: false,
      loading: false,
      features: [
        'Post va Reels statistikasi',
        'Audiensiya demografiyasi',
        'Engagement tahlili',
        'Hashtag performance',
        'Best posting times',
      ],
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'facebook',
      category: 'social',
      icon: '👥',
      name: 'Facebook',
      description: 'Facebook sahifangiz va reklamalaringizni boshqaring',
      isConnected: false,
      isActive: false,
      loading: false,
      features: [
        'Sahifa statistikasi',
        'Ads Manager integratsiyasi',
        'Audiensiya tahlili',
        'Post performance',
        'Lead generation',
      ],
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'telegram',
      category: 'messaging',
      icon: '✈️',
      name: 'Telegram',
      description: 'Telegram bot orqali mijozlar bilan muloqot qiling',
      isConnected: false,
      isActive: false,
      loading: false,
      features: [
        'AI Chatbot',
        'Avtomatik javoblar',
        'Guruh boshqaruvi',
        'Xabarlar statistikasi',
        'Broadcasting',
      ],
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'whatsapp',
      category: 'messaging',
      icon: '💬',
      name: 'WhatsApp Business',
      description: 'WhatsApp orqali mijozlar bilan professional muloqot',
      isConnected: false,
      isActive: false,
      loading: false,
      features: [
        'AI Chatbot',
        'Quick replies',
        'Business catalog',
        'Xabarlar templatelari',
        'Avtomatlashtirish',
      ],
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'pos',
      category: 'business',
      icon: '💳',
      name: 'POS Sistema',
      description: 'POS tizimingizni ulang va savdo ma\'lumotlarini avtomatik sinxronlang',
      isConnected: false,
      isActive: false,
      loading: false,
      features: [
        'Real-time savdo ma\'lumotlari',
        'Inventar boshqaruvi',
        'Kassir hisobotlari',
        'Mijozlar bazasi',
        'Chek tahlili',
      ],
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'google_analytics',
      category: 'marketing',
      icon: '📊',
      name: 'Google Analytics 4',
      description: 'Veb-sayt trafigi va foydalanuvchi xatti-harakatini kuzating',
      isConnected: false,
      isActive: false,
      loading: false,
      features: [
        'Website traffic tahlili',
        'User behavior tracking',
        'Conversion funnel',
        'Real-time analytics',
        'E-commerce tracking',
      ],
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'yandex_metrica',
      category: 'marketing',
      icon: '📈',
      name: 'Yandex.Metrica',
      description: 'Yandex analytics platformasi (Rossiya va MDH uchun)',
      isConnected: false,
      isActive: false,
      loading: false,
      features: [
        'Website statistikasi',
        'Session replay',
        'Heatmaps',
        'Form analytics',
        'Webvisor',
      ],
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'google_ads',
      category: 'marketing',
      icon: '🎯',
      name: 'Google Ads',
      description: 'Google reklama kampaniyalarini boshqaring',
      isConnected: false,
      isActive: false,
      loading: false,
      features: [
        'Campaign performance',
        'Keyword tahlili',
        'Budget optimization',
        'ROAS tracking',
        'Conversion tracking',
      ],
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'yandex_direct',
      category: 'marketing',
      icon: '🎪',
      name: 'Yandex.Direct',
      description: 'Yandex reklama platformasi (Rossiya va MDH)',
      isConnected: false,
      isActive: false,
      loading: false,
      features: [
        'Kampaniya boshqaruvi',
        'CTR optimization',
        'Budget tracking',
        'ROI analysis',
        'Geo-targeting',
      ],
      connectionInfo: null,
      stats: null,
    },
    {
      id: 'email',
      category: 'marketing',
      icon: '📧',
      name: 'Email Marketing',
      description: 'Email kampaniyalaringizni boshqaring (MailChimp, SendGrid)',
      isConnected: false,
      isActive: false,
      loading: false,
      features: [
        'Email campaigns',
        'Subscriber management',
        'Template builder',
        'Analytics',
        'A/B testing',
      ],
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
      toast.info(`${integration.name} integratsiyasi ishlab chiqilmoqda...`);
    }
  } catch (error) {
    toast.error(`${integration.name}ni ulashda xatolik yuz berdi`);
  } finally {
    integration.loading = false;
  }
};

// Handle disconnect
const handleDisconnect = async (integration) => {
  if (!confirm(`${integration.name}ni uzmoqchimisiz?`)) {
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

    toast.success(`${integration.name} muvaffaqiyatli uzildi`);
  } catch (error) {
    toast.error(`${integration.name}ni uzishda xatolik yuz berdi`);
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

    toast.success(`${integration.name} sinxronlashdi`);
    await loadIntegrationStatus();
  } catch (error) {
    toast.error(`${integration.name}ni sinxronlashda xatolik yuz berdi`);
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
  toast.success('Barcha integratsiyalar yangilandi');
};

// Format relative time
const formatRelativeTime = (date) => {
  const now = new Date();
  const diff = Math.floor((now - date) / 1000);

  if (diff < 60) return 'Hozirgina';
  if (diff < 3600) return `${Math.floor(diff / 60)} daqiqa oldin`;
  if (diff < 86400) return `${Math.floor(diff / 3600)} soat oldin`;
  if (diff < 604800) return `${Math.floor(diff / 86400)} kun oldin`;

  return date.toLocaleDateString('uz-UZ');
};

onMounted(() => {
  initializeIntegrations();
  loadIntegrationStatus();
});
</script>
