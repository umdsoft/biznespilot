<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramFunnelBuilder from '@/components/telegram/TelegramFunnelBuilder.vue'

defineProps({
  bot: Object,
  funnel: Object,
  steps: { type: Array, default: () => [] }
})
</script>

<template>
  <MarketingLayout :title="`${funnel.name} - Funnel Builder`">
    <Head :title="`${funnel.name} - Funnel Builder`" />
    <TelegramFunnelBuilder :bot="bot" :funnel="funnel" :steps="steps" panel-type="marketing" />
  </MarketingLayout>
</template>
