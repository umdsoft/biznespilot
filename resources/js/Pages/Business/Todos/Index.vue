<script setup>
import { ref, computed, watch } from 'vue';
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
    UserIcon,
    ArrowPathIcon,
    DocumentDuplicateIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    Squares2X2Icon,
    ListBulletIcon,
} from '@heroicons/vue/24/outline';
import TodoModal from '@/components/todos/TodoModal.vue';
import RecurrenceModal from '@/components/todos/RecurrenceModal.vue';

const props = defineProps({
    todos: Object,
    stats: Object,
    teamMembers: Array,
    templates: Array,
    types: Object,
    priorities: Object,
    statuses: Object,
    filter: String,
    statusFilter: String,
});

// State
const showTodoModal = ref(false);
const showRecurrenceModal = ref(false);
const editingTodo = ref(null);
const selectedTodoForRecurrence = ref(null);
const activeFilter = ref(props.filter || 'all');
const activeStatus = ref(props.statusFilter || 'active');
const expandedTodos = ref({});

// Filters
const filterOptions = [
    { value: 'all', label: 'Barchasi' },
    { value: 'personal', label: 'Shaxsiy' },
    { value: 'team', label: 'Jamoa' },
    { value: 'process', label: 'Jarayon' },
];

const statusOptions = [
    { value: 'active', label: 'Faol' },
    { value: 'completed', label: 'Bajarilgan' },
    { value: 'all', label: 'Barchasi' },
];

// Watch filters and reload
watch([activeFilter, activeStatus], () => {
    router.get(route('business.todos.index'), {
        filter: activeFilter.value,
        status: activeStatus.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
});

// Methods
const openTodoModal = (todo = null) => {
    editingTodo.value = todo;
    showTodoModal.value = true;
};

const closeTodoModal = () => {
    showTodoModal.value = false;
    editingTodo.value = null;
};

const openRecurrenceModal = (todo) => {
    selectedTodoForRecurrence.value = todo;
    showRecurrenceModal.value = true;
};

const closeRecurrenceModal = () => {
    showRecurrenceModal.value = false;
    selectedTodoForRecurrence.value = null;
};

const toggleTodo = async (todo) => {
    try {
        const response = await fetch(route('business.todos.toggle', todo.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to toggle todo:', error);
    }
};

const toggleSubtask = async (todo, subtask) => {
    try {
        const response = await fetch(route('business.todos.subtasks.toggle', [todo.id, subtask.id]), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to toggle subtask:', error);
    }
};

const deleteTodo = async (todo) => {
    if (!confirm('Vazifani o\'chirmoqchimisiz?')) return;

    try {
        const response = await fetch(route('business.todos.destroy', todo.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to delete todo:', error);
    }
};

const toggleExpanded = (todoId) => {
    expandedTodos.value[todoId] = !expandedTodos.value[todoId];
};

const onTodoSaved = () => {
    router.reload({ preserveScroll: true });
};

const onRecurrenceSaved = () => {
    router.reload({ preserveScroll: true });
};

// Priority colors
const getPriorityColor = (priority) => {
    const colors = {
        urgent: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
        high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-400',
        medium: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400',
        low: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
    };
    return colors[priority] || colors.medium;
};

// Type icons
const getTypeIcon = (type) => {
    const icons = {
        personal: UserIcon,
        team: Squares2X2Icon,
        process: ListBulletIcon,
    };
    return icons[type] || ListBulletIcon;
};

// Period labels
const periodLabels = {
    overdue: 'Muddati o\'tgan',
    today: 'Bugun',
    tomorrow: 'Ertaga',
    this_week: 'Shu hafta',
    later: 'Keyinroq',
    no_date: 'Muddatsiz',
};

const periodColors = {
    overdue: 'red',
    today: 'blue',
    tomorrow: 'indigo',
    this_week: 'purple',
    later: 'gray',
    no_date: 'gray',
};
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
                            {{ stats.total }} ta faol vazifa
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link
                            :href="route('business.todo-templates.index')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-xl transition-colors"
                        >
                            <DocumentDuplicateIcon class="w-5 h-5" />
                            Shablonlar
                        </Link>
                        <button
                            @click="openTodoModal()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors shadow-lg shadow-blue-500/25"
                        >
                            <PlusIcon class="w-5 h-5" />
                            Yangi vazifa
                        </button>
                    </div>
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
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800/30">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-800/30 rounded-lg flex items-center justify-center">
                                <CheckCircleIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.completed_today }}</p>
                                <p class="text-xs text-green-500 dark:text-green-400">Bugun bajarilgan</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800/30">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-800/30 rounded-lg flex items-center justify-center">
                                <UserIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.my_todos }}</p>
                                <p class="text-xs text-purple-500 dark:text-purple-400">Mening vazifalarim</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 border border-indigo-200 dark:border-indigo-800/30">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-800/30 rounded-lg flex items-center justify-center">
                                <CalendarIcon class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ stats.total }}</p>
                                <p class="text-xs text-indigo-500 dark:text-indigo-400">Jami faol</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex items-center gap-4 mt-6">
                    <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                        <button
                            v-for="option in filterOptions"
                            :key="option.value"
                            @click="activeFilter = option.value"
                            :class="[
                                'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                                activeFilter === option.value
                                    ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                            ]"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            @click="activeStatus = option.value"
                            :class="[
                                'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                                activeStatus === option.value
                                    ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                            ]"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Todo List -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="max-w-4xl mx-auto space-y-6">
                    <!-- Period sections -->
                    <template v-for="(periodTodos, period) in todos" :key="period">
                        <div v-if="periodTodos && periodTodos.length > 0" class="space-y-3">
                            <!-- Period header -->
                            <div class="flex items-center gap-3">
                                <div :class="[
                                    'w-3 h-3 rounded-full',
                                    `bg-${periodColors[period]}-500`
                                ]"></div>
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ periodLabels[period] }}
                                </h3>
                                <span class="text-xs font-bold px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-full">
                                    {{ periodTodos.length }}
                                </span>
                            </div>

                            <!-- Todo items -->
                            <div class="space-y-2">
                                <div
                                    v-for="todo in periodTodos"
                                    :key="todo.id"
                                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all"
                                >
                                    <!-- Main todo item -->
                                    <div class="p-4">
                                        <div class="flex items-start gap-3">
                                            <!-- Checkbox -->
                                            <button
                                                @click="toggleTodo(todo)"
                                                :class="[
                                                    'flex-shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors mt-0.5',
                                                    todo.status === 'completed'
                                                        ? 'bg-green-500 border-green-500 text-white'
                                                        : 'border-gray-300 dark:border-gray-600 hover:border-green-400'
                                                ]"
                                            >
                                                <CheckCircleIcon v-if="todo.status === 'completed'" class="w-4 h-4" />
                                            </button>

                                            <!-- Content -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 :class="[
                                                        'font-medium text-gray-900 dark:text-white',
                                                        todo.status === 'completed' ? 'line-through text-gray-500' : ''
                                                    ]">
                                                        {{ todo.title }}
                                                    </h4>
                                                    <span v-if="todo.is_recurring" class="flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400">
                                                        <ArrowPathIcon class="w-3 h-3" />
                                                        {{ todo.recurrence?.frequency_label }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center flex-wrap gap-2 text-xs">
                                                    <!-- Type badge -->
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                                        <component :is="getTypeIcon(todo.type)" class="w-3 h-3" />
                                                        {{ todo.type_label }}
                                                    </span>

                                                    <!-- Priority badge -->
                                                    <span :class="['px-2 py-0.5 rounded font-medium', getPriorityColor(todo.priority)]">
                                                        {{ todo.priority_label }}
                                                    </span>

                                                    <!-- Due date -->
                                                    <span v-if="todo.due_date_formatted" :class="[
                                                        'flex items-center gap-1',
                                                        todo.is_overdue ? 'text-red-500' : 'text-gray-500 dark:text-gray-400'
                                                    ]">
                                                        <CalendarIcon class="w-3 h-3" />
                                                        {{ todo.due_date_formatted }}
                                                    </span>

                                                    <!-- Assignee -->
                                                    <span v-if="todo.assignee" class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                                        <UserIcon class="w-3 h-3" />
                                                        {{ todo.assignee.name }}
                                                    </span>

                                                    <!-- Subtasks progress -->
                                                    <span v-if="todo.subtasks_count > 0" class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                                        <ListBulletIcon class="w-3 h-3" />
                                                        {{ todo.completed_subtasks_count }}/{{ todo.subtasks_count }}
                                                    </span>
                                                </div>

                                                <!-- Progress bar for subtasks -->
                                                <div v-if="todo.subtasks_count > 0" class="mt-2">
                                                    <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                        <div
                                                            :style="{ width: `${todo.progress}%` }"
                                                            class="h-full bg-green-500 transition-all duration-300"
                                                        ></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex items-center gap-1">
                                                <button
                                                    v-if="todo.subtasks_count > 0"
                                                    @click="toggleExpanded(todo.id)"
                                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                                >
                                                    <ChevronDownIcon v-if="expandedTodos[todo.id]" class="w-5 h-5" />
                                                    <ChevronRightIcon v-else class="w-5 h-5" />
                                                </button>
                                                <button
                                                    @click="openRecurrenceModal(todo)"
                                                    class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                                    title="Takrorlash"
                                                >
                                                    <ArrowPathIcon class="w-5 h-5" />
                                                </button>
                                                <button
                                                    @click="openTodoModal(todo)"
                                                    class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                                >
                                                    <PencilSquareIcon class="w-5 h-5" />
                                                </button>
                                                <button
                                                    @click="deleteTodo(todo)"
                                                    class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                                >
                                                    <TrashIcon class="w-5 h-5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Subtasks -->
                                    <div
                                        v-if="todo.subtasks && todo.subtasks.length > 0 && expandedTodos[todo.id]"
                                        class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-xl"
                                    >
                                        <div class="p-3 space-y-2">
                                            <div
                                                v-for="subtask in todo.subtasks"
                                                :key="subtask.id"
                                                class="flex items-center gap-3 pl-6"
                                            >
                                                <button
                                                    @click="toggleSubtask(todo, subtask)"
                                                    :class="[
                                                        'flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                                                        subtask.is_completed
                                                            ? 'bg-green-500 border-green-500 text-white'
                                                            : 'border-gray-300 dark:border-gray-600 hover:border-green-400'
                                                    ]"
                                                >
                                                    <CheckCircleIcon v-if="subtask.is_completed" class="w-3 h-3" />
                                                </button>
                                                <span :class="[
                                                    'text-sm',
                                                    subtask.is_completed
                                                        ? 'line-through text-gray-400'
                                                        : 'text-gray-700 dark:text-gray-300'
                                                ]">
                                                    {{ subtask.title }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty state -->
                    <div
                        v-if="Object.values(todos).every(t => !t || t.length === 0)"
                        class="text-center py-16"
                    >
                        <CheckCircleIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Vazifalar yo'q</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">Yangi vazifa qo'shib boshlang</p>
                        <button
                            @click="openTodoModal()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors"
                        >
                            <PlusIcon class="w-5 h-5" />
                            Yangi vazifa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <TodoModal
            :show="showTodoModal"
            :todo="editingTodo"
            :team-members="teamMembers"
            :templates="templates"
            :types="types"
            :priorities="priorities"
            @close="closeTodoModal"
            @saved="onTodoSaved"
        />

        <RecurrenceModal
            :show="showRecurrenceModal"
            :todo="selectedTodoForRecurrence"
            @close="closeRecurrenceModal"
            @saved="onRecurrenceSaved"
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
