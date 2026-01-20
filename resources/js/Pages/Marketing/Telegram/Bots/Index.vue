<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramBotsIndex from '@/components/telegram/TelegramBotsIndex.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

defineProps({
  bots: {
    type: Array,
    default: () => []
  }
})
</script>

<template>
  <MarketingLayout :title="t('telegram.bots')">
    <Head :title="t('telegram.bots')" />
    <TelegramBotsIndex :bots="bots" panel-type="marketing" />
  </MarketingLayout>
</template>
