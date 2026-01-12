<script setup>
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import SimpleTasks from '@/components/tasks/SimpleTasks.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ tasks: Array, stats: Object, filters: Object });
</script>

<template>
    <FinanceLayout title="Vazifalar">
        <Head title="Vazifalar" />
        <SimpleTasks :tasks="tasks" :stats="stats" :filters="filters" panel-type="finance" />
    </FinanceLayout>
</template>
