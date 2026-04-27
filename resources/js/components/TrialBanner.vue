<template>
  <div
    v-if="showBanner"
    :class="bannerClass"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between flex-wrap gap-2">
      <div class="flex items-center gap-2 min-w-0">
        <component :is="bannerIcon" class="w-5 h-5 flex-shrink-0" />
        <p class="text-sm font-medium truncate">
          <template v-if="isExpired">
            {{ isTrial ? 'Sinov davri' : 'Obuna muddati' }} tugadi. Davom etish uchun tarif tanlang.
          </template>
          <template v-else-if="isTrial && !isUrgent">
            <span class="inline-flex items-center gap-1.5">
              <span class="px-2 py-0.5 bg-white/20 rounded-full text-xs font-bold uppercase tracking-wide">Sinov</span>
              Tizim sinov muddatida — {{ daysRemaining }} kun qoldi
            </span>
          </template>
          <template v-else>
            {{ isTrial ? 'Sinov davri' : 'Obuna muddati' }} {{ daysRemaining }} kunda tugaydi! Tarifni hozir tanlang.
          </template>
        </p>
      </div>
      <div class="flex items-center gap-2 flex-shrink-0">
        <Link
          href="/business/subscription"
          :class="buttonClass"
        >
          Tarif tanlash
        </Link>
        <button
          v-if="!isExpired && !isUrgent"
          @click="dismiss"
          class="p-1 rounded-md hover:bg-white/20 transition-colors"
          aria-label="Yashirish"
        >
          <XMarkIcon class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import {
  ClockIcon,
  ExclamationTriangleIcon,
  SparklesIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const dismissed = ref(false);

// subscriptionStatus — har sahifada minimal info (TrialBanner uchun)
// subscription — lazy load, faqat subscription sahifasida kerak
const subStatus = computed(() => page.props.subscriptionStatus || {});

const isAdmin = computed(() => {
  const roles = page.props.auth?.user?.roles || [];
  return roles.some(r => r.name === 'admin' || r.name === 'super_admin');
});

const isTrial = computed(() => subStatus.value.is_trial === true);
const hasNoSubscription = computed(() => subStatus.value.has_subscription === false);
const daysRemaining = computed(() => subStatus.value.days_remaining ?? 999);

const isExpired = computed(() => {
  return hasNoSubscription.value;
});

const isUrgent = computed(() => {
  return daysRemaining.value <= 3;
});

const showBanner = computed(() => {
  if (dismissed.value) return false;

  // Admin uchun obuna banner ko'rsatmaslik
  if (isAdmin.value) return false;

  // Obuna umuman yo'q — tugagan
  if (hasNoSubscription.value) return true;

  // Trial davrida har doim ko'rsatish — foydalanuvchi sinov muddatida ekanini bilishi uchun
  if (isTrial.value) return true;

  // Pullik obuna uchun: faqat 3 kun yoki kamroq qolganda ko'rsatish
  if (daysRemaining.value <= 3) return true;

  return false;
});

const bannerClass = computed(() => {
  if (isExpired.value) {
    return 'bg-red-600 text-white';
  }
  if (isUrgent.value) {
    return 'bg-amber-500 text-white';
  }
  // Trial davri (urgent emas) — yumshoqroq indigo gradient
  if (isTrial.value) {
    return 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white';
  }
  return 'bg-blue-600 text-white';
});

const buttonClass = computed(() => {
  if (isExpired.value) {
    return 'px-4 py-1.5 bg-white text-red-600 text-sm font-semibold rounded-lg hover:bg-red-50 transition-colors';
  }
  if (isUrgent.value) {
    return 'px-4 py-1.5 bg-white text-amber-600 text-sm font-semibold rounded-lg hover:bg-amber-50 transition-colors';
  }
  if (isTrial.value) {
    return 'px-4 py-1.5 bg-white text-indigo-600 text-sm font-semibold rounded-lg hover:bg-indigo-50 transition-colors';
  }
  return 'px-4 py-1.5 bg-white text-blue-600 text-sm font-semibold rounded-lg hover:bg-blue-50 transition-colors';
});

const bannerIcon = computed(() => {
  if (isExpired.value) return XCircleIcon;
  if (isUrgent.value) return ExclamationTriangleIcon;
  if (isTrial.value) return SparklesIcon;
  return ClockIcon;
});

const dismiss = () => {
  dismissed.value = true;
};
</script>
