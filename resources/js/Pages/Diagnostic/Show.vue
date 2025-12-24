<template>
  <Head :title="`Diagnostika #${diagnostic.version}`" />

  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/50 to-indigo-50">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-xl border-b border-gray-200/50 sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center gap-4">
            <Link href="/business/diagnostic" class="flex items-center gap-4 hover:opacity-80 transition-opacity">
              <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/25">
                <ChartBarIcon class="w-6 h-6 text-white" />
              </div>
              <div>
                <h1 class="text-lg font-bold text-gray-900">Diagnostika #{{ diagnostic.version }}</h1>
                <p class="text-xs text-gray-500">{{ diagnostic.completed_at }}</p>
              </div>
            </Link>
          </div>
          <div class="flex items-center gap-3">
            <button
              @click="downloadReport"
              :disabled="downloading"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center text-sm disabled:opacity-50"
            >
              <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
              {{ downloading ? 'Yuklanmoqda...' : 'Hisobotni yuklab olish' }}
            </button>
            <Link
              href="/business/diagnostic"
              class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm"
            >
              Yangi diagnostika
            </Link>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Executive Summary -->
      <ExecutiveSummary :diagnostic="diagnostic" />

      <!-- Navigation Tabs -->
      <DiagnosticsNavigation
        v-model:activeTab="activeTab"
        :tabs="tabs"
      />

      <!-- Tab Content -->
      <div class="transition-all duration-300">
        <!-- Financial Analysis -->
        <div v-show="activeTab === 'financial'">
          <FinancialAnalysis
            v-if="diagnostic.money_loss_analysis"
            :money-loss="diagnostic.money_loss_analysis"
            @navigate="navigateToModule"
          />
          <EmptyState
            v-else
            title="Moliyaviy tahlil mavjud emas"
            description="Diagnostika ma'lumotlarida pul yo'qotish tahlili topilmadi"
            icon="CurrencyDollarIcon"
          />
        </div>

        <!-- Action Plan -->
        <div v-show="activeTab === 'action'">
          <ActionPlan
            v-if="diagnostic.action_plan"
            :action-plan="diagnostic.action_plan"
            @start-action="startAction"
          />
          <EmptyState
            v-else
            title="Harakat rejasi mavjud emas"
            description="Diagnostika ma'lumotlarida harakat rejasi topilmadi"
            icon="ClipboardDocumentListIcon"
          />
        </div>

        <!-- Platform Recommendations -->
        <div v-show="activeTab === 'platform'">
          <PlatformRecommendations
            v-if="diagnostic.platform_recommendations?.length"
            :recommendations="diagnostic.platform_recommendations"
            :videos="diagnostic.recommended_videos"
            @navigate="navigateToModule"
          />
          <EmptyState
            v-else
            title="Platforma tavsiyalari mavjud emas"
            description="Diagnostika ma'lumotlarida platforma tavsiyalari topilmadi"
            icon="CubeIcon"
          />
        </div>

        <!-- Customer Analysis -->
        <div v-show="activeTab === 'customer'">
          <CustomerAnalysis
            v-if="diagnostic.ideal_customer_analysis"
            :customer="diagnostic.ideal_customer_analysis"
          />
          <EmptyState
            v-else
            title="Mijoz tahlili mavjud emas"
            description="Ideal mijoz tahlili topilmadi"
            icon="UserGroupIcon"
          />
        </div>

        <!-- Offer Strength -->
        <div v-show="activeTab === 'offer'">
          <OfferStrength
            v-if="diagnostic.offer_strength"
            :offer="diagnostic.offer_strength"
          />
          <EmptyState
            v-else
            title="Taklif tahlili mavjud emas"
            description="Taklif kuchi tahlili topilmadi"
            icon="GiftIcon"
          />
        </div>

        <!-- Channels Analysis -->
        <div v-show="activeTab === 'channels'">
          <ChannelsAnalysis
            v-if="diagnostic.channels_analysis"
            :channels="diagnostic.channels_analysis"
          />
          <EmptyState
            v-else
            title="Kanallar tahlili mavjud emas"
            description="Marketing kanallari tahlili topilmadi"
            icon="MegaphoneIcon"
          />
        </div>

        <!-- Funnel Analysis -->
        <div v-show="activeTab === 'funnel'">
          <FunnelAnalysis
            v-if="diagnostic.funnel_analysis"
            :funnel="diagnostic.funnel_analysis"
          />
          <EmptyState
            v-else
            title="Funnel tahlili mavjud emas"
            description="Sotuv voronkasi tahlili topilmadi"
            icon="FunnelIcon"
          />
        </div>

        <!-- Risks -->
        <div v-show="activeTab === 'risks'">
          <RisksAnalysis
            v-if="diagnostic.risks"
            :risks="diagnostic.risks"
          />
          <EmptyState
            v-else
            title="Xavflar tahlili mavjud emas"
            description="Xavflar va imkoniyatlar tahlili topilmadi"
            icon="ExclamationTriangleIcon"
          />
        </div>

        <!-- SWOT -->
        <div v-show="activeTab === 'swot'">
          <SWOTAnalysis
            v-if="diagnostic.swot"
            :swot="diagnostic.swot"
          />
          <EmptyState
            v-else
            title="SWOT tahlili mavjud emas"
            description="SWOT tahlili topilmadi"
            icon="Squares2X2Icon"
          />
        </div>
      </div>

      <!-- ROI Calculator -->
      <div v-if="diagnostic.roi_calculations" class="mt-8">
        <ROICalculator :data="diagnostic.roi_calculations" />
      </div>

      <!-- Cause Effect Matrix -->
      <div v-if="diagnostic.cause_effect_matrix?.length" class="mt-8">
        <CauseEffectMatrix :data="diagnostic.cause_effect_matrix" />
      </div>

      <!-- Quick Strategies -->
      <div v-if="diagnostic.quick_strategies" class="mt-8">
        <QuickStrategies :data="diagnostic.quick_strategies" />
      </div>

      <!-- Expected Results -->
      <div v-if="diagnostic.expected_results" class="mt-8">
        <ExpectedResults :expected-results="diagnostic.expected_results" />
      </div>

      <!-- Similar Businesses (Legacy - keep for backward compatibility) -->
      <div v-if="diagnostic.similar_businesses && !diagnostic.roi_calculations" class="mt-8">
        <SimilarBusinesses :similar-businesses="diagnostic.similar_businesses" />
      </div>

      <!-- Start Working CTA Section -->
      <div class="mt-8 bg-gradient-to-br from-emerald-500 via-green-500 to-teal-600 rounded-3xl p-8 relative overflow-hidden">
        <!-- Background decorations -->
        <div class="absolute inset-0 opacity-10">
          <div class="absolute top-0 right-0 w-72 h-72 bg-white rounded-full -translate-y-1/3 translate-x-1/3"></div>
          <div class="absolute bottom-0 left-0 w-56 h-56 bg-white rounded-full translate-y-1/3 -translate-x-1/3"></div>
          <div class="absolute top-1/2 left-1/2 w-40 h-40 bg-white rounded-full -translate-x-1/2 -translate-y-1/2"></div>
        </div>

        <div class="relative">
          <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
            <!-- Left content -->
            <div class="flex-1 text-center lg:text-left">
              <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full mb-4">
                <RocketLaunchIcon class="w-5 h-5 text-white" />
                <span class="text-sm font-semibold text-white">Tahlil yakunlandi</span>
              </div>

              <h2 class="text-2xl sm:text-3xl font-bold text-white mb-3">
                Ishni boshlash vaqti keldi!
              </h2>

              <p class="text-emerald-100 text-lg mb-6 max-w-xl">
                Diagnostika natijalariga ko'ra sizning biznesingiz uchun aniq harakat rejasi tayyor.
                Endi Dashboard'ga o'ting va birinchi qadamni qo'ying.
              </p>

              <!-- Quick stats -->
              <div class="flex flex-wrap justify-center lg:justify-start gap-4 mb-6">
                <div class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl">
                  <CheckCircleIcon class="w-5 h-5 text-white" />
                  <span class="text-white font-medium">{{ diagnostic.action_plan?.total_steps || 3 }} qadam tayyor</span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl">
                  <ClockIcon class="w-5 h-5 text-white" />
                  <span class="text-white font-medium">~{{ diagnostic.action_plan?.total_time_hours || 5 }} soat</span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl">
                  <BanknotesIcon class="w-5 h-5 text-white" />
                  <span class="text-white font-medium">+{{ formatPotentialSavings(diagnostic.action_plan?.total_potential_savings) }} potensial</span>
                </div>
              </div>
            </div>

            <!-- Right - CTA buttons -->
            <div class="flex flex-col gap-4">
              <button
                @click="goToDashboard"
                class="group flex items-center justify-center gap-3 px-8 py-4 bg-white text-emerald-600 font-bold text-lg rounded-2xl shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300"
              >
                <PlayIcon class="w-6 h-6" />
                <span>Dashboard'ga o'tish</span>
                <ArrowRightIcon class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
              </button>

              <Link
                v-if="diagnostic.action_plan?.steps?.[0]?.module_route"
                :href="diagnostic.action_plan.steps[0].module_route"
                class="group flex items-center justify-center gap-3 px-8 py-4 bg-white/20 backdrop-blur-sm text-white font-semibold text-lg rounded-2xl border-2 border-white/30 hover:bg-white/30 hover:border-white/50 transition-all duration-300"
              >
                <SparklesIcon class="w-6 h-6" />
                <span>{{ diagnostic.action_plan.steps[0].title || 'Birinchi qadamni boshlash' }}</span>
              </Link>
            </div>
          </div>

          <!-- First step preview -->
          <div v-if="diagnostic.action_plan?.steps?.[0]" class="mt-8 p-6 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="text-2xl font-bold text-emerald-600">1</span>
              </div>
              <div class="flex-1">
                <h4 class="text-white font-semibold text-lg mb-1">
                  {{ diagnostic.action_plan.steps[0].title }}
                </h4>
                <p class="text-emerald-100 text-sm mb-3">
                  {{ diagnostic.action_plan.steps[0].why }}
                </p>
                <div class="flex flex-wrap gap-3">
                  <span class="inline-flex items-center gap-1 px-3 py-1 bg-white/20 rounded-lg text-white text-sm">
                    <ClockIcon class="w-4 h-4" />
                    {{ diagnostic.action_plan.steps[0].time_minutes }} daqiqa
                  </span>
                  <span class="inline-flex items-center gap-1 px-3 py-1 bg-white/20 rounded-lg text-white text-sm">
                    <TrophyIcon class="w-4 h-4" />
                    {{ diagnostic.action_plan.steps[0].similar_business_result }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Meta Info -->
      <div class="mt-8 text-center text-sm text-gray-500">
        <p>
          Diagnostika {{ formatTashkentTime(diagnostic.completed_at_raw) }} sanasida yakunlandi |
          {{ diagnostic.tokens_used?.toLocaleString() }} token ishlatildi |
          {{ diagnostic.generation_time_ms }}ms
        </p>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDiagnosticStore } from '@/stores/diagnostic';

// Components
import DiagnosticsNavigation from './Components/DiagnosticsNavigation.vue';
import ExecutiveSummary from './Components/ExecutiveSummary.vue';
import FinancialAnalysis from './Components/FinancialAnalysis.vue';
import ActionPlan from './Components/ActionPlan.vue';
import PlatformRecommendations from './Components/PlatformRecommendations.vue';
import CustomerAnalysis from './Components/CustomerAnalysis.vue';
import OfferStrength from './Components/OfferStrength.vue';
import ChannelsAnalysis from './Components/ChannelsAnalysis.vue';
import FunnelAnalysis from './Components/FunnelAnalysis.vue';
import RisksAnalysis from './Components/RisksAnalysis.vue';
import SWOTAnalysis from './Components/SWOTAnalysis.vue';
import SimilarBusinesses from './Components/SimilarBusinesses.vue';
import ExpectedResults from './Components/ExpectedResults.vue';
import ROICalculator from './Components/ROICalculator.vue';
import CauseEffectMatrix from './Components/CauseEffectMatrix.vue';
import QuickStrategies from './Components/QuickStrategies.vue';
import EmptyState from './Components/EmptyState.vue';

// Icons
import {
  DocumentArrowDownIcon,
  ChartBarIcon,
  ArrowRightIcon,
  CurrencyDollarIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  UserGroupIcon,
  GiftIcon,
  MegaphoneIcon,
  FunnelIcon,
  ExclamationTriangleIcon,
  Squares2X2Icon,
  PlayIcon,
  CheckCircleIcon,
  RocketLaunchIcon,
  BanknotesIcon,
  ClockIcon,
  TrophyIcon,
  SparklesIcon,
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
});

const store = useDiagnosticStore();
const activeTab = ref('financial');
const downloading = ref(false);

const tabs = [
  { id: 'financial', label: 'Moliyaviy', icon: CurrencyDollarIcon },
  { id: 'action', label: 'Harakat Rejasi', icon: ClipboardDocumentListIcon },
  { id: 'platform', label: 'Platformalar', icon: CubeIcon },
  { id: 'customer', label: 'Mijoz', icon: UserGroupIcon },
  { id: 'offer', label: 'Taklif', icon: GiftIcon },
  { id: 'channels', label: 'Kanallar', icon: MegaphoneIcon },
  { id: 'funnel', label: 'Funnel', icon: FunnelIcon },
  { id: 'risks', label: 'Xavflar', icon: ExclamationTriangleIcon },
  { id: 'swot', label: 'SWOT', icon: Squares2X2Icon },
];

async function downloadReport() {
  downloading.value = true;
  try {
    await store.downloadReport(props.diagnostic.id);
  } catch (error) {
    console.error('Failed to download report:', error);
  } finally {
    downloading.value = false;
  }
}

function navigateToModule(route) {
  if (route) {
    router.visit(route);
  }
}

function startAction(step) {
  if (step.module_route) {
    router.visit(step.module_route);
  }
}

function formatPotentialSavings(amount) {
  if (!amount) return '0 so\'m';
  return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
}

function formatTashkentTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('uz-UZ', {
    timeZone: 'Asia/Tashkent',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function goToDashboard() {
  router.post('/business/diagnostic/complete-and-go');
}
</script>
