<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import TelegramBotsIndex from '@/components/telegram/TelegramBotsIndex.vue'

defineProps({
  bots: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <BusinessLayout title="Telegram Botlar">
    <Head title="Telegram Botlar" />
    <TelegramBotsIndex :bots="bots" panel-type="business" />
  </BusinessLayout>
</template>
