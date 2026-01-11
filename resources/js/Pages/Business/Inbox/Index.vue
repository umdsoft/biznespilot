<script setup>
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
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
    <BusinessLayout title="Yagona Inbox">
        <Head title="Yagona Inbox" />
        <InboxIndex
            :conversations="conversations"
            :stats="stats"
            :filters="filters"
            :current-business="currentBusiness"
            panel-type="business"
        />
    </BusinessLayout>
</template>
