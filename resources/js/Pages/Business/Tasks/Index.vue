<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
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
    FunnelIcon,
} from '@heroicons/vue/24/outline';
import TaskModal from '@/components/TaskModal.vue';

const props = defineProps({
    tasks: Object,
    stats: Object,
    leads: Array,
    types: Object,
    priorities: Object,
    statuses: Object,
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
        const response = await fetch(route('business.tasks.complete', task.id), {
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
        const response = await fetch(route('business.tasks.destroy', task.id), {
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
</script>

<template>
    <BusinessLayout title="Vazifalar">
        <Head title="Vazifalar" />

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
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors shadow-lg shadow-blue-500/25"
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
                    <!-- Column 1: Muddati o'tgan (Overdue) -->
                    <div class="flex flex-col bg-red-50/50 dark:bg-red-900/10 rounded-xl border border-red-200 dark:border-red-800/30 overflow-hidden">
                        <div class="p-3 bg-red-100 dark:bg-red-900/30 border-b border-red-200 dark:border-red-800/30">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                    <span class="font-semibold text-red-700 dark:text-red-400">Muddati o'tgan</span>
                                </div>
                                <span class="text-xs font-bold px-2 py-0.5 bg-red-200 dark:bg-red-800/50 text-red-700 dark:text-red-300 rounded-full">
                                    {{ tasks.overdue?.length || 0 }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 space-y-2">
                            <div
                                v-for="task in tasks.overdue"
                                :key="task.id"
                                class="bg-white dark:bg-gray-800 rounded-lg border border-red-200 dark:border-red-700/50 p-3 shadow-sm hover:shadow-md transition-shadow"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ task.due_date_human }}</span>
                                    <span :class="[
                                        'text-xs px-2 py-0.5 rounded font-medium',
                                        task.type === 'call' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' :
                                        task.type === 'meeting' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' :
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                    ]">{{ task.type_label }}</span>
                                </div>
                                <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">{{ task.title }}</h4>
                                <p class="text-xs text-red-500 dark:text-red-400 mb-2">{{ task.due_date_full.split(' ')[0] }}</p>
                                <!-- Lead info -->
                                <Link v-if="task.lead" :href="route('business.sales.show', task.lead.id)" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 mb-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
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
                            <div v-if="!tasks.overdue?.length" class="text-center py-8 text-gray-400 dark:text-gray-500">
                                <CheckCircleIcon class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                <p class="text-xs">Yo'q</p>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Bugun (Today) -->
                    <div class="flex flex-col bg-blue-50/50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-800/30 overflow-hidden">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 border-b border-blue-200 dark:border-blue-800/30">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                    <span class="font-semibold text-blue-700 dark:text-blue-400">Bugun</span>
                                </div>
                                <span class="text-xs font-bold px-2 py-0.5 bg-blue-200 dark:bg-blue-800/50 text-blue-700 dark:text-blue-300 rounded-full">
                                    {{ tasks.today?.length || 0 }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 space-y-2">
                            <div
                                v-for="task in tasks.today"
                                :key="task.id"
                                class="bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700/50 p-3 shadow-sm hover:shadow-md transition-shadow"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ task.due_date_human }}</span>
                                    <span :class="[
                                        'text-xs px-2 py-0.5 rounded font-medium',
                                        task.type === 'call' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' :
                                        task.type === 'meeting' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' :
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                    ]">{{ task.type_label }}</span>
                                </div>
                                <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">{{ task.title }}</h4>
                                <div v-if="task.priority === 'urgent' || task.priority === 'high'" class="mb-2">
                                    <span :class="[
                                        'text-xs px-2 py-0.5 rounded font-bold',
                                        task.priority === 'urgent' ? 'bg-red-100 text-red-700 animate-pulse' : 'bg-orange-100 text-orange-700'
                                    ]">{{ task.priority_label }}</span>
                                </div>
                                <!-- Lead info -->
                                <Link v-if="task.lead" :href="route('business.sales.show', task.lead.id)" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 mb-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
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
                            <div v-if="!tasks.today?.length" class="text-center py-8 text-gray-400 dark:text-gray-500">
                                <CalendarIcon class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                <p class="text-xs">Yo'q</p>
                            </div>
                        </div>
                        <div class="p-2 border-t border-blue-200 dark:border-blue-800/30">
                            <button @click="openTaskModal()" class="w-full py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors flex items-center justify-center gap-1">
                                <PlusIcon class="w-4 h-4" />
                                Vazifa qo'shish
                            </button>
                        </div>
                    </div>

                    <!-- Column 3: Ertaga (Tomorrow) -->
                    <div class="flex flex-col bg-indigo-50/50 dark:bg-indigo-900/10 rounded-xl border border-indigo-200 dark:border-indigo-800/30 overflow-hidden">
                        <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 border-b border-indigo-200 dark:border-indigo-800/30">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
                                    <span class="font-semibold text-indigo-700 dark:text-indigo-400">Ertaga</span>
                                </div>
                                <span class="text-xs font-bold px-2 py-0.5 bg-indigo-200 dark:bg-indigo-800/50 text-indigo-700 dark:text-indigo-300 rounded-full">
                                    {{ tasks.tomorrow?.length || 0 }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 space-y-2">
                            <div
                                v-for="task in tasks.tomorrow"
                                :key="task.id"
                                class="bg-white dark:bg-gray-800 rounded-lg border border-indigo-200 dark:border-indigo-700/50 p-3 shadow-sm hover:shadow-md transition-shadow"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ task.due_date_human }}</span>
                                    <span :class="[
                                        'text-xs px-2 py-0.5 rounded font-medium',
                                        task.type === 'call' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' :
                                        task.type === 'meeting' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' :
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                    ]">{{ task.type_label }}</span>
                                </div>
                                <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">{{ task.title }}</h4>
                                <div v-if="task.priority === 'urgent' || task.priority === 'high'" class="mb-2">
                                    <span :class="[
                                        'text-xs px-2 py-0.5 rounded font-bold',
                                        task.priority === 'urgent' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700'
                                    ]">{{ task.priority_label }}</span>
                                </div>
                                <!-- Lead info -->
                                <Link v-if="task.lead" :href="route('business.sales.show', task.lead.id)" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 mb-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
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
                            <div v-if="!tasks.tomorrow?.length" class="text-center py-8 text-gray-400 dark:text-gray-500">
                                <CalendarIcon class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                <p class="text-xs">Yo'q</p>
                            </div>
                        </div>
                    </div>

                    <!-- Column 4: Shu hafta (This Week) -->
                    <div class="flex flex-col bg-purple-50/50 dark:bg-purple-900/10 rounded-xl border border-purple-200 dark:border-purple-800/30 overflow-hidden">
                        <div class="p-3 bg-purple-100 dark:bg-purple-900/30 border-b border-purple-200 dark:border-purple-800/30">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                    <span class="font-semibold text-purple-700 dark:text-purple-400">Shu hafta</span>
                                </div>
                                <span class="text-xs font-bold px-2 py-0.5 bg-purple-200 dark:bg-purple-800/50 text-purple-700 dark:text-purple-300 rounded-full">
                                    {{ tasks.this_week?.length || 0 }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 space-y-2">
                            <div
                                v-for="task in tasks.this_week"
                                :key="task.id"
                                class="bg-white dark:bg-gray-800 rounded-lg border border-purple-200 dark:border-purple-700/50 p-3 shadow-sm hover:shadow-md transition-shadow"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ task.due_date_human }}</span>
                                    <span :class="[
                                        'text-xs px-2 py-0.5 rounded font-medium',
                                        task.type === 'call' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' :
                                        task.type === 'meeting' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' :
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                    ]">{{ task.type_label }}</span>
                                </div>
                                <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">{{ task.title }}</h4>
                                <p class="text-xs text-purple-500 dark:text-purple-400 mb-2">{{ task.due_date_full.split(' ')[0] }}</p>
                                <!-- Lead info -->
                                <Link v-if="task.lead" :href="route('business.sales.show', task.lead.id)" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 mb-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
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
                            <div v-if="!tasks.this_week?.length" class="text-center py-8 text-gray-400 dark:text-gray-500">
                                <CalendarIcon class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                <p class="text-xs">Yo'q</p>
                            </div>
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
            @close="closeTaskModal"
            @saved="onTaskSaved"
        />
    </BusinessLayout>
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
