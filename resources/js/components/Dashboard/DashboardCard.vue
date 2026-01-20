<template>
    <div :class="[
        'rounded-2xl border overflow-hidden transition-all duration-300',
        'bg-white dark:bg-gray-800',
        'border-gray-200 dark:border-gray-700',
        'hover:shadow-lg'
    ]">
        <!-- Header -->
        <div v-if="title || $slots.header" :class="[
            'px-5 py-4 border-b flex items-center justify-between',
            'border-gray-100 dark:border-gray-700'
        ]">
            <slot name="header">
                <div class="flex items-center gap-3">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
                    <span
                        v-if="badge"
                        :class="[
                            'inline-flex items-center justify-center min-w-[22px] h-6 px-2 text-xs font-bold rounded-full',
                            badgeColorClass
                        ]"
                    >
                        {{ badge }}
                    </span>
                </div>
            </slot>
            <slot name="action">
                <Link
                    v-if="linkHref"
                    :href="linkHref"
                    class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 flex items-center gap-1 transition-colors"
                >
                    {{ linkText || t('dashboard.view_details') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </slot>
        </div>

        <!-- Body -->
        <div :class="bodyClass">
            <slot></slot>
        </div>

        <!-- Footer -->
        <div v-if="$slots.footer" :class="[
            'px-5 py-3 border-t',
            'bg-gray-50 dark:bg-gray-700/50',
            'border-gray-100 dark:border-gray-700'
        ]">
            <slot name="footer"></slot>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    padding: {
        type: Boolean,
        default: true,
    },
    badge: {
        type: [String, Number],
        default: null,
    },
    badgeColor: {
        type: String,
        default: 'blue', // blue, red, green, yellow, purple, gray
    },
    linkHref: {
        type: String,
        default: '',
    },
    linkText: {
        type: String,
        default: '',
    },
});

const bodyClass = computed(() => {
    return props.padding ? 'p-5' : '';
});

const badgeColorMap = {
    blue: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
    red: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    green: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
    yellow: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
    purple: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
    gray: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400',
};

const badgeColorClass = computed(() => badgeColorMap[props.badgeColor] || badgeColorMap.blue);
</script>
