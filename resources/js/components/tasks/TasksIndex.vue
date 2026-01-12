<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PlusIcon,
    CalendarIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    PencilSquareIcon,
    TrashIcon,
    PhoneIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';
import TaskModal from '@/components/TaskModal.vue';

const props = defineProps({
    tasks: Object,
    stats: Object,
    leads: Array,
    types: Object,
    priorities: Object,
    statuses: Object,
    panelType: {
        type: String,
        default: 'business',
        validator: (value) => ['business', 'saleshead', 'operator', 'marketing', 'finance'].includes(value),
    },
});

// Panel-specific configuration
const panelConfig = computed(() => {
    const configs = {
        business: {
            primaryColor: 'blue',
            routePrefix: 'business',
            leadRoute: 'business.sales.show',
            buttonClass: 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/25',
            addButtonClass: 'text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30',
        },
        saleshead: {
            primaryColor: 'emerald',
            routePrefix: 'sales-head',
            leadRoute: 'sales-head.leads.show',
            buttonClass: 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/25',
            addButtonClass: 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30',
        },
        operator: {
            primaryColor: 'blue',
            routePrefix: 'operator',
            leadRoute: 'operator.leads.show',
            buttonClass: 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/25',
            addButtonClass: 'text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30',
        },
        marketing: {
            primaryColor: 'blue',
            routePrefix: 'marketing',
            leadRoute: 'business.sales.show',
            buttonClass: 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/25',
            addButtonClass: 'text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30',
        },
        finance: {
            primaryColor: 'green',
            routePrefix: 'finance',
            leadRoute: 'business.sales.show',
            buttonClass: 'bg-green-600 hover:bg-green-700 shadow-green-500/25',
            addButtonClass: 'text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/30',
        },
    };
    return configs[props.panelType];
});

// Task modal state
const showTaskModal = ref(false);
const editingTask = ref(null);
const selectedLead = ref(null);

// Open task modal
const openTaskModal = (task = null, lead = null) => {
    editingTask.value = task;
    selectedLead.value = lead;
    showTaskModal.value = true;
};

// Close task modal
const closeTaskModal = () => {
    showTaskModal.value = false;
    editingTask.value = null;
    selectedLead.value = null;
};

// Complete task
const completeTask = async (task) => {
    try {
        const response = await fetch(route(`${panelConfig.value.routePrefix}.tasks.complete`, task.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            router.reload();
        }
    } catch (error) {
        console.error('Failed to complete task:', error);
    }
};

// Delete task
const deleteTask = async (task) => {
    if (!confirm('Vazifani o\'chirmoqchimisiz?')) return;

    try {
        const response = await fetch(route(`${panelConfig.value.routePrefix}.tasks.destroy`, task.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            router.reload();
        }
    } catch (error) {
        console.error('Failed to delete task:', error);
    }
};

// Handle task saved
const onTaskSaved = () => {
    router.reload();
};

// Total tasks count
const totalTasks = computed(() => {
    return props.stats.total;
});

// Get lead route
const getLeadRoute = (leadId) => {
    return route(panelConfig.value.leadRoute, leadId);
};

// Kanban columns configuration
const columns = [
    { key: 'overdue', label: "Muddati o'tgan", color: 'red' },
    { key: 'today', label: 'Bugun', color: 'blue', showAddButton: true },
    { key: 'tomorrow', label: 'Ertaga', color: 'indigo' },
    { key: 'this_week', label: 'Shu hafta', color: 'purple' },
];

const getColumnBgClass = (color) => ({
    red: 'bg-red-50/50 dark:bg-red-900/10 border-red-200 dark:border-red-800/30',
    blue: 'bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800/30',
    indigo: 'bg-indigo-50/50 dark:bg-indigo-900/10 border-indigo-200 dark:border-indigo-800/30',
    purple: 'bg-purple-50/50 dark:bg-purple-900/10 border-purple-200 dark:border-purple-800/30',
}[color]);

const getColumnHeaderClass = (color) => ({
    red: 'bg-red-100 dark:bg-red-900/30 border-b border-red-200 dark:border-red-800/30',
    blue: 'bg-blue-100 dark:bg-blue-900/30 border-b border-blue-200 dark:border-blue-800/30',
    indigo: 'bg-indigo-100 dark:bg-indigo-900/30 border-b border-indigo-200 dark:border-indigo-800/30',
    purple: 'bg-purple-100 dark:bg-purple-900/30 border-b border-purple-200 dark:border-purple-800/30',
}[color]);

const getColumnDotClass = (color) => ({
    red: 'bg-red-500',
    blue: 'bg-blue-500',
    indigo: 'bg-indigo-500',
    purple: 'bg-purple-500',
}[color]);

const getColumnTitleClass = (color) => ({
    red: 'text-red-700 dark:text-red-400',
    blue: 'text-blue-700 dark:text-blue-400',
    indigo: 'text-indigo-700 dark:text-indigo-400',
    purple: 'text-purple-700 dark:text-purple-400',
}[color]);

const getColumnBadgeClass = (color) => ({
    red: 'bg-red-200 dark:bg-red-800/50 text-red-700 dark:text-red-300',
    blue: 'bg-blue-200 dark:bg-blue-800/50 text-blue-700 dark:text-blue-300',
    indigo: 'bg-indigo-200 dark:bg-indigo-800/50 text-indigo-700 dark:text-indigo-300',
    purple: 'bg-purple-200 dark:bg-purple-800/50 text-purple-700 dark:text-purple-300',
}[color]);

const getColumnCardBorderClass = (color) => ({
    red: 'border-red-200 dark:border-red-700/50',
    blue: 'border-blue-200 dark:border-blue-700/50',
    indigo: 'border-indigo-200 dark:border-indigo-700/50',
    purple: 'border-purple-200 dark:border-purple-700/50',
}[color]);

const getColumnTimeClass = (color) => ({
    red: 'text-red-600 dark:text-red-400',
    blue: 'text-blue-600 dark:text-blue-400',
    indigo: 'text-indigo-600 dark:text-indigo-400',
    purple: 'text-purple-600 dark:text-purple-400',
}[color]);

const getColumnDateClass = (color) => ({
    red: 'text-red-500 dark:text-red-400',
    blue: 'text-blue-500 dark:text-blue-400',
    indigo: 'text-indigo-500 dark:text-indigo-400',
    purple: 'text-purple-500 dark:text-purple-400',
}[color]);

const getColumnAddButtonClass = (color) => ({
    red: 'text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 border-t border-red-200 dark:border-red-800/30',
    blue: 'text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 border-t border-blue-200 dark:border-blue-800/30',
    indigo: 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 border-t border-indigo-200 dark:border-indigo-800/30',
    purple: 'text-purple-600 dark:text-purple-400 hover:bg-purple-100 dark:hover:bg-purple-900/30 border-t border-purple-200 dark:border-purple-800/30',
}[color]);
</script>

<template>
    <div class="h-full flex flex-col -m-4 sm:-m-6 lg:-m-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Vazifalar</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ totalTasks }} ta faol vazifa
                    </p>
                </div>
                <button
                    @click="openTaskModal()"
                    :class="[
                        'inline-flex items-center gap-2 px-4 py-2.5 text-white font-medium rounded-xl transition-colors shadow-lg',
                        panelConfig.buttonClass
                    ]"
                >
                    <PlusIcon class="w-5 h-5" />
                    Vazifa qo'shish
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-5 gap-4 mt-6">
                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 border border-red-200 dark:border-red-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-800/30 rounded-lg flex items-center justify-center">
                            <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.overdue }}</p>
                            <p class="text-xs text-red-500 dark:text-red-400">Muddati o'tgan</p>
                        </div>
                    </div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-800/30 rounded-lg flex items-center justify-center">
                            <ClockIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.today }}</p>
                            <p class="text-xs text-blue-500 dark:text-blue-400">Bugun</p>
                        </div>
                    </div>
                </div>
                <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 border border-indigo-200 dark:border-indigo-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-800/30 rounded-lg flex items-center justify-center">
                            <CalendarIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ stats.tomorrow }}</p>
                            <p class="text-xs text-indigo-500 dark:text-indigo-400">Ertaga</p>
                        </div>
                    </div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-800/30 rounded-lg flex items-center justify-center">
                            <CalendarIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.this_week }}</p>
                            <p class="text-xs text-purple-500 dark:text-purple-400">Shu hafta</p>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-800/30 rounded-lg flex items-center justify-center">
                            <CheckCircleIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.completed }}</p>
                            <p class="text-xs text-green-500 dark:text-green-400">Bajarilgan</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="flex-1 overflow-hidden p-4 sm:p-6 lg:p-8">
            <div class="grid grid-cols-4 gap-4 h-full">
                <!-- Dynamic Columns -->
                <div
                    v-for="column in columns"
                    :key="column.key"
                    :class="['flex flex-col rounded-xl border overflow-hidden', getColumnBgClass(column.color)]"
                >
                    <div :class="['p-3', getColumnHeaderClass(column.color)]">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div :class="['w-3 h-3 rounded-full', getColumnDotClass(column.color)]"></div>
                                <span :class="['font-semibold', getColumnTitleClass(column.color)]">{{ column.label }}</span>
                            </div>
                            <span :class="['text-xs font-bold px-2 py-0.5 rounded-full', getColumnBadgeClass(column.color)]">
                                {{ tasks[column.key]?.length || 0 }}
                            </span>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 space-y-2">
                        <div
                            v-for="task in tasks[column.key]"
                            :key="task.id"
                            :class="['bg-white dark:bg-gray-800 rounded-lg border p-3 shadow-sm hover:shadow-md transition-shadow', getColumnCardBorderClass(column.color)]"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span :class="['text-lg font-bold', getColumnTimeClass(column.color)]">{{ task.due_date_human }}</span>
                                <span :class="[
                                    'text-xs px-2 py-0.5 rounded font-medium',
                                    task.type === 'call' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' :
                                    task.type === 'meeting' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' :
                                    'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                ]">{{ task.type_label }}</span>
                            </div>
                            <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">{{ task.title }}</h4>
                            <p v-if="column.key === 'overdue' || column.key === 'this_week'" :class="['text-xs mb-2', getColumnDateClass(column.color)]">
                                {{ task.due_date_full.split(' ')[0] }}
                            </p>
                            <div v-if="task.priority === 'urgent' || task.priority === 'high'" class="mb-2">
                                <span :class="[
                                    'text-xs px-2 py-0.5 rounded font-bold',
                                    task.priority === 'urgent' ? 'bg-red-100 text-red-700 animate-pulse' : 'bg-orange-100 text-orange-700'
                                ]">{{ task.priority_label }}</span>
                            </div>
                            <!-- Lead info -->
                            <Link v-if="task.lead" :href="getLeadRoute(task.lead.id)" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 mb-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <UserIcon class="w-3 h-3" />
                                <span class="truncate">{{ task.lead.name }}</span>
                                <PhoneIcon class="w-3 h-3 ml-auto" />
                            </Link>
                            <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                                <button @click="completeTask(task)" class="text-xs text-green-600 hover:text-green-700 font-medium">Bajarildi</button>
                                <div class="flex gap-1">
                                    <button @click="openTaskModal(task, task.lead)" class="p-1 text-gray-400 hover:text-blue-600"><PencilSquareIcon class="w-4 h-4" /></button>
                                    <button @click="deleteTask(task)" class="p-1 text-gray-400 hover:text-red-600"><TrashIcon class="w-4 h-4" /></button>
                                </div>
                            </div>
                        </div>
                        <div v-if="!tasks[column.key]?.length" class="text-center py-8 text-gray-400 dark:text-gray-500">
                            <CheckCircleIcon v-if="column.key === 'overdue'" class="w-8 h-8 mx-auto mb-2 opacity-50" />
                            <CalendarIcon v-else class="w-8 h-8 mx-auto mb-2 opacity-50" />
                            <p class="text-xs">Yo'q</p>
                        </div>
                    </div>
                    <div v-if="column.showAddButton" :class="['p-2', getColumnAddButtonClass(column.color)]">
                        <button @click="openTaskModal()" class="w-full py-2 text-sm rounded-lg transition-colors flex items-center justify-center gap-1">
                            <PlusIcon class="w-4 h-4" />
                            Vazifa qo'shish
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Modal with lead selection -->
    <TaskModal
        :show="showTaskModal"
        :lead="selectedLead"
        :task="editingTask"
        :leads="leads"
        :panel-type="panelType"
        @close="closeTaskModal"
        @saved="onTaskSaved"
    />
</template>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.3);
    border-radius: 3px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background-color: rgba(156, 163, 175, 0.5);
}
</style>
