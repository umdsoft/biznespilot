<script setup>
import HRLayout from '@/layouts/HRLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { CurrencyDollarIcon, PlusIcon, CheckCircleIcon, XCircleIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    structures: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
});

// Modal
const showCreateModal = ref(false);

// Form
const salaryForm = useForm({
    user_id: null,
    base_salary: '',
    payment_frequency: 'monthly',
    effective_from: new Date().toISOString().split('T')[0],
    allowances: [],
    deductions: [],
});

// Allowances and Deductions
const newAllowance = ref({ name: '', amount: '' });
const newDeduction = ref({ name: '', amount: '' });

// Methods
const addAllowance = () => {
    if (newAllowance.value.name && newAllowance.value.amount) {
        salaryForm.allowances.push({ ...newAllowance.value });
        newAllowance.value = { name: '', amount: '' };
    }
};

const removeAllowance = (index) => {
    salaryForm.allowances.splice(index, 1);
};

const addDeduction = () => {
    if (newDeduction.value.name && newDeduction.value.amount) {
        salaryForm.deductions.push({ ...newDeduction.value });
        newDeduction.value = { name: '', amount: '' };
    }
};

const removeDeduction = (index) => {
    salaryForm.deductions.splice(index, 1);
};

const createSalary = () => {
    salaryForm.post(route('hr.payroll.salary-structures.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            salaryForm.reset();
            salaryForm.allowances = [];
            salaryForm.deductions = [];
        },
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' UZS';
};
</script>

<template>
    <HRLayout title="Maosh Tuzilmalari">
        <Head title="Maosh Tuzilmalari" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Maosh Tuzilmalari</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Xodimlar uchun maosh parametrlari</p>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
                >
                    <PlusIcon class="w-5 h-5" />
                    Maosh Qo'shish
                </button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <CurrencyDollarIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ structures.length }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami Tuzilmalar</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ structures.filter(s => s.is_active).length }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Faol</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <CurrencyDollarIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                {{ formatCurrency(structures.filter(s => s.is_active).reduce((sum, s) => sum + parseFloat(s.base_salary), 0)) }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami Maosh</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Salary Structures List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Barcha Maosh Tuzilmalari</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Xodimlar maosh parametrlari</p>
                </div>

                <div v-if="structures.length === 0" class="p-12 text-center">
                    <CurrencyDollarIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">Hali maosh tuzilmalari yo'q</p>
                    <button
                        @click="showCreateModal = true"
                        class="mt-4 text-blue-600 dark:text-blue-400 hover:underline"
                    >
                        Birinchi maoshni qo'shing
                    </button>
                </div>

                <div v-else class="p-6 space-y-4">
                    <div
                        v-for="structure in structures"
                        :key="structure.id"
                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                        :class="{ 'bg-green-50 dark:bg-green-900/10': structure.is_active }"
                    >
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ structure.user_name }}</h3>
                                    <span v-if="structure.is_active" class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs rounded">
                                        Faol
                                    </span>
                                    <span v-else class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded">
                                        Nofaol
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                    <span>{{ structure.payment_frequency_label }}</span>
                                    <span>Kuchga kirish: {{ structure.effective_from }}</span>
                                    <span v-if="structure.effective_until">Tugash: {{ structure.effective_until }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="block text-xs text-gray-500 dark:text-gray-500 mb-1">Asosiy Maosh</span>
                                <span class="font-semibold text-gray-900 dark:text-gray-100">{{ formatCurrency(structure.base_salary) }}</span>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-500 dark:text-gray-500 mb-1">Ustamalar</span>
                                <span class="font-semibold text-green-600 dark:text-green-400">+{{ formatCurrency(structure.total_allowances) }}</span>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-500 dark:text-gray-500 mb-1">Chegirmalar</span>
                                <span class="font-semibold text-red-600 dark:text-red-400">-{{ formatCurrency(structure.total_deductions) }}</span>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-500 dark:text-gray-500 mb-1">Sof Maosh</span>
                                <span class="font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(structure.net_salary) }}</span>
                            </div>
                        </div>

                        <!-- Allowances & Deductions Details -->
                        <div v-if="structure.allowances.length > 0 || structure.deductions.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-if="structure.allowances.length > 0">
                                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Ustamalar:</h4>
                                    <div class="space-y-1">
                                        <div v-for="(allowance, index) in structure.allowances" :key="index" class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                            <span>{{ allowance.name }}</span>
                                            <span class="text-green-600 dark:text-green-400">+{{ formatCurrency(allowance.amount) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="structure.deductions.length > 0">
                                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Chegirmalar:</h4>
                                    <div class="space-y-1">
                                        <div v-for="(deduction, index) in structure.deductions" :key="index" class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
                                            <span>{{ deduction.name }}</span>
                                            <span class="text-red-600 dark:text-red-400">-{{ formatCurrency(deduction.amount) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Salary Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Yangi Maosh Tuzilmasi</h2>
                </div>

                <form @submit.prevent="createSalary" class="p-6 space-y-6">
                    <!-- Basic Info -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Xodim <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="salaryForm.user_id"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option :value="null" disabled>Xodimni tanlang</option>
                                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                    {{ emp.name }}
                                </option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Asosiy Maosh (UZS) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="salaryForm.base_salary"
                                    type="number"
                                    step="100000"
                                    min="0"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="5000000"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    To'lov Davri <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="salaryForm.payment_frequency"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="monthly">Oylik</option>
                                    <option value="bi-weekly">2 Haftalik</option>
                                    <option value="weekly">Haftalik</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Kuchga Kirish Sanasi <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="salaryForm.effective_from"
                                type="date"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <!-- Allowances -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Ustamalar (Allowances)</h3>

                        <div v-if="salaryForm.allowances.length > 0" class="mb-4 space-y-2">
                            <div v-for="(allowance, index) in salaryForm.allowances" :key="index"
                                class="flex items-center gap-2 p-2 bg-green-50 dark:bg-green-900/20 rounded">
                                <span class="flex-1 text-sm text-gray-900 dark:text-gray-100">{{ allowance.name }}</span>
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ formatCurrency(allowance.amount) }}</span>
                                <button type="button" @click="removeAllowance(index)" class="text-red-600 hover:text-red-700">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <input
                                v-model="newAllowance.name"
                                type="text"
                                placeholder="Nomi (Transport, Uy-joy, va h.k.)"
                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                            />
                            <input
                                v-model="newAllowance.amount"
                                type="number"
                                step="10000"
                                min="0"
                                placeholder="Summa"
                                class="w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                            />
                            <button type="button" @click="addAllowance"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                Qo'shish
                            </button>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Chegirmalar (Deductions)</h3>

                        <div v-if="salaryForm.deductions.length > 0" class="mb-4 space-y-2">
                            <div v-for="(deduction, index) in salaryForm.deductions" :key="index"
                                class="flex items-center gap-2 p-2 bg-red-50 dark:bg-red-900/20 rounded">
                                <span class="flex-1 text-sm text-gray-900 dark:text-gray-100">{{ deduction.name }}</span>
                                <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ formatCurrency(deduction.amount) }}</span>
                                <button type="button" @click="removeDeduction(index)" class="text-red-600 hover:text-red-700">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <input
                                v-model="newDeduction.name"
                                type="text"
                                placeholder="Nomi (Soliq, Sug'urta, va h.k.)"
                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                            />
                            <input
                                v-model="newDeduction.amount"
                                type="number"
                                step="10000"
                                min="0"
                                placeholder="Summa"
                                class="w-32 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm"
                            />
                            <button type="button" @click="addDeduction"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                                Qo'shish
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            @click="showCreateModal = false; salaryForm.reset(); salaryForm.allowances = []; salaryForm.deductions = [];"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            :disabled="salaryForm.processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                        >
                            {{ salaryForm.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </HRLayout>
</template>
