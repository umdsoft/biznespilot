<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import TodosPage from '@/components/todos/TodosPage.vue';

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
    <BusinessLayout title="Vazifalar">
        <TodosPage
            :todos="todos"
            :stats="stats"
            :team-members="teamMembers"
            :templates="templates"
            :types="types"
            :priorities="priorities"
            :statuses="statuses"
            :filter="filter"
            :status-filter="statusFilter"
            panel-type="business"
        />
    </BusinessLayout>
</template>
