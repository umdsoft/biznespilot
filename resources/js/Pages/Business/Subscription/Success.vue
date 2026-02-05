<template>
  <BusinessLayout title="To'lov natijasi">
    <div class="max-w-xl mx-auto px-4 py-16 text-center">

      <!-- Muvaffaqiyatli to'lov -->
      <div v-if="transaction?.is_paid">
        <div class="w-20 h-20 mx-auto mb-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
          <svg class="w-10 h-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">
          To'lov muvaffaqiyatli!
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-1">
          <span class="font-semibold">{{ transaction.plan_name }}</span> tarifi aktivlashtirildi.
        </p>
        <p class="text-gray-500 dark:text-gray-500 mb-8">
          Summa: {{ formatPrice(transaction.amount) }} so'm
          <span class="text-gray-400">| {{ transaction.provider === 'click' ? 'Click' : 'Payme' }}</span>
        </p>
        <Link
          href="/business"
          class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold text-sm hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Boshqaruv paneliga qaytish
        </Link>
      </div>

      <!-- Jarayonda (polling) -->
      <div v-else-if="transaction?.is_processing">
        <div class="w-20 h-20 mx-auto mb-6 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
          <svg class="w-10 h-10 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">
          To'lov tekshirilmoqda...
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-2">
          Iltimos, kuting. Bu bir necha daqiqa vaqt olishi mumkin.
        </p>
        <p class="text-sm text-gray-400 dark:text-gray-500">
          Sahifa avtomatik yangilanadi.
        </p>
      </div>

      <!-- Noma'lum holat -->
      <div v-else>
        <div class="w-20 h-20 mx-auto mb-6 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
          <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">
          To'lov holati noma'lum
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
          To'lov ma'lumotlarini tekshirib bo'lmadi. Iltimos, qaytadan urinib ko'ring.
        </p>
        <Link
          href="/business/subscription"
          class="inline-flex items-center px-6 py-3 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 rounded-xl font-semibold text-sm hover:bg-gray-800 dark:hover:bg-gray-200 transition-all duration-200"
        >
          Tariflarga qaytish
        </Link>
      </div>

    </div>
  </BusinessLayout>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
  transaction: { type: Object, default: null },
});

const formatPrice = (price) => {
  return Math.round(price).toLocaleString('uz-UZ');
};

// Jarayondagi tranzaksiya uchun har 5 sekundda tekshirish
let pollInterval = null;

onMounted(() => {
  if (props.transaction?.is_processing) {
    pollInterval = setInterval(() => {
      router.reload({ only: ['transaction'] });
    }, 5000);
  }
});

onUnmounted(() => {
  if (pollInterval) {
    clearInterval(pollInterval);
  }
});
</script>
