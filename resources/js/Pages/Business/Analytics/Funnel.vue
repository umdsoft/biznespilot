<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import {
    FunnelIcon,
    ArrowRightIcon,
    ArrowDownIcon,
    ChartBarIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    funnel_data: Object,
    conversion_rates: Object,
    filters: Object,
});

const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

// Format percentage
const formatPercent = (value) => {
    return value?.toFixed(1) || '0';
};

// Get stage color
const getStageColor = (stage) => {
    const colors = {
        new: 'bg-blue-500',
        contacted: 'bg-indigo-500',
        qualified: 'bg-purple-500',
        proposal: 'bg-pink-500',
        negotiation: 'bg-orange-500',
        won: 'bg-green-500',
        lost: 'bg-red-500',
    };
    return colors[stage] || 'bg-gray-500';
};

// Get stage icon color
const getStageIconColor = (stage) => {
    const colors = {
        new: 'text-blue-600',
        contacted: 'text-indigo-600',
        qualified: 'text-purple-600',
        proposal: 'text-pink-600',
        negotiation: 'text-orange-600',
        won: 'text-green-600',
        lost: 'text-red-600',
    };
    return colors[stage] || 'text-gray-600';
};

// Calculate funnel width percentage
const getFunnelWidth = (percentage) => {
    return Math.max(percentage, 10); // Minimum 10% for visibility
};

const applyFilters = () => {
    window.location.href = route('business.analytics.funnel', {
        date_from: dateFrom.value,
        date_to: dateTo.value,
    });
};

const clearFilters = () => {
    dateFrom.value = '';
    dateTo.value = '';
    window.location.href = route('business.analytics.funnel');
};
</script>

<template>
    <BusinessLayout title="Conversion Funnel">
        <Head title="Conversion Funnel" />

        <div class="py-12">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <FunnelIcon class="w-10 h-10 text-purple-600" />
                            Conversion Funnel
                        </h1>
                        <p class="mt-2 text-gray-600">
                            Har bir bosqichda konversiya tahlili
                        </p>
                    </div>

                    <Link
                        :href="route('business.analytics.index')"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors gap-2"
                    >
                        <ChartBarIcon class="w-4 h-4" />
                        Dashboard
                    </Link>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Boshlanish sanasi
                            </label>
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            />
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tugash sanasi
                            </label>
                            <input
                                v-model="dateTo"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            />
                        </div>
                        <button
                            @click="applyFilters"
                            class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors"
                        >
                            Qo'llash
                        </button>
                        <button
                            @click="clearFilters"
                            class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
                        >
                            Tozalash
                        </button>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <FunnelIcon class="w-6 h-6 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Leads</p>
                                <p class="text-2xl font-bold text-gray-900">{{ funnel_data.summary.total_leads || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <CheckCircleIcon class="w-6 h-6 text-green-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Won Deals</p>
                                <p class="text-2xl font-bold text-green-600">{{ funnel_data.summary.won_leads || 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <ChartBarIcon class="w-6 h-6 text-purple-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Conversion Rate</p>
                                <p class="text-2xl font-bold text-purple-600">{{ formatPercent(funnel_data.summary.overall_conversion_rate) }}%</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                <ClockIcon class="w-6 h-6 text-orange-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Win Rate</p>
                                <p class="text-2xl font-bold text-orange-600">{{ formatPercent(funnel_data.summary.win_rate) }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visual Funnel -->
                <div class="bg-white rounded-xl shadow-md p-8 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Funnel Visualization</h3>

                    <div class="space-y-4">
                        <div
                            v-for="(stage, index) in funnel_data.funnel_stages.filter(s => !['lost'].includes(s.stage))"
                            :key="stage.stage"
                            class="relative"
                        >
                            <!-- Stage Bar -->
                            <div class="flex items-center gap-4">
                                <!-- Stage Label -->
                                <div class="w-32 text-right">
                                    <p class="text-sm font-semibold text-gray-700">{{ stage.label }}</p>
                                </div>

                                <!-- Progress Bar -->
                                <div class="flex-1">
                                    <div class="relative h-16 bg-gray-100 rounded-lg overflow-hidden">
                                        <div
                                            :class="[getStageColor(stage.stage), 'h-full transition-all duration-500 flex items-center justify-between px-4']"
                                            :style="{ width: getFunnelWidth(stage.percentage) + '%' }"
                                        >
                                            <span class="text-white font-bold">{{ stage.count }}</span>
                                            <span class="text-white text-sm font-medium">{{ formatPercent(stage.percentage) }}%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Metrics -->
                                <div class="w-48">
                                    <div class="text-sm">
                                        <p class="text-gray-600">
                                            CR: <span class="font-semibold text-gray-900">{{ formatPercent(stage.conversion_rate) }}%</span>
                                        </p>
                                        <p v-if="stage.dropoff_rate > 0" class="text-red-600">
                                            Dropoff: <span class="font-semibold">{{ formatPercent(stage.dropoff_rate) }}%</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Connector Arrow -->
                            <div
                                v-if="index < funnel_data.funnel_stages.filter(s => !['lost', 'won'].includes(s.stage)).length - 1"
                                class="flex justify-center my-2"
                            >
                                <ArrowDownIcon class="w-6 h-6 text-gray-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stage Details -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Stage-by-Stage Analysis</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="stage in funnel_data.funnel_stages.filter(s => !['lost'].includes(s.stage))"
                            :key="stage.stage"
                            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-center gap-2 mb-3">
                                <div :class="[getStageColor(stage.stage), 'w-3 h-3 rounded-full']"></div>
                                <p class="font-bold text-gray-900">{{ stage.label }}</p>
                            </div>

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Leads:</span>
                                    <span class="font-semibold text-gray-900">{{ stage.count }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Percentage:</span>
                                    <span class="font-semibold text-gray-900">{{ formatPercent(stage.percentage) }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Conversion:</span>
                                    <span class="font-semibold text-green-600">{{ formatPercent(stage.conversion_rate) }}%</span>
                                </div>
                                <div v-if="stage.dropoff_rate > 0" class="flex justify-between">
                                    <span class="text-gray-600">Dropoff:</span>
                                    <span class="font-semibold text-red-600">{{ formatPercent(stage.dropoff_rate) }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conversion Rates Breakdown -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Conversion Insights</h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Overall Stats -->
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-4">Overall Performance</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-700">Total Leads</span>
                                    <span class="font-bold text-gray-900">{{ conversion_rates.overall.total_leads }}</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                    <span class="text-gray-700">Won Deals</span>
                                    <span class="font-bold text-green-600">{{ conversion_rates.overall.won_leads }}</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                                    <span class="text-gray-700">Conversion Rate</span>
                                    <span class="font-bold text-purple-600">{{ formatPercent(conversion_rates.overall.conversion_rate) }}%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Time to Close -->
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-4">Time to Close</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-700">Average Days</span>
                                    <span class="font-bold text-gray-900">{{ conversion_rates.avg_time_to_close.avg_days }} kun</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                                    <span class="text-gray-700">Fastest Close</span>
                                    <span class="font-bold text-green-600">{{ conversion_rates.avg_time_to_close.fastest_close }} kun</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-orange-50 rounded-lg">
                                    <span class="text-gray-700">Slowest Close</span>
                                    <span class="font-bold text-orange-600">{{ conversion_rates.avg_time_to_close.slowest_close }} kun</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
