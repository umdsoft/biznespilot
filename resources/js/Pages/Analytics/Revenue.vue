<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
    CurrencyDollarIcon,
    ChartBarIcon,
    ArrowTrendingUpIcon,
    ArrowDownTrayIcon,
    CalendarIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    trends: Object,
    forecast: Object,
    period: String,
});

const selectedPeriod = ref(props.period || 'daily');
const exportingPDF = ref(false);
const exportingExcel = ref(false);

// Format price
const formatPrice = (price) => {
    return new Intl.NumberFormat('uz-UZ').format(price) + ' so\'m';
};

// Format percentage
const formatPercent = (value) => {
    return value?.toFixed(1) || '0';
};

// Change period
const changePeriod = (period) => {
    selectedPeriod.value = period;
    window.location.href = route('business.analytics.revenue', { period });
};

// Export functions
const exportPDF = async () => {
    exportingPDF.value = true;
    try {
        const response = await fetch(route('business.analytics.export.pdf'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                report_type: 'revenue',
            }),
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'revenue_report.pdf';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            alert('PDF yaratishda xatolik');
        }
    } catch (error) {
        console.error('Export error:', error);
        alert('Export xatolik');
    } finally {
        exportingPDF.value = false;
    }
};

const exportExcel = async () => {
    exportingExcel.value = true;
    try {
        const response = await fetch(route('business.analytics.export.excel'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                report_type: 'revenue',
            }),
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'revenue_report.xlsx';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            alert('Excel yaratishda xatolik');
        }
    } catch (error) {
        console.error('Export error:', error);
        alert('Export xatolik');
    } finally {
        exportingExcel.value = false;
    }
};

// Chart data
const revenueChartData = computed(() => {
    if (!props.trends?.chart_data) return null;

    return {
        labels: props.trends.chart_data.labels,
        datasets: props.trends.chart_data.datasets.map(dataset => ({
            ...dataset,
            fill: true,
        })),
    };
});

const forecastChartData = computed(() => {
    if (!props.forecast?.forecast) return null;

    const labels = props.forecast.forecast.map(f => f.label);
    const forecastValues = props.forecast.forecast.map(f => f.forecast_revenue);
    const lowerBounds = props.forecast.forecast.map(f => f.lower_bound);
    const upperBounds = props.forecast.forecast.map(f => f.upper_bound);

    return {
        labels,
        datasets: [
            {
                label: 'Forecast',
                data: forecastValues,
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
            },
            {
                label: 'Upper Bound',
                data: upperBounds,
                borderColor: 'rgb(239, 68, 68)',
                borderDash: [5, 5],
                fill: false,
            },
            {
                label: 'Lower Bound',
                data: lowerBounds,
                borderColor: 'rgb(34, 197, 94)',
                borderDash: [5, 5],
                fill: false,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'top',
        },
    },
    scales: {
        y: {
            beginAtZero: true,
        },
    },
};
</script>

<template>
    <BusinessLayout title="Revenue Analytics">
        <Head title="Revenue Analytics" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <CurrencyDollarIcon class="w-10 h-10 text-green-600" />
                            Revenue Analytics
                        </h1>
                        <p class="mt-2 text-gray-600">
                            Revenue trends va forecasting
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <Link
                            :href="route('business.analytics.index')"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors gap-2"
                        >
                            <ChartBarIcon class="w-4 h-4" />
                            Dashboard
                        </Link>
                        <button
                            @click="exportPDF"
                            :disabled="exportingPDF"
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white font-medium rounded-lg transition-colors gap-2"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4" />
                            {{ exportingPDF ? 'Yuklanmoqda...' : 'PDF' }}
                        </button>
                        <button
                            @click="exportExcel"
                            :disabled="exportingExcel"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white font-medium rounded-lg transition-colors gap-2"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4" />
                            {{ exportingExcel ? 'Yuklanmoqda...' : 'Excel' }}
                        </button>
                    </div>
                </div>

                <!-- Period Selector -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                    <div class="flex items-center gap-2">
                        <CalendarIcon class="w-5 h-5 text-gray-500" />
                        <span class="text-sm font-medium text-gray-700 mr-4">Davr:</span>
                        <div class="flex gap-2">
                            <button
                                @click="changePeriod('daily')"
                                :class="[
                                    selectedPeriod === 'daily'
                                        ? 'bg-green-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                    'px-4 py-2 rounded-lg font-medium transition-colors'
                                ]"
                            >
                                Kunlik
                            </button>
                            <button
                                @click="changePeriod('weekly')"
                                :class="[
                                    selectedPeriod === 'weekly'
                                        ? 'bg-green-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                    'px-4 py-2 rounded-lg font-medium transition-colors'
                                ]"
                            >
                                Haftalik
                            </button>
                            <button
                                @click="changePeriod('monthly')"
                                :class="[
                                    selectedPeriod === 'monthly'
                                        ? 'bg-green-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                    'px-4 py-2 rounded-lg font-medium transition-colors'
                                ]"
                            >
                                Oylik
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Revenue Trends Chart -->
                <div class="bg-white rounded-xl shadow-md p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <ArrowTrendingUpIcon class="w-6 h-6 text-green-600" />
                        Revenue Trends
                    </h3>
                    <div class="h-80">
                        <canvas id="revenueTrendsChart"></canvas>
                    </div>
                </div>

                <!-- Forecast Section -->
                <div v-if="forecast && forecast.forecast" class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl shadow-md p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <SparklesIcon class="w-6 h-6 text-indigo-600" />
                        Revenue Forecast (Keyingi 30 kun)
                    </h3>

                    <!-- Forecast Metrics -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">O'rtacha Kunlik</p>
                            <p class="text-xl font-bold text-gray-900">{{ formatPrice(forecast.summary.avg_daily_revenue) }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">So'nggi O'rtacha</p>
                            <p class="text-xl font-bold text-gray-900">{{ formatPrice(forecast.summary.recent_avg) }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">O'sish Sur'ati</p>
                            <p class="text-xl font-bold text-green-600">{{ formatPercent(forecast.summary.growth_rate) }}%</p>
                        </div>
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">Prognoz Jami</p>
                            <p class="text-xl font-bold text-indigo-600">{{ formatPrice(forecast.summary.forecast_total) }}</p>
                        </div>
                    </div>

                    <!-- Forecast Chart -->
                    <div class="bg-white rounded-lg p-4">
                        <div class="h-80">
                            <canvas id="forecastChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Revenue Trends Table -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Revenue Details</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Sana</th>
                                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Revenue</th>
                                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Deals</th>
                                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Avg Deal Size</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(trend, index) in trends.trends"
                                    :key="trend.date"
                                    :class="[
                                        index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                                        'hover:bg-green-50 transition-colors'
                                    ]"
                                >
                                    <td class="py-3 px-4 font-medium text-gray-900">{{ trend.label }}</td>
                                    <td class="py-3 px-4 text-right font-semibold text-green-600">
                                        {{ formatPrice(trend.revenue) }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-700">{{ trend.deal_count }}</td>
                                    <td class="py-3 px-4 text-right text-gray-700">
                                        {{ formatPrice(trend.avg_deal_size) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<script>
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

export default {
    mounted() {
        this.$nextTick(() => {
            this.initializeCharts();
        });
    },
    methods: {
        initializeCharts() {
            // Revenue Trends Chart
            if (this.revenueChartData) {
                const trendsCtx = document.getElementById('revenueTrendsChart');
                if (trendsCtx) {
                    new Chart(trendsCtx, {
                        type: 'line',
                        data: this.revenueChartData,
                        options: this.chartOptions,
                    });
                }
            }

            // Forecast Chart
            if (this.forecastChartData) {
                const forecastCtx = document.getElementById('forecastChart');
                if (forecastCtx) {
                    new Chart(forecastCtx, {
                        type: 'line',
                        data: this.forecastChartData,
                        options: this.chartOptions,
                    });
                }
            }
        },
    },
};
</script>
