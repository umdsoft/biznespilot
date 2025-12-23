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

      <!-- AI Questions Link -->
      <div class="mt-8 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-2xl border border-indigo-200 p-6">
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

      <!-- Meta Info -->
      <div class="mt-8 text-center text-sm text-gray-500">
        <p>
          Diagnostika {{ diagnostic.completed_at }} sanasida yakunlandi |
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
</script>
