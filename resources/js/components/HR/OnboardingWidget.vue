<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    UserPlusIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    activePlans: { type: Number, default: 0 },
    completedPlans: { type: Number, default: 0 },
    overdueTasksCount: { type: Number, default: 0 },
    recentOnboardings: { type: Array, default: () => [] },
    milestoneScores: {
        type: Object,
        default: () => ({
            day_30: 0,
            day_60: 0,
            day_90: 0,
        }),
    },
    businessId: { type: String, default: '' },
});

const getProgressColor = (progress) => {
    if (progress >= 80) return 'bg-green-500';
    if (progress >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getPhaseLabel = (phase) => {
    const labels = {
        day_30: '30 kun',
        day_60: '60 kun',
        day_90: '90 kun',
    };
    return labels[phase] || phase;
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="p-6 bg-gradient-to-br from-blue-500 to-cyan-600 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <UserPlusIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Onboarding</p>
                        <p class="text-xl font-bold">Yangi hodimlar</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-bold">{{ activePlans }}</div>
                    <div class="text-sm opacity-90">faol reja</div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="p-6">
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                    <CheckCircleIcon class="w-6 h-6 mx-auto text-green-600 dark:text-green-400 mb-1" />
                    <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ completedPlans }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Yakunlangan</div>
                </div>
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                    <ClockIcon class="w-6 h-6 mx-auto text-blue-600 dark:text-blue-400 mb-1" />
                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ activePlans }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Jarayonda</div>
                </div>
                <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                    <ExclamationCircleIcon class="w-6 h-6 mx-auto text-red-600 dark:text-red-400 mb-1" />
                    <div class="text-xl font-bold text-red-600 dark:text-red-400">{{ overdueTasksCount }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Kechikkan</div>
                </div>
            </div>

            <!-- Milestone avg scores -->
            <div class="mb-6">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    O'rtacha milestone ballari
                </h4>
                <div class="space-y-2">
                    <div v-for="(score, phase) in milestoneScores" :key="phase" class="flex items-center gap-3">
                        <span class="text-xs w-16 text-gray-600 dark:text-gray-400">{{ getPhaseLabel(phase) }}</span>
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div
                                :class="['h-2 rounded-full transition-all duration-500', getProgressColor(score)]"
                                :style="{ width: `${score}%` }"
                            ></div>
                        </div>
                        <span class="text-xs w-10 text-right font-medium text-gray-700 dark:text-gray-300">
                            {{ score }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Recent onboardings -->
            <div v-if="recentOnboardings.length > 0" class="space-y-3">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    So'nggi onboardinglar
                </h4>
                <div class="space-y-2">
                    <div
                        v-for="plan in recentOnboardings.slice(0, 3)"
                        :key="plan.id"
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                <span class="text-xs font-medium text-blue-600 dark:text-blue-400">
                                    {{ plan.user?.name?.charAt(0) || '?' }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ plan.user?.name || 'Noma\'lum' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ plan.current_phase }} • {{ plan.days_elapsed }} kun
                                </p>
                            </div>
                        </div>
                        <div class="w-16">
                            <div class="bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div
                                    :class="['h-2 rounded-full', getProgressColor(plan.progress)]"
                                    :style="{ width: `${plan.progress}%` }"
                                ></div>
                            </div>
                            <p class="text-xs text-center mt-1 text-gray-500 dark:text-gray-400">
                                {{ plan.progress }}%
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 text-center">
                <Link
                    href="/hr/onboarding"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
                >
                    Batafsil ko'rish
                </Link>
            </div>
        </div>
    </div>
</template>
