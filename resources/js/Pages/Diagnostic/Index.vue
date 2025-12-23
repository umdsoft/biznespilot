<template>
  <Head title="AI Diagnostika" />

  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/50 to-indigo-50">
    <!-- Header - Same as Onboarding -->
    <header class="bg-white/80 backdrop-blur-xl border-b border-gray-200/50 sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center gap-4">
            <Link href="/onboarding" class="flex items-center gap-4 hover:opacity-80 transition-opacity">
              <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/25">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <div>
                <h1 class="text-lg font-bold text-gray-900">BiznesPilot AI</h1>
                <p class="text-xs text-gray-500">Diagnostika</p>
              </div>
            </Link>
          </div>
          <div class="flex items-center gap-4">
            <Link
              href="/onboarding"
              class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
            >
              <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Onboardingga qaytish
              </span>
            </Link>
            <div class="text-right hidden sm:block">
              <p class="text-sm font-medium text-gray-900">{{ businessName }}</p>
              <p class="text-xs text-gray-500">{{ userName }}</p>
            </div>
            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-full flex items-center justify-center">
              <span class="text-sm font-bold text-blue-600">{{ userInitial }}</span>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Hero Section -->
      <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 rounded-3xl p-8 sm:p-10 mb-8 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
          <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full -translate-y-1/2 translate-x-1/2"></div>
          <div class="absolute bottom-0 left-0 w-48 h-48 bg-white rounded-full translate-y-1/2 -translate-x-1/2"></div>
        </div>

        <div class="relative flex flex-col lg:flex-row items-center justify-between gap-8">
          <div class="text-center lg:text-left flex-1">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full mb-4">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <span class="text-sm font-medium text-white">Claude AI Tahlil</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-bold text-white mb-3">
              Biznes Diagnostikasi
            </h1>
            <p class="text-indigo-100 text-lg max-w-xl">
              AI yordamida biznesingizning kuchli va zaif tomonlarini aniqlang,
              90 kunlik harakat rejasini oling
            </p>
          </div>
          <div class="flex-shrink-0">
            <div class="text-center">
              <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full mb-2">
                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                <span class="text-sm font-medium text-white">{{ onboardingProgress }}% ma'lumot tayyor</span>
              </div>
              <div v-if="claudeAvailable" class="text-xs text-indigo-200">Claude AI tayyor</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Onboarding Warning -->
      <div v-if="onboardingProgress < 50" class="mb-8">
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-6">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
              <ExclamationTriangleIcon class="w-6 h-6 text-amber-600" />
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-amber-900">Ma'lumotlar yetarli emas</h3>
              <p class="text-amber-700 mt-1">
                AI Diagnostika o'tkazish uchun kamida 50% onboarding ma'lumotlarini to'ldiring.
                Hozirgi progress: <span class="font-bold">{{ onboardingProgress }}%</span>
              </p>
              <div class="mt-4 flex flex-wrap gap-3">
                <Link
                  href="/onboarding"
                  class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 text-white font-medium rounded-xl hover:bg-amber-700 transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  Ma'lumotlarni to'ldirish
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ready to Start -->
      <template v-else>
        <!-- No Previous Diagnostic - Start New -->
        <div v-if="!latestDiagnostic" class="mb-8">
          <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="p-8 sm:p-10">
              <div class="flex flex-col lg:flex-row items-center gap-8">
                <div class="flex-1 text-center lg:text-left">
                  <div class="inline-flex items-center gap-2 px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Tayyor
                  </div>
                  <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">
                    Birinchi Diagnostikangizni Boshlang
                  </h2>
                  <p class="text-gray-600 text-lg mb-6 max-w-lg">
                    Claude AI biznesingizni 360° tahlil qiladi va sizga 90 kunlik harakat rejasi beradi.
                    Jarayon 2-3 daqiqa davom etadi.
                  </p>

                  <!-- Features -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <BanknotesIcon class="w-5 h-5 text-red-600" />
                      </div>
                      <span class="text-gray-700">Pul yo'qotish tahlili</span>
                    </div>
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <RocketLaunchIcon class="w-5 h-5 text-green-600" />
                      </div>
                      <span class="text-gray-700">90 kunlik natija prognozi</span>
                    </div>
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <TrophyIcon class="w-5 h-5 text-purple-600" />
                      </div>
                      <span class="text-gray-700">O'xshash bizneslar tajribasi</span>
                    </div>
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <ClipboardDocumentListIcon class="w-5 h-5 text-indigo-600" />
                      </div>
                      <span class="text-gray-700">Qadam-baqadam harakat rejasi</span>
                    </div>
                  </div>

                  <button
                    @click="startDiagnostic"
                    :disabled="loading || !claudeAvailable"
                    class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg shadow-indigo-500/25 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <SparklesIcon class="w-6 h-6" />
                    <span>Diagnostikani Boshlash</span>
                    <svg v-if="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </button>

                  <p v-if="!claudeAvailable" class="mt-3 text-sm text-red-500">
                    AI xizmati hozirda mavjud emas
                  </p>
                </div>

                <div class="flex-shrink-0 hidden lg:block">
                  <div class="w-64 h-64 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-3xl flex items-center justify-center">
                    <svg class="w-32 h-32 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Has Latest Diagnostic -->
        <template v-else>
          <!-- Status Level and Score Header -->
          <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 sm:p-8 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
              <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                  <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-gray-900">
                      Diagnostika #{{ latestDiagnostic.version }}
                    </h2>
                    <p class="text-sm text-gray-500">{{ latestDiagnostic.completed_at }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <StatusLevelBadge
                    v-if="latestDiagnostic.status_level"
                    :level="latestDiagnostic.status_level"
                  />
                  <Link
                    :href="`/business/diagnostic/${latestDiagnostic.id}`"
                    class="px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition-colors"
                  >
                    Batafsil ko'rish
                  </Link>
                  <button
                    @click="startDiagnostic"
                    :disabled="loading"
                    class="px-5 py-2.5 border-2 border-indigo-200 text-indigo-600 font-medium rounded-xl hover:bg-indigo-50 transition-colors disabled:opacity-50"
                  >
                    Yangi diagnostika
                  </button>
                </div>
              </div>
            </div>

            <div class="p-6 sm:p-8">
              <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
                <!-- Overall Score -->
                <div class="lg:col-span-1 flex justify-center">
                  <HealthScoreGauge
                    :score="latestDiagnostic.overall_score"
                    :size="160"
                    :animate="true"
                  />
                </div>

                <!-- Category Scores -->
                <div class="lg:col-span-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                  <CategoryScoreCard
                    v-for="(score, category) in latestDiagnostic.category_scores"
                    :key="category"
                    :category="category"
                    :score="score"
                  />
                </div>
              </div>

              <!-- Status Message -->
              <div v-if="latestDiagnostic.status_message" class="mt-6 p-4 bg-gray-50 rounded-xl">
                <p class="text-gray-700">{{ latestDiagnostic.status_message }}</p>
              </div>
            </div>
          </div>

          <!-- Money Loss + Expected Results Row -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Money Loss Card -->
            <MoneyLossCard
              v-if="latestDiagnostic.money_loss"
              :money-loss="latestDiagnostic.money_loss"
            />

            <!-- Expected Results Card -->
            <ExpectedResultsCard
              v-if="latestDiagnostic.expected_results"
              :expected-results="latestDiagnostic.expected_results"
            />
          </div>

          <!-- Action Plan Card -->
          <div class="mb-8">
            <ActionPlanCard
              v-if="latestDiagnostic.action_plan"
              :action-plan="latestDiagnostic.action_plan"
              :max-steps="5"
            />
          </div>

          <!-- ROI Calculator -->
          <div v-if="latestDiagnostic.roi_calculations" class="mb-8">
            <ROICalculator :data="latestDiagnostic.roi_calculations" />
          </div>

          <!-- Cause Effect Matrix -->
          <div v-if="latestDiagnostic.cause_effect_matrix?.length" class="mb-8">
            <CauseEffectMatrix :data="latestDiagnostic.cause_effect_matrix" />
          </div>

          <!-- Quick Strategies -->
          <div v-if="latestDiagnostic.quick_strategies" class="mb-8">
            <QuickStrategies :data="latestDiagnostic.quick_strategies" />
          </div>

          <!-- Quick Actions -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <Link
              :href="`/business/diagnostic/${latestDiagnostic.id}/questions`"
              class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:border-purple-200 transition-all group"
            >
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                  <ChatBubbleLeftRightIcon class="w-7 h-7 text-purple-600" />
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 group-hover:text-purple-700 transition-colors">AI Savollari</h3>
                  <p class="text-sm text-gray-500">Qo'shimcha ma'lumot bering</p>
                </div>
              </div>
            </Link>

            <Link
              href="/business/diagnostic/history"
              class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:border-blue-200 transition-all group"
            >
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                  <ClockIcon class="w-7 h-7 text-blue-600" />
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors">Tarix</h3>
                  <p class="text-sm text-gray-500">Oldingi diagnostikalar</p>
                </div>
              </div>
            </Link>

            <button
              @click="downloadReport(latestDiagnostic.id)"
              class="bg-white rounded-2xl border border-gray-100 p-6 hover:shadow-lg hover:border-green-200 transition-all group text-left w-full"
            >
              <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-200 transition-colors">
                  <DocumentArrowDownIcon class="w-7 h-7 text-green-600" />
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 group-hover:text-green-700 transition-colors">Hisobot</h3>
                  <p class="text-sm text-gray-500">PDF yuklab olish</p>
                </div>
              </div>
            </button>
          </div>
        </template>

        <!-- History Section -->
        <div v-if="history?.length > 1" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Diagnostika tarixi</h3>
          </div>
          <div class="divide-y divide-gray-100">
            <Link
              v-for="diagnostic in history"
              :key="diagnostic.id"
              :href="`/business/diagnostic/${diagnostic.id}`"
              class="p-5 flex items-center justify-between hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-4">
                <div
                  class="w-12 h-12 rounded-xl flex items-center justify-center font-bold"
                  :class="scoreColorClass(diagnostic.overall_score)"
                >
                  {{ diagnostic.overall_score }}
                </div>
                <div>
                  <span class="font-medium text-gray-900">Diagnostika #{{ diagnostic.version }}</span>
                  <p class="text-sm text-gray-500">{{ diagnostic.completed_at }}</p>
                </div>
              </div>
              <ChevronRightIcon class="w-5 h-5 text-gray-400" />
            </Link>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDiagnosticStore } from '@/stores/diagnostic';
import HealthScoreGauge from '@/Components/diagnostic/HealthScoreGauge.vue';
import CategoryScoreCard from '@/Components/diagnostic/CategoryScoreCard.vue';
import MoneyLossCard from '@/Components/diagnostic/MoneyLossCard.vue';
import ActionPlanCard from '@/Components/diagnostic/ActionPlanCard.vue';
import ExpectedResultsCard from '@/Components/diagnostic/ExpectedResultsCard.vue';
import StatusLevelBadge from '@/Components/diagnostic/StatusLevelBadge.vue';
import ROICalculator from './Components/ROICalculator.vue';
import CauseEffectMatrix from './Components/CauseEffectMatrix.vue';
import QuickStrategies from './Components/QuickStrategies.vue';
import {
  SparklesIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon,
  DocumentArrowDownIcon,
  ChevronRightIcon,
  ExclamationTriangleIcon,
  BanknotesIcon,
  RocketLaunchIcon,
  TrophyIcon,
  ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  latestDiagnostic: Object,
  history: Array,
  canStart: Object,
  onboardingProgress: {
    type: Number,
    default: 0,
  },
  claudeAvailable: {
    type: Boolean,
    default: false,
  },
});

// Page data
const page = usePage();
const businessName = computed(() => page.props.currentBusiness?.name || 'Biznes');
const userName = computed(() => page.props.auth?.user?.name || 'Foydalanuvchi');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());

const store = useDiagnosticStore();
const loading = ref(false);

async function startDiagnostic() {
  loading.value = true;
  try {
    const result = await store.startDiagnostic();
    if (result.diagnostic_id) {
      router.visit(`/business/diagnostic/${result.diagnostic_id}/processing`);
    }
  } catch (error) {
    console.error('Failed to start diagnostic:', error);
    alert(error.response?.data?.message || 'Diagnostika boshlab bo\'lmadi. Qayta urinib ko\'ring.');
  } finally {
    loading.value = false;
  }
}

async function downloadReport(diagnosticId) {
  try {
    await store.downloadReport(diagnosticId);
  } catch (error) {
    console.error('Failed to download report:', error);
  }
}

function scoreColorClass(score) {
  if (score >= 80) return 'bg-blue-100 text-blue-700';
  if (score >= 60) return 'bg-green-100 text-green-700';
  if (score >= 40) return 'bg-yellow-100 text-yellow-700';
  return 'bg-red-100 text-red-700';
}
</script>
