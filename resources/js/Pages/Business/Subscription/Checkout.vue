<template>
  <BusinessLayout title="To'lov">
    <div class="max-w-2xl mx-auto px-4 py-8">

      <!-- Orqaga -->
      <Link
        href="/business/subscription"
        class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 mb-6 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Tariflarga qaytish
      </Link>

      <!-- Plan summary -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
          {{ checkoutData.plan.name }} tarifi
        </h2>
        <div class="flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
            {{ formatPrice(checkoutData.amount) }}
          </span>
          <span class="text-gray-500 dark:text-gray-400">so'm/oy</span>
        </div>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
          {{ checkoutData.business.name }} uchun to'lov
        </p>
      </div>

      <!-- To'lov usullarini tanlash -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
          To'lov usulini tanlang
        </h3>

        <div class="space-y-3">
          <!-- Click -->
          <button
            v-if="checkoutData.providers.click.enabled"
            @click="pay('click')"
            :disabled="loading"
            class="w-full flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-blue-400 dark:hover:border-blue-500 transition-all duration-200 bg-white dark:bg-gray-800 hover:bg-blue-50 dark:hover:bg-blue-900/10 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <div class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
            </div>
            <div class="text-left flex-1">
              <p class="font-semibold text-gray-900 dark:text-gray-100">Click</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Karta yoki Click ilovasi orqali</p>
            </div>
            <svg v-if="!loading || selectedProvider !== 'click'" class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <svg v-else class="w-5 h-5 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          </button>

          <!-- Payme -->
          <button
            v-if="checkoutData.providers.payme.enabled"
            @click="pay('payme')"
            :disabled="loading"
            class="w-full flex items-center gap-4 p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-teal-400 dark:hover:border-teal-500 transition-all duration-200 bg-white dark:bg-gray-800 hover:bg-teal-50 dark:hover:bg-teal-900/10 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <div class="w-12 h-12 rounded-xl bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center flex-shrink-0">
              <svg class="w-6 h-6 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="text-left flex-1">
              <p class="font-semibold text-gray-900 dark:text-gray-100">Payme</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">Karta yoki Payme ilovasi orqali</p>
            </div>
            <svg v-if="!loading || selectedProvider !== 'payme'" class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <svg v-else class="w-5 h-5 text-teal-500 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          </button>
        </div>

        <!-- Xavfsizlik -->
        <div class="mt-6 flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          <span>Barcha to'lovlar xavfsiz SSL shifrlash bilan himoyalangan</span>
        </div>
      </div>

    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
  checkoutData: { type: Object, required: true },
  currentSubscription: { type: Object, default: null },
});

const loading = ref(false);
const selectedProvider = ref(null);

const pay = (provider) => {
  loading.value = true;
  selectedProvider.value = provider;

  router.post(`/business/subscription/${props.checkoutData.plan.id}/pay`, {
    provider,
  });
};

const formatPrice = (price) => {
  return Math.round(price).toLocaleString('uz-UZ');
};
</script>
