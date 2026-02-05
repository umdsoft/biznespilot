<template>
  <div
    :class="[
      'relative flex flex-col h-full rounded-2xl border-2 transition-all duration-300',
      isPopular
        ? 'border-purple-500 dark:border-purple-400 shadow-xl shadow-purple-500/10'
        : plan.is_current
          ? 'border-green-500 dark:border-green-400'
          : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-lg',
      'bg-white dark:bg-gray-800'
    ]"
  >
    <!-- Mashhur badge -->
    <div v-if="isPopular" class="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
      <span class="px-4 py-1 bg-gradient-to-r from-purple-600 to-indigo-600 text-white text-xs font-bold rounded-full shadow-lg">
        Mashhur
      </span>
    </div>

    <!-- Joriy tarif badge -->
    <div v-if="plan.is_current" class="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
      <span class="px-4 py-1 bg-gradient-to-r from-green-500 to-emerald-500 text-white text-xs font-bold rounded-full shadow-lg">
        Joriy tarif
      </span>
    </div>

    <div class="p-6 flex-1 flex flex-col">
      <!-- Plan nomi -->
      <div class="mb-4">
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ plan.name }}</h3>
        <p v-if="plan.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ plan.description }}</p>
      </div>

      <!-- Narx -->
      <div class="mb-6">
        <div class="flex items-baseline gap-1">
          <span class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
            {{ formatPrice(currentPrice) }}
          </span>
          <span class="text-gray-500 dark:text-gray-400 text-sm">so'm/oy</span>
        </div>
        <p v-if="isYearly && plan.price_yearly" class="text-xs text-green-600 dark:text-green-400 mt-1">
          Yillik to'lovda 20% tejash
        </p>
      </div>

      <!-- Limitlar -->
      <div class="space-y-2.5 mb-6 flex-1">
        <div v-for="(item, index) in limitsList" :key="index" class="flex items-center gap-2.5">
          <svg class="w-4.5 h-4.5 flex-shrink-0 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span class="text-sm text-gray-700 dark:text-gray-300">{{ item }}</span>
        </div>

        <!-- Features -->
        <div v-for="(item, index) in featuresList" :key="'f-' + index" class="flex items-center gap-2.5">
          <svg class="w-4.5 h-4.5 flex-shrink-0 text-emerald-500 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          <span class="text-sm text-gray-700 dark:text-gray-300">{{ item }}</span>
        </div>
      </div>

      <!-- CTA tugma -->
      <div class="mt-auto">
        <button
          v-if="!plan.is_current"
          @click="$emit('select', plan.id)"
          :class="[
            'w-full py-3 px-4 rounded-xl font-semibold text-sm transition-all duration-200',
            isPopular
              ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white hover:from-purple-700 hover:to-indigo-700 shadow-lg shadow-purple-500/25 hover:shadow-xl'
              : 'bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-200'
          ]"
        >
          {{ plan.is_upgrade ? "Upgrade qilish" : "Tanlash" }}
        </button>

        <div
          v-else
          class="w-full py-3 px-4 rounded-xl font-semibold text-sm text-center bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800"
        >
          Hozirgi tarifingiz
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  plan: { type: Object, required: true },
  isYearly: { type: Boolean, default: false },
});

defineEmits(['select']);

const isPopular = computed(() => props.plan.slug === 'business-pack');

const currentPrice = computed(() => {
  if (props.isYearly && props.plan.price_yearly) {
    return Math.round(props.plan.price_yearly / 12);
  }
  return props.plan.price_monthly;
});

const formatPrice = (price) => {
  return Math.round(price).toLocaleString('uz-UZ');
};

// Limitlarni o'zbek tilida ko'rsatish
const limitLabels = {
  users: 'xodim',
  branches: 'filial',
  monthly_leads: 'lid/oy',
  ai_call_minutes: 'daqiqa Call Center AI',
  instagram_accounts: 'Instagram akkaunt',
  telegram_bots: 'Telegram bot',
  chatbot_channels: 'Chatbot kanal',
  ai_requests: 'AI so\'rov',
  storage_mb: 'MB saqlash',
};

const limitsList = computed(() => {
  const limits = props.plan.limits || {};
  const items = [];

  for (const [key, label] of Object.entries(limitLabels)) {
    const value = limits[key];
    if (value !== undefined && value !== null) {
      if (value === -1) {
        items.push(`Cheksiz ${label}`);
      } else {
        items.push(`${value.toLocaleString('uz-UZ')} ${label}`);
      }
    }
  }

  return items;
});

const featureLabels = {
  hr_tasks: 'HR vazifalar',
  hr_bot: 'HR Bot (yollash)',
  anti_fraud: 'Anti-Fraud SMS',
};

const featuresList = computed(() => {
  const features = props.plan.features || {};
  const items = [];

  for (const [key, label] of Object.entries(featureLabels)) {
    if (features[key]) {
      items.push(label);
    }
  }

  return items;
});
</script>
