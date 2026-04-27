<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'
import OperatorLayout from '@/layouts/OperatorLayout.vue'
import HRLayout from '@/layouts/HRLayout.vue'
import FinanceLayout from '@/layouts/FinanceLayout.vue'
import TelegramBroadcastCreate from '@/components/telegram/TelegramBroadcastCreate.vue'
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  bot: Object,
  availableTags: {
    type: Array,
    default: () => []
  },
  totalUsers: {
    type: Number,
    default: 0
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
  <component :is="layoutComponent" :title="`Yangi Broadcast - @${bot.username}`">
    <Head :title="`Yangi Broadcast - @${bot.username}`" />
    <TelegramBroadcastCreate :bot="bot" :available-tags="availableTags" :total-users="totalUsers" :panel-type="panelType" />
  </component>
</template>
