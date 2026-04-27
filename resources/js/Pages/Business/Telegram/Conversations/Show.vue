<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue'
import OperatorLayout from '@/layouts/OperatorLayout.vue'
import HRLayout from '@/layouts/HRLayout.vue'
import FinanceLayout from '@/layouts/FinanceLayout.vue'
import TelegramConversationShow from '@/components/telegram/TelegramConversationShow.vue'
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  bot: Object,
  conversation: Object,
  user: Object,
  messages: {
    type: Array,
    default: () => []
  },
  lead: Object,
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
  <component :is="layoutComponent" :title="`Suhbat - ${user.full_name}`">
    <Head :title="`Suhbat - ${user.full_name}`" />
    <TelegramConversationShow :bot="bot" :conversation="conversation" :user="user" :messages="messages" :lead="lead" :panel-type="panelType" />
  </component>
</template>
