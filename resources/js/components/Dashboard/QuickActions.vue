<template>
    <div class="grid grid-cols-2 gap-3">
        <button
            v-for="action in actions"
            :key="action.id"
            @click="$emit('action', action)"
            class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors text-left"
        >
            <div :class="action.iconBg || 'bg-indigo-100'" class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg :class="action.iconColor || 'text-indigo-600'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path v-if="action.icon === 'plus'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    <path v-else-if="action.icon === 'user-plus'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    <path v-else-if="action.icon === 'document'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    <path v-else-if="action.icon === 'calendar'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    <path v-else-if="action.icon === 'chart'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900">{{ action.label }}</p>
                <p v-if="action.description" class="text-xs text-gray-500">{{ action.description }}</p>
            </div>
        </button>
    </div>
</template>

<script setup>
defineProps({
    actions: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['action']);
</script>
