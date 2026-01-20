<script setup>
import { CurrencyDollarIcon, DocumentTextIcon, CreditCardIcon, ArrowTrendingUpIcon } from '@heroicons/vue/24/outline';
import StatCard from './StatCard.vue';
import DashboardCard from './DashboardCard.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    recentTransactions: { type: Array, default: () => [] },
    pendingInvoices: { type: Array, default: () => [] },
    currentBusiness: { type: Object, default: null },
    panelType: {
        type: String,
        default: 'finance',
        validator: (v) => ['finance', 'business'].includes(v),
    },
});

const formatCurrency = (v) => {
    if (!v) return `0 ${t('common.currency')}`;
    return new Intl.NumberFormat('uz-UZ').format(v) + ` ${t('common.currency')}`;
};

const getBasePath = () => {
    return props.panelType === 'finance' ? '/finance' : '/business/finance';
};
</script>

<template>
    <div class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
                :title="t('dashboard.finance.this_month_revenue')"
                :value="formatCurrency(stats?.revenue?.this_month)"
                :badge="`+${stats?.revenue?.growth || 0}%`"
                badge-color="green"
                :icon="ArrowTrendingUpIcon"
                icon-bg-color="green"
            />
            <StatCard
                :title="t('dashboard.finance.this_month_expenses')"
                :value="formatCurrency(stats?.expenses?.this_month)"
                :badge="`+${stats?.expenses?.growth || 0}%`"
                badge-color="red"
                :icon="CreditCardIcon"
                icon-bg-color="red"
            />
            <StatCard
                :title="t('dashboard.finance.net_profit')"
                :value="formatCurrency(stats?.profit?.this_month)"
                :badge="`${stats?.profit?.margin || 0}% ${t('dashboard.finance.margin')}`"
                badge-color="blue"
                :icon="CurrencyDollarIcon"
                icon-bg-color="blue"
            />
            <StatCard
                :title="t('dashboard.finance.pending_invoices')"
                :value="stats?.invoices?.pending || 0"
                :badge="`${stats?.invoices?.overdue || 0} ${t('dashboard.finance.overdue')}`"
                badge-color="orange"
                :icon="DocumentTextIcon"
                icon-bg-color="orange"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Transactions -->
            <DashboardCard
                :title="t('dashboard.finance.recent_transactions')"
                :link-href="`${getBasePath()}/transactions`"
                divided
                no-padding
            >
                <template v-if="recentTransactions?.length">
                    <div
                        v-for="t in recentTransactions"
                        :key="t.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ t.description }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t.date }}</p>
                            </div>
                            <span
                                :class="t.type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                class="font-semibold"
                            >
                                {{ t.type === 'income' ? '+' : '-' }}{{ formatCurrency(t.amount) }}
                            </span>
                        </div>
                    </div>
                </template>
                <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">
                    {{ t('dashboard.finance.no_transactions') }}
                </div>
            </DashboardCard>

            <!-- Pending Invoices -->
            <DashboardCard
                :title="t('dashboard.finance.pending_invoices_title')"
                :link-href="`${getBasePath()}/invoices`"
                divided
                no-padding
            >
                <template v-if="pendingInvoices?.length">
                    <div
                        v-for="inv in pendingInvoices"
                        :key="inv.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ inv.number }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ inv.client }} · {{ inv.due_date }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(inv.amount) }}</p>
                                <span
                                    :class="inv.status === 'overdue'
                                        ? 'text-red-600 dark:text-red-400'
                                        : 'text-yellow-600 dark:text-yellow-400'"
                                    class="text-xs"
                                >
                                    {{ inv.status === 'overdue' ? t('dashboard.finance.overdue') : t('dashboard.finance.pending') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">
                    {{ t('dashboard.finance.no_pending_invoices') }}
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
