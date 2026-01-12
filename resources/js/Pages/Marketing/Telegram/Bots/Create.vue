<script setup>
import { Head } from '@inertiajs/vue3'
import MarketingLayout from '@/layouts/MarketingLayout.vue'
import TelegramBotCreate from '@/components/telegram/TelegramBotCreate.vue'
</script>

<template>
  <MarketingLayout title="Yangi Bot Qo'shish">
    <Head title="Yangi Bot Qo'shish" />
    <TelegramBotCreate panel-type="marketing" />
  </MarketingLayout>
</template>
