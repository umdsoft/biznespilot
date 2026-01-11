<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import TodosIndex from '@/components/todos/TodosIndex.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    todos: Object,
    stats: Object,
    teamMembers: Array,
    templates: Array,
    types: Object,
    priorities: Object,
    statuses: Object,
    filter: String,
    statusFilter: String,
});
</script>

<template>
    <SalesHeadLayout title="Vazifalar">
        <Head title="Vazifalar" />
        <TodosIndex
            :todos="todos"
            :stats="stats"
            :team-members="teamMembers"
            :templates="templates"
            :types="types"
            :priorities="priorities"
            :statuses="statuses"
            :filter="filter"
            :status-filter="statusFilter"
            panel-type="saleshead"
        />
    </SalesHeadLayout>
</template>
