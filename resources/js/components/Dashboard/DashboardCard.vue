<script setup>
defineProps({
    title: { type: String, required: true },
    linkHref: { type: String, default: null },
    linkText: { type: String, default: 'Barchasini ko\'rish' },
    badge: { type: [String, Number], default: null },
    badgeColor: { type: String, default: 'red' },
    noPadding: { type: Boolean, default: false },
    divided: { type: Boolean, default: false },
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                {{ title }}
                <span
                    v-if="badge"
                    class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full"
                    :class="{
                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': badgeColor === 'red',
                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': badgeColor === 'blue',
                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': badgeColor === 'green',
                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': badgeColor === 'yellow',
                    }"
                >
                    {{ badge }}
                </span>
            </h3>
            <a v-if="linkHref" :href="linkHref" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                {{ linkText }}
            </a>
        </div>
        <div :class="[noPadding ? '' : 'p-6', divided ? 'divide-y divide-gray-100 dark:divide-gray-700' : '']">
            <slot />
        </div>
    </div>
</template>
