<script setup>
defineProps({
    actions: {
        type: Array,
        required: true,
        // Each action: { href, icon, label, color }
    },
});

const getColorClasses = (color) => {
    const colors = {
        blue: 'bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        green: 'bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 text-green-600 dark:text-green-400',
        emerald: 'bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
        purple: 'bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 text-purple-600 dark:text-purple-400',
        orange: 'bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 text-orange-600 dark:text-orange-400',
        red: 'bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400',
        yellow: 'bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
        cyan: 'bg-cyan-50 dark:bg-cyan-900/20 hover:bg-cyan-100 dark:hover:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400',
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tezkor Harakatlar</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a
                v-for="action in actions"
                :key="action.href"
                :href="action.href"
                class="flex flex-col items-center p-4 rounded-xl transition-colors"
                :class="getColorClasses(action.color)"
            >
                <component :is="action.icon" class="w-8 h-8" />
                <span class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ action.label }}</span>
            </a>
        </div>
    </div>
</template>
