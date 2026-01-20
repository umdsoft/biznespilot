<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import TelegramFunnelsIndex from '@/components/telegram/TelegramFunnelsIndex.vue'
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
  bot: Object,
  funnels: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <BusinessLayout :title="`${bot.first_name} - Funnellar`">
    <Head :title="`${bot.first_name} - Funnellar`" />
    <TelegramFunnelsIndex :bot="bot" :funnels="funnels" panel-type="business" />
  </BusinessLayout>
</template>
