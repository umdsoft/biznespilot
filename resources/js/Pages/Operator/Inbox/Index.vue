<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import InboxIndex from '@/components/inbox/InboxIndex.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    conversations: Array,
    stats: Object,
    filters: Object,
});
</script>

<template>
    <OperatorLayout title="Yagona Inbox">
        <Head title="Yagona Inbox" />
        <InboxIndex :conversations="conversations" :stats="stats" :filters="filters" panel-type="operator" />
    </OperatorLayout>
</template>
