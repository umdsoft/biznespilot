<script setup>
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
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
    <BusinessLayout title="Vazifalar">
        <Head title="Vazifalar" />
        <TasksIndex
            :tasks="tasks"
            :stats="stats"
            :leads="leads"
            :types="types"
            :priorities="priorities"
            :statuses="statuses"
            panel-type="business"
        />
    </BusinessLayout>
</template>
