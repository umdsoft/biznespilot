<template>
    <div class="space-y-2">
        <div v-if="displayTasks.length === 0" class="text-center py-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400">{{ t('dashboard.tasks.no_tasks') }}</p>
        </div>
        <div
            v-for="task in displayTasks"
            :key="task.id"
            :class="[
                'flex items-center gap-3 p-3 rounded-xl transition-all duration-200',
                'bg-gray-50 dark:bg-gray-700/50',
                'hover:bg-gray-100 dark:hover:bg-gray-700',
                'border border-transparent hover:border-gray-200 dark:hover:border-gray-600'
            ]"
        >
            <!-- Checkbox -->
            <button
                @click="$emit('toggle', task)"
                :class="[
                    'w-5 h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 transition-all duration-200',
                    task.status === 'completed'
                        ? 'bg-emerald-500 dark:bg-emerald-600 border-emerald-500 dark:border-emerald-600 text-white'
                        : 'border-gray-300 dark:border-gray-500 hover:border-emerald-500 dark:hover:border-emerald-400'
                ]"
            >
                <svg v-if="task.status === 'completed'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </button>

            <!-- Task Content -->
            <div class="flex-1 min-w-0">
                <p :class="[
                    'text-sm font-medium transition-colors',
                    task.status === 'completed'
                        ? 'text-gray-400 dark:text-gray-500 line-through'
                        : 'text-gray-900 dark:text-white'
                ]">
                    {{ task.title }}
                </p>
                <div class="flex items-center gap-2 mt-1.5">
                    <!-- Due Date -->
                    <span v-if="task.due_date" :class="[
                        'inline-flex items-center text-xs',
                        dueDateColor(task)
                    ]">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ formatDate(task.due_date) }}
                    </span>
                    <!-- Priority Badge -->
                    <span v-if="task.priority" :class="[
                        'px-2 py-0.5 rounded-full text-xs font-medium',
                        priorityColor(task.priority)
                    ]">
                        {{ priorityLabel(task.priority) }}
                    </span>
                </div>
            </div>

            <!-- Lead/Contact Info -->
            <div v-if="task.lead_name" class="text-right flex-shrink-0">
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[100px]">
                    {{ task.lead_name }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    tasks: {
        type: Array,
        default: () => [],
    },
    limit: {
        type: Number,
        default: 5,
    },
});

defineEmits(['toggle', 'click']);

const displayTasks = computed(() => {
    const safeTasks = Array.isArray(props.tasks) ? props.tasks : [];
    return props.limit > 0 ? safeTasks.slice(0, props.limit) : safeTasks;
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};

const dueDateColor = (task) => {
    if (task.status === 'completed') return 'text-gray-400 dark:text-gray-500';
    if (!task.due_date) return 'text-gray-500 dark:text-gray-400';

    const dueDate = new Date(task.due_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (dueDate < today) return 'text-red-600 dark:text-red-400';
    if (dueDate.getTime() === today.getTime()) return 'text-orange-600 dark:text-orange-400';
    return 'text-gray-500 dark:text-gray-400';
};

const priorityColor = (priority) => {
    const colors = {
        high: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        medium: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        low: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
    };
    return colors[priority] || 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400';
};

const priorityLabel = (priority) => {
    const labels = {
        high: t('priority.high'),
        medium: t('priority.medium'),
        low: t('priority.low'),
    };
    return labels[priority] || priority;
};
</script>
