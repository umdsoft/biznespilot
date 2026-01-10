<script setup>
import { ref, computed, watch } from 'vue';
import {
    XMarkIcon,
    UserPlusIcon,
    UserIcon,
    CheckCircleIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    leads: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'assigned']);

// State
const operators = ref([]);
const operatorStats = ref([]);
const selectedOperator = ref(null);
const reassignTasks = ref(true);
const isLoading = ref(false);
const isLoadingOperators = ref(false);
const error = ref('');

// Load operators
const loadOperators = async () => {
    isLoadingOperators.value = true;
    try {
        const [operatorsRes, statsRes] = await Promise.all([
            fetch(route('business.api.sales.operators')),
            fetch(route('business.api.sales.operator-stats')),
        ]);

        const operatorsData = await operatorsRes.json();
        const statsData = await statsRes.json();

        operators.value = operatorsData.operators || [];
        operatorStats.value = statsData.stats || [];
    } catch (err) {
        console.error('Failed to load operators:', err);
    } finally {
        isLoadingOperators.value = false;
    }
};

// Get operator stats by ID
const getOperatorStats = (operatorId) => {
    return operatorStats.value.find(s => s.operator.id === operatorId);
};

// Watch for modal open
watch(() => props.show, (newVal) => {
    if (newVal) {
        error.value = '';
        selectedOperator.value = null;
        reassignTasks.value = true;
        loadOperators();
    }
});

// Submit bulk assignment
const submit = async () => {
    if (isLoading.value || props.leads.length === 0) return;

    isLoading.value = true;
    error.value = '';

    try {
        const leadIds = props.leads.map(lead => lead.id);

        const response = await fetch(route('business.api.sales.bulk-assign'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                lead_ids: leadIds,
                operator_id: selectedOperator.value,
                reassign_tasks: reassignTasks.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            emit('assigned', {
                leads: data.leads,
                operator_id: selectedOperator.value,
            });
            emit('close');
        } else {
            error.value = data.error || data.message || 'Xatolik yuz berdi';
        }
    } catch (err) {
        console.error('Failed to bulk assign leads:', err);
        error.value = 'Tarmoq xatosi';
    } finally {
        isLoading.value = false;
    }
};

// Close modal
const close = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50" @click="close"></div>

                <!-- Modal -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="show" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg z-10 overflow-hidden max-h-[90vh] overflow-y-auto">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl flex items-center justify-center">
                                    <UsersIcon class="w-5 h-5 text-white" />
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Leadlarni operatorga tayinlash
                                </h3>
                            </div>
                            <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6 space-y-5">
                            <!-- Selected Leads Info -->
                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                        {{ leads.length }}
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">
                                            {{ leads.length }} ta lead tanlandi
                                        </h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                            Tanlangan barcha leadlar operatorga tayinlanadi
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Operator Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                    Operatorni tanlang
                                </label>

                                <!-- Loading state -->
                                <div v-if="isLoadingOperators" class="flex items-center justify-center py-8">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500"></div>
                                </div>

                                <!-- No operators -->
                                <div v-else-if="operators.length === 0" class="text-center py-8">
                                    <UserIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                                    <p class="text-gray-500 dark:text-gray-400">
                                        Sotuv operatorlari topilmadi
                                    </p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">
                                        Avval Sozlamalar > Jamoa bo'limidan operator qo'shing
                                    </p>
                                </div>

                                <!-- Operators list -->
                                <div v-else class="space-y-2">
                                    <!-- Unassigned option -->
                                    <button
                                        @click="selectedOperator = null"
                                        :class="[
                                            'w-full flex items-center gap-3 p-3 rounded-xl border-2 transition-all text-left',
                                            selectedOperator === null
                                                ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                        ]"
                                    >
                                        <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                            <XMarkIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Tayinlanmagan</span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Operatorsiz qoldirish</p>
                                        </div>
                                        <CheckCircleIcon v-if="selectedOperator === null" class="w-5 h-5 text-orange-500" />
                                    </button>

                                    <!-- Operator options -->
                                    <button
                                        v-for="operator in operators"
                                        :key="operator.id"
                                        @click="selectedOperator = operator.id"
                                        :class="[
                                            'w-full flex items-center gap-3 p-3 rounded-xl border-2 transition-all text-left',
                                            selectedOperator === operator.id
                                                ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                        ]"
                                    >
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center text-white font-semibold">
                                            {{ operator.name?.charAt(0)?.toUpperCase() || '?' }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ operator.name }}</span>
                                            <div v-if="getOperatorStats(operator.id)" class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ getOperatorStats(operator.id).leads.total }} lid</span>
                                                <span>{{ getOperatorStats(operator.id).leads.conversion_rate }}% conversion</span>
                                            </div>
                                        </div>
                                        <CheckCircleIcon v-if="selectedOperator === operator.id" class="w-5 h-5 text-orange-500" />
                                    </button>
                                </div>
                            </div>

                            <!-- Reassign Tasks Checkbox -->
                            <div v-if="operators.length > 0" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <input
                                    type="checkbox"
                                    id="bulk-reassign-tasks"
                                    v-model="reassignTasks"
                                    class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500"
                                />
                                <label for="bulk-reassign-tasks" class="text-sm text-gray-700 dark:text-gray-300">
                                    Mavjud vazifalarni ham operatorga tayinlash
                                </label>
                            </div>

                            <!-- Error -->
                            <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 sticky bottom-0">
                            <button
                                @click="close"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium rounded-xl transition-colors"
                            >
                                Bekor qilish
                            </button>
                            <button
                                @click="submit"
                                :disabled="isLoading || leads.length === 0"
                                :class="[
                                    'px-6 py-2 font-medium rounded-xl transition-colors',
                                    !isLoading && leads.length > 0
                                        ? 'bg-orange-500 hover:bg-orange-600 text-white'
                                        : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                ]"
                            >
                                {{ isLoading ? 'Saqlanmoqda...' : `${leads.length} ta leadni tayinlash` }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
