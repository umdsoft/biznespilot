<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PlusIcon,
    CalendarIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    UserIcon,
    UsersIcon,
    ArrowPathIcon,
    DocumentDuplicateIcon,
    ListBulletIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';
import TodoModal from '@/components/todos/TodoModal.vue';
import RecurrenceModal from '@/components/todos/RecurrenceModal.vue';
import TodoDetailModal from '@/components/todos/TodoDetailModal.vue';

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
    panelType: {
        type: String,
        default: 'business',
        validator: (value) => ['business', 'saleshead', 'marketing', 'finance', 'operator'].includes(value),
    },
});

// Panel-specific configuration
const panelConfig = computed(() => {
    const configs = {
        business: {
            primaryColor: 'blue',
            routePrefix: 'business',
            buttonClass: 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/25',
            templatesRoute: 'business.todo-templates.index',
        },
        saleshead: {
            primaryColor: 'emerald',
            routePrefix: 'sales-head',
            buttonClass: 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/25',
            templatesRoute: 'sales-head.todo-templates.index',
        },
        marketing: {
            primaryColor: 'blue',
            routePrefix: 'marketing',
            buttonClass: 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/25',
            templatesRoute: 'marketing.todo-templates.index',
        },
        finance: {
            primaryColor: 'green',
            routePrefix: 'finance',
            buttonClass: 'bg-green-600 hover:bg-green-700 shadow-green-500/25',
            templatesRoute: 'finance.todo-templates.index',
        },
        operator: {
            primaryColor: 'blue',
            routePrefix: 'operator',
            buttonClass: 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/25',
            templatesRoute: 'operator.todo-templates.index',
        },
    };
    return configs[props.panelType];
});

// State
const showTodoModal = ref(false);
const showRecurrenceModal = ref(false);
const showDetailModal = ref(false);
const editingTodo = ref(null);
const selectedTodo = ref(null);
const selectedTodoForRecurrence = ref(null);
const activeFilter = ref(props.filter || 'all');
const activeStatus = ref(props.statusFilter || 'active');
const loadingTodoId = ref(null);

// Kanban columns configuration
const columns = [
    { key: 'overdue', label: "Muddati o'tgan", color: 'red', icon: ExclamationTriangleIcon },
    { key: 'today', label: 'Bugun', color: 'blue', icon: ClockIcon },
    { key: 'tomorrow', label: 'Ertaga', color: 'indigo', icon: CalendarIcon },
    { key: 'this_week', label: 'Shu hafta', color: 'purple', icon: CalendarIcon },
    { key: 'later', label: 'Keyinroq', color: 'gray', icon: CalendarIcon },
];

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
    router.get(route(`${panelConfig.value.routePrefix}.todos.index`), {
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

// Open detail modal (Trello-style)
const openDetailModal = async (todo) => {
    loadingTodoId.value = todo.id;
    try {
        const response = await fetch(route(`${panelConfig.value.routePrefix}.todos.show`, todo.id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const data = await response.json();
            selectedTodo.value = data.todo;
            showDetailModal.value = true;
        }
    } catch (error) {
        console.error('Failed to load todo:', error);
    } finally {
        loadingTodoId.value = null;
    }
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedTodo.value = null;
};

const toggleTodo = async (todo, event) => {
    event?.stopPropagation();
    try {
        const response = await fetch(route(`${panelConfig.value.routePrefix}.todos.toggle`, todo.id), {
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

const onTodoSaved = () => {
    router.reload({ preserveScroll: true });
};

const onRecurrenceSaved = () => {
    router.reload({ preserveScroll: true });
};

// Detail modal event handlers
const handleDetailUpdate = async (todoId, formData) => {
    try {
        const response = await fetch(route(`${panelConfig.value.routePrefix}.todos.update`, todoId), {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData),
        });
        if (response.ok) {
            const data = await response.json();
            selectedTodo.value = data.todo;
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to update todo:', error);
    }
};

const handleDetailDelete = async (todo) => {
    try {
        const response = await fetch(route(`${panelConfig.value.routePrefix}.todos.destroy`, todo.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            closeDetailModal();
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to delete todo:', error);
    }
};

const handleDetailToggle = async (todo) => {
    try {
        const response = await fetch(route(`${panelConfig.value.routePrefix}.todos.toggle`, todo.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            const data = await response.json();
            selectedTodo.value = data.todo;
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to toggle todo:', error);
    }
};

const handleDetailToggleSubtask = async (todo, subtask) => {
    try {
        const response = await fetch(route(`${panelConfig.value.routePrefix}.todos.subtasks.toggle`, [todo.id, subtask.id]), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            // Reload todo data
            const todoResponse = await fetch(route(`${panelConfig.value.routePrefix}.todos.show`, todo.id), {
                headers: { 'Accept': 'application/json' },
            });
            if (todoResponse.ok) {
                const data = await todoResponse.json();
                selectedTodo.value = data.todo;
            }
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to toggle subtask:', error);
    }
};

const handleDetailAddSubtask = async (todo, title) => {
    try {
        const response = await fetch(route(`${panelConfig.value.routePrefix}.todos.subtasks.store`, todo.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ title }),
        });
        if (response.ok) {
            // Reload todo data
            const todoResponse = await fetch(route(`${panelConfig.value.routePrefix}.todos.show`, todo.id), {
                headers: { 'Accept': 'application/json' },
            });
            if (todoResponse.ok) {
                const data = await todoResponse.json();
                selectedTodo.value = data.todo;
            }
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to add subtask:', error);
    }
};

// Priority colors
const getPriorityColor = (priority) => {
    const colors = {
        urgent: 'bg-red-500',
        high: 'bg-orange-500',
        medium: 'bg-yellow-500',
        low: 'bg-green-500',
    };
    return colors[priority] || colors.medium;
};

const getPriorityBadgeColor = (priority) => {
    const colors = {
        urgent: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
        high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-400',
        medium: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400',
        low: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
    };
    return colors[priority] || colors.medium;
};

// Get column color classes
const getColumnHeaderClass = (color) => {
    const classes = {
        red: 'bg-red-500',
        blue: 'bg-blue-500',
        indigo: 'bg-indigo-500',
        purple: 'bg-purple-500',
        gray: 'bg-gray-500',
    };
    return classes[color] || classes.gray;
};

// Get todos for a column
const getColumnTodos = (key) => {
    return props.todos[key] || [];
};

// Get todos count for column
const getColumnCount = (key) => {
    return getColumnTodos(key).length;
};

// Check if templates route exists
const hasTemplatesRoute = computed(() => {
    try {
        route(panelConfig.value.templatesRoute);
        return true;
    } catch {
        return false;
    }
});
</script>

<template>
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
                        v-if="hasTemplatesRoute"
                        :href="route(panelConfig.templatesRoute)"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-xl transition-colors"
                    >
                        <DocumentDuplicateIcon class="w-5 h-5" />
                        Shablonlar
                    </Link>
                    <button
                        @click="openTodoModal()"
                        :class="[
                            'inline-flex items-center gap-2 px-4 py-2.5 text-white font-medium rounded-xl transition-colors shadow-lg',
                            panelConfig.buttonClass
                        ]"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Yangi vazifa
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex items-center gap-4 mt-4">
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

                <!-- Stats inline -->
                <div class="flex items-center gap-4 ml-auto text-sm">
                    <div class="flex items-center gap-1.5 text-red-600 dark:text-red-400">
                        <ExclamationTriangleIcon class="w-4 h-4" />
                        <span class="font-semibold">{{ stats.overdue }}</span>
                        <span class="text-gray-500 dark:text-gray-400">muddati o'tgan</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-green-600 dark:text-green-400">
                        <CheckCircleIcon class="w-4 h-4" />
                        <span class="font-semibold">{{ stats.completed_today }}</span>
                        <span class="text-gray-500 dark:text-gray-400">bugun bajarilgan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="flex-1 overflow-x-auto p-4 sm:p-6">
            <div class="flex gap-4 h-full min-w-max">
                <!-- Kanban Columns -->
                <div
                    v-for="column in columns"
                    :key="column.key"
                    class="flex flex-col w-80 bg-gray-100 dark:bg-gray-800/50 rounded-xl"
                >
                    <!-- Column Header -->
                    <div class="p-3 flex items-center gap-2">
                        <div :class="['w-2 h-2 rounded-full', getColumnHeaderClass(column.color)]"></div>
                        <h3 class="font-semibold text-gray-700 dark:text-gray-300 text-sm">
                            {{ column.label }}
                        </h3>
                        <span class="ml-auto text-xs font-bold px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full">
                            {{ getColumnCount(column.key) }}
                        </span>
                    </div>

                    <!-- Column Content -->
                    <div class="flex-1 overflow-y-auto p-2 space-y-2">
                        <!-- Task Cards -->
                        <div
                            v-for="todo in getColumnTodos(column.key)"
                            :key="todo.id"
                            @click="openDetailModal(todo)"
                            :class="[
                                'bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 cursor-pointer transition-all hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600',
                                loadingTodoId === todo.id ? 'opacity-50' : '',
                                todo.status === 'completed' ? 'opacity-60' : ''
                            ]"
                        >
                            <!-- Priority indicator -->
                            <div class="flex items-start gap-2 mb-2">
                                <div :class="['w-1 h-full min-h-[20px] rounded-full', getPriorityColor(todo.priority)]"></div>
                                <div class="flex-1 min-w-0">
                                    <!-- Title -->
                                    <h4 :class="[
                                        'font-medium text-gray-900 dark:text-white text-sm leading-snug',
                                        todo.status === 'completed' ? 'line-through text-gray-500 dark:text-gray-500' : ''
                                    ]">
                                        {{ todo.title }}
                                    </h4>

                                    <!-- Description preview -->
                                    <p v-if="todo.description" class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                        {{ todo.description }}
                                    </p>
                                </div>

                                <!-- Checkbox -->
                                <button
                                    @click="toggleTodo(todo, $event)"
                                    :class="[
                                        'flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-all',
                                        todo.status === 'completed'
                                            ? 'bg-green-500 border-green-500 text-white'
                                            : 'border-gray-300 dark:border-gray-600 hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20'
                                    ]"
                                >
                                    <CheckIcon v-if="todo.status === 'completed'" class="w-3 h-3" />
                                </button>
                            </div>

                            <!-- Badges row -->
                            <div class="flex items-center flex-wrap gap-1.5 mt-2">
                                <!-- Type badge -->
                                <span :class="[
                                    'inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs',
                                    todo.type === 'team'
                                        ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-400'
                                        : todo.type === 'process'
                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                ]">
                                    <UsersIcon v-if="todo.type === 'team'" class="w-3 h-3" />
                                    <ListBulletIcon v-else-if="todo.type === 'process'" class="w-3 h-3" />
                                    <UserIcon v-else class="w-3 h-3" />
                                    {{ todo.type_label }}
                                </span>

                                <!-- Priority badge -->
                                <span :class="['px-1.5 py-0.5 rounded text-xs font-medium', getPriorityBadgeColor(todo.priority)]">
                                    {{ todo.priority_label }}
                                </span>

                                <!-- Recurring badge -->
                                <span v-if="todo.is_recurring" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400">
                                    <ArrowPathIcon class="w-3 h-3" />
                                </span>
                            </div>

                            <!-- Team progress (for team tasks) -->
                            <div v-if="todo.type === 'team' && todo.assignees && todo.assignees.length > 0" class="mt-3">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="text-gray-500 dark:text-gray-400">Jamoa progress</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        {{ todo.completed_assignees_count || 0 }}/{{ todo.assignees_count || todo.assignees.length }}
                                    </span>
                                </div>
                                <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        :style="{ width: `${todo.team_progress || 0}%` }"
                                        :class="[
                                            'h-full transition-all duration-300',
                                            (todo.team_progress || 0) === 100 ? 'bg-green-500' : 'bg-blue-500'
                                        ]"
                                    ></div>
                                </div>
                                <!-- Assignee avatars -->
                                <div class="flex items-center mt-2 -space-x-1">
                                    <div
                                        v-for="(assignee, idx) in todo.assignees.slice(0, 5)"
                                        :key="assignee.id"
                                        :class="[
                                            'w-6 h-6 rounded-full border-2 border-white dark:border-gray-800 flex items-center justify-center text-xs font-medium',
                                            assignee.is_completed
                                                ? 'bg-green-500 text-white'
                                                : 'bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300'
                                        ]"
                                        :title="assignee.user?.name + (assignee.is_completed ? ' (bajarilgan)' : '')"
                                    >
                                        {{ assignee.user?.name?.charAt(0)?.toUpperCase() }}
                                    </div>
                                    <div
                                        v-if="todo.assignees.length > 5"
                                        class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-800 bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-400"
                                    >
                                        +{{ todo.assignees.length - 5 }}
                                    </div>
                                </div>
                            </div>

                            <!-- Subtasks progress -->
                            <div v-else-if="todo.subtasks_count > 0" class="mt-3">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="text-gray-500 dark:text-gray-400">Sub-tasklar</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                        {{ todo.completed_subtasks_count }}/{{ todo.subtasks_count }}
                                    </span>
                                </div>
                                <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        :style="{ width: `${todo.progress}%` }"
                                        :class="[
                                            'h-full transition-all duration-300',
                                            todo.progress === 100 ? 'bg-green-500' : 'bg-blue-500'
                                        ]"
                                    ></div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between mt-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <!-- Due time -->
                                <div v-if="todo.due_date_formatted" :class="[
                                    'flex items-center gap-1 text-xs',
                                    todo.is_overdue ? 'text-red-500' : 'text-gray-500 dark:text-gray-400'
                                ]">
                                    <ClockIcon class="w-3.5 h-3.5" />
                                    {{ todo.due_date_formatted }}
                                </div>
                                <div v-else class="text-xs text-gray-400">Muddatsiz</div>

                                <!-- Single assignee or creator -->
                                <div v-if="todo.type !== 'team' && todo.assignee" class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <div class="w-5 h-5 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ todo.assignee.name?.charAt(0)?.toUpperCase() }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty column state -->
                        <div
                            v-if="getColumnCount(column.key) === 0"
                            class="text-center py-8 text-gray-400 dark:text-gray-500"
                        >
                            <component :is="column.icon" class="w-8 h-8 mx-auto mb-2 opacity-50" />
                            <p class="text-sm">Vazifa yo'q</p>
                        </div>
                    </div>

                    <!-- Add task button at bottom -->
                    <div class="p-2 border-t border-gray-200 dark:border-gray-700">
                        <button
                            @click="openTodoModal()"
                            class="w-full flex items-center justify-center gap-2 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            <PlusIcon class="w-4 h-4" />
                            Vazifa qo'shish
                        </button>
                    </div>
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
        :panel-type="panelType"
        @close="closeTodoModal"
        @saved="onTodoSaved"
    />

    <RecurrenceModal
        :show="showRecurrenceModal"
        :todo="selectedTodoForRecurrence"
        :panel-type="panelType"
        @close="closeRecurrenceModal"
        @saved="onRecurrenceSaved"
    />

    <TodoDetailModal
        :show="showDetailModal"
        :todo="selectedTodo"
        :team-members="teamMembers"
        :types="types"
        :priorities="priorities"
        @close="closeDetailModal"
        @update="handleDetailUpdate"
        @delete="handleDetailDelete"
        @toggle="handleDetailToggle"
        @toggle-subtask="handleDetailToggleSubtask"
        @add-subtask="handleDetailAddSubtask"
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

.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}
.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.3);
    border-radius: 4px;
}
.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background-color: rgba(156, 163, 175, 0.5);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
