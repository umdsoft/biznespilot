<script setup>
import TodosIndex from '@/components/todos/TodosIndex.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    todos: { type: Object, default: () => ({ overdue: [], today: [], tomorrow: [], this_week: [], later: [] }) },
    stats: { type: Object, default: () => ({ total: 0, overdue: 0, completed_today: 0 }) },
    teamMembers: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    types: { type: Object, default: () => ({}) },
    priorities: { type: Object, default: () => ({}) },
    statuses: { type: Object, default: () => ({}) },
    filter: { type: String, default: 'all' },
    statusFilter: { type: String, default: 'active' },
    panelType: {
        type: String,
        required: true,
        validator: (value) => ['business', 'saleshead', 'marketing', 'finance', 'operator'].includes(value),
    },
});
</script>

<template>
    <div>
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
            :panel-type="panelType"
        />
    </div>
</template>
