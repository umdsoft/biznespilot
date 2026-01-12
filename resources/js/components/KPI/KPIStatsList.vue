<script setup>
defineProps({
    title: { type: String, required: true },
    items: {
        type: Array,
        default: () => [],
        // Each item: { label, value, icon?, iconColor?, dotColor? }
    },
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ title }}</h3>
        <div class="space-y-4">
            <div
                v-for="(item, index) in items"
                :key="index"
                :class="[
                    'flex items-center justify-between p-3 rounded-xl',
                    item.highlight ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-700/50'
                ]"
            >
                <div class="flex items-center gap-3">
                    <component
                        v-if="item.icon"
                        :is="item.icon"
                        :class="['w-5 h-5', item.iconColor || 'text-gray-600']"
                    />
                    <div
                        v-else-if="item.dotColor"
                        :class="['w-3 h-3 rounded-full', item.dotColor]"
                    ></div>
                    <span class="text-gray-700 dark:text-gray-300">{{ item.label }}</span>
                </div>
                <span class="font-bold text-gray-900 dark:text-white">{{ item.value }}</span>
            </div>
        </div>
    </div>
</template>
