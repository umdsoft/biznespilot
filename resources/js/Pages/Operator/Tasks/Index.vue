<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SimpleTasks from '@/components/tasks/SimpleTasks.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    tasks: Array,
    stats: Object,
    filters: Object,
});
</script>

<template>
    <OperatorLayout title="Vazifalar">
        <Head title="Mening Vazifalarim" />
        <SimpleTasks :tasks="tasks" :stats="stats" :filters="filters" panel-type="operator" />
    </OperatorLayout>
</template>
