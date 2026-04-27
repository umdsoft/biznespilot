<template>
  <div
    v-if="visible"
    class="bg-gradient-to-r from-amber-400 via-orange-400 to-pink-500 text-white"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-3 flex-wrap">
      <div class="flex items-center gap-2 min-w-0">
        <BeakerIcon class="w-5 h-5 flex-shrink-0" />
        <span class="px-2 py-0.5 bg-white/25 rounded-full text-[10px] font-bold uppercase tracking-wider flex-shrink-0">
          Beta
        </span>
        <p class="text-sm font-medium truncate">
          Tizim sinov rejimida — ba'zi funksiyalar takomillashtirilmoqda. Xato yoki taklif bo'lsa bizga yozing.
        </p>
      </div>
      <div class="flex items-center gap-2 flex-shrink-0">
        <a
          href="https://t.me/biznespilot_support"
          target="_blank"
          rel="noopener"
          class="px-3 py-1 bg-white/20 hover:bg-white/30 text-white text-xs font-semibold rounded-md transition-colors"
        >
          Yordam
        </a>
        <button
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
import { ref, onMounted } from 'vue';
import { BeakerIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const STORAGE_KEY = 'beta_banner_dismissed_v1';
const visible = ref(true);

onMounted(() => {
  try {
    if (localStorage.getItem(STORAGE_KEY) === '1') {
      visible.value = false;
    }
  } catch (e) {
    // localStorage taqiqlangan bo'lishi mumkin (private mode) — banner ko'rinaveradi
  }
});

const dismiss = () => {
  visible.value = false;
  try {
    localStorage.setItem(STORAGE_KEY, '1');
  } catch (e) {
    // ignore
  }
};
</script>
