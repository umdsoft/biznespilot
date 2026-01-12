<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramBotsIndex from '@/components/telegram/TelegramBotsIndex.vue'

defineProps({
  bots: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <MarketingLayout title="Telegram Botlar">
    <Head title="Telegram Botlar" />
    <TelegramBotsIndex :bots="bots" panel-type="marketing" />
  </MarketingLayout>
</template>
