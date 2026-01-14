<script setup>
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
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
    <BusinessLayout :title="t('nav.tasks')">
        <Head :title="t('nav.tasks')" />
        <TasksIndex
            :tasks="tasks"
            :stats="stats"
            :leads="leads"
            :types="types"
            :priorities="priorities"
            :statuses="statuses"
            panel-type="business"
        />
    </BusinessLayout>
</template>
