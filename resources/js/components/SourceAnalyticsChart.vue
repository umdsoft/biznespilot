<script setup>
import { ref, computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import {
    ChartBarIcon,
    ChartPieIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sourceData: {
        type: Array,
        default: () => [],
    },
});

const chartType = ref('bar'); // 'bar' or 'pie'

// Colors for sources
const colors = [
    '#3B82F6', '#8B5CF6', '#EC4899', '#F97316', '#22C55E',
    '#06B6D4', '#EAB308', '#EF4444', '#6366F1', '#14B8A6',
];

// Bar chart options
const barChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 350,
        toolbar: { show: false },
        background: 'transparent',
    },
    plotOptions: {
        bar: {
            borderRadius: 6,
            horizontal: false,
            columnWidth: '60%',
            distributed: true,
        },
    },
    colors: colors.slice(0, props.sourceData.length),
    dataLabels: {
        enabled: false,
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent'],
    },
    xaxis: {
        categories: props.sourceData.map(s => s.name),
        labels: {
            style: {
                colors: '#9CA3AF',
                fontSize: '11px',
            },
            rotate: -45,
            rotateAlways: props.sourceData.length > 5,
        },
    },
    yaxis: {
        title: {
            text: 'Lidlar soni',
            style: {
                color: '#9CA3AF',
            },
        },
        labels: {
            style: {
                colors: '#9CA3AF',
            },
        },
    },
    fill: {
        opacity: 1,
    },
    tooltip: {
        theme: 'dark',
        y: {
            formatter: (val) => val + ' ta lid',
        },
    },
    grid: {
        borderColor: '#374151',
        strokeDashArray: 3,
    },
    legend: {
        show: false,
    },
}));

const barChartSeries = computed(() => [{
    name: 'Lidlar',
    data: props.sourceData.map(s => s.total),
}]);

// Pie chart options
const pieChartOptions = computed(() => ({
    chart: {
        type: 'donut',
        background: 'transparent',
    },
    colors: colors.slice(0, props.sourceData.length),
    labels: props.sourceData.map(s => s.name),
    stroke: {
        width: 2,
        colors: ['#1F2937'],
    },
    dataLabels: {
        enabled: true,
        style: {
            fontSize: '12px',
            fontWeight: 600,
        },
        formatter: (val) => val.toFixed(1) + '%',
    },
    legend: {
        show: true,
        position: 'bottom',
        labels: {
            colors: '#9CA3AF',
        },
    },
    tooltip: {
        theme: 'dark',
        y: {
            formatter: (val) => val + ' ta lid',
        },
    },
    plotOptions: {
        pie: {
            donut: {
                size: '65%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Jami',
                        color: '#9CA3AF',
                        formatter: (w) => {
                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + ' ta';
                        },
                    },
                },
            },
        },
    },
}));

const pieChartSeries = computed(() => props.sourceData.map(s => s.total));

const formatCurrency = (value) => {
    if (!value) return '0';
    return new Intl.NumberFormat('uz-UZ').format(value);
};

const totalLeads = computed(() => props.sourceData.reduce((sum, s) => sum + s.total, 0));
const totalWon = computed(() => props.sourceData.reduce((sum, s) => sum + s.won, 0));
const totalValue = computed(() => props.sourceData.reduce((sum, s) => sum + s.won_value, 0));
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Reklama Kanallari
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Qaysi kanaldan qancha lid kelgan
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="chartType = 'bar'"
                        :class="[
                            'p-2 rounded-lg transition-colors',
                            chartType === 'bar'
                                ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                                : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        <ChartBarIcon class="w-5 h-5" />
                    </button>
                    <button
                        @click="chartType = 'pie'"
                        :class="[
                            'p-2 rounded-lg transition-colors',
                            chartType === 'pie'
                                ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                                : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        <ChartPieIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-3 border-b border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-r border-gray-200 dark:border-gray-700">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalLeads }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Jami lidlar</p>
            </div>
            <div class="px-4 py-3 border-r border-gray-200 dark:border-gray-700">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ totalWon }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Yutilgan</p>
            </div>
            <div class="px-4 py-3">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatCurrency(totalValue) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Daromad</p>
            </div>
        </div>

        <!-- Chart -->
        <div class="p-6">
            <div v-if="sourceData.length > 0">
                <VueApexCharts
                    v-if="chartType === 'bar'"
                    type="bar"
                    height="320"
                    :options="barChartOptions"
                    :series="barChartSeries"
                />
                <VueApexCharts
                    v-else
                    type="donut"
                    height="350"
                    :options="pieChartOptions"
                    :series="pieChartSeries"
                />
            </div>
            <div v-else class="flex items-center justify-center py-12 text-gray-400 dark:text-gray-500">
                <p>Ma'lumot yo'q</p>
            </div>
        </div>

        <!-- Source Table -->
        <div class="px-6 pb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <th class="pb-2 font-medium">Kanal</th>
                        <th class="pb-2 font-medium text-right">Lidlar</th>
                        <th class="pb-2 font-medium text-right">Yutilgan</th>
                        <th class="pb-2 font-medium text-right">Konversiya</th>
                        <th class="pb-2 font-medium text-right">Daromad</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(source, index) in sourceData"
                        :key="source.source_id || index"
                        class="border-b border-gray-100 dark:border-gray-700/50"
                    >
                        <td class="py-2.5">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-3 h-3 rounded-full"
                                    :style="{ backgroundColor: colors[index % colors.length] }"
                                ></div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ source.name }}</span>
                            </div>
                        </td>
                        <td class="py-2.5 text-right text-gray-600 dark:text-gray-400">{{ source.total }}</td>
                        <td class="py-2.5 text-right text-green-600 dark:text-green-400">{{ source.won }}</td>
                        <td class="py-2.5 text-right">
                            <span :class="[
                                'px-2 py-0.5 rounded-full text-xs font-medium',
                                source.conversion_rate >= 20
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : source.conversion_rate >= 10
                                        ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                            ]">
                                {{ source.conversion_rate }}%
                            </span>
                        </td>
                        <td class="py-2.5 text-right font-medium text-gray-900 dark:text-white">
                            {{ formatCurrency(source.won_value) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
