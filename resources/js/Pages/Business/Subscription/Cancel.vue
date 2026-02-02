<template>
  <BusinessLayout title="To'lov bekor qilindi">
    <div class="max-w-xl mx-auto px-4 py-16 text-center">

      <div class="w-20 h-20 mx-auto mb-6 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
        <svg class="w-10 h-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </div>

      <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">
        To'lov bekor qilindi
      </h1>
      <p class="text-gray-600 dark:text-gray-400 mb-8">
        To'lov amalga oshmadi yoki bekor qilindi. Qaytadan urinib ko'ring.
      </p>

      <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
        <Link
          href="/business/subscription"
          class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold text-sm hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Qaytadan urinish
        </Link>
        <Link
          href="/business"
          class="inline-flex items-center px-6 py-3 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 font-semibold text-sm transition-colors"
        >
          Bosh sahifaga qaytish
        </Link>
      </div>

    </div>
  </BusinessLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

defineProps({
  orderId: { type: String, default: null },
});
</script>
