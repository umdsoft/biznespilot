<template>
  <div>
    <!-- Header -->
    <div class="mb-8">
      <Link
        :href="getRoute('telegram-funnels.index')"
        class="inline-flex items-center text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 mb-4 transition-colors"
      >
        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        {{ t('common.back') }}
      </Link>
      <div class="flex items-center gap-3">
        <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-600/25">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Yangi bot yaratish</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400">Qanday turdagi bot yaratmoqchisiz? Turni tanlang va sozlashni boshlang.</p>
        </div>
      </div>
    </div>

    <!-- Bot Type Selection Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <button
        v-for="bt in botTypes"
        :key="bt.key"
        @click="selectBotType(bt.key)"
        class="group relative flex flex-col items-start p-5 rounded-xl border-2 transition-all duration-200 text-left hover:shadow-md"
        :class="selectedType === bt.key
          ? 'border-blue-500 bg-blue-50/80 dark:bg-blue-950/30 shadow-md ring-1 ring-blue-500/20'
          : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 bg-white dark:bg-slate-800'"
      >
        <!-- Icon -->
        <div
          class="w-11 h-11 rounded-lg flex items-center justify-center mb-3 transition-all"
          :class="selectedType === bt.key
            ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30'
            : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 group-hover:bg-slate-200 dark:group-hover:bg-slate-600'"
        >
          <span class="text-xl leading-none">{{ bt.icon }}</span>
        </div>

        <!-- Label & Description -->
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-1">{{ bt.label }}</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-2">{{ bt.description }}</p>

        <!-- Features Preview -->
        <div v-if="bt.features && bt.features.length" class="mt-auto pt-3 flex flex-wrap gap-1">
          <span
            v-for="feature in bt.features.slice(0, 3)"
            :key="feature"
            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
            :class="selectedType === bt.key
              ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300'
              : 'bg-slate-100 dark:bg-slate-700/80 text-slate-500 dark:text-slate-400'"
          >
            {{ getFeatureLabel(feature) }}
          </span>
          <span
            v-if="bt.features.length > 3"
            class="text-[10px] text-slate-400 dark:text-slate-500 px-1 py-0.5"
          >
            +{{ bt.features.length - 3 }}
          </span>
        </div>

        <!-- Selected indicator -->
        <div
          class="absolute top-3 right-3 transition-all"
          :class="selectedType === bt.key ? 'opacity-100 scale-100' : 'opacity-0 scale-75'"
        >
          <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
      </button>
    </div>

    <!-- Footer -->
    <div class="mt-8 flex items-center justify-between">
      <Link
        :href="getRoute('telegram-funnels.index')"
        class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors"
      >
        {{ t('common.cancel') }}
      </Link>
      <button
        @click="proceedToSetup"
        :disabled="!selectedType"
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 dark:disabled:bg-slate-700 text-white disabled:text-slate-500 dark:disabled:text-slate-500 font-medium rounded-lg transition-all shadow-sm hover:shadow-md disabled:shadow-none"
      >
        <span>Davom etish</span>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
  panelType: {
    type: String,
    default: 'business',
    validator: (value) => ['business', 'marketing'].includes(value)
  },
  botTypes: {
    type: Array,
    default: () => []
  }
})

const selectedType = ref(null)

const featureLabels = {
  cart: 'Savat',
  variants: 'Variantlar',
  reviews: 'Sharhlar',
  wishlist: 'Sevimlilar',
  delivery: 'Yetkazish',
  promo_codes: 'Promo kodlar',
  booking: 'Band qilish',
  staff: 'Xodimlar',
  schedule: 'Jadval',
  modifiers: "Qo'shimchalar",
  tickets: 'Chiptalar',
  map: 'Xarita',
  qr_code: 'QR kod',
  calculator: 'Kalkulyator',
}

const getFeatureLabel = (feature) => featureLabels[feature] || feature

const getRoute = (name, params = null) => {
  const prefix = props.panelType === 'business' ? 'business.' : 'marketing.'
  return params ? route(prefix + name, params) : route(prefix + name)
}

const selectBotType = (type) => {
  selectedType.value = type
}

const proceedToSetup = () => {
  if (!selectedType.value) return
  router.visit(route('business.store.setup.wizard') + '?type=' + selectedType.value)
}
</script>
