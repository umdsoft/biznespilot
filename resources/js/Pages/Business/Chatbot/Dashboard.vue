<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { computed } from 'vue';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    BarElement
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    BarElement
);

const props = defineProps({
    config: Object,
    stats: Object,
});

// Chart data
const dailyChartData = computed(() => ({
    labels: props.stats.daily_chart.map(d => d.date),
    datasets: [
        {
            label: 'Suhbatlar',
            data: props.stats.daily_chart.map(d => d.conversations),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
        },
        {
            label: 'Xabarlar',
            data: props.stats.daily_chart.map(d => d.messages),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
        },
        {
            label: 'Lidlar',
            data: props.stats.daily_chart.map(d => d.leads),
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            tension: 0.4,
        },
    ],
}));

const channelChartData = computed(() => ({
    labels: ['Telegram', 'Instagram', 'Facebook'],
    datasets: [{
        data: [
            props.stats.channels.telegram,
            props.stats.channels.instagram,
            props.stats.channels.facebook,
        ],
        backgroundColor: ['#0088cc', '#E4405F', '#1877F2'],
    }],
}));

const funnelChartData = computed(() => {
    const stages = ['AWARENESS', 'INTEREST', 'CONSIDERATION', 'INTENT', 'PURCHASE', 'POST_PURCHASE'];
    const labels = ['Xabardorlik', 'Qiziqish', 'Mulohaza', 'Niyat', 'Xarid', 'Xariddan keyin'];

    return {
        labels: labels,
        datasets: [{
            label: 'Suhbatlar',
            data: stages.map(stage => props.stats.funnel[stage] || 0),
            backgroundColor: [
                '#3b82f6',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6',
                '#ec4899',
            ],
        }],
    };
});

const intentChartData = computed(() => {
    if (!props.stats.intents || Object.keys(props.stats.intents).length === 0) {
        return null;
    }

    return {
        labels: Object.keys(props.stats.intents),
        datasets: [{
            data: Object.values(props.stats.intents),
            backgroundColor: [
                '#3b82f6',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6',
                '#ec4899',
                '#06b6d4',
                '#84cc16',
            ],
        }],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
};
</script>

<template>
    <Head title="Chatbot Dashboard" />

    <BusinessLayout>
        <div class="py-12">
            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Chatbot Analytics</h1>
                    <p class="mt-2 text-gray-600">Mijozlar bilan suhbatlarning to'liq tahlili</p>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Active Conversations -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Faol suhbatlar
                                    </dt>
                                    <dd class="text-2xl font-semibold text-gray-900">
                                        {{ stats.active_conversations }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Conversations -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Bugun
                                    </dt>
                                    <dd class="text-2xl font-semibold text-gray-900">
                                        {{ stats.today.conversations }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- This Month -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Bu oy
                                    </dt>
                                    <dd class="text-2xl font-semibold text-gray-900">
                                        {{ stats.month.conversations }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Conversion Rate -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00 2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">
                                        Konversiya
                                    </dt>
                                    <dd class="text-2xl font-semibold text-gray-900">
                                        {{ stats.month.conversion_rate }}%
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Daily Activity Chart -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">30 kunlik faollik</h3>
                        <div style="height: 300px;">
                            <Line :data="dailyChartData" :options="chartOptions" />
                        </div>
                    </div>

                    <!-- Funnel Chart -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Sotuv voronkasi</h3>
                        <div style="height: 300px;">
                            <Bar :data="funnelChartData" :options="chartOptions" />
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Channel Distribution -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Kanallar bo'yicha</h3>
                        <div style="height: 300px;">
                            <Doughnut :data="channelChartData" :options="chartOptions" />
                        </div>
                    </div>

                    <!-- Intent Distribution -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Intent tahlili</h3>
                        <div v-if="intentChartData" style="height: 300px;">
                            <Doughnut :data="intentChartData" :options="chartOptions" />
                        </div>
                        <div v-else class="flex items-center justify-center h-64 text-gray-500">
                            Ma'lumot yo'q
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
