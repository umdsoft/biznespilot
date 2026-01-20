<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from '@/i18n';
import {
    XMarkIcon,
    CalendarIcon,
    UserIcon,
    CheckCircleIcon,
    ClockIcon,
    PencilSquareIcon,
    TrashIcon,
    PlusIcon,
    ArrowPathIcon,
    UserGroupIcon,
    ExclamationTriangleIcon,
    ChevronDownIcon,
    ChevronUpIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleSolidIcon } from '@heroicons/vue/24/solid';

const { t } = useI18n();

const props = defineProps({
    show: Boolean,
    todo: Object,
    teamMembers: Array,
    types: Object,
    priorities: Object,
});

const emit = defineEmits(['close', 'update', 'delete', 'toggle', 'toggleSubtask', 'addSubtask']);

// Local state
const isEditing = ref(false);
const editForm = ref({});
const newSubtask = ref('');
const showSubtasks = ref(true);
const saving = ref(false);

// Watch for todo changes
watch(() => props.todo, (newTodo) => {
    if (newTodo) {
        editForm.value = {
            title: newTodo.title,
            description: newTodo.description || '',
            type: newTodo.type,
            priority: newTodo.priority,
            due_date: newTodo.due_date ? newTodo.due_date.substring(0, 16) : '',
            assigned_to: newTodo.assignee?.id || '',
            assignee_ids: newTodo.assignees?.map(a => a.user_id) || [],
        };
    }
}, { immediate: true });

// Computed
const priorityColors = {
    urgent: { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-700 dark:text-red-400', border: 'border-red-500' },
    high: { bg: 'bg-orange-100 dark:bg-orange-900/30', text: 'text-orange-700 dark:text-orange-400', border: 'border-orange-500' },
    medium: { bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-700 dark:text-yellow-400', border: 'border-yellow-500' },
    low: { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-700 dark:text-green-400', border: 'border-green-500' },
};

const typeIcons = {
    personal: UserIcon,
    team: UserGroupIcon,
    process: ArrowPathIcon,
};

const isCompleted = computed(() => props.todo?.status === 'completed');
const isTeamTask = computed(() => props.todo?.is_team_task);

// Methods
const close = () => {
    isEditing.value = false;
    emit('close');
};

const startEditing = () => {
    isEditing.value = true;
};

const cancelEditing = () => {
    isEditing.value = false;
    if (props.todo) {
        editForm.value = {
            title: props.todo.title,
            description: props.todo.description || '',
            type: props.todo.type,
            priority: props.todo.priority,
            due_date: props.todo.due_date ? props.todo.due_date.substring(0, 16) : '',
            assigned_to: props.todo.assignee?.id || '',
            assignee_ids: props.todo.assignees?.map(a => a.user_id) || [],
        };
    }
};

const saveChanges = async () => {
    saving.value = true;
    try {
        await emit('update', props.todo.id, editForm.value);
        isEditing.value = false;
    } finally {
        saving.value = false;
    }
};

const toggleComplete = () => {
    emit('toggle', props.todo);
};

const deleteTodo = () => {
    if (confirm(t('todos.detail.delete_confirm'))) {
        emit('delete', props.todo);
    }
};

const addSubtask = () => {
    if (newSubtask.value.trim()) {
        emit('addSubtask', props.todo, newSubtask.value.trim());
        newSubtask.value = '';
    }
};

const toggleSubtask = (subtask) => {
    emit('toggleSubtask', props.todo, subtask);
};

const toggleAssignee = (userId) => {
    const idx = editForm.value.assignee_ids.indexOf(userId);
    if (idx > -1) {
        editForm.value.assignee_ids.splice(idx, 1);
    } else {
        editForm.value.assignee_ids.push(userId);
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show && todo" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
                <div class="flex min-h-screen items-start justify-center p-4 pt-16">
                    <div class="fixed inset-0 bg-black/60" @click="close" />

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl z-10">
                        <!-- Header with priority color -->
                        <div :class="[
                            'h-2 rounded-t-2xl',
                            priorityColors[todo.priority]?.border ? `bg-${todo.priority === 'urgent' ? 'red' : todo.priority === 'high' ? 'orange' : todo.priority === 'medium' ? 'yellow' : 'green'}-500` : 'bg-gray-400'
                        ]"></div>

                        <!-- Content -->
                        <div class="p-6">
                            <!-- Title and status -->
                            <div class="flex items-start gap-4 mb-6">
                                <!-- Checkbox -->
                                <button
                                    @click="toggleComplete"
                                    :disabled="isTeamTask && !todo.can_complete"
                                    :class="[
                                        'flex-shrink-0 w-8 h-8 rounded-full border-2 flex items-center justify-center transition-all mt-1',
                                        isCompleted
                                            ? 'bg-green-500 border-green-500 text-white'
                                            : 'border-gray-300 dark:border-gray-600 hover:border-green-400',
                                        isTeamTask && !todo.can_complete ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                >
                                    <CheckCircleSolidIcon v-if="isCompleted" class="w-5 h-5" />
                                </button>

                                <div class="flex-1">
                                    <!-- Editing title -->
                                    <input
                                        v-if="isEditing"
                                        v-model="editForm.title"
                                        type="text"
                                        class="w-full text-xl font-bold bg-transparent border-b-2 border-blue-500 text-gray-900 dark:text-white focus:outline-none pb-1"
                                    />
                                    <!-- Display title -->
                                    <h2 v-else :class="[
                                        'text-xl font-bold text-gray-900 dark:text-white',
                                        isCompleted ? 'line-through text-gray-500' : ''
                                    ]">
                                        {{ todo.title }}
                                    </h2>

                                    <!-- Badges -->
                                    <div class="flex items-center flex-wrap gap-2 mt-2">
                                        <!-- Type badge -->
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm">
                                            <component :is="typeIcons[todo.type]" class="w-4 h-4" />
                                            {{ todo.type_label }}
                                        </span>

                                        <!-- Priority badge -->
                                        <span :class="[
                                            'px-2.5 py-1 rounded-lg text-sm font-medium',
                                            priorityColors[todo.priority]?.bg,
                                            priorityColors[todo.priority]?.text
                                        ]">
                                            {{ todo.priority_label }}
                                        </span>

                                        <!-- Due date -->
                                        <span v-if="todo.due_date_formatted" :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-sm',
                                            todo.is_overdue
                                                ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                                        ]">
                                            <CalendarIcon class="w-4 h-4" />
                                            {{ todo.due_date_formatted }}
                                        </span>

                                        <!-- Recurring -->
                                        <span v-if="todo.is_recurring" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-sm">
                                            <ArrowPathIcon class="w-4 h-4" />
                                            {{ todo.recurrence?.frequency_label }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Close button -->
                                <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                    <XMarkIcon class="w-6 h-6" />
                                </button>
                            </div>

                            <!-- Team Progress (for team tasks) -->
                            <div v-if="isTeamTask" class="mb-6 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800/30">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="font-semibold text-purple-800 dark:text-purple-300 flex items-center gap-2">
                                        <UserGroupIcon class="w-5 h-5" />
                                        {{ t('todos.detail.team_progress') }}
                                    </h3>
                                    <span class="text-sm font-bold text-purple-700 dark:text-purple-400">
                                        {{ todo.completed_assignees_count }}/{{ todo.assignees_count }} {{ t('todos.detail.completed') }}
                                    </span>
                                </div>

                                <!-- Progress bar -->
                                <div class="h-2 bg-purple-200 dark:bg-purple-800/50 rounded-full overflow-hidden mb-3">
                                    <div
                                        :style="{ width: `${todo.team_progress}%` }"
                                        class="h-full bg-purple-500 transition-all duration-300"
                                    ></div>
                                </div>

                                <!-- Team members -->
                                <div class="space-y-2">
                                    <div
                                        v-for="assignee in todo.assignees"
                                        :key="assignee.id"
                                        class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded-lg"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div :class="[
                                                'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold',
                                                assignee.is_completed
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400'
                                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
                                            ]">
                                                {{ assignee.user?.name?.charAt(0).toUpperCase() }}
                                            </div>
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ assignee.user?.name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span v-if="assignee.is_completed" class="text-xs text-green-600 dark:text-green-400">
                                                {{ assignee.completed_at }}
                                            </span>
                                            <CheckCircleSolidIcon
                                                v-if="assignee.is_completed"
                                                class="w-5 h-5 text-green-500"
                                            />
                                            <div v-else class="w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Warning if not all completed -->
                                <div v-if="!todo.is_team_completed && todo.assignees_count > 0" class="mt-3 flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400">
                                    <ExclamationTriangleIcon class="w-4 h-4" />
                                    {{ t('todos.detail.team_incomplete_warning') }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-6">
                                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ t('todos.detail.description') }}</h3>
                                <textarea
                                    v-if="isEditing"
                                    v-model="editForm.description"
                                    rows="3"
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white resize-none"
                                    :placeholder="t('todos.detail.description_placeholder')"
                                ></textarea>
                                <p v-else class="text-gray-700 dark:text-gray-300">
                                    {{ todo.description || t('todos.detail.no_description') }}
                                </p>
                            </div>

                            <!-- Editing fields -->
                            <div v-if="isEditing" class="grid grid-cols-2 gap-4 mb-6">
                                <!-- Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('todos.detail.type') }}</label>
                                    <select v-model="editForm.type" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                                        <option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                </div>

                                <!-- Priority -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('todos.detail.priority') }}</label>
                                    <select v-model="editForm.priority" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                                        <option v-for="(label, value) in priorities" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                </div>

                                <!-- Due date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('todos.detail.due_date') }}</label>
                                    <input
                                        v-model="editForm.due_date"
                                        type="datetime-local"
                                        class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"
                                    />
                                </div>

                                <!-- Assignee (for personal/process) -->
                                <div v-if="editForm.type !== 'team'">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('todos.detail.assign_to') }}</label>
                                    <select v-model="editForm.assigned_to" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                                        <option value="">{{ t('common.select') }}</option>
                                        <option v-for="member in teamMembers" :key="member.id" :value="member.id">{{ member.name }}</option>
                                    </select>
                                </div>

                                <!-- Team assignees (for team tasks) -->
                                <div v-if="editForm.type === 'team'" class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ t('todos.detail.team_members') }}</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="member in teamMembers"
                                            :key="member.id"
                                            @click="toggleAssignee(member.id)"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                                                editForm.assignee_ids.includes(member.id)
                                                    ? 'bg-purple-500 text-white'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                            ]"
                                        >
                                            {{ member.name }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Subtasks -->
                            <div v-if="todo.subtasks && todo.subtasks.length > 0" class="mb-6">
                                <button
                                    @click="showSubtasks = !showSubtasks"
                                    class="flex items-center gap-2 text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 hover:text-gray-700 dark:hover:text-gray-200"
                                >
                                    <component :is="showSubtasks ? ChevronUpIcon : ChevronDownIcon" class="w-4 h-4" />
                                    {{ t('todos.detail.subtasks') }} ({{ todo.completed_subtasks_count }}/{{ todo.subtasks_count }})
                                </button>

                                <div v-show="showSubtasks" class="space-y-2">
                                    <div
                                        v-for="subtask in todo.subtasks"
                                        :key="subtask.id"
                                        class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                    >
                                        <button
                                            @click="toggleSubtask(subtask)"
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
                                            subtask.is_completed ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300'
                                        ]">
                                            {{ subtask.title }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Add subtask -->
                            <div class="mb-6">
                                <div class="flex gap-2">
                                    <input
                                        v-model="newSubtask"
                                        type="text"
                                        :placeholder="t('todos.detail.add_subtask_placeholder')"
                                        @keyup.enter="addSubtask"
                                        class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white text-sm"
                                    />
                                    <button
                                        @click="addSubtask"
                                        :disabled="!newSubtask.trim()"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg transition-colors"
                                    >
                                        <PlusIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Footer actions -->
                            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ t('todos.detail.created') }}: {{ todo.created_at }}
                                </div>

                                <div class="flex items-center gap-2">
                                    <template v-if="isEditing">
                                        <button
                                            @click="cancelEditing"
                                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                        >
                                            {{ t('common.cancel') }}
                                        </button>
                                        <button
                                            @click="saveChanges"
                                            :disabled="saving"
                                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                                        >
                                            {{ saving ? t('common.saving') + '...' : t('common.save') }}
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button
                                            @click="deleteTodo"
                                            class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                        >
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="startEditing"
                                            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors flex items-center gap-2"
                                        >
                                            <PencilSquareIcon class="w-4 h-4" />
                                            {{ t('common.edit') }}
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
