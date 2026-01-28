<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    CreditCardIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    MagnifyingGlassIcon,
    ArrowDownTrayIcon,
    BuildingOfficeIcon,
    ClockIcon,
    BanknotesIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineOptions({
    layout: AdminLayout
});

const props = defineProps({
    subscriptions: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            trial: 0,
            cancelled: 0,
            expired: 0,
            total_revenue: 0,
            mrr: 0
        })
    },
    plans: {
        type: Array,
        default: () => []
    }
});

const searchQuery = ref('');
const selectedStatus = ref('all');
const selectedPlan = ref('all');
const isLoading = ref(false);

const statusFilters = [
    { value: 'all', label: 'Barchasi', count: props.stats.total },
    { value: 'active', label: 'Faol', count: props.stats.active },
    { value: 'trial', label: 'Sinov', count: props.stats.trial },
    { value: 'cancelled', label: 'Bekor', count: props.stats.cancelled },
    { value: 'expired', label: 'Tugagan', count: props.stats.expired }
];

const filteredSubscriptions = computed(() => {
    let result = props.subscriptions;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(sub =>
            sub.business?.name?.toLowerCase().includes(query) ||
            sub.plan?.name?.toLowerCase().includes(query)
        );
    }

    if (selectedStatus.value !== 'all') {
        result = result.filter(sub => sub.status === selectedStatus.value);
    }

    if (selectedPlan.value !== 'all') {
        result = result.filter(sub => sub.plan_id === selectedPlan.value);
    }

    return result;
});

const getStatusConfig = (status) => {
    const configs = {
        active: { bg: 'bg-emerald-500', text: 'text-emerald-700 dark:text-emerald-400', label: 'Faol' },
        trial: { bg: 'bg-blue-500', text: 'text-blue-700 dark:text-blue-400', label: 'Sinov' },
        cancelled: { bg: 'bg-red-500', text: 'text-red-700 dark:text-red-400', label: 'Bekor' },
        expired: { bg: 'bg-gray-400', text: 'text-gray-600 dark:text-gray-400', label: 'Tugagan' },
        pending: { bg: 'bg-amber-500', text: 'text-amber-700 dark:text-amber-400', label: 'Kutilmoqda' }
    };
    return configs[status] || configs.pending;
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('uz-UZ', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const formatCurrency = (amount) => {
    if (!amount) return '0';
    return new Intl.NumberFormat('uz-UZ').format(amount);
};

const refreshData = () => {
    isLoading.value = true;
    router.reload({ onFinish: () => isLoading.value = false });
};
</script>

<template>
    <Head title="Obunalar" />

    <div class="p-4 lg:p-6 min-h-screen">
        <div class="max-w-[1800px] mx-auto">
            <!-- Compact Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-gray-900 dark:text-white">Obunalar</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ filteredSubscriptions.length }} ta obuna</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="refreshData"
                        :disabled="isLoading"
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                    >
                        <ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': isLoading }" />
                    </button>
                    <button class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                        <ArrowDownTrayIcon class="w-4 h-4" />
                        Export
                    </button>
                </div>
            </div>

            <!-- Stats Bar - Compact Horizontal -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                            <CreditCardIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Jami</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <CheckCircleIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.active }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Faol</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <ClockIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.trial }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Sinov</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <XCircleIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.cancelled }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Bekor</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <BanknotesIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ formatCurrency(stats.mrr) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">MRR</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <BanknotesIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatCurrency(stats.total_revenue) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Jami daromad</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Filters Row -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col lg:flex-row gap-4 lg:items-center lg:justify-between">
                        <!-- Search -->
                        <div class="relative flex-1 max-w-md">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Qidirish..."
                                class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>

                        <!-- Status Tabs -->
                        <div class="flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-900 rounded-lg">
                            <button
                                v-for="filter in statusFilters"
                                :key="filter.value"
                                @click="selectedStatus = filter.value"
                                :class="[
                                    'px-3 py-1.5 text-xs font-medium rounded-md transition-all',
                                    selectedStatus === filter.value
                                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                ]"
                            >
                                {{ filter.label }}
                                <span class="ml-1 text-gray-400">{{ filter.count }}</span>
                            </button>
                        </div>

                        <!-- Plan Filter -->
                        <select
                            v-if="plans.length > 0"
                            v-model="selectedPlan"
                            class="px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                            <option value="all">Barcha tariflar</option>
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Biznes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarif</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Narx</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Davr</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Boshlanish</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tugash</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Auto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="sub in filteredSubscriptions"
                                :key="sub.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-bold text-white">{{ sub.business?.name?.charAt(0) || '?' }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ sub.business?.name || 'N/A' }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ sub.business?.owner?.email || '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-900 dark:text-white">{{ sub.plan?.name || '—' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span :class="['w-2 h-2 rounded-full', getStatusConfig(sub.status).bg]"></span>
                                        <span :class="['text-sm font-medium', getStatusConfig(sub.status).text]">
                                            {{ getStatusConfig(sub.status).label }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatCurrency(sub.amount) }}</span>
                                    <span class="text-xs text-gray-500 ml-1">{{ sub.currency }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ sub.billing_cycle === 'monthly' ? 'Oylik' : 'Yillik' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatDate(sub.starts_at) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ formatDate(sub.ends_at) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <CheckCircleIcon v-if="sub.auto_renew" class="w-5 h-5 text-emerald-500 mx-auto" />
                                    <XCircleIcon v-else class="w-5 h-5 text-gray-300 dark:text-gray-600 mx-auto" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="filteredSubscriptions.length === 0" class="py-16 text-center">
                    <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                        <CreditCardIcon class="w-8 h-8 text-gray-400" />
                    </div>
                    <h3 class="text-base font-medium text-gray-900 dark:text-white mb-1">Obunalar topilmadi</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Hozircha hech qanday obuna mavjud emas</p>
                </div>
            </div>
        </div>
    </div>
</template>
