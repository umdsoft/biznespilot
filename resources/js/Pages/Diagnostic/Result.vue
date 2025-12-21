<template>
  <Head :title="`Diagnostika #${diagnostic.version}`" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <div class="flex items-center space-x-2 text-sm text-gray-500 mb-1">
            <Link href="/business/diagnostic" class="hover:text-gray-700">Diagnostika</Link>
            <ChevronRightIcon class="w-4 h-4" />
            <span>#{{ diagnostic.version }}</span>
          </div>
          <h1 class="text-2xl font-bold text-gray-900">Diagnostika Natijasi</h1>
          <p class="text-gray-500 mt-1">{{ diagnostic.completed_at }}</p>
        </div>

        <div class="flex items-center space-x-3">
          <button
            @click="downloadReport"
            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center text-sm"
          >
            <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
            Hisobotni yuklab olish
          </button>
          <Link
            href="/business/diagnostic"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm"
          >
            Yangi diagnostika
          </Link>
        </div>
      </div>

      <!-- Main content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left column - Scores -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Overall score -->
          <div class="bg-white rounded-lg border p-6">
            <h3 class="font-semibold text-gray-900 mb-4 text-center">Umumiy Ball</h3>
            <div class="flex justify-center">
              <HealthScoreGauge
                :score="diagnostic.overall_score"
                :size="180"
                :trend="diagnostic.trend_data?.overall"
                :animate="true"
              />
            </div>

            <!-- Trend info -->
            <div
              v-if="diagnostic.trend_data?.overall"
              class="mt-4 p-3 rounded-lg text-center"
              :class="trendBgClass"
            >
              <span class="text-sm" :class="trendTextClass">
                {{ diagnostic.trend_data.overall.label }}
              </span>
            </div>
          </div>

          <!-- Category scores -->
          <div class="space-y-3">
            <CategoryScoreCard
              v-for="(score, category) in diagnostic.category_scores"
              :key="category"
              :category="category"
              :score="score"
              :trend="diagnostic.trend_data?.categories?.[category]"
            />
          </div>

          <!-- Benchmark summary -->
          <div v-if="diagnostic.benchmark_summary" class="bg-white rounded-lg border p-4">
            <h4 class="font-medium text-gray-900 mb-3">Benchmark Xulosa</h4>
            <div class="space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Ajoyib</span>
                <span class="text-blue-600 font-medium">{{ diagnostic.benchmark_summary.excellent }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Yaxshi</span>
                <span class="text-green-600 font-medium">{{ diagnostic.benchmark_summary.good }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">O'rtacha</span>
                <span class="text-yellow-600 font-medium">{{ diagnostic.benchmark_summary.average }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Zaif</span>
                <span class="text-red-600 font-medium">{{ diagnostic.benchmark_summary.poor }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right column - Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- AI Insights -->
          <div v-if="diagnostic.ai_insights" class="bg-white rounded-lg border p-6">
            <div class="flex items-center space-x-2 mb-4">
              <SparklesIcon class="w-5 h-5 text-indigo-500" />
              <h3 class="font-semibold text-gray-900">AI Tahlili</h3>
            </div>
            <div class="prose prose-sm max-w-none text-gray-700">
              <p class="whitespace-pre-line">{{ diagnostic.ai_insights }}</p>
            </div>
          </div>

          <!-- SWOT Analysis -->
          <SWOTCard :swot="diagnostic.swot" />

          <!-- Recommendations -->
          <RecommendationList :recommendations="diagnostic.recommendations" />

          <!-- Questions link -->
          <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg border border-indigo-200 p-6">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-semibold text-gray-900">AI Savollari</h3>
                <p class="text-sm text-gray-600 mt-1">
                  AI sizdan qo'shimcha ma'lumot so'ramoqda. Javob berib, tahlilni yaxshilang.
                </p>
              </div>
              <Link
                :href="`/business/diagnostic/${diagnostic.id}/questions`"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm flex items-center"
              >
                Savollarga o'tish
                <ArrowRightIcon class="w-4 h-4 ml-2" />
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useDiagnosticStore } from '@/stores/diagnostic';
import HealthScoreGauge from '@/Components/diagnostic/HealthScoreGauge.vue';
import CategoryScoreCard from '@/Components/diagnostic/CategoryScoreCard.vue';
import SWOTCard from '@/Components/diagnostic/SWOTCard.vue';
import RecommendationList from '@/Components/diagnostic/RecommendationList.vue';
import {
  ChevronRightIcon,
  DocumentArrowDownIcon,
  SparklesIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  diagnostic: {
    type: Object,
    required: true,
  },
  questions: {
    type: Array,
    default: () => [],
  },
  kpis: {
    type: Object,
    default: null,
  },
});

const store = useDiagnosticStore();

const trendBgClass = computed(() => {
  const trend = props.diagnostic.trend_data?.overall?.trend;
  if (trend === 'up') return 'bg-green-50';
  if (trend === 'down') return 'bg-red-50';
  return 'bg-gray-50';
});

const trendTextClass = computed(() => {
  const trend = props.diagnostic.trend_data?.overall?.trend;
  if (trend === 'up') return 'text-green-700';
  if (trend === 'down') return 'text-red-700';
  return 'text-gray-700';
});

async function downloadReport() {
  try {
    await store.downloadReport(props.diagnostic.id);
  } catch (error) {
    console.error('Failed to download report:', error);
  }
}
</script>
