<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    PlusIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    CalendarIcon,
    PencilSquareIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    tasks: Array,
    stats: Object,
    filters: Object,
    panelType: {
        type: String,
        default: 'business',
        validator: (value) => ['business', 'marketing', 'finance', 'operator'].includes(value),
    },
});

const panelConfig = computed(() => {
    const configs = {
        business: {
            routePrefix: 'business',
            primaryColor: 'blue',
            buttonClass: 'bg-blue-600 hover:bg-blue-700',
            borderClass: 'border-blue-100 dark:border-blue-900/30',
        },
        marketing: {
            routePrefix: 'marketing',
            primaryColor: 'purple',
            buttonClass: 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700',
            borderClass: 'border-purple-100 dark:border-purple-900/30',
        },
        finance: {
            routePrefix: 'finance',
            primaryColor: 'green',
            buttonClass: 'bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700',
            borderClass: 'border-green-100 dark:border-green-900/30',
        },
        operator: {
            routePrefix: 'operator',
            primaryColor: 'blue',
            buttonClass: 'bg-blue-600 hover:bg-blue-700',
            borderClass: 'border-blue-100 dark:border-blue-900/30',
        },
    };
    return configs[props.panelType];
});

const statusFilter = ref(props.filters?.status || 'all');

const statuses = [
    { value: 'all', label: 'Barchasi' },
    { value: 'pending', label: 'Kutilmoqda' },
    { value: 'in_progress', label: 'Jarayonda' },
    { value: 'completed', label: 'Bajarildi' },
];

const applyFilter = () => {
    router.get(`/${panelConfig.value.routePrefix}/tasks`, {
        status: statusFilter.value !== 'all' ? statusFilter.value : null,
    }, { preserveState: true });
};

const completeTask = (task) => {
    router.post(`/${panelConfig.value.routePrefix}/tasks/${task.id}/complete`, {}, {
        preserveScroll: true,
    });
};

const deleteTask = (task) => {
    if (!confirm('Vazifani o\'chirmoqchimisiz?')) return;
    router.delete(`/${panelConfig.value.routePrefix}/tasks/${task.id}`, {
        preserveScroll: true,
    });
};

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        completed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Kutilmoqda',
        in_progress: 'Jarayonda',
        completed: 'Bajarildi',
    };
    return labels[status] || status;
};

const getPriorityClass = (priority) => {
    const classes = {
        high: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        medium: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        low: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[priority] || 'bg-gray-100 text-gray-700';
};

const getPriorityLabel = (priority) => {
    const labels = {
        high: 'Yuqori',
        medium: 'O\'rta',
        low: 'Past',
    };
    return labels[priority] || priority;
};

// Show add task modal (stub - can be expanded)
const showAddModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Vazifalar</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Barcha vazifalarni boshqaring</p>
            </div>
            <button
                @click="showAddModal = true"
                :class="['inline-flex items-center gap-2 px-4 py-2 text-white rounded-xl transition-all', panelConfig.buttonClass]"
            >
                <PlusIcon class="w-5 h-5" />
                Yangi vazifa
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div :class="['bg-white dark:bg-gray-800 rounded-xl p-4 border', panelConfig.borderClass]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                        <CalendarIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats?.total || 0 }}</p>
                        <p class="text-xs text-gray-500">Jami</p>
                    </div>
                </div>
            </div>
            <div :class="['bg-white dark:bg-gray-800 rounded-xl p-4 border', panelConfig.borderClass]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                        <ClockIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats?.pending || 0 }}</p>
                        <p class="text-xs text-gray-500">Kutilmoqda</p>
                    </div>
                </div>
            </div>
            <div :class="['bg-white dark:bg-gray-800 rounded-xl p-4 border', panelConfig.borderClass]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <ClockIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats?.in_progress || 0 }}</p>
                        <p class="text-xs text-gray-500">Jarayonda</p>
                    </div>
                </div>
            </div>
            <div :class="['bg-white dark:bg-gray-800 rounded-xl p-4 border', panelConfig.borderClass]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <CheckCircleIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats?.completed || 0 }}</p>
                        <p class="text-xs text-gray-500">Bajarildi</p>
                    </div>
                </div>
            </div>
            <div :class="['bg-white dark:bg-gray-800 rounded-xl p-4 border', panelConfig.borderClass]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                        <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats?.overdue || 0 }}</p>
                        <p class="text-xs text-gray-500">Muddati o'tgan</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div :class="['bg-white dark:bg-gray-800 rounded-2xl p-4 border', panelConfig.borderClass]">
            <div class="flex items-center gap-4">
                <select
                    v-model="statusFilter"
                    class="px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                    @change="applyFilter"
                >
                    <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                </select>
            </div>
        </div>

        <!-- Tasks List -->
        <div :class="['bg-white dark:bg-gray-800 rounded-2xl border overflow-hidden', panelConfig.borderClass]">
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                <div
                    v-for="task in tasks"
                    :key="task.id"
                    class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 flex-wrap">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ task.title }}</h3>
                                <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', getStatusClass(task.status)]">
                                    {{ getStatusLabel(task.status) }}
                                </span>
                                <span v-if="task.priority" :class="['px-2.5 py-1 text-xs font-medium rounded-full', getPriorityClass(task.priority)]">
                                    {{ getPriorityLabel(task.priority) }}
                                </span>
                                <span v-if="task.is_overdue" class="px-2.5 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 animate-pulse">
                                    Muddati o'tgan
                                </span>
                            </div>
                            <p v-if="task.description" class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                {{ task.description }}
                            </p>
                            <div class="flex items-center gap-4 mt-3 text-sm text-gray-500 dark:text-gray-400">
                                <span v-if="task.due_date" class="flex items-center gap-1">
                                    <CalendarIcon class="w-4 h-4" />
                                    {{ task.due_date }}
                                </span>
                                <span v-if="task.assignee" class="flex items-center gap-1">
                                    Tayinlangan: {{ task.assignee.name }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="task.status !== 'completed'"
                                @click="completeTask(task)"
                                class="px-3 py-1.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors"
                            >
                                Bajarildi
                            </button>
                            <button
                                @click="deleteTask(task)"
                                class="p-1.5 text-gray-400 hover:text-red-600 transition-colors"
                            >
                                <TrashIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!tasks?.length" class="p-12 text-center">
                <CalendarIcon class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto" />
                <p class="text-gray-500 dark:text-gray-400 mt-4">Hozircha vazifalar yo'q</p>
            </div>
        </div>
    </div>
</template>
