<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import InboxIndex from '@/components/inbox/InboxIndex.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    conversations: Array,
    stats: Object,
    filters: Object,
    currentBusiness: Object,
});
</script>

<template>
    <SalesHeadLayout title="Yagona Inbox">
        <Head title="Yagona Inbox" />
        <InboxIndex
            :conversations="conversations"
            :stats="stats"
            :filters="filters"
            :current-business="currentBusiness"
            panel-type="saleshead"
        />
    </SalesHeadLayout>
</template>
