<script setup>
import { ref, computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    funnelData: {
        type: Array,
        default: () => [],
    },
});

// Filter out 'lost' for funnel visualization (show separately)
const funnelStages = computed(() => {
    return props.funnelData.filter(s => s.key !== 'lost');
});

const lostStage = computed(() => {
    return props.funnelData.find(s => s.key === 'lost');
});

// Calculate totals
const totalLeads = computed(() => {
    return props.funnelData.reduce((sum, s) => sum + (s.count || 0), 0);
});

const wonCount = computed(() => {
    const won = props.funnelData.find(s => s.key === 'won');
    return won?.count || 0;
});

const overallConversion = computed(() => {
    const totalExclLost = totalLeads.value - (lostStage.value?.count || 0);
    return totalExclLost > 0 ? Math.round((wonCount.value / totalExclLost) * 100) : 0;
});

// Funnel chart options - Real pyramid shape
const chartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 400,
        toolbar: { show: false },
        background: 'transparent',
    },
    plotOptions: {
        bar: {
            borderRadius: 0,
            horizontal: true,
            barHeight: '80%',
            isFunnel: true, // Enable funnel/pyramid mode
        },
    },
    colors: funnelStages.value.map(s => s.color),
    dataLabels: {
        enabled: true,
        formatter: function(val, opt) {
            const stage = funnelStages.value[opt.dataPointIndex];
            return `${stage?.label}: ${stage?.count || 0} ta`;
        },
        style: {
            colors: ['#fff'],
            fontSize: '13px',
            fontWeight: 600,
        },
        dropShadow: {
            enabled: true,
            top: 1,
            left: 1,
            blur: 1,
            opacity: 0.45
        },
    },
    xaxis: {
        categories: funnelStages.value.map(s => s.label),
        labels: { show: false },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        labels: { show: false },
    },
    tooltip: {
        enabled: true,
        theme: 'dark',
        y: {
            formatter: function(val, { dataPointIndex }) {
                const stage = funnelStages.value[dataPointIndex];
                if (!stage) return val + ' ta';
                return `${stage.count} ta lid (${stage.percentage}%)`;
            },
            title: {
                formatter: function(seriesName, { dataPointIndex }) {
                    const stage = funnelStages.value[dataPointIndex];
                    return stage?.label || seriesName;
                }
            }
        },
    },
    grid: {
        show: false,
        padding: {
            left: 0,
            right: 0,
        },
    },
    legend: {
        show: false,
    },
    states: {
        hover: {
            filter: {
                type: 'darken',
                value: 0.15,
            }
        },
    },
}));

const chartSeries = computed(() => [{
    name: 'Lidlar',
    data: funnelStages.value.map(s => s.count),
}]);

const formatCurrency = (value) => {
    if (!value) return '0 so\'m';
    return new Intl.NumberFormat('uz-UZ').format(value) + ' so\'m';
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Sotuv Voronkasi
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Lidlar qaysi bosqichda ekanini ko'ring
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalLeads }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Jami lidlar</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ overallConversion }}%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Konversiya</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Funnel Chart -->
        <div class="p-6">
            <div v-if="funnelStages.length > 0" class="flex justify-center">
                <div class="w-full max-w-lg">
                    <VueApexCharts
                        type="bar"
                        height="400"
                        :options="chartOptions"
                        :series="chartSeries"
                    />
                </div>
            </div>
            <div v-else class="flex items-center justify-center py-12 text-gray-400 dark:text-gray-500">
                <p>Ma'lumot yo'q</p>
            </div>

            <!-- Lost Stats (separate) -->
            <div v-if="lostStage && lostStage.count > 0" class="mt-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">Yo'qotilgan lidlar</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ formatCurrency(lostStage.value) }} qiymat yo'qotildi
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ lostStage.count }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ lostStage.percentage }}% barcha lidlardan</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stage Details Legend -->
        <div class="px-6 pb-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div
                    v-for="stage in funnelStages"
                    :key="stage.key"
                    class="p-3 rounded-xl border transition-all hover:shadow-md cursor-default"
                    :style="{ borderColor: stage.color + '40', backgroundColor: stage.color + '10' }"
                >
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: stage.color }"></div>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ stage.label }}</span>
                    </div>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stage.count }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ stage.percentage }}%</p>
                </div>
            </div>
        </div>
    </div>
</template>
