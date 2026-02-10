<template>
  <BusinessLayout title="Tarif va To'lov">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

      <!-- Welcome Banner (for new businesses with trial) -->
      <div
        v-if="$page.props.flash?.success && currentSubscription.is_trial"
        class="mb-6 mx-auto max-w-2xl p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800"
      >
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-green-800 dark:text-green-300">
              Tabriklaymiz! 14 kunlik bepul sinov davri berildi.
            </h3>
            <p class="mt-1 text-sm text-green-700 dark:text-green-400">
              Barcha imkoniyatlarni sinab ko'ring. Hoziroq tarif tanlasangiz ham yoki sinov davri tugagach ham to'lashingiz mumkin.
            </p>
            <Link
              href="/business"
              class="inline-flex items-center mt-2 text-sm font-medium text-green-700 dark:text-green-300 hover:text-green-900 dark:hover:text-green-100"
            >
              Bosh sahifaga o'tish &rarr;
            </Link>
          </div>
        </div>
      </div>

      <!-- Expired Subscription Warning -->
      <div
        v-if="!currentSubscription.has_subscription"
        class="mb-6 mx-auto max-w-2xl p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800"
      >
        <div class="flex items-center gap-3">
          <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <p class="text-sm font-medium text-red-800 dark:text-red-300">
            Sinov davri tugadi. Platformadan foydalanish uchun tariflardan birini tanlang.
          </p>
        </div>
      </div>

      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
          Tarif va <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">To'lov</span>
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          Biznesingizga mos tarifni tanlang. Yashirin to'lovlar yo'q.
        </p>
      </div>

      <!-- Joriy obuna -->
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
                Joriy tarif: {{ currentSubscription.plan?.name || 'Trial' }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ currentSubscription.days_remaining }} kun qoldi
              </p>
            </div>
          </div>
          <div class="flex items-center gap-3">
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
            <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
              {{ isYearly ? 'Yillik' : 'Oylik' }}
            </span>
          </div>
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

      <!-- Comparison Table -->
      <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        <table class="w-full text-sm">
          <!-- Plan Names Header -->
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-700">
              <th class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800/80 backdrop-blur-sm px-4 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-48 min-w-[180px]">
                Imkoniyatlar
              </th>
              <th
                v-for="plan in plans"
                :key="plan.id"
                :class="[
                  'px-3 py-4 text-center min-w-[140px]',
                  plan.is_current ? 'bg-blue-50/50 dark:bg-blue-900/10' : 'bg-gray-50 dark:bg-gray-800/80'
                ]"
              >
                <div class="space-y-2">
                  <!-- Badge -->
                  <div class="h-5">
                    <span
                      v-if="plan.is_current"
                      class="inline-block px-2.5 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-[10px] font-bold rounded-full uppercase"
                    >
                      Joriy
                    </span>
                    <span
                      v-else-if="plan.slug === 'business-pack'"
                      class="inline-block px-2.5 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-[10px] font-bold rounded-full uppercase"
                    >
                      Mashhur
                    </span>
                  </div>
                  <!-- Plan name -->
                  <div class="font-bold text-gray-900 dark:text-gray-100 text-base">{{ plan.name }}</div>
                  <!-- Price -->
                  <div>
                    <span class="text-lg font-extrabold text-gray-900 dark:text-gray-100">
                      {{ formatPrice(getPlanPrice(plan)) }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 block">so'm/oy</span>
                  </div>
                  <!-- CTA -->
                  <button
                    v-if="!plan.is_current"
                    @click="selectPlan(plan.id)"
                    :class="[
                      'w-full py-2 px-3 rounded-lg font-semibold text-xs transition-all',
                      plan.slug === 'business-pack'
                        ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white hover:from-purple-700 hover:to-indigo-700 shadow-sm'
                        : 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200'
                    ]"
                  >
                    {{ plan.is_upgrade ? 'Upgrade' : 'Tanlash' }}
                  </button>
                  <div
                    v-else
                    class="w-full py-2 px-3 rounded-lg text-xs font-semibold text-center bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800"
                  >
                    Hozirgi tarif
                  </div>
                </div>
              </th>
            </tr>
          </thead>

          <tbody>
            <!-- Section: Asosiy limitlar -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
              <td :colspan="plans.length + 1" class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Asosiy limitlar
              </td>
            </tr>

            <tr
              v-for="(row, idx) in limitRows"
              :key="'limit-' + idx"
              class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors"
            >
              <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-4 py-3 text-gray-700 dark:text-gray-300 font-medium text-sm">
                {{ row.label }}
              </td>
              <td
                v-for="plan in plans"
                :key="plan.id"
                :class="[
                  'px-3 py-3 text-center text-sm',
                  plan.is_current ? 'bg-blue-50/30 dark:bg-blue-900/5' : ''
                ]"
              >
                <span v-if="row.getValue(plan) === null" class="text-gray-300 dark:text-gray-600">—</span>
                <span v-else-if="row.getValue(plan) === -1" class="font-semibold text-blue-600 dark:text-blue-400">Cheksiz</span>
                <span v-else class="font-medium text-gray-900 dark:text-gray-100">
                  {{ row.format ? row.format(row.getValue(plan)) : formatNumber(row.getValue(plan)) }}
                </span>
              </td>
            </tr>

            <!-- Section: Qo'shimcha funksiyalar -->
            <tr class="bg-gray-50 dark:bg-gray-700/30">
              <td :colspan="plans.length + 1" class="px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                Qo'shimcha funksiyalar
              </td>
            </tr>

            <tr
              v-for="(row, idx) in featureRows"
              :key="'feature-' + idx"
              class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors"
            >
              <td class="sticky left-0 z-10 bg-white dark:bg-gray-800 px-4 py-3 text-gray-700 dark:text-gray-300 font-medium text-sm">
                {{ row.label }}
              </td>
              <td
                v-for="plan in plans"
                :key="plan.id"
                :class="[
                  'px-3 py-3 text-center',
                  plan.is_current ? 'bg-blue-50/30 dark:bg-blue-900/5' : ''
                ]"
              >
                <!-- Enabled -->
                <svg v-if="row.getValue(plan)" class="w-5 h-5 mx-auto text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <!-- Disabled -->
                <svg v-else class="w-5 h-5 mx-auto text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </td>
            </tr>

            <!-- Bottom CTA Row -->
            <tr class="border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/10">
              <td class="sticky left-0 z-10 bg-gray-50 dark:bg-gray-800 px-4 py-4"></td>
              <td
                v-for="plan in plans"
                :key="'cta-' + plan.id"
                :class="[
                  'px-3 py-4 text-center',
                  plan.is_current ? 'bg-blue-50/30 dark:bg-blue-900/5' : ''
                ]"
              >
                <button
                  v-if="!plan.is_current"
                  @click="selectPlan(plan.id)"
                  :class="[
                    'w-full py-2.5 px-4 rounded-lg font-semibold text-sm transition-all',
                    plan.slug === 'business-pack'
                      ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white hover:from-purple-700 hover:to-indigo-700 shadow-sm'
                      : 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200'
                  ]"
                >
                  {{ plan.is_upgrade ? 'Upgrade' : 'Tanlash' }}
                </button>
                <div
                  v-else
                  class="py-2.5 px-4 rounded-lg text-sm font-semibold text-green-700 dark:text-green-400"
                >
                  Hozirgi tarif
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Trust section -->
      <div class="mt-10 text-center">
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
import { router, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
  plans: { type: Array, required: true },
  currentSubscription: { type: Object, required: true },
});

const isYearly = ref(false);

const getPlanPrice = (plan) => {
  if (isYearly.value && plan.price_yearly) {
    return Math.round(plan.price_yearly / 12);
  }
  return plan.price_monthly;
};

const formatPrice = (price) => {
  return Math.round(price).toLocaleString('uz-UZ');
};

const formatNumber = (val) => {
  if (typeof val === 'number') {
    return val.toLocaleString('uz-UZ');
  }
  return val;
};

const selectPlan = (planId) => {
  router.visit(`/business/subscription/${planId}/checkout`);
};

// Limitlar jadvali satrlari
const limitRows = [
  {
    label: 'Xodimlar soni',
    getValue: (plan) => plan.limits?.users ?? null,
  },
  {
    label: 'Filiallar',
    getValue: (plan) => plan.limits?.branches ?? null,
  },
  {
    label: 'Instagram akkauntlar',
    getValue: (plan) => plan.limits?.instagram_accounts ?? null,
  },
  {
    label: 'Oylik lidlar',
    getValue: (plan) => plan.limits?.monthly_leads ?? null,
    format: (val) => val === -1 ? 'Cheksiz' : val.toLocaleString('uz-UZ'),
  },
  {
    label: 'AI Call Center (daqiqa)',
    getValue: (plan) => plan.limits?.ai_call_minutes ?? null,
  },
  {
    label: 'Qo\'shimcha daqiqa narxi',
    getValue: (plan) => plan.limits?.extra_call_price ?? null,
    format: (val) => val === 0 ? 'Bepul' : formatPrice(val) + ' so\'m',
  },
  {
    label: 'Chatbot kanallari',
    getValue: (plan) => plan.limits?.chatbot_channels ?? null,
  },
  {
    label: 'Telegram botlar',
    getValue: (plan) => plan.limits?.telegram_bots ?? null,
  },
  {
    label: 'AI so\'rovlar',
    getValue: (plan) => plan.limits?.ai_requests ?? null,
  },
  {
    label: 'Xotira hajmi (MB)',
    getValue: (plan) => plan.limits?.storage_mb ?? null,
  },
];

// Funksiyalar jadvali satrlari
const featureRows = [
  {
    label: 'HR vazifalar boshqaruvi',
    getValue: (plan) => plan.features?.hr_tasks ?? false,
  },
  {
    label: 'HR Bot (avtomatik yollash)',
    getValue: (plan) => plan.features?.hr_bot ?? false,
  },
  {
    label: 'Anti-Fraud SMS himoya',
    getValue: (plan) => plan.features?.anti_fraud ?? false,
  },
];
</script>
