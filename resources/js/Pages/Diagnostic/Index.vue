<template>
  <Head title="AI Diagnostika" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">AI Diagnostika</h1>
        <p class="text-gray-500 mt-1">
          Biznesingizni chuqur tahlil qiling va yaxshilash yo'llarini aniqlang
        </p>
      </div>

      <!-- Onboarding check -->
      <div
        v-if="onboardingProgress < 100"
        class="mb-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6"
      >
        <div class="flex items-start space-x-4">
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="w-6 h-6 text-yellow-500" />
          </div>
          <div>
            <h3 class="font-medium text-yellow-800">Onboarding tugallanmagan</h3>
            <p class="text-sm text-yellow-700 mt-1">
              Diagnostika o'tkazish uchun avval onboarding jarayonini 100% tugatishingiz kerak.
              Hozirgi progress: {{ onboardingProgress }}%
            </p>
            <Link
              href="/business/onboarding"
              class="mt-3 inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm rounded-lg hover:bg-yellow-700"
            >
              Onboarding'ga o'tish
              <ArrowRightIcon class="w-4 h-4 ml-2" />
            </Link>
          </div>
        </div>
      </div>

      <!-- Can start diagnostic -->
      <template v-else>
        <!-- No previous diagnostic -->
        <div v-if="!latestDiagnostic" class="mb-8">
          <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-8 text-white">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-2xl font-bold mb-2">Birinchi Diagnostikangizni Boshlang</h2>
                <p class="text-indigo-100 max-w-lg">
                  AI yordamida biznesingizning marketing, sotuv va boshqa yo'nalishlarini
                  chuqur tahlil qiling. Kuchli va zaif tomonlaringizni aniqlang.
                </p>
                <button
                  @click="startDiagnostic"
                  :disabled="loading"
                  class="mt-6 px-6 py-3 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-indigo-50 transition-colors disabled:opacity-50 flex items-center"
                >
                  <SparklesIcon class="w-5 h-5 mr-2" />
                  Diagnostikani Boshlash
                </button>
              </div>
              <div class="hidden lg:block">
                <div class="w-48 h-48 bg-white/10 rounded-full flex items-center justify-center">
                  <ChartPieIcon class="w-24 h-24 text-white/50" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Has latest diagnostic -->
        <template v-else>
          <!-- Latest diagnostic summary -->
          <div class="bg-white rounded-lg border shadow-sm mb-8">
            <div class="p-6 border-b">
              <div class="flex items-center justify-between">
                <div>
                  <h2 class="text-lg font-semibold text-gray-900">
                    Oxirgi Diagnostika #{{ latestDiagnostic.version }}
                  </h2>
                  <p class="text-sm text-gray-500 mt-1">
                    {{ latestDiagnostic.completed_at }}
                  </p>
                </div>
                <div class="flex items-center space-x-3">
                  <Link
                    :href="`/business/diagnostic/${latestDiagnostic.id}`"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm"
                  >
                    Batafsil ko'rish
                  </Link>
                  <button
                    @click="startDiagnostic"
                    :disabled="loading"
                    class="px-4 py-2 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-50 text-sm disabled:opacity-50"
                  >
                    Yangi diagnostika
                  </button>
                </div>
              </div>
            </div>

            <div class="p-6">
              <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <!-- Overall score -->
                <div class="lg:col-span-1 flex justify-center">
                  <HealthScoreGauge
                    :score="latestDiagnostic.overall_score"
                    :size="150"
                    :animate="true"
                  />
                </div>

                <!-- Category scores -->
                <div class="lg:col-span-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                  <CategoryScoreCard
                    v-for="(score, category) in latestDiagnostic.category_scores"
                    :key="category"
                    :category="category"
                    :score="score"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Quick actions -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <Link
              :href="`/business/diagnostic/${latestDiagnostic.id}/questions`"
              class="bg-white rounded-lg border p-4 hover:shadow-md transition-shadow flex items-center space-x-4"
            >
              <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                <ChatBubbleLeftRightIcon class="w-6 h-6 text-purple-600" />
              </div>
              <div>
                <h3 class="font-medium text-gray-900">AI Savollari</h3>
                <p class="text-sm text-gray-500">Qo'shimcha ma'lumot bering</p>
              </div>
            </Link>

            <Link
              href="/business/diagnostic/history"
              class="bg-white rounded-lg border p-4 hover:shadow-md transition-shadow flex items-center space-x-4"
            >
              <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                <ClockIcon class="w-6 h-6 text-blue-600" />
              </div>
              <div>
                <h3 class="font-medium text-gray-900">Tarix</h3>
                <p class="text-sm text-gray-500">Oldingi diagnostikalar</p>
              </div>
            </Link>

            <button
              @click="downloadReport(latestDiagnostic.id)"
              class="bg-white rounded-lg border p-4 hover:shadow-md transition-shadow flex items-center space-x-4 text-left"
            >
              <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                <DocumentArrowDownIcon class="w-6 h-6 text-green-600" />
              </div>
              <div>
                <h3 class="font-medium text-gray-900">Hisobot</h3>
                <p class="text-sm text-gray-500">PDF yuklab olish</p>
              </div>
            </button>
          </div>
        </template>
      </template>

      <!-- History section -->
      <div v-if="history?.length > 1" class="bg-white rounded-lg border">
        <div class="p-4 border-b">
          <h3 class="font-semibold text-gray-900">Diagnostika tarixi</h3>
        </div>
        <div class="divide-y">
          <Link
            v-for="diagnostic in history"
            :key="diagnostic.id"
            :href="`/business/diagnostic/${diagnostic.id}`"
            class="p-4 flex items-center justify-between hover:bg-gray-50"
          >
            <div class="flex items-center space-x-4">
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center"
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
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDiagnosticStore } from '@/stores/diagnostic';
import HealthScoreGauge from '@/Components/diagnostic/HealthScoreGauge.vue';
import CategoryScoreCard from '@/Components/diagnostic/CategoryScoreCard.vue';
import {
  SparklesIcon,
  ChartPieIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon,
  DocumentArrowDownIcon,
  ChevronRightIcon,
  ArrowRightIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  latestDiagnostic: Object,
  history: Array,
  canStart: Object,
  onboardingProgress: {
    type: Number,
    default: 0,
  },
});

const store = useDiagnosticStore();
const loading = ref(false);

async function startDiagnostic() {
  loading.value = true;
  try {
    const result = await store.startDiagnostic();
    router.visit(`/business/diagnostic/${result.diagnostic_id}/processing`);
  } catch (error) {
    console.error('Failed to start diagnostic:', error);
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
