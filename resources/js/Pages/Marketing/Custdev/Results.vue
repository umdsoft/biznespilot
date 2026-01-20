<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();
import { Doughnut, Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    ArcElement,
    CategoryScale,
    LinearScale,
    BarElement,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';

// Register Chart.js components
ChartJS.register(
    ArcElement,
    CategoryScale,
    LinearScale,
    BarElement,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    survey: Object,
    responses: Array,
    analytics: Object,
});

const activeTab = ref('overview');

const completedResponses = computed(() => {
    return props.responses?.filter(r => r.status === 'completed') || [];
});

const completionRate = computed(() => {
    if (!props.responses?.length) return 0;
    return Math.round((completedResponses.value.length / props.responses.length) * 100);
});

const avgTimeSpent = computed(() => {
    const completed = completedResponses.value;
    if (!completed.length) return 0;
    const totalSeconds = completed.reduce((sum, r) => sum + (r.time_spent || 0), 0);
    return Math.round(totalSeconds / completed.length);
});

// Device Distribution Chart Data
const deviceChartData = computed(() => {
    if (!props.responses?.length) return null;

    const deviceCounts = {
        mobile: props.responses.filter(r => r.device_type === 'mobile').length,
        tablet: props.responses.filter(r => r.device_type === 'tablet').length,
        desktop: props.responses.filter(r => r.device_type === 'desktop').length,
    };

    return {
        labels: ['Mobil', 'Planshet', 'Kompyuter'],
        datasets: [{
            data: [deviceCounts.mobile, deviceCounts.tablet, deviceCounts.desktop],
            backgroundColor: ['#10b981', '#3b82f6', '#8b5cf6'],
            borderWidth: 0,
            hoverOffset: 8,
        }]
    };
});

const deviceChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                padding: 20,
                usePointStyle: true,
                pointStyle: 'circle',
                color: '#9ca3af',
            }
        },
        tooltip: {
            backgroundColor: '#1f2937',
            titleColor: '#f9fafb',
            bodyColor: '#d1d5db',
            borderColor: '#374151',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12,
        }
    },
    cutout: '65%',
};

// Region Distribution Chart Data
const regionChartData = computed(() => {
    if (!props.responses?.length) return null;

    const regionCounts = {};
    props.responses.forEach(r => {
        const region = r.respondent_region || 'Noma\'lum';
        regionCounts[region] = (regionCounts[region] || 0) + 1;
    });

    // Sort by count and take top 7
    const sorted = Object.entries(regionCounts)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 7);

    return {
        labels: sorted.map(([region]) => region.replace(' viloyati', '').replace(' shahri', '')),
        datasets: [{
            label: 'Javoblar',
            data: sorted.map(([, count]) => count),
            backgroundColor: 'rgba(16, 185, 129, 0.8)',
            borderRadius: 8,
            borderSkipped: false,
        }]
    };
});

const regionChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#1f2937',
            titleColor: '#f9fafb',
            bodyColor: '#d1d5db',
            borderColor: '#374151',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12,
        }
    },
    scales: {
        x: {
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            ticks: {
                color: '#9ca3af',
            }
        },
        y: {
            grid: {
                display: false,
            },
            ticks: {
                color: '#9ca3af',
            }
        }
    }
};

// Response Timeline Chart Data
const timelineChartData = computed(() => {
    if (!props.responses?.length) return null;

    // Group responses by date
    const dateCounts = {};
    props.responses.forEach(r => {
        const date = new Date(r.created_at).toLocaleDateString('uz-UZ', { month: 'short', day: 'numeric' });
        dateCounts[date] = (dateCounts[date] || 0) + 1;
    });

    // Get last 7 days
    const dates = Object.keys(dateCounts).slice(-7);

    return {
        labels: dates,
        datasets: [{
            label: 'Javoblar',
            data: dates.map(d => dateCounts[d]),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
        }]
    };
});

const timelineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#1f2937',
            titleColor: '#f9fafb',
            bodyColor: '#d1d5db',
            borderColor: '#374151',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12,
        }
    },
    scales: {
        x: {
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            ticks: {
                color: '#9ca3af',
            }
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            ticks: {
                color: '#9ca3af',
                stepSize: 1,
            }
        }
    }
};

// Completion Funnel Data
const funnelData = computed(() => {
    if (!props.responses?.length) return null;

    const total = props.responses.length;
    const completed = completedResponses.value.length;
    const inProgress = props.responses.filter(r => r.status === 'in_progress').length;

    return {
        started: total,
        inProgress: inProgress,
        completed: completed,
        completionRate: completionRate.value,
    };
});

const formatTime = (seconds) => {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    if (minutes > 0) {
        return `${minutes} daqiqa ${secs} soniya`;
    }
    return `${secs} soniya`;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('uz-UZ', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getDeviceIcon = (type) => {
    const icons = {
        mobile: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
        tablet: 'M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
        desktop: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    };
    return icons[type] || icons.desktop;
};

const getCategoryColor = (category) => {
    const colors = {
        where_spend_time: 'from-blue-500 to-cyan-500',
        info_sources: 'from-purple-500 to-pink-500',
        frustrations: 'from-red-500 to-rose-500',
        dreams: 'from-green-500 to-emerald-500',
        fears: 'from-amber-500 to-orange-500',
        satisfaction: 'from-indigo-500 to-violet-500',
        custom: 'from-gray-500 to-slate-500',
    };
    return colors[category] || colors.custom;
};

const getAnswerForQuestion = (response, questionId) => {
    return response.answers?.find(a => a.question_id === questionId);
};

// Chart colors
const chartColors = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444', '#ec4899', '#06b6d4', '#84cc16'];

// Question type helpers
const getTypeLabel = (type) => {
    const labels = {
        text: 'Matn',
        textarea: 'Uzun matn',
        select: 'Tanlash',
        multiselect: 'Ko\'p tanlash',
        rating: 'Reyting',
        scale: 'Shkala',
    };
    return labels[type] || type;
};

const getTypeColor = (type) => {
    const colors = {
        text: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        textarea: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300',
        select: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
        multiselect: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
        rating: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
        scale: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
    };
    return colors[type] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
};

const getResponseRateColor = (rate) => {
    if (rate >= 80) return 'text-emerald-600 dark:text-emerald-400';
    if (rate >= 50) return 'text-amber-600 dark:text-amber-400';
    return 'text-red-600 dark:text-red-400';
};

// Question statistics helpers
const getQuestionResponseCount = (questionId) => {
    return completedResponses.value.filter(r => {
        const answer = getAnswerForQuestion(r, questionId);
        return answer && (answer.answer || answer.selected_options?.length || answer.rating_value);
    }).length;
};

const getQuestionResponseRate = (questionId) => {
    if (!completedResponses.value.length) return 0;
    return Math.round((getQuestionResponseCount(questionId) / completedResponses.value.length) * 100);
};

const getTextAnswers = (questionId) => {
    return completedResponses.value.filter(r => {
        const answer = getAnswerForQuestion(r, questionId);
        return answer?.answer && answer.answer.trim().length > 0;
    });
};

// Select/Multiselect helpers
const getOptionCount = (questionId, option) => {
    return completedResponses.value.filter(r => {
        const answer = getAnswerForQuestion(r, questionId);
        if (answer?.selected_options) {
            return answer.selected_options.includes(option);
        }
        return answer?.answer === option;
    }).length;
};

const getOptionPercentage = (questionId, option) => {
    if (!completedResponses.value.length) return 0;
    return Math.round((getOptionCount(questionId, option) / completedResponses.value.length) * 100);
};

const getSelectChartData = (question) => {
    if (!question.options?.length) return null;

    // Sort options by count (descending)
    const sortedOptions = question.options
        .map((option, index) => ({
            name: option,
            count: getOptionCount(question.id, option),
            originalIndex: index
        }))
        .sort((a, b) => b.count - a.count);

    return {
        labels: sortedOptions.map(o => o.name.length > 20 ? o.name.substring(0, 20) + '...' : o.name),
        datasets: [{
            label: 'Javoblar',
            data: sortedOptions.map(o => o.count),
            backgroundColor: sortedOptions.map((_, i) => chartColors[i % chartColors.length]),
            borderRadius: 8,
            borderSkipped: false,
        }]
    };
};

const compactBarOptions = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#1f2937',
            titleColor: '#f9fafb',
            bodyColor: '#d1d5db',
            borderColor: '#374151',
            borderWidth: 1,
            cornerRadius: 6,
            padding: 8,
            callbacks: {
                label: (context) => `${context.raw} kishi`
            }
        }
    },
    scales: {
        x: {
            beginAtZero: true,
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
            ticks: {
                color: '#9ca3af',
                stepSize: 1,
                font: { size: 10 }
            }
        },
        y: {
            grid: {
                display: false,
            },
            ticks: {
                color: '#9ca3af',
                font: { size: 10 },
                callback: function(value) {
                    const label = this.getLabelForValue(value);
                    return label.length > 12 ? label.substring(0, 12) + '...' : label;
                }
            }
        }
    }
};

// Get top options sorted by count
const getTopOptions = (question) => {
    if (!question.options?.length) return [];

    return question.options
        .map(option => ({
            name: option,
            count: getOptionCount(question.id, option),
            percentage: getOptionPercentage(question.id, option)
        }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 5);
};

// Rating helpers
const getAverageRating = (questionId) => {
    const answers = completedResponses.value
        .map(r => getAnswerForQuestion(r, questionId)?.rating_value)
        .filter(v => v !== null && v !== undefined);

    if (!answers.length) return '0.0';
    return (answers.reduce((sum, v) => sum + v, 0) / answers.length).toFixed(1);
};

const getRatingCount = (questionId, rating) => {
    return completedResponses.value.filter(r => {
        const answer = getAnswerForQuestion(r, questionId);
        return answer?.rating_value === rating;
    }).length;
};

const getRatingDistribution = (questionId, rating) => {
    const total = completedResponses.value.filter(r => {
        const answer = getAnswerForQuestion(r, questionId);
        return answer?.rating_value !== null && answer?.rating_value !== undefined;
    }).length;

    if (!total) return 0;
    return Math.round((getRatingCount(questionId, rating) / total) * 100);
};

const exportResults = () => {
    window.location.href = route('marketing.custdev.export', { custdev: props.survey.id });
};

const syncToDreamBuyer = () => {
    if (confirm('Javoblarni Ideal Mijoz profiliga sinxronlashni xohlaysizmi?')) {
        router.post(route('marketing.custdev.sync-dream-buyer', { custdev: props.survey.id }));
    }
};

const copyLink = () => {
    const link = `${window.location.origin}/s/${props.survey.slug}`;
    navigator.clipboard.writeText(link);
};
</script>

<template>
    <MarketingLayout :title="t('custdev.survey_results')">
        <Head :title="`${t('custdev.results')} - ${survey.title}`" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('marketing.custdev.index')"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
                    >
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ survey.title }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ survey.description || 'So\'rovnoma natijalari' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="copyLink"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Link
                    </button>
                    <button
                        @click="exportResults"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export
                    </button>
                    <button
                        v-if="survey.dream_buyer_id"
                        @click="syncToDreamBuyer"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/25 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Ideal Mijozga Sinxronlash
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami javoblar</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ responses?.length || 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tugallangan</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ completedResponses.length }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tugallash %</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ completionRate }}%</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha vaqt</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatTime(avgTimeSpent) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px">
                        <button
                            @click="activeTab = 'overview'"
                            :class="activeTab === 'overview'
                                ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                            class="px-6 py-4 border-b-2 font-medium text-sm transition-colors"
                        >
                            Umumiy Ko'rinish
                        </button>
                        <button
                            @click="activeTab = 'questions'"
                            :class="activeTab === 'questions'
                                ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                            class="px-6 py-4 border-b-2 font-medium text-sm transition-colors"
                        >
                            Savollar bo'yicha
                        </button>
                        <button
                            @click="activeTab = 'responses'"
                            :class="activeTab === 'responses'
                                ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                            class="px-6 py-4 border-b-2 font-medium text-sm transition-colors"
                        >
                            Barcha Javoblar
                        </button>
                    </nav>
                </div>

                <!-- Overview Tab with Charts -->
                <div v-if="activeTab === 'overview'" class="p-6">
                    <div v-if="!responses?.length" class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Hali javoblar yo'q</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">So'rovnoma linkini ulashing va javoblar to'plang</p>
                        <button
                            @click="copyLink"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium rounded-xl hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Linkni nusxalash
                        </button>
                    </div>

                    <div v-else class="space-y-6">
                        <!-- Charts Row 1 -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Device Distribution -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Qurilmalar bo'yicha</h3>
                                <div class="h-64">
                                    <Doughnut
                                        v-if="deviceChartData"
                                        :data="deviceChartData"
                                        :options="deviceChartOptions"
                                    />
                                </div>
                            </div>

                            <!-- Region Distribution -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6">
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Hududlar bo'yicha</h3>
                                <div class="h-64">
                                    <Bar
                                        v-if="regionChartData"
                                        :data="regionChartData"
                                        :options="regionChartOptions"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">Javoblar dinamikasi</h3>
                            <div class="h-64">
                                <Line
                                    v-if="timelineChartData"
                                    :data="timelineChartData"
                                    :options="timelineChartOptions"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions Tab -->
                <div v-if="activeTab === 'questions'" class="p-6">
                    <div v-if="!survey.questions?.length" class="text-center py-12 text-gray-500 dark:text-gray-400">
                        Savollar mavjud emas
                    </div>

                    <div v-else class="space-y-8">
                        <div
                            v-for="(question, index) in survey.questions"
                            :key="question.id"
                            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                        >
                            <!-- Question Header -->
                            <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                                <div class="flex items-start gap-4">
                                    <div :class="`bg-gradient-to-br ${getCategoryColor(question.category)} w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg`">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ question.question }}</h4>
                                        <div class="flex items-center gap-4 mt-2">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ getQuestionResponseCount(question.id) }} javob
                                            </span>
                                            <span :class="getTypeColor(question.type)" class="px-2 py-1 rounded-full text-xs font-medium">
                                                {{ getTypeLabel(question.type) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold" :class="getResponseRateColor(getQuestionResponseRate(question.id))">
                                            {{ getQuestionResponseRate(question.id) }}%
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">javob berdi</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Question Content -->
                            <div class="p-6">
                                <!-- Text answers -->
                                <div v-if="question.type === 'text' || question.type === 'textarea'">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div
                                            v-for="(response, rIndex) in getTextAnswers(question.id).slice(0, 6)"
                                            :key="response.id"
                                            class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl"
                                        >
                                            <p class="text-gray-700 dark:text-gray-300 text-sm">
                                                "{{ getAnswerForQuestion(response, question.id)?.answer || '-' }}"
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                {{ response.respondent_name || 'Anonim' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div v-if="!getTextAnswers(question.id).length" class="text-center py-8 text-gray-400">
                                        Hali javoblar yo'q
                                    </div>
                                </div>

                                <!-- Select/Multiselect answers -->
                                <div v-else-if="question.type === 'select' || question.type === 'multiselect'" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                                    <!-- Chart -->
                                    <div class="lg:col-span-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                                        <div class="h-48">
                                            <Bar
                                                v-if="getSelectChartData(question)"
                                                :data="getSelectChartData(question)"
                                                :options="compactBarOptions"
                                            />
                                        </div>
                                    </div>
                                    <!-- Top Results -->
                                    <div class="lg:col-span-2 space-y-2">
                                        <h5 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">Top natijalar</h5>
                                        <div
                                            v-for="(option, oIndex) in getTopOptions(question)"
                                            :key="option.name"
                                            class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-900/50 rounded-lg"
                                        >
                                            <div class="w-6 h-6 rounded-md flex items-center justify-center text-white text-xs font-bold" :style="{ backgroundColor: chartColors[oIndex % chartColors.length] }">
                                                {{ oIndex + 1 }}
                                            </div>
                                            <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate">{{ option.name }}</span>
                                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ option.count }} kishi</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rating/Scale answers -->
                                <div v-else-if="question.type === 'rating' || question.type === 'scale'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                    <!-- Main Score -->
                                    <div class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-2xl">
                                        <div class="text-5xl font-bold text-emerald-600 dark:text-emerald-400 mb-2">
                                            {{ getAverageRating(question.id) }}
                                        </div>
                                        <div class="flex items-center gap-1 mb-2">
                                            <svg v-for="star in 5" :key="star" class="w-5 h-5" :class="star <= Math.round(getAverageRating(question.id)) ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600'" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ question.type === 'rating' ? '5 balldan' : '10 balldan' }}
                                        </p>
                                    </div>

                                    <!-- Distribution -->
                                    <div class="lg:col-span-2">
                                        <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Taqsimot</h5>
                                        <div class="space-y-2">
                                            <div v-for="rating in (question.type === 'rating' ? [5,4,3,2,1] : [10,9,8,7,6,5,4,3,2,1])" :key="rating" class="flex items-center gap-3">
                                                <span class="w-6 text-sm font-medium text-gray-600 dark:text-gray-400 text-right">{{ rating }}</span>
                                                <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                                                    <div
                                                        class="h-full bg-gradient-to-r from-amber-400 to-amber-500 rounded-lg transition-all duration-500"
                                                        :style="{ width: `${getRatingDistribution(question.id, rating)}%` }"
                                                    ></div>
                                                </div>
                                                <span class="w-10 text-xs text-gray-500 dark:text-gray-400">{{ getRatingDistribution(question.id, rating) }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Responses Tab -->
                <div v-if="activeTab === 'responses'" class="p-6">
                    <div v-if="!responses?.length" class="text-center py-12 text-gray-500 dark:text-gray-400">
                        Hali javoblar yo'q
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-900 dark:text-gray-100">Respondent</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-900 dark:text-gray-100">Qurilma</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-900 dark:text-gray-100">Vaqt</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-900 dark:text-gray-100">Status</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-gray-900 dark:text-gray-100">Sana</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="response in responses"
                                    :key="response.id"
                                    class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900/50"
                                >
                                    <td class="py-3 px-4">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ response.respondent_name || 'Anonim' }}
                                            </p>
                                            <p v-if="response.respondent_region" class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ response.respondent_region }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getDeviceIcon(response.device_type)" />
                                            </svg>
                                            <span class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ response.device_type }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatTime(response.time_spent || 0) }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span
                                            :class="response.status === 'completed'
                                                ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'
                                                : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300'"
                                            class="px-2 py-1 rounded-full text-xs font-medium"
                                        >
                                            {{ response.status === 'completed' ? 'Tugallangan' : 'Jarayonda' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatDate(response.created_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>
