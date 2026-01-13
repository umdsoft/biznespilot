<script setup>
import HRLayout from '@/layouts/HRLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CurrencyDollarIcon, UsersIcon, ClockIcon, BanknotesIcon, CalendarIcon, ChartBarIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    cycles: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' UZS';
};

const getStatusClass = (status) => {
    const classes = {
        'draft': 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
        'processing': 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'approved': 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'paid': 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
    };
    return classes[status] || classes['draft'];
};
</script>

<template>
    <HRLayout title="Ish Haqi">
        <Head title="Ish Haqi" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Ish Haqi Boshqaruvi</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Oylik maosh va to'lovlar tizimi</p>
                </div>
                <div class="flex gap-3">
                    <Link :href="route('hr.payroll.salary-structures')"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
                        <Cog6ToothIcon class="w-5 h-5" />
                        Maosh Tuzilmalari
                    </Link>
                    <Link :href="route('hr.payroll.bonuses')"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                        <BanknotesIcon class="w-5 h-5" />
                        Bonuslar
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <UsersIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total_employees || 0 }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami Xodimlar</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <ChartBarIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.active_salaries || 0 }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Faol Maoshlar</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                            <BanknotesIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div>
                            <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(stats.pending_bonuses || 0) }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Kutilayotgan Bonuslar</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <CurrencyDollarIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(stats.this_month_payroll || 0) }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Shu Oy</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payroll Cycles -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Ish Haqi Davrlari</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Oylik to'lov davrlari</p>
                </div>

                <div v-if="cycles.length === 0" class="p-12 text-center">
                    <CalendarIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">Hali davrlar yaratilmagan</p>
                </div>

                <div v-else class="p-6">
                    <div class="space-y-4">
                        <div v-for="cycle in cycles" :key="cycle.id"
                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-blue-500 dark:hover:border-blue-500 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ cycle.period }}</h3>
                                        <span :class="getStatusClass(cycle.status)" class="text-xs px-2 py-1 rounded">
                                            {{ cycle.status_label }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 dark:text-gray-400">
                                        <div>
                                            <span class="block text-xs text-gray-500 dark:text-gray-500">Davr</span>
                                            <span>{{ cycle.start_date }} - {{ cycle.end_date }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-xs text-gray-500 dark:text-gray-500">To'lov Sanasi</span>
                                            <span>{{ cycle.payment_date }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-xs text-gray-500 dark:text-gray-500">Xodimlar</span>
                                            <span>{{ cycle.employee_count }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-xs text-gray-500 dark:text-gray-500">Jami</span>
                                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(cycle.total_net) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
