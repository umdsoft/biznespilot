<template>
  <BusinessLayout title="To'lov natijasi">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-12">
      <div class="max-w-lg mx-auto text-center">
        <!-- Success Icon -->
        <div class="mb-6">
          <div v-if="transaction?.status === 'paid'" class="w-20 h-20 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div v-else class="w-20 h-20 mx-auto bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
          {{ transaction?.status === 'paid' ? 'To\'lov muvaffaqiyatli!' : 'To\'lov kutilmoqda' }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
          {{ transaction?.status === 'paid'
            ? 'Obunangiz aktivlashtirildi. Endi barcha imkoniyatlardan foydalanishingiz mumkin.'
            : 'To\'lovingiz hali tasdiqlanmagan. Bu bir necha daqiqa vaqt olishi mumkin.'
          }}
        </p>

        <!-- Transaction Details -->
        <div v-if="transaction" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-8 text-left">
          <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Tranzaksiya tafsilotlari</h3>
          <div class="space-y-3">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500 dark:text-gray-400">Buyurtma ID:</span>
              <span class="font-mono font-medium text-gray-900 dark:text-gray-100">{{ transaction.order_id }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500 dark:text-gray-400">Tarif:</span>
              <span class="font-medium text-gray-900 dark:text-gray-100">{{ transaction.plan_name }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500 dark:text-gray-400">Davr:</span>
              <span class="font-medium text-gray-900 dark:text-gray-100">{{ transaction.billing_cycle === 'yearly' ? 'Yillik' : 'Oylik' }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500 dark:text-gray-400">Summa:</span>
              <span class="font-bold text-gray-900 dark:text-gray-100">{{ formatPrice(transaction.amount) }} {{ transaction.currency }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500 dark:text-gray-400">To'lov usuli:</span>
              <span class="inline-flex items-center gap-1.5">
                <span class="px-2 py-0.5 text-xs font-semibold rounded-md"
                  :class="transaction.provider === 'click' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'">
                  {{ transaction.provider === 'click' ? 'Click' : 'Payme' }}
                </span>
              </span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500 dark:text-gray-400">Holat:</span>
              <span class="px-2 py-0.5 text-xs font-semibold rounded-md"
                :class="statusClass(transaction.status)">
                {{ statusLabel(transaction.status) }}
              </span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500 dark:text-gray-400">Sana:</span>
              <span class="text-gray-900 dark:text-gray-100">{{ transaction.created_at }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <a href="/business"
            class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Bosh sahifaga qaytish
          </a>
          <a href="/business/billing/history"
            class="inline-flex items-center justify-center gap-2 px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
            To'lov tarixi
          </a>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
  transaction: { type: Object, default: () => null },
});

const formatPrice = (price) => {
  if (!price) return '0';
  return new Intl.NumberFormat('uz-UZ').format(price);
};

const statusLabel = (status) => {
  const labels = {
    created: 'Yaratildi',
    waiting: 'Kutilmoqda',
    processing: 'Jarayonda',
    paid: 'To\'langan',
    cancelled: 'Bekor qilindi',
    failed: 'Xatolik',
    refunded: 'Qaytarildi',
  };
  return labels[status] || status;
};

const statusClass = (status) => {
  const classes = {
    paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    created: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    waiting: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    processing: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    refunded: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
  };
  return classes[status] || 'bg-gray-100 text-gray-700';
};
</script>
