<script setup>
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';

defineProps({ invoices: Array, stats: Object, filters: Object });
const formatCurrency = (v) => new Intl.NumberFormat('uz-UZ').format(v) + ' so\'m';
const getStatusClass = (s) => ({ paid: 'bg-green-100 text-green-700', pending: 'bg-yellow-100 text-yellow-700', overdue: 'bg-red-100 text-red-700', partial: 'bg-blue-100 text-blue-700' }[s] || 'bg-gray-100 text-gray-700');
const getStatusLabel = (s) => ({ paid: 'To\'langan', pending: 'Kutilmoqda', overdue: 'Kechikkan', partial: 'Qisman' }[s] || s);
</script>

<template>
    <FinanceLayout title="Hisob-fakturalar">
        <Head title="Hisob-fakturalar" />
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Hisob-fakturalar</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Barcha hisob-fakturalarni boshqaring</p>
                </div>
                <Link href="/finance/invoices/create" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-xl hover:from-green-700 hover:to-teal-700 transition-all">
                    <PlusIcon class="w-5 h-5" /> Yangi Faktura
                </Link>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats?.total || 0 }}</p>
                    <p class="text-sm text-gray-500">Jami</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30">
                    <p class="text-2xl font-bold text-yellow-600">{{ stats?.pending || 0 }}</p>
                    <p class="text-sm text-gray-500">Kutilmoqda</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30">
                    <p class="text-2xl font-bold text-red-600">{{ stats?.overdue || 0 }}</p>
                    <p class="text-sm text-gray-500">Kechikkan</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-green-100 dark:border-green-900/30">
                    <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats?.received_amount || 0) }}</p>
                    <p class="text-sm text-gray-500">Olingan</p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-green-100 dark:border-green-900/30 overflow-hidden">
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <Link v-for="inv in invoices" :key="inv.id" :href="`/finance/invoices/${inv.id}`" class="block p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ inv.number }}</h3>
                                    <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', getStatusClass(inv.status)]">{{ getStatusLabel(inv.status) }}</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">{{ inv.client }} · Muddat: {{ inv.due_date }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(inv.amount) }}</p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </FinanceLayout>
</template>
