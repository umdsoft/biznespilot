<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramFunnelBuilder from '@/components/telegram/TelegramFunnelBuilder.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
  bot: Object,
  funnel: Object,
  steps: { type: Array, default: () => [] }
})
</script>

<template>
  <MarketingLayout :title="`${funnel.name} - ${t('telegram.funnel_builder')}`">
    <Head :title="`${funnel.name} - ${t('telegram.funnel_builder')}`" />
    <TelegramFunnelBuilder :bot="bot" :funnel="funnel" :steps="steps" panel-type="marketing" />
  </MarketingLayout>
</template>
