<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Biznes Salomatligi</h3>
            <span
                :class="[
                    'px-3 py-1 rounded-full text-sm font-medium',
                    labelClasses
                ]"
            >
                {{ label }}
            </span>
        </div>

        <div class="relative">
            <!-- Progress Circle -->
            <div class="flex items-center justify-center">
                <div class="relative w-32 h-32">
                    <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                        <!-- Background circle -->
                        <circle
                            cx="60"
                            cy="60"
                            r="54"
                            stroke="currentColor"
                            stroke-width="8"
                            fill="none"
                            class="text-gray-200 dark:text-gray-700"
                        />
                        <!-- Progress circle -->
                        <circle
                            cx="60"
                            cy="60"
                            r="54"
                            stroke="currentColor"
                            stroke-width="8"
                            fill="none"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="progressOffset"
                            stroke-linecap="round"
                            :class="progressColorClass"
                            style="transition: stroke-dashoffset 0.5s ease-in-out"
                        />
                    </svg>
                    <!-- Score text -->
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ score }}</span>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <p class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ description }}
            </p>

            <!-- Breakdown -->
            <div v-if="breakdown" class="mt-4 space-y-2">
                <div
                    v-for="item in breakdown"
                    :key="item.label"
                    class="flex items-center justify-between text-sm"
                >
                    <span class="text-gray-600 dark:text-gray-400">{{ item.label }}</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div
                                :class="getBarColorClass(item.value)"
                                :style="{ width: `${item.value}%` }"
                                class="h-full rounded-full transition-all duration-300"
                            />
                        </div>
                        <span class="text-gray-900 dark:text-white font-medium w-8 text-right">
                            {{ item.value }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface BreakdownItem {
    label: string;
    value: number;
}

const props = withDefaults(defineProps<{
    score: number;
    label: string;
    color: string;
    breakdown?: BreakdownItem[];
}>(), {
    score: 0,
    label: 'N/A',
    color: 'gray',
});

const circumference = 2 * Math.PI * 54; // 2πr where r=54

const progressOffset = computed(() => {
    const progress = props.score / 100;
    return circumference - (progress * circumference);
});

const progressColorClass = computed(() => {
    if (props.score >= 80) return 'text-green-500';
    if (props.score >= 60) return 'text-yellow-500';
    if (props.score >= 40) return 'text-orange-500';
    return 'text-red-500';
});

const labelClasses = computed(() => {
    const classes: Record<string, string> = {
        green: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        red: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        gray: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
    };
    return classes[props.color] || classes.gray;
});

const description = computed(() => {
    if (props.score >= 80) return 'Biznesingiz a\'lo holatda!';
    if (props.score >= 60) return 'Yaxshi, lekin yaxshilash uchun joy bor';
    if (props.score >= 40) return 'Diqqat talab qiladigan sohalar mavjud';
    return 'Tezkor choralar ko\'rish kerak';
});

function getBarColorClass(value: number): string {
    if (value >= 80) return 'bg-green-500';
    if (value >= 60) return 'bg-yellow-500';
    if (value >= 40) return 'bg-orange-500';
    return 'bg-red-500';
}
</script>
