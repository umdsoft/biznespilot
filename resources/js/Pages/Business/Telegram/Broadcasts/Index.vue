<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import TelegramBroadcastsIndex from '@/components/telegram/TelegramBroadcastsIndex.vue'
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
  bot: Object,
  broadcasts: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <BusinessLayout :title="`Broadcast - @${bot.username}`">
    <Head :title="`Broadcast - @${bot.username}`" />
    <TelegramBroadcastsIndex :bot="bot" :broadcasts="broadcasts" panel-type="business" />
  </BusinessLayout>
</template>
