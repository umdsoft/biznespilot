<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ExclamationTriangleIcon,
    UserMinusIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    distribution: {
        type: Object,
        default: () => ({
            critical: { count: 0, label: 'Juda yuqori xavf', color: 'red' },
            high: { count: 0, label: 'Yuqori xavf', color: 'orange' },
            medium: { count: 0, label: "O'rtacha xavf", color: 'yellow' },
            low: { count: 0, label: 'Past xavf', color: 'green' },
        }),
    },
    topRisks: { type: Array, default: () => [] },
    avgRiskScore: { type: Number, default: 0 },
    businessId: { type: String, default: '' },
});

const totalEmployees = computed(() => {
    return (props.distribution.critical?.count || 0) +
           (props.distribution.high?.count || 0) +
           (props.distribution.medium?.count || 0) +
           (props.distribution.low?.count || 0);
});

const highRiskCount = computed(() => {
    return (props.distribution.critical?.count || 0) + (props.distribution.high?.count || 0);
});

const riskStatus = computed(() => {
    const percentage = totalEmployees.value > 0 ? (highRiskCount.value / totalEmployees.value) * 100 : 0;
    if (percentage >= 15) return { label: 'Kritik holat', color: 'from-red-500 to-pink-600' };
    if (percentage >= 10) return { label: 'Ogohlantirish', color: 'from-orange-500 to-red-600' };
    if (percentage >= 5) return { label: 'Kuzatuvda', color: 'from-yellow-500 to-orange-600' };
    return { label: 'Normal', color: 'from-emerald-500 to-green-600' };
});

const getRiskLevelColor = (level) => {
    const colors = {
        critical: 'bg-red-500',
        high: 'bg-orange-500',
        medium: 'bg-yellow-500',
        low: 'bg-green-500',
    };
    return colors[level] || 'bg-gray-500';
};

const getRiskBadgeColor = (level) => {
    const colors = {
        critical: 'bg-red-100 text-red-800',
        high: 'bg-orange-100 text-orange-800',
        medium: 'bg-yellow-100 text-yellow-800',
        low: 'bg-green-100 text-green-800',
    };
    return colors[level] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div :class="['p-6 text-white bg-gradient-to-br', riskStatus.color]">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <ExclamationTriangleIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Ketish xavfi</p>
                        <p class="text-xl font-bold">{{ riskStatus.label }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-bold">{{ highRiskCount }}</div>
                    <div class="text-sm opacity-90">yuqori xavfli</div>
                </div>
            </div>
        </div>

        <!-- Distribution -->
        <div class="p-6">
            <div class="grid grid-cols-4 gap-2 mb-6">
                <div
                    v-for="(data, level) in distribution"
                    :key="level"
                    class="text-center"
                >
                    <div :class="['w-full h-2 rounded-full mb-2', getRiskLevelColor(level)]"></div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ data.count }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ data.label }}</div>
                </div>
            </div>

            <!-- Top Risk Employees -->
            <div v-if="topRisks.length > 0" class="space-y-3">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                    <UserMinusIcon class="w-4 h-4" />
                    Eng yuqori xavfli hodimlar
                </h4>
                <div class="space-y-2">
                    <div
                        v-for="risk in topRisks.slice(0, 3)"
                        :key="risk.user?.id"
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                    {{ risk.user?.name?.charAt(0) || '?' }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ risk.user?.name || 'Noma\'lum' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ risk.top_factors?.[0] || 'Ma\'lumot yo\'q' }}
                                </p>
                            </div>
                        </div>
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getRiskBadgeColor(risk.risk_level)]">
                            {{ risk.risk_score }}%
                        </span>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400">
                <p class="text-sm">Yuqori xavfli hodimlar yo'q</p>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-center">
                <Link
                    href="/hr/flight-risk"
                    class="text-sm text-purple-600 dark:text-purple-400 hover:underline"
                >
                    Batafsil ko'rish
                </Link>
            </div>
        </div>
    </div>
</template>
