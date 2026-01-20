<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        :class="[
            'relative overflow-hidden rounded-2xl border transition-all duration-300',
            'bg-white dark:bg-gray-800',
            'border-gray-200 dark:border-gray-700',
            href ? 'hover:shadow-lg hover:scale-[1.02] cursor-pointer' : 'hover:shadow-md',
            'group'
        ]"
    >
        <!-- Background Gradient Decoration -->
        <div :class="[
            'absolute top-0 right-0 w-32 h-32 -mr-8 -mt-8 rounded-full opacity-10 transition-opacity',
            bgGradientClass,
            'group-hover:opacity-20'
        ]"></div>

        <div class="relative p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ title }}</p>
                    <p :class="[
                        'text-2xl font-bold mt-1.5 transition-colors',
                        valueColorClass
                    ]">
                        {{ formattedValue }}
                    </p>

                    <!-- Subtitle or Change -->
                    <div class="mt-2">
                        <div v-if="change !== undefined" class="flex items-center">
                            <span
                                :class="[
                                    'inline-flex items-center text-sm font-medium px-2 py-0.5 rounded-full',
                                    change >= 0
                                        ? 'text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30'
                                        : 'text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900/30'
                                ]"
                            >
                                <svg v-if="change >= 0" class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                <svg v-else class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                                {{ Math.abs(change) }}%
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">{{ changeLabel || t('dashboard.stat.vs_last_month') }}</span>
                        </div>
                        <p v-else-if="subtitle" class="text-sm text-gray-500 dark:text-gray-400">
                            {{ subtitle }}
                        </p>
                    </div>
                </div>

                <!-- Icon -->
                <div :class="[
                    'w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 transition-transform group-hover:scale-110',
                    iconBgClass
                ]">
                    <component :is="icon" v-if="icon" :class="['w-6 h-6', iconTextClass]" />
                    <slot v-else name="icon">
                        <svg :class="['w-6 h-6', iconTextClass]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </slot>
                </div>
            </div>

            <!-- Link Text -->
            <div v-if="linkText && href" class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                <span class="text-sm font-medium text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300 flex items-center">
                    {{ linkText }}
                    <svg class="w-4 h-4 ml-1 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </div>
        </div>
    </component>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [Number, String],
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    change: {
        type: Number,
        default: undefined,
    },
    changeLabel: {
        type: String,
        default: '',
    },
    icon: {
        type: [Object, Function],
        default: null,
    },
    iconBgColor: {
        type: String,
        default: 'gray', // emerald, green, blue, purple, orange, red, gray
    },
    valueColor: {
        type: String,
        default: 'default', // emerald, green, blue, purple, orange, red, default
    },
    format: {
        type: String,
        default: 'number', // number, currency, percent
    },
    href: {
        type: String,
        default: '',
    },
    linkText: {
        type: String,
        default: '',
    },
});

const formattedValue = computed(() => {
    if (typeof props.value === 'string') return props.value;

    if (props.format === 'currency') {
        return new Intl.NumberFormat('uz-UZ').format(props.value) + ` ${t('common.currency')}`;
    }
    if (props.format === 'percent') {
        return props.value + '%';
    }
    return new Intl.NumberFormat('uz-UZ').format(props.value);
});

const colorMap = {
    emerald: {
        bg: 'bg-emerald-100 dark:bg-emerald-900/30',
        text: 'text-emerald-600 dark:text-emerald-400',
        value: 'text-emerald-600 dark:text-emerald-400',
        gradient: 'bg-emerald-500',
    },
    green: {
        bg: 'bg-green-100 dark:bg-green-900/30',
        text: 'text-green-600 dark:text-green-400',
        value: 'text-green-600 dark:text-green-400',
        gradient: 'bg-green-500',
    },
    blue: {
        bg: 'bg-blue-100 dark:bg-blue-900/30',
        text: 'text-blue-600 dark:text-blue-400',
        value: 'text-blue-600 dark:text-blue-400',
        gradient: 'bg-blue-500',
    },
    purple: {
        bg: 'bg-purple-100 dark:bg-purple-900/30',
        text: 'text-purple-600 dark:text-purple-400',
        value: 'text-purple-600 dark:text-purple-400',
        gradient: 'bg-purple-500',
    },
    orange: {
        bg: 'bg-orange-100 dark:bg-orange-900/30',
        text: 'text-orange-600 dark:text-orange-400',
        value: 'text-orange-600 dark:text-orange-400',
        gradient: 'bg-orange-500',
    },
    red: {
        bg: 'bg-red-100 dark:bg-red-900/30',
        text: 'text-red-600 dark:text-red-400',
        value: 'text-red-600 dark:text-red-400',
        gradient: 'bg-red-500',
    },
    gray: {
        bg: 'bg-gray-100 dark:bg-gray-700',
        text: 'text-gray-600 dark:text-gray-400',
        value: 'text-gray-900 dark:text-white',
        gradient: 'bg-gray-500',
    },
};

const iconBgClass = computed(() => colorMap[props.iconBgColor]?.bg || colorMap.gray.bg);
const iconTextClass = computed(() => colorMap[props.iconBgColor]?.text || colorMap.gray.text);
const bgGradientClass = computed(() => colorMap[props.iconBgColor]?.gradient || colorMap.gray.gradient);
const valueColorClass = computed(() => {
    if (props.valueColor === 'default') {
        return 'text-gray-900 dark:text-white';
    }
    return colorMap[props.valueColor]?.value || 'text-gray-900 dark:text-white';
});
</script>
