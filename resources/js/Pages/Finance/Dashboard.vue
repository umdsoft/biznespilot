<script setup>
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import FinanceDashboard from '@/components/Dashboard/FinanceDashboard.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    stats: Object,
    recentTransactions: Array,
    pendingInvoices: Array,
    currentBusiness: Object,
});
</script>

<template>
    <FinanceLayout :title="t('finance.dashboard')">
        <Head :title="t('finance.dashboard_page_title')" />
        <FinanceDashboard
            :stats="stats"
            :recent-transactions="recentTransactions"
            :pending-invoices="pendingInvoices"
            :current-business="currentBusiness"
            panel-type="finance"
        />
    </FinanceLayout>
</template>
