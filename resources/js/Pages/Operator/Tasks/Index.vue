<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import TasksIndex from '@/components/tasks/TasksIndex.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    tasks: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    leads: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Object,
        default: () => ({}),
    },
    priorities: {
        type: Object,
        default: () => ({}),
    },
    statuses: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <OperatorLayout title="Vazifalar">
        <Head title="Mening Vazifalarim" />
        <TasksIndex
            :tasks="tasks"
            :stats="stats"
            :leads="leads"
            :types="types"
            :priorities="priorities"
            :statuses="statuses"
            panel-type="operator"
        />
    </OperatorLayout>
</template>
