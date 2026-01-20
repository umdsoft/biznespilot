<script setup>
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import SimpleTasks from '@/components/tasks/SimpleTasks.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({ tasks: Array, stats: Object, filters: Object });
</script>

<template>
    <FinanceLayout :title="t('nav.tasks')">
        <Head :title="t('nav.tasks')" />
        <SimpleTasks :tasks="tasks" :stats="stats" :filters="filters" panel-type="finance" />
    </FinanceLayout>
</template>
