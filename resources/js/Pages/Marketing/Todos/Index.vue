<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import TodosPage from '@/components/todos/TodosPage.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

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
    <MarketingLayout :title="t('tasks.title')">
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
            panel-type="marketing"
        />
    </MarketingLayout>
</template>
