<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    subtitle: { type: String, default: null },
    badge: { type: String, default: null },
    badgeColor: { type: String, default: 'blue' },
    icon: { type: [Object, Function], default: null },
    iconBgColor: { type: String, default: 'blue' },
    valueColor: { type: String, default: 'default' },
    href: { type: String, default: null },
    linkText: { type: String, default: null },
});

const iconBgClasses = computed(() => {
    const colors = {
        blue: 'bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30',
        green: 'bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30',
        emerald: 'bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30',
        purple: 'bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30',
        orange: 'bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30',
        red: 'bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30',
        yellow: 'bg-gradient-to-br from-yellow-100 to-amber-100 dark:from-yellow-900/30 dark:to-amber-900/30',
        cyan: 'bg-gradient-to-br from-cyan-100 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/30',
    };
    return colors[props.iconBgColor] || colors.blue;
});

const iconColorClasses = computed(() => {
    const colors = {
        blue: 'text-blue-600 dark:text-blue-400',
        green: 'text-green-600 dark:text-green-400',
        emerald: 'text-emerald-600 dark:text-emerald-400',
        purple: 'text-purple-600 dark:text-purple-400',
        orange: 'text-orange-600 dark:text-orange-400',
        red: 'text-red-600 dark:text-red-400',
        yellow: 'text-yellow-600 dark:text-yellow-400',
        cyan: 'text-cyan-600 dark:text-cyan-400',
    };
    return colors[props.iconBgColor] || colors.blue;
});

const badgeClasses = computed(() => {
    const colors = {
        blue: 'text-blue-600 dark:text-blue-400',
        green: 'text-green-600 dark:text-green-400',
        emerald: 'text-emerald-600 dark:text-emerald-400',
        purple: 'text-purple-600 dark:text-purple-400',
        orange: 'text-orange-600 dark:text-orange-400',
        red: 'text-red-600 dark:text-red-400',
        yellow: 'text-yellow-600 dark:text-yellow-400',
    };
    return colors[props.badgeColor] || colors.blue;
});

const valueClasses = computed(() => {
    const colors = {
        default: 'text-gray-900 dark:text-white',
        blue: 'text-blue-600 dark:text-blue-400',
        green: 'text-green-600 dark:text-green-400',
        emerald: 'text-emerald-600 dark:text-emerald-400',
        purple: 'text-purple-600 dark:text-purple-400',
        orange: 'text-orange-600 dark:text-orange-400',
        red: 'text-red-600 dark:text-red-400',
    };
    return colors[props.valueColor] || colors.default;
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-lg transition-shadow">
        <div class="flex items-center justify-between">
            <div v-if="icon" class="w-12 h-12 rounded-xl flex items-center justify-center" :class="iconBgClasses">
                <component :is="icon" class="w-6 h-6" :class="iconColorClasses" />
            </div>
            <span v-if="badge" class="text-sm font-medium" :class="badgeClasses">{{ badge }}</span>
        </div>
        <div class="mt-4">
            <p class="text-3xl font-bold" :class="valueClasses">{{ value }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ title }}</p>
            <p v-if="subtitle" class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ subtitle }}</p>
            <a
                v-if="href && linkText"
                :href="href"
                class="text-xs text-emerald-600 dark:text-emerald-400 mt-2 inline-flex items-center hover:underline"
            >
                {{ linkText }}
                <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</template>
