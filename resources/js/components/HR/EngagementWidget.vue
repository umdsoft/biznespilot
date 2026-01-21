<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    HeartIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    MinusIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    avgScore: { type: Number, default: 0 },
    engagementStatus: { type: String, default: 'unknown' },
    trend: { type: Object, default: () => ({ direction: 'stable', change: 0 }) },
    distribution: {
        type: Object,
        default: () => ({
            highly_engaged: { count: 0 },
            engaged: { count: 0 },
            neutral: { count: 0 },
            disengaged: { count: 0 },
        }),
    },
    businessId: { type: String, default: '' },
});

const statusColor = computed(() => {
    const colors = {
        excellent: 'from-emerald-500 to-green-600',
        good: 'from-blue-500 to-cyan-600',
        average: 'from-yellow-500 to-orange-600',
        needs_attention: 'from-red-500 to-pink-600',
        unknown: 'from-gray-500 to-gray-600',
    };
    return colors[props.engagementStatus] || colors.unknown;
});

const statusLabel = computed(() => {
    const labels = {
        excellent: "A'lo darajada",
        good: 'Yaxshi',
        average: "O'rtacha",
        needs_attention: "E'tibor kerak",
        unknown: "Ma'lumot yo'q",
    };
    return labels[props.engagementStatus] || labels.unknown;
});

const trendIcon = computed(() => {
    if (props.trend.direction === 'up') return ArrowTrendingUpIcon;
    if (props.trend.direction === 'down') return ArrowTrendingDownIcon;
    return MinusIcon;
});

const trendColor = computed(() => {
    if (props.trend.direction === 'up') return 'text-green-500';
    if (props.trend.direction === 'down') return 'text-red-500';
    return 'text-gray-500';
});

const totalEmployees = computed(() => {
    return (props.distribution.highly_engaged?.count || 0) +
           (props.distribution.engaged?.count || 0) +
           (props.distribution.neutral?.count || 0) +
           (props.distribution.disengaged?.count || 0);
});
</script>

<template>
    <div :class="['rounded-2xl p-6 text-white shadow-xl bg-gradient-to-br', statusColor]">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <HeartIcon class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm opacity-90">Hodimlar Ishga Qiziqishi</p>
                    <p class="text-xl font-bold">{{ statusLabel }}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-4xl font-bold">{{ avgScore.toFixed(1) }}</div>
                <div :class="['text-sm flex items-center justify-end gap-1', trendColor]">
                    <component :is="trendIcon" class="w-4 h-4" />
                    <span>{{ trend.change > 0 ? '+' : '' }}{{ trend.change.toFixed(1) }}%</span>
                </div>
            </div>
        </div>

        <!-- Distribution bars -->
        <div class="space-y-3">
            <div class="flex items-center gap-3">
                <span class="text-xs w-24 opacity-90">Juda qiziqgan</span>
                <div class="flex-1 bg-white/20 rounded-full h-2">
                    <div
                        class="bg-white rounded-full h-2 transition-all duration-500"
                        :style="{ width: totalEmployees > 0 ? `${(distribution.highly_engaged?.count || 0) / totalEmployees * 100}%` : '0%' }"
                    ></div>
                </div>
                <span class="text-xs w-8 text-right">{{ distribution.highly_engaged?.count || 0 }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs w-24 opacity-90">Qiziqgan</span>
                <div class="flex-1 bg-white/20 rounded-full h-2">
                    <div
                        class="bg-white rounded-full h-2 transition-all duration-500"
                        :style="{ width: totalEmployees > 0 ? `${(distribution.engaged?.count || 0) / totalEmployees * 100}%` : '0%' }"
                    ></div>
                </div>
                <span class="text-xs w-8 text-right">{{ distribution.engaged?.count || 0 }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs w-24 opacity-90">Neytral</span>
                <div class="flex-1 bg-white/20 rounded-full h-2">
                    <div
                        class="bg-white rounded-full h-2 transition-all duration-500"
                        :style="{ width: totalEmployees > 0 ? `${(distribution.neutral?.count || 0) / totalEmployees * 100}%` : '0%' }"
                    ></div>
                </div>
                <span class="text-xs w-8 text-right">{{ distribution.neutral?.count || 0 }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs w-24 opacity-90">Qiziqmagan</span>
                <div class="flex-1 bg-white/20 rounded-full h-2">
                    <div
                        class="bg-white rounded-full h-2 transition-all duration-500"
                        :style="{ width: totalEmployees > 0 ? `${(distribution.disengaged?.count || 0) / totalEmployees * 100}%` : '0%' }"
                    ></div>
                </div>
                <span class="text-xs w-8 text-right">{{ distribution.disengaged?.count || 0 }}</span>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-white/20 text-center">
            <Link
                :href="`/hr/engagement`"
                class="text-sm opacity-90 hover:opacity-100 transition-opacity underline"
            >
                Batafsil ko'rish
            </Link>
        </div>
    </div>
</template>
