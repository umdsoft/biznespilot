<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    DocumentTextIcon,
    UserIcon,
    PlusIcon,
    PencilIcon,
    PrinterIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    employees: Array,
    currentBusiness: Object,
});

const searchQuery = ref('');
const selectedDepartment = ref('');
const selectedContractType = ref('');
const showContractModal = ref(false);
const selectedEmployee = ref(null);

const contractTypes = [
    { value: '', label: 'Barcha turlar' },
    { value: 'unlimited', label: 'Muddatsiz' },
    { value: 'fixed', label: 'Muddatli' },
    { value: 'probation', label: 'Sinov muddati' },
    { value: 'part_time', label: 'Yarim stavka' },
];

const departments = computed(() => {
    const depts = [...new Set(props.employees?.map(e => e.department).filter(Boolean))];
    return [{ value: '', label: 'Barcha bo\'limlar' }, ...depts.map(d => ({ value: d, label: d }))];
});

const filteredEmployees = computed(() => {
    return props.employees?.filter(emp => {
        const matchesSearch = !searchQuery.value ||
            emp.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            emp.position?.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesDept = !selectedDepartment.value || emp.department === selectedDepartment.value;
        const matchesType = !selectedContractType.value || emp.contract_type === selectedContractType.value;
        return matchesSearch && matchesDept && matchesType;
    }) || [];
});

const getContractTypeLabel = (type) => {
    const labels = {
        unlimited: 'Muddatsiz',
        fixed: 'Muddatli',
        probation: 'Sinov muddati',
        part_time: 'Yarim stavka',
    };
    return labels[type] || type;
};

const getContractTypeColor = (type) => {
    const colors = {
        unlimited: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        fixed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        probation: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        part_time: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

const openContractModal = (employee) => {
    selectedEmployee.value = employee;
    showContractModal.value = true;
};

const stats = computed(() => ({
    total: props.employees?.length || 0,
    unlimited: props.employees?.filter(e => e.contract_type === 'unlimited').length || 0,
    fixed: props.employees?.filter(e => e.contract_type === 'fixed').length || 0,
    probation: props.employees?.filter(e => e.contract_type === 'probation').length || 0,
}));
</script>

<template>
    <HRLayout title="Mehnat shartnomalari">
        <Head title="Mehnat shartnomalari" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Mehnat shartnomalari
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Xodimlar bilan tuzilgan mehnat shartnomalari
                    </p>
                </div>
                <button class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    <PlusIcon class="w-5 h-5" />
                    Yangi shartnoma
                </button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                            <DocumentTextIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami shartnomalar</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                            <DocumentTextIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Muddatsiz</p>
                            <p class="text-2xl font-bold text-green-600">{{ stats.unlimited }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <DocumentTextIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Muddatli</p>
                            <p class="text-2xl font-bold text-blue-600">{{ stats.fixed }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                            <DocumentTextIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Sinov muddati</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ stats.probation }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Xodim qidirish..."
                            class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-purple-500"
                        />
                    </div>
                </div>
                <select
                    v-model="selectedDepartment"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg"
                >
                    <option v-for="dept in departments" :key="dept.value" :value="dept.value">
                        {{ dept.label }}
                    </option>
                </select>
                <select
                    v-model="selectedContractType"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg"
                >
                    <option v-for="type in contractTypes" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Xodim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lavozim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Shartnoma turi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Boshlanish</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tugash</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="employee in filteredEmployees" :key="employee.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-purple-600 dark:text-purple-400">
                                                {{ employee.name?.charAt(0) || '?' }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ employee.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.department_label }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ employee.position }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 text-xs font-medium rounded-full', getContractTypeColor(employee.contract_type)]">
                                        {{ getContractTypeLabel(employee.contract_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ employee.contract_start || employee.joined_at }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ employee.contract_end || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openContractModal(employee)"
                                            class="p-2 text-gray-400 hover:text-purple-600 dark:hover:text-purple-400"
                                            title="Tahrirlash"
                                        >
                                            <PencilIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                                            title="Chop etish"
                                        >
                                            <PrinterIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="filteredEmployees.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <DocumentTextIcon class="w-12 h-12 mx-auto opacity-50 mb-3" />
                    <p>Shartnomalar topilmadi</p>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
