<script setup>
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';

defineProps({ expenses: Array, stats: Object, categories: Array, filters: Object });
const formatCurrency = (v) => new Intl.NumberFormat('uz-UZ').format(v) + ' so\'m';
const getCategoryLabel = (c) => ({ salary: 'Ish haqi', rent: 'Ijara', utilities: 'Kommunal', marketing: 'Marketing', technology: 'Texnologiya', office: 'Ofis', travel: 'Safar', other: 'Boshqa' }[c] || c);
</script>

<template>
    <FinanceLayout title="Xarajatlar">
        <Head title="Xarajatlar" />
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Xarajatlar</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Barcha xarajatlarni kuzating</p>
                </div>
                <Link href="/finance/expenses/create" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-xl hover:from-green-700 hover:to-teal-700 transition-all">
                    <PlusIcon class="w-5 h-5" /> Yangi Xarajat
                </Link>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats?.total || 0) }}</p>
                    <p class="text-sm text-gray-500">Jami xarajat</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30">
                    <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats?.this_month || 0) }}</p>
                    <p class="text-sm text-gray-500">Bu oy</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30 col-span-2 md:col-span-1">
                    <p class="text-lg font-bold text-gray-900 dark:text-white">Kategoriyalar bo'yicha</p>
                    <div class="mt-2 space-y-1">
                        <div v-for="cat in (stats?.by_category || []).slice(0, 3)" :key="cat.category" class="flex justify-between text-sm">
                            <span class="text-gray-500">{{ getCategoryLabel(cat.category) }}</span>
                            <span class="font-medium">{{ cat.percentage }}%</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-green-100 dark:border-green-900/30 overflow-hidden">
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="exp in expenses" :key="exp.id" class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ exp.description }}</h3>
                                <p class="text-sm text-gray-500 mt-1">{{ getCategoryLabel(exp.category) }} · {{ exp.vendor }} · {{ exp.date }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-red-600">-{{ formatCurrency(exp.amount) }}</p>
                                <span :class="exp.status === 'paid' ? 'text-green-600' : 'text-yellow-600'" class="text-xs">
                                    {{ exp.status === 'paid' ? 'To\'langan' : 'Kutilmoqda' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FinanceLayout>
</template>
