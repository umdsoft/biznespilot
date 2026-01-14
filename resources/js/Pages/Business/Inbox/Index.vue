<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import InboxIndex from '@/components/inbox/InboxIndex.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    conversations: Array,
    stats: Object,
    filters: Object,
    currentBusiness: Object,
});
</script>

<template>
    <BusinessLayout :title="t('nav.inbox')">
        <Head :title="t('nav.inbox')" />
        <InboxIndex
            :conversations="conversations"
            :stats="stats"
            :filters="filters"
            :current-business="currentBusiness"
            panel-type="business"
        />
    </BusinessLayout>
</template>
