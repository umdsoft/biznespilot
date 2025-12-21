<template>
  <div
    v-if="show"
    class="bg-gradient-to-r from-amber-500 to-orange-500 text-white"
  >
    <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-3">
          <span class="flex p-2 rounded-lg bg-white/20">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
          </span>
          <p class="font-medium text-sm sm:text-base">
            <span class="hidden sm:inline">Onboarding jarayonini yakunlang!</span>
            <span class="sm:hidden">Onboardingni tugatng!</span>
            <span class="ml-2 font-normal opacity-90">{{ percent }}% bajarildi</span>
          </p>
        </div>

        <div class="flex items-center gap-3">
          <!-- Progress mini bar -->
          <div class="hidden sm:flex items-center gap-2 bg-white/20 rounded-full px-3 py-1">
            <div class="w-24 h-2 bg-white/30 rounded-full overflow-hidden">
              <div
                class="h-full bg-white rounded-full transition-all duration-500"
                :style="{ width: `${percent}%` }"
              ></div>
            </div>
            <span class="text-xs font-semibold">{{ percent }}%</span>
          </div>

          <router-link
            :to="{ name: 'onboarding' }"
            class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-lg bg-white text-amber-600 hover:bg-amber-50 transition-colors"
          >
            Davom etish
            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </router-link>

          <button
            @click="dismiss"
            class="p-1 rounded-lg hover:bg-white/20 transition-colors"
            title="Yopish"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useOnboardingStore } from '@/stores/onboarding';

const store = useOnboardingStore();
const dismissed = ref(false);

const props = defineProps({
  percent: {
    type: Number,
    default: 0
  }
});

const show = computed(() => {
  return props.percent < 100 && !dismissed.value;
});

function dismiss() {
  dismissed.value = true;
  // Store in session storage to remember dismissal
  sessionStorage.setItem('onboarding_banner_dismissed', 'true');
}

onMounted(() => {
  // Check if previously dismissed this session
  if (sessionStorage.getItem('onboarding_banner_dismissed') === 'true') {
    dismissed.value = true;
  }
});
</script>
