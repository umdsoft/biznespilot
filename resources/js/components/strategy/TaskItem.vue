<template>
    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
        <button
            @click="$emit('toggle', task)"
            :class="[
                'w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-colors',
                task.completed
                    ? 'bg-green-500 border-green-500 text-white'
                    : 'border-gray-300 hover:border-green-500'
            ]"
        >
            <svg v-if="task.completed" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
        </button>
        <div class="flex-1 min-w-0">
            <p :class="['text-sm', task.completed ? 'text-gray-400 line-through' : 'text-gray-900']">
                {{ task.title }}
            </p>
            <div v-if="task.due_date" class="text-xs text-gray-500 mt-0.5">
                {{ formatDate(task.due_date) }}
            </div>
        </div>
        <span v-if="task.priority" :class="priorityClass" class="px-2 py-0.5 rounded text-xs font-medium">
            {{ priorityLabel }}
        </span>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
});

defineEmits(['toggle']);

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};

const priorityClass = computed(() => {
    const priority = props.task.priority;
    const classes = {
        high: 'bg-red-100 text-red-700',
        medium: 'bg-yellow-100 text-yellow-700',
        low: 'bg-gray-100 text-gray-600',
    };
    return classes[priority] || 'bg-gray-100 text-gray-600';
});

const priorityLabel = computed(() => {
    const priority = props.task.priority;
    const labels = {
        high: t('strategy.priority.high'),
        medium: t('strategy.priority.medium'),
        low: t('strategy.priority.low'),
    };
    return labels[priority] || priority;
});
</script>
