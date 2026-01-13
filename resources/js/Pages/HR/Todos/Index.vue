<script setup>
import HRLayout from '@/layouts/HRLayout.vue';
import { Head } from '@inertiajs/vue3';
import TodosIndex from '@/components/todos/TodosIndex.vue';

defineProps({
    todos: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    teamMembers: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    types: { type: Object, default: () => ({}) },
    priorities: { type: Object, default: () => ({}) },
    statuses: { type: Object, default: () => ({}) },
    filter: { type: String, default: 'all' },
    statusFilter: { type: String, default: 'active' },
});
</script>

<template>
    <HRLayout title="Todo List">
        <Head title="Todo List" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Todo List</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kunlik vazifalar va eslatkichlar</p>
            </div>

            <!-- Todos Component -->
            <TodosIndex
                :todos="todos"
                :stats="stats"
                :teamMembers="teamMembers"
                :templates="templates"
                :types="types"
                :priorities="priorities"
                :statuses="statuses"
                :filter="filter"
                :statusFilter="statusFilter"
                panelType="hr"
            />
        </div>
    </HRLayout>
</template>
