<script setup>
import { CurrencyDollarIcon, DocumentTextIcon, CreditCardIcon, ArrowTrendingUpIcon } from '@heroicons/vue/24/outline';
import StatCard from './StatCard.vue';
import DashboardCard from './DashboardCard.vue';

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
    if (!v) return "0 so'm";
    return new Intl.NumberFormat('uz-UZ').format(v) + " so'm";
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
                title="Bu oy daromad"
                :value="formatCurrency(stats?.revenue?.this_month)"
                :badge="`+${stats?.revenue?.growth || 0}%`"
                badge-color="green"
                :icon="ArrowTrendingUpIcon"
                icon-bg-color="green"
            />
            <StatCard
                title="Bu oy xarajat"
                :value="formatCurrency(stats?.expenses?.this_month)"
                :badge="`+${stats?.expenses?.growth || 0}%`"
                badge-color="red"
                :icon="CreditCardIcon"
                icon-bg-color="red"
            />
            <StatCard
                title="Sof foyda"
                :value="formatCurrency(stats?.profit?.this_month)"
                :badge="`${stats?.profit?.margin || 0}% marja`"
                badge-color="blue"
                :icon="CurrencyDollarIcon"
                icon-bg-color="blue"
            />
            <StatCard
                title="Kutilayotgan faktura"
                :value="stats?.invoices?.pending || 0"
                :badge="`${stats?.invoices?.overdue || 0} kechikkan`"
                badge-color="orange"
                :icon="DocumentTextIcon"
                icon-bg-color="orange"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Transactions -->
            <DashboardCard
                title="So'nggi Tranzaksiyalar"
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
                    Tranzaksiyalar mavjud emas
                </div>
            </DashboardCard>

            <!-- Pending Invoices -->
            <DashboardCard
                title="Kutilayotgan Fakturalar"
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
                                    {{ inv.status === 'overdue' ? 'Kechikkan' : 'Kutilmoqda' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">
                    Kutilayotgan faktura yo'q
                </div>
            </DashboardCard>
        </div>
    </div>
</template>
