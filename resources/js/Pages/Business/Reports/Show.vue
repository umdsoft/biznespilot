<template>
    <BusinessLayout :title="report.title">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <button @click="goBack" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ report.title }}</h2>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ report.period_label }} | Yaratildi: {{ report.created_at }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a
                        v-if="report.has_pdf"
                        :href="report.pdf_url"
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        PDF
                    </a>
                    <button @click="deleteReport" class="text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Health Score Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex flex-col md:flex-row md:items-center gap-6">
                    <div
                        class="w-24 h-24 rounded-2xl flex flex-col items-center justify-center"
                        :class="getHealthScoreClass(report.health_score)"
                    >
                        <div class="text-3xl font-bold">{{ report.health_score }}</div>
                        <div class="text-xs opacity-80">/ 100</div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Salomatlik balli: {{ report.health_score_label }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Bu ko'rsatkich biznesingizning umumiy holatini aks ettiradi
                        </p>

                        <!-- Breakdown -->
                        <div v-if="report.health_breakdown" class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-3">
                            <div
                                v-for="item in report.health_breakdown"
                                :key="item.category"
                                class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                            >
                                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ item.score }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.label }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Asosiy ko'rsatkichlar</h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <MetricCard
                        v-if="report.metrics_data?.sales"
                        label="Sotuvlar"
                        :value="report.metrics_data.sales.total_sales"
                        suffix=" ta"
                        icon="shopping-cart"
                        color="blue"
                    />
                    <MetricCard
                        v-if="report.metrics_data?.sales"
                        label="Daromad"
                        :value="formatCurrency(report.metrics_data.sales.total_revenue)"
                        icon="currency"
                        color="green"
                    />
                    <MetricCard
                        v-if="report.metrics_data?.marketing"
                        label="Lidlar"
                        :value="report.metrics_data.marketing.total_leads"
                        suffix=" ta"
                        icon="users"
                        color="purple"
                    />
                    <MetricCard
                        v-if="report.metrics_data?.marketing"
                        label="Konversiya"
                        :value="report.metrics_data.marketing.conversion_rate"
                        suffix="%"
                        icon="chart"
                        color="yellow"
                    />
                    <MetricCard
                        v-if="report.metrics_data?.financial"
                        label="ROI"
                        :value="report.metrics_data.financial.roi"
                        suffix="%"
                        icon="trending-up"
                        color="green"
                    />
                    <MetricCard
                        v-if="report.metrics_data?.financial"
                        label="CAC"
                        :value="formatCurrency(report.metrics_data.financial.cac)"
                        icon="tag"
                        color="red"
                    />
                    <MetricCard
                        v-if="report.metrics_data?.financial"
                        label="CLV"
                        :value="formatCurrency(report.metrics_data.financial.clv)"
                        icon="heart"
                        color="pink"
                    />
                    <MetricCard
                        v-if="report.metrics_data?.financial"
                        label="LTV/CAC"
                        :value="report.metrics_data.financial.ltv_cac_ratio"
                        suffix="x"
                        icon="scale"
                        color="indigo"
                    />
                </div>
            </div>

            <!-- Insights -->
            <div v-if="report.insights?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Tushunchalar</h3>

                <div class="space-y-3">
                    <div
                        v-for="(insight, index) in report.insights"
                        :key="index"
                        class="flex items-start space-x-3 p-3 rounded-lg"
                        :class="getInsightClass(insight.type)"
                    >
                        <span class="text-lg">{{ insight.icon }}</span>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ insight.message }}</p>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ insight.category }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recommendations -->
            <div v-if="report.recommendations?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Tavsiyalar</h3>

                <div class="space-y-4">
                    <div
                        v-for="(rec, index) in report.recommendations"
                        :key="index"
                        class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800"
                    >
                        <div class="flex items-start space-x-3">
                            <span class="text-lg">{{ rec.icon }}</span>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ rec.title }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ rec.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comparisons -->
            <div v-if="report.comparisons?.previous_period" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Oldingi davr bilan taqqoslash
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div
                        v-for="(metric, key) in report.comparisons.previous_period.metrics"
                        :key="key"
                        class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                    >
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ metric.label }}</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                            {{ typeof metric.current === 'number' && metric.current > 1000 ? formatNumber(metric.current) : metric.current }}
                        </div>
                        <div class="flex items-center mt-2">
                            <span
                                :class="metric.direction === 'up' ? 'text-green-500' : metric.direction === 'down' ? 'text-red-500' : 'text-gray-500'"
                                class="text-sm font-medium"
                            >
                                {{ metric.direction === 'up' ? '+' : '' }}{{ metric.change_percent }}%
                            </span>
                            <svg
                                v-if="metric.direction !== 'stable'"
                                class="w-4 h-4 ml-1"
                                :class="metric.direction === 'up' ? 'text-green-500' : 'text-red-500'"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="metric.direction === 'up' ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7V3'"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Anomalies -->
            <div v-if="report.anomalies?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Anomaliyalar
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ report.anomalies.length }})</span>
                </h3>

                <div class="space-y-3">
                    <div
                        v-for="(anomaly, index) in report.anomalies"
                        :key="index"
                        class="flex items-start justify-between p-3 rounded-lg"
                        :class="anomaly.severity === 'high' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20'"
                    >
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ anomaly.message }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ anomaly.date }}</p>
                        </div>
                        <span
                            class="text-xs font-medium px-2 py-1 rounded"
                            :class="anomaly.type === 'spike' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                        >
                            {{ anomaly.type === 'spike' ? 'O\'sish' : 'Pasayish' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Raw Text Content (for Telegram) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Telegram uchun matn</h3>
                    <button
                        @click="copyText"
                        class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                    >
                        Nusxalash
                    </button>
                </div>
                <pre class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg overflow-x-auto">{{ report.content_text }}</pre>
            </div>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

const props = defineProps({
    report: Object,
});

// Simple MetricCard component inline
const MetricCard = {
    props: ['label', 'value', 'suffix', 'icon', 'color'],
    template: `
        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ label }}</div>
            <div class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                {{ value }}{{ suffix || '' }}
            </div>
        </div>
    `,
};

const formatCurrency = (value) => {
    if (!value) return '0 UZS';
    return new Intl.NumberFormat('uz-UZ').format(Math.round(value)) + ' UZS';
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('uz-UZ').format(Math.round(value));
};

const getHealthScoreClass = (score) => {
    if (!score) return 'bg-gray-200 dark:bg-gray-700 text-gray-600';
    if (score >= 80) return 'bg-green-500 text-white';
    if (score >= 60) return 'bg-blue-500 text-white';
    if (score >= 40) return 'bg-yellow-500 text-white';
    return 'bg-red-500 text-white';
};

const getInsightClass = (type) => {
    return {
        'bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500': type === 'positive',
        'bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500': type === 'negative',
        'bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500': type === 'warning',
        'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500': type === 'neutral',
    };
};

const goBack = () => {
    router.visit(route('business.reports.algorithmic'));
};

const deleteReport = async () => {
    if (!confirm('Hisobotni o\'chirishni xohlaysizmi?')) return;

    try {
        await axios.delete(route('business.reports.delete', props.report.id));
        router.visit(route('business.reports.algorithmic'));
    } catch (error) {
        console.error('Delete failed:', error);
        alert('O\'chirishda xatolik');
    }
};

const copyText = () => {
    navigator.clipboard.writeText(props.report.content_text);
    alert('Matn nusxalandi!');
};
</script>
