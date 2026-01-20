<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
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
    <SalesHeadLayout :title="t('nav.inbox')">
        <Head :title="t('nav.inbox')" />
        <InboxIndex
            :conversations="conversations"
            :stats="stats"
            :filters="filters"
            :current-business="currentBusiness"
            panel-type="saleshead"
        />
    </SalesHeadLayout>
</template>
