<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import TasksIndex from '@/components/tasks/TasksIndex.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    tasks: Object,
    stats: Object,
    leads: Array,
    types: Object,
    priorities: Object,
    statuses: Object,
});
</script>

<template>
    <SalesHeadLayout title="Vazifalar">
        <Head title="Vazifalar" />
        <TasksIndex
            :tasks="tasks"
            :stats="stats"
            :leads="leads"
            :types="types"
            :priorities="priorities"
            :statuses="statuses"
            panel-type="saleshead"
        />
    </SalesHeadLayout>
</template>
