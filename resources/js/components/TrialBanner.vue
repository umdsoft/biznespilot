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
            Sinov davri tugadi. Davom etish uchun tarif tanlang.
          </template>
          <template v-else-if="daysRemaining <= 3">
            Sinov davri {{ daysRemaining }} kunda tugaydi! Tarifni hozir tanlang.
          </template>
          <template v-else>
            Sinov davri: <strong>{{ daysRemaining }} kun</strong> qoldi.
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
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const dismissed = ref(false);

const subscription = computed(() => page.props.subscription);

const isTrial = computed(() => {
  return subscription.value?.subscription?.is_trial === true;
});

const hasNoSubscription = computed(() => {
  return subscription.value?.has_subscription === false;
});

const daysRemaining = computed(() => {
  return subscription.value?.subscription?.days_remaining ?? 0;
});

const isExpired = computed(() => {
  return hasNoSubscription.value;
});

const isUrgent = computed(() => {
  return daysRemaining.value <= 3;
});

const showBanner = computed(() => {
  if (dismissed.value) return false;

  // Obuna umuman yo'q — tugagan
  if (hasNoSubscription.value) return true;

  // Faqat trial da ko'rsatish
  return isTrial.value;
});

const bannerClass = computed(() => {
  if (isExpired.value) {
    return 'bg-red-600 text-white';
  }
  if (isUrgent.value) {
    return 'bg-amber-500 text-white';
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
  return 'px-4 py-1.5 bg-white text-blue-600 text-sm font-semibold rounded-lg hover:bg-blue-50 transition-colors';
});

const bannerIcon = computed(() => {
  if (isExpired.value) return XCircleIcon;
  if (isUrgent.value) return ExclamationTriangleIcon;
  return ClockIcon;
});

const dismiss = () => {
  dismissed.value = true;
};
</script>
