<script setup>
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import FinanceDashboard from '@/components/dashboard/FinanceDashboard.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    recentTransactions: Array,
    pendingInvoices: Array,
    currentBusiness: Object,
});
</script>

<template>
    <FinanceLayout title="Dashboard">
        <Head title="Moliya Dashboard" />
        <FinanceDashboard
            :stats="stats"
            :recent-transactions="recentTransactions"
            :pending-invoices="pendingInvoices"
            :current-business="currentBusiness"
            panel-type="finance"
        />
    </FinanceLayout>
</template>
