<template>
  <Head>
    <title>{{ title }}</title>
    <meta name="robots" content="noindex, nofollow" />
  </Head>

  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/50 flex items-center justify-center px-4">
    <div class="max-w-lg w-full text-center">
      <!-- Error Code -->
      <div class="relative mb-8">
        <span class="text-[160px] sm:text-[200px] font-black text-transparent bg-clip-text bg-gradient-to-r leading-none select-none"
              :class="statusGradient">
          {{ status }}
        </span>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="w-24 h-24 rounded-full flex items-center justify-center" :class="iconBg">
            <!-- 404 icon -->
            <svg v-if="status === 404" class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
            </svg>
            <!-- 500 icon -->
            <svg v-else-if="status === 500" class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <!-- 403 icon -->
            <svg v-else-if="status === 403" class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <!-- Default icon -->
            <svg v-else class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 2a10 10 0 110 20 10 10 0 010-20z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Error Message -->
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">{{ heading }}</h1>
      <p class="text-gray-500 text-lg mb-8 max-w-md mx-auto">{{ description }}</p>

      <!-- Actions -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
        <a href="/" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/25 hover:shadow-xl hover:-translate-y-0.5">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Bosh sahifa
        </a>
        <button @click="goBack" class="inline-flex items-center gap-2 px-6 py-3 bg-white text-gray-700 font-semibold rounded-xl border border-gray-200 hover:bg-gray-50 transition-all shadow-sm">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Orqaga
        </button>
      </div>

      <!-- Help links -->
      <div class="mt-10 flex items-center justify-center gap-6 text-sm text-gray-400">
        <a href="/pricing" class="hover:text-blue-600 transition-colors">Tariflar</a>
        <span>|</span>
        <a href="/blog" class="hover:text-blue-600 transition-colors">Blog</a>
        <span>|</span>
        <a href="https://t.me/biznespilot_support" target="_blank" class="hover:text-blue-600 transition-colors">Yordam</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  status: { type: Number, default: 404 },
});

const messages = {
  404: { title: 'Sahifa topilmadi | BiznesPilot', heading: 'Sahifa topilmadi', description: "Kechirasiz, siz qidirayotgan sahifa mavjud emas yoki ko'chirilgan." },
  500: { title: 'Server xatosi | BiznesPilot', heading: 'Server xatosi', description: "Kechirasiz, kutilmagan xatolik yuz berdi. Tez orada tuzatamiz." },
  403: { title: 'Ruxsat berilmagan | BiznesPilot', heading: 'Ruxsat berilmagan', description: "Kechirasiz, bu sahifaga kirish uchun ruxsatingiz yo'q." },
  419: { title: 'Sessiya tugagan | BiznesPilot', heading: 'Sessiya muddati tugadi', description: "Sahifani yangilang va qayta urinib ko'ring." },
  503: { title: 'Texnik ish | BiznesPilot', heading: 'Texnik ish olib borilmoqda', description: "Platforma vaqtincha ishlamayapti. Tez orada qaytamiz!" },
};

const fallback = { title: 'Xatolik | BiznesPilot', heading: 'Xatolik yuz berdi', description: "Kutilmagan xatolik. Iltimos, qayta urinib ko'ring." };

const msg = computed(() => messages[props.status] || fallback);
const title = computed(() => msg.value.title);
const heading = computed(() => msg.value.heading);
const description = computed(() => msg.value.description);

const statusGradient = computed(() => ({
  404: 'from-blue-200 to-indigo-200',
  500: 'from-red-200 to-orange-200',
  403: 'from-amber-200 to-yellow-200',
  419: 'from-gray-200 to-slate-200',
  503: 'from-purple-200 to-violet-200',
}[props.status] || 'from-gray-200 to-slate-200'));

const iconBg = computed(() => ({
  404: 'bg-gradient-to-br from-blue-500 to-indigo-600',
  500: 'bg-gradient-to-br from-red-500 to-orange-600',
  403: 'bg-gradient-to-br from-amber-500 to-yellow-600',
  419: 'bg-gradient-to-br from-gray-500 to-slate-600',
  503: 'bg-gradient-to-br from-purple-500 to-violet-600',
}[props.status] || 'bg-gradient-to-br from-gray-500 to-slate-600'));

const goBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    window.location.href = '/';
  }
};
</script>
