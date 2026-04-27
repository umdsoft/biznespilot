<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'
import OperatorLayout from '@/layouts/OperatorLayout.vue'
import HRLayout from '@/layouts/HRLayout.vue'
import FinanceLayout from '@/layouts/FinanceLayout.vue'
import TelegramBotShow from '@/components/telegram/TelegramBotShow.vue'

const props = defineProps({
  bot: Object,
  funnels: {
    type: Array,
    default: () => []
  },
  recentStats: {
    type: Array,
    default: () => []
  },
  store: {
    type: Object,
    default: null
  },
  panelType: {
    type: String,
    default: 'business'
  }
})

const layoutComponent = computed(() => {
  const map = {
    business: BusinessLayout,
    marketing: MarketingLayout,
    saleshead: SalesHeadLayout,
    operator: OperatorLayout,
    hr: HRLayout,
    finance: FinanceLayout,
  }
  return map[props.panelType] || BusinessLayout
})
</script>

<template>
  <component :is="layoutComponent" :title="bot.first_name">
    <Head :title="bot.first_name" />
    <TelegramBotShow :bot="bot" :funnels="funnels" :recent-stats="recentStats" :store="store" :panel-type="panelType" />
  </component>
</template>
