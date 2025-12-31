<template>
  <Head title="Algoritm Tahlili" />

  <BusinessLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Algoritm Tahlili</h2>
          <p class="text-sm text-gray-500 mt-1">AI'siz - Tez va aniq algoritmik tahlil</p>
        </div>
        <button
          @click="refreshData"
          :disabled="isRefreshing"
          class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
        >
          <ArrowPathIcon :class="['w-5 h-5 mr-2', isRefreshing && 'animate-spin']" />
          Yangilash
        </button>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Health Score -->
          <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Salomatlik balli</p>
                <p class="text-3xl font-bold mt-1" :class="getScoreColor(overview.health_score?.score)">
                  {{ overview.health_score?.score || 0 }}
                </p>
              </div>
              <div :class="['p-3 rounded-full', getScoreBgColor(overview.health_score?.score)]">
                <HeartIcon class="w-6 h-6 text-white" />
              </div>
            </div>
            <p class="text-sm mt-2" :class="getScoreTextColor(overview.health_score?.status?.level)">
              {{ overview.health_score?.status?.label || 'Hisoblanmoqda...' }}
            </p>
          </div>

          <!-- Data Accuracy -->
          <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Ma'lumot aniqligi</p>
                <p class="text-3xl font-bold mt-1 text-blue-600">
                  {{ overview.data_accuracy?.score || 0 }}%
                </p>
              </div>
              <div class="p-3 rounded-full bg-blue-500">
                <ShieldCheckIcon class="w-6 h-6 text-white" />
              </div>
            </div>
            <p class="text-sm mt-2 text-blue-600">
              {{ overview.data_accuracy?.label || 'Tekshirilmoqda...' }}
            </p>
          </div>

          <!-- Data Quality -->
          <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Ma'lumot sifati</p>
                <p class="text-3xl font-bold mt-1 text-purple-600">
                  {{ overview.data_quality?.grade || '-' }}
                </p>
              </div>
              <div class="p-3 rounded-full bg-purple-500">
                <ChartBarIcon class="w-6 h-6 text-white" />
              </div>
            </div>
            <p class="text-sm mt-2 text-gray-500">
              {{ overview.data_quality?.overall || 0 }}% to'liqlik
            </p>
          </div>

          <!-- Prediction Confidence -->
          <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-500">Bashorat ishonchligi</p>
                <p class="text-3xl font-bold mt-1 text-amber-600">
                  {{ overview.prediction_confidence?.score || 0 }}%
                </p>
              </div>
              <div class="p-3 rounded-full bg-amber-500">
                <LightBulbIcon class="w-6 h-6 text-white" />
              </div>
            </div>
            <p class="text-sm mt-2" :class="getConfidenceColor(overview.prediction_confidence?.level)">
              {{ overview.prediction_confidence?.label || 'Hisoblanmoqda...' }}
            </p>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Left Column: Predictions -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Next Steps Predictions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
              <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-semibold text-gray-900">Keyingi Qadamlar</h3>
                  <span class="text-sm text-gray-500">{{ predictions.length }} ta tavsiya</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Algoritmik tahlil asosida eng muhim harakatlar</p>
              </div>

              <div class="divide-y divide-gray-100">
                <div
                  v-for="(prediction, index) in predictions"
                  :key="prediction.key"
                  class="p-4 hover:bg-gray-50 transition-colors"
                >
                  <div class="flex items-start gap-4">
                    <div
                      :class="[
                        'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm',
                        getPriorityBgColor(prediction.priority)
                      ]"
                    >
                      {{ prediction.priority }}
                    </div>

                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="font-medium text-gray-900">{{ prediction.title }}</h4>
                        <span
                          :class="[
                            'px-2 py-0.5 text-xs rounded-full',
                            getImpactBadgeColor(prediction.impact)
                          ]"
                        >
                          {{ getImpactLabel(prediction.impact) }}
                        </span>
                        <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
                          {{ prediction.module_label }}
                        </span>
                      </div>

                      <p class="text-sm text-gray-600 mt-1">{{ prediction.reason }}</p>

                      <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                        <span class="flex items-center gap-1">
                          <ClockIcon class="w-4 h-4" />
                          {{ prediction.timeframe }}
                        </span>
                        <span class="flex items-center gap-1">
                          <BoltIcon class="w-4 h-4" />
                          Mehnat: {{ getEffortLabel(prediction.effort) }}
                        </span>
                      </div>

                      <!-- Steps (collapsible) -->
                      <details class="mt-3">
                        <summary class="text-sm text-indigo-600 cursor-pointer hover:text-indigo-800">
                          Qadamlarni ko'rish ({{ prediction.steps?.length || 0 }})
                        </summary>
                        <ol class="mt-2 pl-4 space-y-1 text-sm text-gray-600 list-decimal">
                          <li v-for="(step, i) in prediction.steps" :key="i">
                            {{ step }}
                          </li>
                        </ol>
                      </details>

                      <!-- Expected Outcome -->
                      <div class="mt-2 p-2 bg-green-50 rounded-lg">
                        <p class="text-xs text-green-700">
                          <strong>Kutilayotgan natija:</strong> {{ prediction.expected_outcome }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="predictions.length === 0" class="p-8 text-center text-gray-500">
                  <LightBulbIcon class="w-12 h-12 mx-auto text-gray-300 mb-4" />
                  <p>Tavsiyalar hisoblash uchun ma'lumotlar yetarli emas</p>
                </div>
              </div>
            </div>

            <!-- Quick Wins -->
            <div v-if="quickWins.length > 0" class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl shadow-sm p-6 text-white">
              <h3 class="text-lg font-semibold flex items-center gap-2">
                <BoltIcon class="w-5 h-5" />
                Tez Natijalar (Quick Wins)
              </h3>
              <p class="text-sm opacity-90 mt-1">Kam mehnat, yuqori ta'sir - darhol boshlanadigan ishlar</p>

              <div class="mt-4 space-y-3">
                <div
                  v-for="qw in quickWins"
                  :key="qw.key"
                  class="bg-white/10 rounded-lg p-3"
                >
                  <div class="flex items-center justify-between">
                    <span class="font-medium">{{ qw.title }}</span>
                    <span class="text-sm opacity-80">{{ qw.timeframe }}</span>
                  </div>
                  <p class="text-sm opacity-80 mt-1">{{ qw.reason }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column: Module Analysis -->
          <div class="space-y-6">
            <!-- Modules Health -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
              <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Modullar Holati</h3>
              </div>

              <div class="p-4 space-y-4">
                <div
                  v-for="(module, key) in modules"
                  :key="key"
                  class="p-3 bg-gray-50 rounded-lg"
                >
                  <div class="flex items-center justify-between mb-2">
                    <span class="font-medium text-gray-900">{{ module.label }}</span>
                    <span
                      :class="['text-sm font-bold', getScoreColor(module.health_score)]"
                    >
                      {{ module.health_score }}
                    </span>
                  </div>

                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div
                      :class="['h-2 rounded-full', getProgressColor(module.health_score)]"
                      :style="{ width: module.health_score + '%' }"
                    ></div>
                  </div>

                  <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                    <span>Ma'lumot: {{ module.data_completeness }}%</span>
                    <span v-if="module.trend">
                      Trend: {{ module.trend?.direction === 'up' ? '📈' : (module.trend?.direction === 'down' ? '📉' : '➡️') }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Cross-Module Insights -->
            <div v-if="crossModuleInsights.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100">
              <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Modullar Aro Tahlil</h3>
              </div>

              <div class="p-4 space-y-3">
                <div
                  v-for="(insight, index) in crossModuleInsights"
                  :key="index"
                  :class="[
                    'p-3 rounded-lg text-sm',
                    getInsightBgColor(insight.type)
                  ]"
                >
                  <div class="flex items-start gap-2">
                    <span>{{ getInsightIcon(insight.type) }}</span>
                    <p>{{ insight.message }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Data Recommendations -->
            <div v-if="dataRecommendations.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100">
              <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Ma'lumotlar Tavsiyalari</h3>
              </div>

              <div class="p-4 space-y-3">
                <div
                  v-for="(rec, index) in dataRecommendations"
                  :key="index"
                  class="flex items-start gap-3 text-sm"
                >
                  <span
                    :class="[
                      'flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs text-white',
                      rec.priority === 'high' ? 'bg-red-500' : 'bg-yellow-500'
                    ]"
                  >
                    {{ index + 1 }}
                  </span>
                  <div>
                    <p class="text-gray-900">{{ rec.recommendation }}</p>
                    <p class="text-gray-500 text-xs mt-1">{{ rec.impact }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
  HeartIcon,
  ShieldCheckIcon,
  ChartBarIcon,
  LightBulbIcon,
  ArrowPathIcon,
  ClockIcon,
  BoltIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
  business: Object,
  overview: Object,
  modules: Object,
  predictions: Array,
  quickWins: Array,
  crossModuleInsights: Array,
  dataRecommendations: Array,
});

const isRefreshing = ref(false);

async function refreshData() {
  isRefreshing.value = true;
  try {
    await axios.post('/business/algorithm/api/refresh');
    router.reload();
  } catch (error) {
    console.error('Refresh error:', error);
  } finally {
    isRefreshing.value = false;
  }
}

function getScoreColor(score) {
  if (score >= 80) return 'text-emerald-600';
  if (score >= 60) return 'text-green-600';
  if (score >= 40) return 'text-yellow-600';
  return 'text-red-600';
}

function getScoreBgColor(score) {
  if (score >= 80) return 'bg-emerald-500';
  if (score >= 60) return 'bg-green-500';
  if (score >= 40) return 'bg-yellow-500';
  return 'bg-red-500';
}

function getScoreTextColor(level) {
  const colors = {
    excellent: 'text-emerald-600',
    good: 'text-green-600',
    average: 'text-yellow-600',
    poor: 'text-red-600',
  };
  return colors[level] || 'text-gray-600';
}

function getProgressColor(score) {
  if (score >= 80) return 'bg-emerald-500';
  if (score >= 60) return 'bg-green-500';
  if (score >= 40) return 'bg-yellow-500';
  return 'bg-red-500';
}

function getConfidenceColor(level) {
  const colors = {
    high: 'text-emerald-600',
    medium: 'text-amber-600',
    low: 'text-red-600',
  };
  return colors[level] || 'text-gray-600';
}

function getPriorityBgColor(priority) {
  if (priority === 1) return 'bg-red-500';
  if (priority === 2) return 'bg-orange-500';
  if (priority === 3) return 'bg-yellow-500';
  if (priority === 4) return 'bg-blue-500';
  return 'bg-gray-500';
}

function getImpactBadgeColor(impact) {
  const colors = {
    critical: 'bg-red-100 text-red-700',
    high: 'bg-orange-100 text-orange-700',
    medium: 'bg-blue-100 text-blue-700',
    low: 'bg-gray-100 text-gray-700',
  };
  return colors[impact] || 'bg-gray-100 text-gray-700';
}

function getImpactLabel(impact) {
  const labels = {
    critical: 'Juda muhim',
    high: 'Yuqori',
    medium: "O'rtacha",
    low: 'Past',
  };
  return labels[impact] || impact;
}

function getEffortLabel(effort) {
  const labels = {
    high: 'Yuqori',
    medium: "O'rtacha",
    low: 'Past',
  };
  return labels[effort] || effort;
}

function getInsightBgColor(type) {
  const colors = {
    success: 'bg-green-50 text-green-800',
    warning: 'bg-yellow-50 text-yellow-800',
    opportunity: 'bg-blue-50 text-blue-800',
  };
  return colors[type] || 'bg-gray-50 text-gray-800';
}

function getInsightIcon(type) {
  const icons = {
    success: '✅',
    warning: '⚠️',
    opportunity: '💡',
  };
  return icons[type] || '📌';
}
</script>
