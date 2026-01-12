<script setup>
import { CheckCircleIcon } from '@heroicons/vue/24/outline';

defineProps({
    tasks: { type: Array, default: () => [] },
    showAssignee: { type: Boolean, default: false },
    isOverdue: { type: Boolean, default: false },
    emptyText: { type: String, default: 'Bugun uchun vazifalar yo\'q' },
    emptyIcon: { type: String, default: 'default' }, // 'default' | 'success'
});

const getOverdueDays = (dueDate) => {
    if (!dueDate) return 0;
    const due = new Date(dueDate);
    const now = new Date();
    const diff = Math.floor((now - due) / (1000 * 60 * 60 * 24));
    return diff > 0 ? diff : 0;
};
</script>

<template>
    <div>
        <template v-if="tasks?.length">
            <!-- Overdue Tasks Style -->
            <template v-if="isOverdue">
                <div
                    v-for="task in tasks"
                    :key="task.id"
                    class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800 mb-3 last:mb-0"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ task.title }}</p>
                            <p v-if="showAssignee" class="text-xs text-gray-500 dark:text-gray-400">
                                {{ task.assignee?.name || 'Tayinlanmagan' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-medium text-red-600 dark:text-red-400">
                            {{ getOverdueDays(task.due_date) }} kun kechikdi
                        </p>
                    </div>
                </div>
            </template>

            <!-- Normal Tasks Style -->
            <template v-else>
                <div
                    v-for="task in tasks"
                    :key="task.id"
                    class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                    <div class="flex items-start gap-3">
                        <div :class="[
                            'w-5 h-5 rounded-full border-2 mt-0.5 flex-shrink-0 flex items-center justify-center',
                            task.completed || task.status === 'completed'
                                ? 'bg-green-500 border-green-500'
                                : 'border-gray-300 dark:border-gray-600'
                        ]">
                            <CheckCircleIcon
                                v-if="task.completed || task.status === 'completed'"
                                class="w-4 h-4 text-white"
                            />
                        </div>
                        <div class="flex-1">
                            <h4 :class="[
                                'font-medium',
                                task.completed || task.status === 'completed'
                                    ? 'text-gray-400 line-through'
                                    : 'text-gray-900 dark:text-white'
                            ]">
                                {{ task.title }}
                            </h4>
                            <p v-if="task.due_time" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ task.due_time }}
                            </p>
                            <p v-if="showAssignee && task.assignee" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ task.assignee.name }}
                            </p>
                        </div>
                    </div>
                </div>
            </template>
        </template>

        <!-- Empty State -->
        <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">
            <template v-if="emptyIcon === 'success'">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-green-600 dark:text-green-400">{{ emptyText }}</p>
            </template>
            <template v-else>
                {{ emptyText }}
            </template>
        </div>
    </div>
</template>
