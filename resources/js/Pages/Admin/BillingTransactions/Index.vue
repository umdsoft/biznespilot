<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    CreditCardIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    BanknotesIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

defineOptions({
    layout: AdminLayout
});

const props = defineProps({
    transactions: { type: Object, default: () => ({ data: [] }) },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            paid: 0,
            pending: 0,
            failed: 0,
            total_revenue: 0,
        })
    },
    filters: { type: Object, default: () => ({}) },
});

const statusFilter = ref(props.filters.status || '');
const providerFilter = ref(props.filters.provider || '');
const searchQuery = ref(props.filters.search || '');

const applyFilters = () => {
    router.get('/dashboard/billing-transactions', {
        status: statusFilter.value || undefined,
        provider: providerFilter.value || undefined,
        search: searchQuery.value || undefined,
    }, { preserveState: true });
};

const formatPrice = (price) => {
    if (!price) return '0';
    return new Intl.NumberFormat('uz-UZ').format(price);
};

const statusLabel = (status) => {
    const labels = {
        created: 'Yaratildi',
        waiting: 'Kutilmoqda',
        processing: 'Jarayonda',
        paid: 'To\'langan',
        cancelled: 'Bekor qilindi',
        failed: 'Xatolik',
        refunded: 'Qaytarildi',
    };
    return labels[status] || status;
};

const statusClass = (status) => {
    const classes = {
        paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        created: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        waiting: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        processing: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        refunded: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const statCards = computed(() => [
    { label: 'Jami tranzaksiyalar', value: props.stats.total, icon: CreditCardIcon, color: 'blue' },
    { label: 'To\'langan', value: props.stats.paid, icon: CheckCircleIcon, color: 'green' },
    { label: 'Kutilayotgan', value: props.stats.pending, icon: ClockIcon, color: 'yellow' },
    { label: 'Muvaffaqiyatsiz', value: props.stats.failed, icon: XCircleIcon, color: 'red' },
]);

const colorClasses = {
    blue: 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
    green: 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400',
    yellow: 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400',
    red: 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400',
};
</script>

<template>
    <Head title="To'lovlar" />

    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">To'lovlar</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Barcha billing tranzaksiyalari</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div v-for="stat in statCards" :key="stat.label"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="colorClasses[stat.color]">
                        <component :is="stat.icon" class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stat.value }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ stat.label }}</p>
                    </div>
                </div>
            </div>
            <!-- Revenue Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400">
                        <BanknotesIcon class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatPrice(stats.total_revenue) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Jami daromad (UZS)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3">
            <select v-model="statusFilter" @change="applyFilters"
                class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl text-sm">
                <option value="">Barcha holatlar</option>
                <option value="paid">To'langan</option>
                <option value="created">Yaratildi</option>
                <option value="waiting">Kutilmoqda</option>
                <option value="processing">Jarayonda</option>
                <option value="cancelled">Bekor qilindi</option>
                <option value="failed">Xatolik</option>
            </select>
            <select v-model="providerFilter" @change="applyFilters"
                class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl text-sm">
                <option value="">Barcha provayderlar</option>
                <option value="click">Click</option>
                <option value="payme">Payme</option>
            </select>
            <div class="relative flex-1 min-w-[200px]">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                    v-model="searchQuery"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Order ID yoki biznes nomi bo'yicha qidirish..."
                    class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-blue-500"
                />
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div v-if="transactions.data && transactions.data.length > 0" class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Biznes</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarif</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Provider</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Summa</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Holat</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sana</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4 text-sm font-mono text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ t.order_id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ t.business_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ t.plan_name }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-md"
                                    :class="t.provider === 'click' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'">
                                    {{ t.provider === 'click' ? 'Click' : 'Payme' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ formatPrice(t.amount) }} {{ t.currency }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-md" :class="statusClass(t.status)">
                                    {{ statusLabel(t.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ t.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16">
                <CreditCardIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                <p class="text-gray-500 dark:text-gray-400 text-lg">Hali tranzaksiyalar yo'q</p>
            </div>

            <!-- Pagination -->
            <div v-if="transactions.links && transactions.last_page > 1" class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ transactions.from }}-{{ transactions.to }} / {{ transactions.total }}
                </p>
                <div class="flex gap-2">
                    <a v-for="link in transactions.links" :key="link.label"
                        :href="link.url"
                        class="px-3 py-1.5 text-sm rounded-lg transition-colors"
                        :class="link.active
                            ? 'bg-blue-600 text-white'
                            : link.url
                                ? 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                : 'text-gray-300 dark:text-gray-600 cursor-not-allowed'"
                        v-html="link.label"
                    ></a>
                </div>
            </div>
        </div>
    </div>
</template>
