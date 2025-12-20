<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import { ref, computed } from 'vue';
import {
    LightBulbIcon,
    BellAlertIcon,
    CheckCircleIcon,
    SparklesIcon,
    FunnelIcon,
    ArrowPathIcon,
    EyeIcon,
    TrashIcon,
    ChevronRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    insights: {
        type: Object,
        required: true
    },
    summary: {
        type: Object,
        default: () => ({})
    },
    topInsights: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({
            status: 'all',
            type: 'all',
            priority: 'all'
        })
    }
});

// Current filters
const currentFilters = ref(props.filters);

// Format date
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uz-UZ', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Get type icon
const getTypeIcon = (type) => {
    const icons = {
        marketing: '📈',
        sales: '💰',
        customer: '👥',
        content: '✍️',
        trends: '📊',
        risks: '⚠️',
        opportunities: '🎯'
    };
    return icons[type] || '💡';
};

// Get priority color
const getPriorityColor = (priority) => {
    const colors = {
        high: 'bg-red-100 text-red-800 border-red-300',
        medium: 'bg-yellow-100 text-yellow-800 border-yellow-300',
        low: 'bg-gray-100 text-gray-800 border-gray-300'
    };
    return colors[priority] || colors.low;
};

// Get sentiment color
const getSentimentColor = (sentiment) => {
    const colors = {
        positive: 'text-green-600',
        neutral: 'text-gray-600',
        negative: 'text-red-600'
    };
    return colors[sentiment] || colors.neutral;
};

// Apply filters
const applyFilters = () => {
    router.get(route('business.ai.insights.index'), currentFilters.value, {
        preserveState: true,
        preserveScroll: true
    });
};

// Generate new insights
const generateInsights = () => {
    router.post(route('business.ai.insights.queue-generation'), {}, {
        preserveState: true,
        onSuccess: () => {
            // Show success message
        }
    });
};

// Mark as read
const markAsRead = (insightId) => {
    router.post(route('business.ai.insights.mark-read', insightId), {}, {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="AI Insights" />

    <BusinessLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        AI Insights
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        AI tomonidan yaratilgan biznesingiz haqida tushunchalar
                    </p>
                </div>
                <button
                    @click="generateInsights"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <SparklesIcon class="w-5 h-5 mr-2" />
                    Yangi Insight Yaratish
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Insights -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <LightBulbIcon class="h-12 w-12 text-indigo-600" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-500 truncate">
                                        Jami Insights
                                    </p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ summary.total_insights || 0 }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Barcha vaqt
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unread Insights -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <BellAlertIcon class="h-12 w-12 text-orange-600" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-500 truncate">
                                        O'qilmagan
                                    </p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ summary.unread_count || 0 }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Yangi xabarlar
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- High Priority -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <BellAlertIcon class="h-12 w-12 text-red-600" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-500 truncate">
                                        Yuqori Prioritet
                                    </p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ summary.high_priority_count || 0 }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Muhim tushunchalar
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- This Month -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <SparklesIcon class="h-12 w-12 text-green-600" />
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-medium text-gray-500 truncate">
                                        Shu Oy
                                    </p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ summary.this_month_count || 0 }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Yangi insights
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-center space-x-4">
                            <FunnelIcon class="h-5 w-5 text-gray-400" />
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Status Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select
                                        v-model="currentFilters.status"
                                        @change="applyFilters"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="all">Barchasi</option>
                                        <option value="unread">O'qilmagan</option>
                                        <option value="actionable">Amaliy</option>
                                    </select>
                                </div>

                                <!-- Type Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Turi</label>
                                    <select
                                        v-model="currentFilters.type"
                                        @change="applyFilters"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="all">Barchasi</option>
                                        <option value="marketing">Marketing</option>
                                        <option value="sales">Sotuv</option>
                                        <option value="customer">Mijoz</option>
                                        <option value="content">Kontent</option>
                                        <option value="trends">Trendlar</option>
                                        <option value="risks">Risklar</option>
                                        <option value="opportunities">Imkoniyatlar</option>
                                    </select>
                                </div>

                                <!-- Priority Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioritet</label>
                                    <select
                                        v-model="currentFilters.priority"
                                        @change="applyFilters"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="all">Barchasi</option>
                                        <option value="high">Yuqori</option>
                                        <option value="medium">O'rta</option>
                                        <option value="low">Past</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Insights List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">
                            Insights
                        </h3>

                        <div v-if="insights.data.length === 0" class="text-center py-12">
                            <LightBulbIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Insights topilmadi</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Yangi insights yaratish uchun yuqoridagi tugmani bosing
                            </p>
                        </div>

                        <div v-else class="space-y-4">
                            <Link
                                v-for="insight in insights.data"
                                :key="insight.id"
                                :href="route('business.ai.insights.show', insight.id)"
                                class="block group"
                            >
                                <div class="border-2 border-gray-200 rounded-lg p-4 hover:border-indigo-500 transition-all duration-200 hover:shadow-md">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <span class="text-2xl">{{ getTypeIcon(insight.type) }}</span>
                                                <div class="flex-1">
                                                    <h4 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600">
                                                        {{ insight.title }}
                                                    </h4>
                                                    <div class="flex items-center space-x-2 mt-1">
                                                        <span :class="`inline-flex items-center px-2 py-1 rounded text-xs font-medium border ${getPriorityColor(insight.priority)}`">
                                                            {{ insight.priority }}
                                                        </span>
                                                        <span class="text-xs text-gray-500">
                                                            {{ formatDate(insight.generated_at) }}
                                                        </span>
                                                        <span v-if="!insight.is_read" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            Yangi
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-600 line-clamp-2 ml-11">
                                                {{ insight.summary }}
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-2 ml-4">
                                            <button
                                                v-if="!insight.is_read"
                                                @click.prevent="markAsRead(insight.id)"
                                                class="p-2 text-gray-400 hover:text-indigo-600 transition-colors"
                                                title="O'qilgan deb belgilash"
                                            >
                                                <CheckCircleIcon class="h-5 w-5" />
                                            </button>
                                            <ChevronRightIcon class="h-5 w-5 text-gray-400 group-hover:text-indigo-600" />
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <!-- Pagination -->
                        <div v-if="insights.data.length > 0" class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
                            <div class="text-sm text-gray-700">
                                <span class="font-medium">{{ insights.from }}</span>
                                -
                                <span class="font-medium">{{ insights.to }}</span>
                                dan
                                <span class="font-medium">{{ insights.total }}</span>
                                ta
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    v-for="link in insights.links"
                                    :key="link.label"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 rounded border text-sm',
                                        link.active
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : link.url
                                            ? 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                            : 'bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed'
                                    ]"
                                    :disabled="!link.url"
                                    preserve-state
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
