<script setup>
import HRLayout from '@/layouts/HRLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import { ref } from 'vue';
import { BanknotesIcon, PlusIcon, CheckCircleIcon, ClockIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    bonuses: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    bonusTypes: { type: Object, default: () => ({}) },
});

// Modal
const showCreateModal = ref(false);

// Form
const bonusForm = useForm({
    user_id: null,
    type: 'performance',
    title: '',
    description: '',
    amount: '',
    granted_date: new Date().toISOString().split('T')[0],
});

// Methods
const createBonus = () => {
    bonusForm.post(route('hr.payroll.bonuses.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            bonusForm.reset();
        },
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' UZS';
};

const getTypeColor = (type) => {
    const colors = {
        'performance': 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        'annual': 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
        'spot': 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        'referral': 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
    };
    return colors[type] || colors['performance'];
};
</script>

<template>
    <HRLayout :title="t('hr.bonuses')">
        <Head :title="t('hr.bonuses')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('hr.bonuses') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('hr.bonuses_subtitle') }}</p>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
                >
                    <PlusIcon class="w-5 h-5" />
                    {{ t('hr.add_bonus') }}
                </button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <BanknotesIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ bonuses.length }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami Bonuslar</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                            <ClockIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ bonuses.filter(b => !b.is_paid).length }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Kutilmoqda</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <CheckCircleIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ bonuses.filter(b => b.is_paid).length }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">To'langan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bonuses List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Barcha Bonuslar</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Xodimlar uchun berilgan bonuslar ro'yxati</p>
                </div>

                <div v-if="bonuses.length === 0" class="p-12 text-center">
                    <BanknotesIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">Hali bonuslar yo'q</p>
                    <button
                        @click="showCreateModal = true"
                        class="mt-4 text-blue-600 dark:text-blue-400 hover:underline"
                    >
                        Birinchi bonusni qo'shing
                    </button>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Xodim
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Turi
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Nomi
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Summa
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Sana
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Holat
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="bonus in bonuses" :key="bonus.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ bonus.user_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="getTypeColor(bonus.type)" class="px-2 py-1 text-xs rounded">
                                        {{ bonus.type_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ bonus.title }}</div>
                                    <div v-if="bonus.description" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ bonus.description }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency(bonus.amount) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ bonus.granted_date }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="bonus.is_paid" class="flex items-center gap-1 text-green-600 dark:text-green-400 text-sm">
                                        <CheckCircleIcon class="w-4 h-4" />
                                        To'langan
                                    </span>
                                    <span v-else class="flex items-center gap-1 text-yellow-600 dark:text-yellow-400 text-sm">
                                        <ClockIcon class="w-4 h-4" />
                                        Kutilmoqda
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Bonus Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Yangi Bonus Qo'shish</h2>
                </div>

                <form @submit.prevent="createBonus" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Xodim <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="bonusForm.user_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option :value="null" disabled>Xodimni tanlang</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                {{ emp.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Bonus Turi <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="bonusForm.type"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option v-for="(label, value) in bonusTypes" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nomi <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="bonusForm.title"
                            type="text"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Masalan: Yillik bonus"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tavsif
                        </label>
                        <textarea
                            v-model="bonusForm.description"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Bonus haqida qo'shimcha ma'lumot"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Summa (UZS) <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="bonusForm.amount"
                                type="number"
                                step="1000"
                                min="0"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="1000000"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Berilgan Sana <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="bonusForm.granted_date"
                                type="date"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4">
                        <button
                            type="button"
                            @click="showCreateModal = false; bonusForm.reset()"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            :disabled="bonusForm.processing"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
                        >
                            {{ bonusForm.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </HRLayout>
</template>
