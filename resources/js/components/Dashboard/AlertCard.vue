<template>
    <div
        class="bg-white rounded-lg border p-4 hover:shadow-md transition-shadow cursor-pointer"
        :class="borderColor"
        @click="$emit('click', alert)"
    >
        <div class="flex items-start gap-3">
            <div
                :class="iconBgColor"
                class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
            >
                <svg v-if="alert.type === 'warning'" class="w-5 h-5" :class="iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <svg v-else-if="alert.type === 'error'" class="w-5 h-5" :class="iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else-if="alert.type === 'success'" class="w-5 h-5" :class="iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="w-5 h-5" :class="iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 truncate">{{ alert.title }}</h3>
                    <span v-if="alert.is_read" class="text-xs text-gray-400">O'qilgan</span>
                </div>
                <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ alert.message }}</p>
                <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                    <span v-if="alert.created_at">{{ formatTime(alert.created_at) }}</span>
                    <span v-if="alert.category" class="px-2 py-0.5 bg-gray-100 rounded">{{ alert.category }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    alert: {
        type: Object,
        required: true,
    },
});

defineEmits(['click']);

const borderColor = computed(() => {
    const type = props.alert.type;
    const colors = {
        warning: 'border-yellow-200',
        error: 'border-red-200',
        success: 'border-green-200',
        info: 'border-blue-200',
    };
    return colors[type] || 'border-gray-200';
});

const iconBgColor = computed(() => {
    const type = props.alert.type;
    const colors = {
        warning: 'bg-yellow-100',
        error: 'bg-red-100',
        success: 'bg-green-100',
        info: 'bg-blue-100',
    };
    return colors[type] || 'bg-gray-100';
});

const iconColor = computed(() => {
    const type = props.alert.type;
    const colors = {
        warning: 'text-yellow-600',
        error: 'text-red-600',
        success: 'text-green-600',
        info: 'text-blue-600',
    };
    return colors[type] || 'text-gray-600';
});

const formatTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Hozir';
    if (diffMins < 60) return `${diffMins} daqiqa oldin`;
    if (diffHours < 24) return `${diffHours} soat oldin`;
    if (diffDays < 7) return `${diffDays} kun oldin`;
    return d.toLocaleDateString('uz-UZ');
};
</script>
