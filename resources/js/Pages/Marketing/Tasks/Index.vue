<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import TasksIndex from '@/components/tasks/TasksIndex.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

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
    <MarketingLayout :title="t('tasks.title')">
        <Head :title="t('tasks.title')" />
        <TasksIndex
            :tasks="tasks"
            :stats="stats"
            :leads="leads"
            :types="types"
            :priorities="priorities"
            :statuses="statuses"
            panel-type="marketing"
        />
    </MarketingLayout>
</template>
