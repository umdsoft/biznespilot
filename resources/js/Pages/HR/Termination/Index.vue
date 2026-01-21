<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    UserMinusIcon,
    UserIcon,
    ExclamationTriangleIcon,
    DocumentTextIcon,
    MagnifyingGlassIcon,
    ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    activeEmployees: Array,
    terminatedEmployees: Array,
    stats: Object,
    currentBusiness: Object,
});

const searchQuery = ref('');
const activeTab = ref('active');
const showTerminationModal = ref(false);
const selectedEmployee = ref(null);

const terminationForm = ref({
    termination_type: 'voluntary',
    termination_date: new Date().toISOString().split('T')[0],
    reason: '',
    notes: '',
});

const terminationTypes = [
    { value: 'voluntary', label: 'O\'z xohishi bilan' },
    { value: 'involuntary', label: 'Ish beruvchi qaroriga ko\'ra' },
    { value: 'retirement', label: 'Pensiyaga chiqish' },
    { value: 'contract_end', label: 'Shartnoma muddati tugashi' },
];

const filteredActiveEmployees = computed(() => {
    if (!searchQuery.value) return props.activeEmployees || [];
    return props.activeEmployees?.filter(emp =>
        emp.name?.toLowerCase().includes(searchQuery.value.toLowerCase())
    ) || [];
});

const filteredTerminatedEmployees = computed(() => {
    if (!searchQuery.value) return props.terminatedEmployees || [];
    return props.terminatedEmployees?.filter(emp =>
        emp.name?.toLowerCase().includes(searchQuery.value.toLowerCase())
    ) || [];
});

const getTerminationTypeLabel = (type) => {
    const found = terminationTypes.find(t => t.value === type);
    return found?.label || type;
};

const getTerminationTypeColor = (type) => {
    const colors = {
        voluntary: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        involuntary: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        retirement: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        contract_end: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    };
    return colors[type] || 'bg-gray-100 text-gray-800';
};

const openTerminationModal = (employee) => {
    selectedEmployee.value = employee;
    showTerminationModal.value = true;
};

const submitTermination = () => {
    if (!selectedEmployee.value) return;
    // Submit termination
    console.log('Terminating:', selectedEmployee.value, terminationForm.value);
    showTerminationModal.value = false;
};
</script>

<template>
    <HRLayout title="Ishdan bo'shatish">
        <Head title="Ishdan bo'shatish" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Ishdan bo'shatish
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        Xodimlarni ishdan bo'shatish va ketish jarayoni
                    </p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                            <UserIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Faol xodimlar</p>
                            <p class="text-2xl font-bold text-green-600">{{ stats?.active_employees || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                            <UserMinusIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Bu yil ketganlar</p>
                            <p class="text-2xl font-bold text-red-600">{{ stats?.terminated_this_year || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <ArrowRightOnRectangleIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">O'z xohishi bilan</p>
                            <p class="text-2xl font-bold text-blue-600">{{ stats?.voluntary || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                            <ExclamationTriangleIcon class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Ish beruvchi qarori</p>
                            <p class="text-2xl font-bold text-orange-600">{{ stats?.involuntary || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex gap-4">
                    <button
                        @click="activeTab = 'active'"
                        :class="[
                            'py-3 px-4 text-sm font-medium border-b-2 transition-colors',
                            activeTab === 'active'
                                ? 'border-purple-600 text-purple-600 dark:text-purple-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                        ]"
                    >
                        Faol xodimlar ({{ activeEmployees?.length || 0 }})
                    </button>
                    <button
                        @click="activeTab = 'terminated'"
                        :class="[
                            'py-3 px-4 text-sm font-medium border-b-2 transition-colors',
                            activeTab === 'terminated'
                                ? 'border-purple-600 text-purple-600 dark:text-purple-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                        ]"
                    >
                        Ketganlar tarixi ({{ terminatedEmployees?.length || 0 }})
                    </button>
                </nav>
            </div>

            <!-- Search -->
            <div class="relative max-w-md">
                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Xodim qidirish..."
                    class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-purple-500"
                />
            </div>

            <!-- Active Employees Table -->
            <div v-if="activeTab === 'active'" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Xodim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bo'lim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Lavozim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Qo'shilgan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Staj (oy)</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="employee in filteredActiveEmployees" :key="employee.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-green-600 dark:text-green-400">
                                                {{ employee.name?.charAt(0) || '?' }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ employee.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ employee.department }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ employee.position }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ employee.joined_at }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ employee.tenure_months }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <button
                                        @click="openTerminationModal(employee)"
                                        class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                    >
                                        Ishdan bo'shatish
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="filteredActiveEmployees.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <UserIcon class="w-12 h-12 mx-auto opacity-50 mb-3" />
                    <p>Xodimlar topilmadi</p>
                </div>
            </div>

            <!-- Terminated Employees Table -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Xodim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bo'lim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ketish sanasi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ketish turi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sabab</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Staj (oy)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="employee in filteredTerminatedEmployees" :key="employee.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                                {{ employee.name?.charAt(0) || '?' }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ employee.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ employee.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ employee.department || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ employee.termination_date }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-3 py-1 text-xs font-medium rounded-full', getTerminationTypeColor(employee.termination_type)]">
                                        {{ getTerminationTypeLabel(employee.termination_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                    {{ employee.reason || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ employee.tenure_months || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="filteredTerminatedEmployees.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <UserMinusIcon class="w-12 h-12 mx-auto opacity-50 mb-3" />
                    <p>Ketgan xodimlar tarixi mavjud emas</p>
                </div>
            </div>
        </div>

        <!-- Termination Modal -->
        <Teleport to="body">
            <div v-if="showTerminationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Ishdan bo'shatish
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        <strong>{{ selectedEmployee?.name }}</strong> ni ishdan bo'shatmoqchimisiz?
                    </p>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Ketish turi
                            </label>
                            <select
                                v-model="terminationForm.termination_type"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg"
                            >
                                <option v-for="type in terminationTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Ketish sanasi
                            </label>
                            <input
                                v-model="terminationForm.termination_date"
                                type="date"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Sabab
                            </label>
                            <textarea
                                v-model="terminationForm.reason"
                                rows="3"
                                class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg"
                                placeholder="Ishdan ketish sababi..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            @click="showTerminationModal = false"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            Bekor qilish
                        </button>
                        <button
                            @click="submitTermination"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                        >
                            Ishdan bo'shatish
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </HRLayout>
</template>
