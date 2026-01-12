<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramTriggersIndex from '@/components/telegram/TelegramTriggersIndex.vue'

defineProps({
  bot: Object,
  triggers: {
    type: Array,
    default: () => []
  },
  funnels: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <MarketingLayout :title="`Triggerlar - @${bot.username}`">
    <Head :title="`Triggerlar - @${bot.username}`" />
    <TelegramTriggersIndex :bot="bot" :triggers="triggers" :funnels="funnels" panel-type="marketing" />
  </MarketingLayout>
</template>
