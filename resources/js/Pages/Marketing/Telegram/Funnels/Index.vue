<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramFunnelsIndex from '@/components/telegram/TelegramFunnelsIndex.vue'

defineProps({
  bot: Object,
  funnels: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <MarketingLayout :title="`Funnellar - @${bot.username}`">
    <Head :title="`Funnellar - @${bot.username}`" />
    <TelegramFunnelsIndex :bot="bot" :funnels="funnels" panel-type="marketing" />
  </MarketingLayout>
</template>
