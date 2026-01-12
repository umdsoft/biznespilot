<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
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
    <OperatorLayout title="Todo List">
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
            panel-type="operator"
        />
    </OperatorLayout>
</template>
