<template>
    <div class="space-y-2">
        <div v-if="tasks.length === 0" class="text-center py-8 text-gray-500">
            Vazifalar yo'q
        </div>
        <div
            v-for="task in tasks"
            :key="task.id"
            class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
        >
            <button
                @click="$emit('toggle', task)"
                :class="[
                    'w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-colors',
                    task.status === 'completed'
                        ? 'bg-green-500 border-green-500 text-white'
                        : 'border-gray-300 hover:border-green-500'
                ]"
            >
                <svg v-if="task.status === 'completed'" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </button>
            <div class="flex-1 min-w-0">
                <p
                    :class="[
                        'text-sm',
                        task.status === 'completed' ? 'text-gray-400 line-through' : 'text-gray-900'
                    ]"
                >
                    {{ task.title }}
                </p>
                <div class="flex items-center gap-2 mt-1">
                    <span v-if="task.due_date" :class="dueDateColor(task)" class="text-xs">
                        {{ formatDate(task.due_date) }}
                    </span>
                    <span v-if="task.priority" :class="priorityColor(task.priority)" class="px-1.5 py-0.5 rounded text-xs font-medium">
                        {{ priorityLabel(task.priority) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    tasks: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['toggle', 'click']);

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};

const dueDateColor = (task) => {
    if (task.status === 'completed') return 'text-gray-400';
    if (!task.due_date) return 'text-gray-500';

    const dueDate = new Date(task.due_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (dueDate < today) return 'text-red-600';
    if (dueDate.getTime() === today.getTime()) return 'text-orange-600';
    return 'text-gray-500';
};

const priorityColor = (priority) => {
    const colors = {
        high: 'bg-red-100 text-red-700',
        medium: 'bg-yellow-100 text-yellow-700',
        low: 'bg-gray-100 text-gray-600',
    };
    return colors[priority] || 'bg-gray-100 text-gray-600';
};

const priorityLabel = (priority) => {
    const labels = {
        high: 'Yuqori',
        medium: 'O\'rta',
        low: 'Past',
    };
    return labels[priority] || priority;
};
</script>
