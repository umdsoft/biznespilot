<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <div class="flex items-center gap-3 mb-2">
          <span v-if="industryIcon" class="text-3xl">{{ industryIcon }}</span>
          <h1 class="text-2xl font-bold text-gray-900">KPI Boshqaruv Paneli</h1>
        </div>
        <p class="text-sm text-gray-500">
          <span class="font-medium">{{ businessName }}</span> - {{ industryName }}
        </p>
        <p v-if="categoryInfo" class="text-xs text-gray-400 mt-1">
          Kategoriya: {{ categoryInfo }}
        </p>
      </div>
      <div class="flex items-center gap-3">
        <!-- Last Updated -->
        <div v-if="lastUpdated && !loading" class="text-xs text-gray-500 mr-2">
          Yangilangan: {{ formatTime(lastUpdated) }}
        </div>

        <!-- Period Selector -->
        <select
          v-model="selectedPeriod"
          @change="() => loadDashboard(true)"
          :disabled="loading"
          class="rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-50"
        >
          <option value="7">Oxirgi 7 kun</option>
          <option value="30">Oxirgi 30 kun</option>
          <option value="90">Oxirgi 90 kun</option>
        </select>

        <!-- Export Button -->
        <button
          @click="exportDashboard"
          :disabled="loading || !summaryCards.length"
          class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          title="Excel formatida eksport qilish"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          Eksport
        </button>

        <!-- Refresh Button -->
        <button
          @click="() => loadDashboard(true)"
          :disabled="loading"
          class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
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
          {{ loading ? 'Yuklanmoqda...' : 'Yangilash' }}
        </button>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <LoadingSkeleton v-if="loading" />

    <!-- Error State -->
    <ErrorState
      v-else-if="error"
      :title="error.title"
      :message="error.message"
      :error-details="error.details"
      @retry="retryLoad"
      @contact-support="() => {}"
    />

    <!-- Dashboard Content -->
    <div v-else>
      <!-- Summary Cards -->
      <div v-if="summaryCards.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <SummaryCard
          v-for="card in summaryCards"
          :key="card.kpi_code"
          :icon="card.icon"
          :name="card.name"
          :value="card.value"
          :target="card.target"
          :performance-percent="card.performance_percent"
          :performance-status="card.performance_status"
          :performance-color="card.performance_color"
          :change-percent="card.change_percent"
        />
      </div>

      <!-- Performance Overview -->
      <div v-if="performanceOverview" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Umumiy Holat</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="text-3xl font-bold text-green-600 mb-1">
              {{ performanceOverview.on_track_count }}
            </div>
            <div class="text-sm text-gray-600">Rejadagi KPI</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-yellow-600 mb-1">
              {{ performanceOverview.needs_attention_count }}
            </div>
            <div class="text-sm text-gray-600">E'tibor Talab Qiladi</div>
          </div>
          <div class="text-center">
            <div class="text-3xl font-bold text-red-600 mb-1">
              {{ performanceOverview.critical_count }}
            </div>
            <div class="text-sm text-gray-600">Kritik Holat</div>
          </div>
        </div>
        <div class="mt-6">
          <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
            <span>Umumiy Foiz</span>
            <span class="font-semibold">{{ performanceOverview.overall_percentage }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3">
            <div
              :class="getOverallProgressColor(performanceOverview.overall_percentage)"
              :style="{ width: `${performanceOverview.overall_percentage}%` }"
              class="h-3 rounded-full transition-all duration-500"
            ></div>
          </div>
        </div>
      </div>

      <!-- KPI Table -->
      <KpiTable
        v-if="kpiTable && kpiTable.sections && kpiTable.sections.length > 0"
        title="Barcha KPI Ko'rsatkichlari"
        :subtitle="`${kpiTable.sections.reduce((sum, s) => sum + s.rows.length, 0)} ta ko'rsatkich`"
        :sections="kpiTable.sections"
      />

      <!-- Recommendations -->
      <div v-if="recommendations && recommendations.length > 0" class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg shadow-sm border border-blue-200 p-6 mt-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="text-3xl">💡</div>
          <h2 class="text-lg font-semibold text-gray-900">AI Tavsiyalari</h2>
        </div>
        <div class="space-y-3">
          <div
            v-for="(recommendation, index) in recommendations"
            :key="index"
            class="bg-white rounded-lg p-4 shadow-sm"
          >
            <div class="flex items-start gap-3">
              <div
                :class="getPriorityBadgeClass(recommendation.priority)"
                class="px-2.5 py-0.5 rounded-full text-xs font-medium mt-1"
              >
                {{ recommendation.priority }}
              </div>
              <div class="flex-1">
                <div class="font-medium text-gray-900 mb-1">{{ recommendation.kpi_name }}</div>
                <p class="text-sm text-gray-600">{{ recommendation.message }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!summaryCards.length && !loading && !error" class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
        <div class="text-6xl mb-4">📊</div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">KPI Ma'lumotlari Topilmadi</h3>
        <p class="text-gray-600 mb-6 max-w-md mx-auto">
          Biznesingiz uchun KPI ma'lumotlari hali yuklanmagan. Integratsiyalarni ulang yoki KPI'larni qo'lda kiriting.
        </p>
        <div class="flex items-center justify-center gap-3">
          <Link
            href="/business/settings"
            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Integratsiyalarni Ulash
          </Link>
          <button
            @click="() => loadDashboard(true)"
            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Qayta Urinish
          </button>
        </div>

        <!-- Help Links -->
        <div class="mt-8 pt-6 border-t border-gray-200">
          <p class="text-sm text-gray-500 mb-3">Yordam kerakmi?</p>
          <div class="flex items-center justify-center gap-4 text-sm">
            <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">
              📚 Qo'llanma
            </a>
            <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">
              🎥 Video Ko'rsatma
            </a>
            <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">
              💬 Qo'llab-quvvatlash
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useToastStore as useToast } from '@/stores/toast';
import SummaryCard from '@/components/KPI/SummaryCard.vue';
import KpiTable from '@/components/KPI/KpiTable.vue';
import LoadingSkeleton from '@/components/KPI/LoadingSkeleton.vue';
import ErrorState from '@/components/KPI/ErrorState.vue';

const authStore = useAuthStore();
const toast = useToast();

const loading = ref(true);
const error = ref(null);
const selectedPeriod = ref(30);
const summaryCards = ref([]);
const kpiTable = ref(null);
const performanceOverview = ref(null);
const recommendations = ref([]);
const industryName = ref('');
const industryIcon = ref('');
const businessName = ref('');
const categoryInfo = ref('');
const lastUpdated = ref(null);

// Get business ID from auth store
const businessId = computed(() => authStore.currentBusiness?.id || null);

const loadDashboard = async (showToast = false) => {
  // Validation: Check if business is selected
  if (!businessId.value) {
    error.value = {
      title: 'Biznes Tanlanmagan',
      message: 'Iltimos, avval biznesingizni tanlang.',
      action: 'select-business',
    };
    loading.value = false;
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const response = await axios.get(`/api/kpi-dashboard`, {
      params: {
        business_id: businessId.value,
        days: selectedPeriod.value,
      },
    });

    // Extract data from response
    const data = response.data.data || response.data;

    // Validate response
    if (!data) {
      throw new Error('Ma\'lumot topilmadi');
    }

    summaryCards.value = data.summary_cards || [];
    kpiTable.value = data.kpi_table || { sections: [] };
    performanceOverview.value = data.performance_overview || null;
    recommendations.value = data.recommendations || [];
    lastUpdated.value = new Date();

    // Business and industry info
    if (data.business) {
      businessName.value = data.business.name || 'Biznes';
      industryName.value = data.business.industry_name || 'Umumiy Biznes';
      industryIcon.value = data.business.industry_icon || '📊';
      categoryInfo.value = data.business.category || data.business.original_industry || '';
    } else {
      industryName.value = data.industry_name || 'Biznes';
    }

    // Show success toast
    if (showToast) {
      toast.success('Ma\'lumotlar muvaffaqiyatli yangilandi');
    }
  } catch (err) {
    console.error('Failed to load KPI dashboard:', err);

    // Set user-friendly error message
    error.value = {
      title: 'Xatolik Yuz Berdi',
      message: err.response?.data?.message ||
               err.message ||
               'KPI ma\'lumotlarini yuklashda xatolik yuz berdi. Iltimos, qaytadan urinib ko\'ring.',
      details: import.meta.env.DEV ? JSON.stringify(err.response?.data || err, null, 2) : null,
    };

    // Show error toast
    toast.error(error.value.message);
  } finally {
    loading.value = false;
  }
};

// Retry handler
const retryLoad = () => {
  error.value = null;
  loadDashboard(true);
};

// Watch for business changes
watch(businessId, (newId, oldId) => {
  if (newId && newId !== oldId) {
    loadDashboard();
  }
});

const getOverallProgressColor = (percentage) => {
  if (percentage >= 80) return 'bg-green-500';
  if (percentage >= 60) return 'bg-yellow-500';
  return 'bg-red-500';
};

const getPriorityBadgeClass = (priority) => {
  const priorityMap = {
    'Yuqori': 'bg-red-100 text-red-800',
    'O\'rta': 'bg-yellow-100 text-yellow-800',
    'Past': 'bg-blue-100 text-blue-800',
  };
  return priorityMap[priority] || 'bg-gray-100 text-gray-800';
};

// Format time for "last updated"
const formatTime = (date) => {
  if (!date) return '';

  const now = new Date();
  const diff = Math.floor((now - date) / 1000); // seconds

  if (diff < 60) return `${diff} soniya oldin`;
  if (diff < 3600) return `${Math.floor(diff / 60)} daqiqa oldin`;
  if (diff < 86400) return `${Math.floor(diff / 3600)} soat oldin`;

  // Format as HH:MM
  const hours = date.getHours().toString().padStart(2, '0');
  const minutes = date.getMinutes().toString().padStart(2, '0');
  return `${hours}:${minutes}`;
};

// Export dashboard data to Excel/CSV
const exportDashboard = async () => {
  try {
    toast.info('Eksport tayyorlanmoqda...');

    // Prepare data for export
    const exportData = {
      business: businessName.value,
      industry: industryName.value,
      period: `${selectedPeriod.value} kun`,
      summary_cards: summaryCards.value,
      kpi_table: kpiTable.value,
      performance_overview: performanceOverview.value,
      recommendations: recommendations.value,
      exported_at: new Date().toISOString(),
    };

    // Convert to CSV format
    const csvContent = generateCSV(exportData);

    // Download file
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', `kpi-dashboard-${businessName.value}-${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    toast.success('Eksport muvaffaqiyatli amalga oshirildi');
  } catch (error) {
    console.error('Export error:', error);
    toast.error('Eksport qilishda xatolik yuz berdi');
  }
};

// Generate CSV from data
const generateCSV = (data) => {
  let csv = `KPI Boshqaruv Paneli - ${data.business}\n`;
  csv += `Soha: ${data.industry}\n`;
  csv += `Davr: ${data.period}\n`;
  csv += `Eksport vaqti: ${new Date().toLocaleString()}\n\n`;

  // Summary Cards
  csv += `UMUMIY KO'RSATKICHLAR\n`;
  csv += `KPI,Joriy Qiymat,Maqsad,Bajarilish %,Holat\n`;
  data.summary_cards.forEach(card => {
    csv += `"${card.name}","${card.value}","${card.target}",${card.performance_percent},"${card.performance_status}"\n`;
  });

  csv += `\n`;

  // KPI Table
  csv += `BATAFSIL KPI'LAR\n`;
  data.kpi_table.sections?.forEach(section => {
    csv += `\n${section.name}\n`;
    csv += `KPI,Joriy,Maqsad,Bajarilish %,Holat\n`;
    section.rows?.forEach(row => {
      csv += `"${row.name}","${row.current_value}","${row.target_value}",${row.performance_percent},"${row.performance_status}"\n`;
    });
  });

  return csv;
};

onMounted(() => {
  loadDashboard();
});
</script>
