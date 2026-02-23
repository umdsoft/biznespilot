<script setup>
import { Head } from '@inertiajs/vue3'
import BusinessLayout from '@/layouts/BusinessLayout.vue'
import TelegramBotsIndex from '@/components/telegram/TelegramBotsIndex.vue'
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
  bots: {
    type: Array,
    default: () => []
  },
  botLimit: {
    type: Object,
    default: null
  }
})
</script>

<template>
  <BusinessLayout :title="t('telegram.bots')">
    <Head :title="t('telegram.bots')" />
    <TelegramBotsIndex :bots="bots" :bot-limit="botLimit" panel-type="business" />
  </BusinessLayout>
</template>
