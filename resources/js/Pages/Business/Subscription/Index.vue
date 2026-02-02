<template>
  <BusinessLayout title="Tarif tanlash">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <!-- Header -->
      <div class="text-center mb-10">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
          Biznesingiz uchun <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">to'g'ri tarif</span>ni tanlang
        </h1>
        <p class="mt-3 text-lg text-gray-600 dark:text-gray-400">
          Yashirin to'lovlar yo'q. Istalgan vaqt bekor qilish mumkin.
        </p>
      </div>

      <!-- Joriy obuna ma'lumoti -->
      <div
        v-if="currentSubscription.has_subscription"
        class="mb-8 mx-auto max-w-2xl p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50"
      >
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ currentSubscription.plan?.name || 'Trial' }} tarifi
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                <template v-if="currentSubscription.is_trial">
                  Sinov davri: {{ currentSubscription.days_remaining }} kun qoldi
                </template>
                <template v-else>
                  {{ currentSubscription.days_remaining }} kun qoldi
                </template>
              </p>
            </div>
          </div>
          <span
            :class="[
              'px-3 py-1 rounded-full text-xs font-semibold',
              currentSubscription.is_trial
                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
            ]"
          >
            {{ currentSubscription.is_trial ? 'Sinov davri' : 'Faol' }}
          </span>
        </div>
      </div>

      <!-- Oylik/Yillik toggle -->
      <div class="flex justify-center mb-8">
        <div class="inline-flex items-center p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
          <button
            @click="isYearly = false"
            :class="[
              'px-6 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200',
              !isYearly
                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
            ]"
          >
            Oylik
          </button>
          <button
            @click="isYearly = true"
            :class="[
              'px-6 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center gap-2',
              isYearly
                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
            ]"
          >
            Yillik
            <span class="px-2 py-0.5 bg-green-500 text-white text-xs font-bold rounded-full">-20%</span>
          </button>
        </div>
      </div>

      <!-- Plans grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 items-stretch">
        <PlanCard
          v-for="plan in plans"
          :key="plan.id"
          :plan="plan"
          :is-yearly="isYearly"
          @select="selectPlan"
        />
      </div>

      <!-- Trust section -->
      <div class="mt-12 text-center">
        <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-gray-500 dark:text-gray-400">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>30 kunlik kafolat</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <span>Click & Payme</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span>24/7 yordam</span>
          </div>
        </div>
      </div>

    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import PlanCard from '@/components/subscription/PlanCard.vue';

const props = defineProps({
  plans: { type: Array, required: true },
  currentSubscription: { type: Object, required: true },
});

const isYearly = ref(false);

const selectPlan = (planId) => {
  router.visit(`/business/subscription/${planId}/checkout`);
};
</script>
