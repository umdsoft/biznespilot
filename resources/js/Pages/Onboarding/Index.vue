<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Warning Banner -->
    <OnboardingWarningBanner
      v-if="progress && progress.overall_percent < 100"
      :percent="progress.overall_percent"
    />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Onboarding</h1>
        <p class="mt-2 text-gray-600">
          Biznesingiz haqida to'liq ma'lumot bering va AI diagnostikasini boshlang
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Progress -->
        <div class="lg:col-span-1">
          <OnboardingProgress
            :progress="progress"
            @start-phase-2="handleStartPhase2"
          />

          <!-- Maturity Score Card -->
          <div v-if="maturityScore" class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Biznes Yetuklik Bahosi</h3>
            <div class="flex items-center justify-center">
              <div class="relative w-32 h-32">
                <svg class="w-full h-full transform -rotate-90">
                  <circle
                    cx="64"
                    cy="64"
                    r="56"
                    stroke-width="12"
                    fill="none"
                    class="stroke-gray-200"
                  />
                  <circle
                    cx="64"
                    cy="64"
                    r="56"
                    stroke-width="12"
                    fill="none"
                    class="stroke-indigo-500"
                    :stroke-dasharray="`${(maturityScore.score / 100) * 352} 352`"
                    stroke-linecap="round"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <span class="text-3xl font-bold text-gray-900">{{ maturityScore.score }}</span>
                  <span class="text-xs text-gray-500">{{ maturityScore.level_label }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Steps -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Category: Profile -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">Biznes Profili</h3>
                <p class="text-sm text-gray-500">Asosiy ma'lumotlar</p>
              </div>
              <div class="ml-auto">
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-sm font-medium',
                    getCategoryBadgeClass('profile')
                  ]"
                >
                  {{ progress?.categories?.profile?.percent || 0 }}%
                </span>
              </div>
            </div>

            <div class="space-y-3">
              <OnboardingStepCard
                v-for="step in profileSteps"
                :key="step.code"
                :step="step"
                @click="openStep"
              />
            </div>
          </div>

          <!-- Category: Integration -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 bg-green-100 text-green-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">Integratsiyalar</h3>
                <p class="text-sm text-gray-500">Platformalarni ulang</p>
              </div>
              <div class="ml-auto">
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-sm font-medium',
                    getCategoryBadgeClass('integration')
                  ]"
                >
                  {{ progress?.categories?.integration?.percent || 0 }}%
                </span>
              </div>
            </div>

            <div class="space-y-3">
              <OnboardingStepCard
                v-for="step in integrationSteps"
                :key="step.code"
                :step="step"
                @click="openStep"
              />
            </div>
          </div>

          <!-- Category: Framework -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">Marketing Framework</h3>
                <p class="text-sm text-gray-500">Strategiya asoslari</p>
              </div>
              <div class="ml-auto">
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-sm font-medium',
                    getCategoryBadgeClass('framework')
                  ]"
                >
                  {{ progress?.categories?.framework?.percent || 0 }}%
                </span>
              </div>
            </div>

            <div class="space-y-3">
              <OnboardingStepCard
                v-for="step in frameworkSteps"
                :key="step.code"
                :step="step"
                @click="openStep"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Step Modal -->
    <Modal
      :show="showStepModal"
      @close="closeStep"
      max-width="3xl"
    >
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900">{{ currentStep?.step?.name?.uz }}</h3>
              <p class="text-sm text-gray-500">{{ currentStep?.step?.description?.uz }}</p>
            </div>
          </div>
          <button @click="closeStep" class="text-gray-400 hover:text-gray-500">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Dynamic form based on step -->
        <BusinessBasicForm
          v-if="activeStepCode === 'business_basic'"
          :business="currentStep?.data?.business"
          :industries="industries"
          @submit="handleStepComplete"
          @cancel="closeStep"
        />

        <BusinessDetailsForm
          v-else-if="activeStepCode === 'business_details'"
          :business="currentStep?.data?.business"
          @submit="handleStepComplete"
          @cancel="closeStep"
        />

        <DreamBuyerForm
          v-else-if="activeStepCode === 'framework_dream_buyer'"
          :dream-buyer="currentStep?.data?.dream_buyer"
          @submit="handleStepComplete"
          @cancel="closeStep"
        />

        <!-- Placeholder for other forms -->
        <div v-else class="text-center py-12">
          <p class="text-gray-500">Bu qadamni tez orada yaratiladi</p>
          <button
            @click="closeStep"
            class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg"
          >
            Yopish
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useOnboardingStore } from '@/stores/onboarding';
import Modal from '@/components/Modal.vue';
import OnboardingProgress from '@/components/onboarding/OnboardingProgress.vue';
import OnboardingStepCard from '@/components/onboarding/OnboardingStepCard.vue';
import OnboardingWarningBanner from '@/components/onboarding/OnboardingWarningBanner.vue';
import BusinessBasicForm from '@/components/onboarding/forms/BusinessBasicForm.vue';
import BusinessDetailsForm from '@/components/onboarding/forms/BusinessDetailsForm.vue';
import DreamBuyerForm from '@/components/onboarding/forms/DreamBuyerForm.vue';

const store = useOnboardingStore();

const showStepModal = ref(false);
const activeStepCode = ref(null);
const currentStep = ref(null);

const progress = computed(() => store.progress);
const maturityScore = computed(() => store.maturityScore);
const industries = computed(() => store.industries);

const profileSteps = computed(() => {
  return (progress.value?.steps || []).filter(s => s.category === 'profile');
});

const integrationSteps = computed(() => {
  return (progress.value?.steps || []).filter(s => s.category === 'integration');
});

const frameworkSteps = computed(() => {
  return (progress.value?.steps || []).filter(s => s.category === 'framework');
});

onMounted(async () => {
  await Promise.all([
    store.fetchProgress(),
    store.fetchIndustries(),
    store.fetchMaturityScore()
  ]);
});

function getCategoryBadgeClass(category) {
  const percent = progress.value?.categories?.[category]?.percent || 0;
  if (percent >= 100) {
    return 'bg-green-100 text-green-700';
  }
  if (percent > 0) {
    return 'bg-yellow-100 text-yellow-700';
  }
  return 'bg-gray-100 text-gray-600';
}

async function openStep(step) {
  activeStepCode.value = step.code;
  showStepModal.value = true;

  try {
    const response = await store.fetchStepDetail(step.code);
    currentStep.value = response.data;
  } catch (err) {
    console.error(err);
  }
}

function closeStep() {
  showStepModal.value = false;
  activeStepCode.value = null;
  currentStep.value = null;
}

async function handleStepComplete() {
  closeStep();
  await store.fetchProgress();
}

async function handleStartPhase2() {
  try {
    await store.startPhase2();
    // Redirect to Phase 2 page or show success message
  } catch (err) {
    console.error(err);
  }
}
</script>
