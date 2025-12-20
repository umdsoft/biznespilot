<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref } from 'vue';
import {
    CalendarIcon,
    SparklesIcon,
    CheckCircleIcon,
    ClockIcon,
    ArchiveBoxIcon,
    ChevronRightIcon,
    TrophyIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    strategies: {
        type: Array,
        default: () => []
    },
    currentMonthStrategy: {
        type: Object,
        default: null
    },
    selectedYear: {
        type: Number,
        required: true
    },
    availableYears: {
        type: Array,
        default: () => []
    }
});

const selectedYear = ref(props.selectedYear);

// Change year filter
const changeYear = () => {
    router.get(route('business.ai.strategy.index'), {
        year: selectedYear.value
    }, {
        preserveState: true
    });
};

// Format date
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uz-UZ', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Get status badge
const getStatusBadge = (status) => {
    const badges = {
        draft: { class: 'bg-gray-100 text-gray-800', label: 'Qoralama' },
        active: { class: 'bg-green-100 text-green-800', label: 'Faol' },
        completed: { class: 'bg-blue-100 text-blue-800', label: 'Bajarildi' },
        archived: { class: 'bg-orange-100 text-orange-800', label: 'Arxivlangan' }
    };
    return badges[status] || badges.draft;
};

// Get month name
const getMonthName = (month) => {
    const months = [
        'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun',
        'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'
    ];
    return months[month - 1] || '';
};

// Generate new strategy
const generateStrategy = () => {
    router.post(route('business.ai.strategy.queue-generation'), {}, {
        preserveState: true
    });
};

// Approve strategy
const approveStrategy = (strategyId) => {
    router.post(route('business.ai.strategy.approve', strategyId), {}, {
        preserveState: true
    });
};
</script>

<template>
    <Head title="Oylik Strategiyalar" />

    <BusinessLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Oylik Strategiyalar
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        AI tomonidan yaratilgan marketing va sotuv strategiyalari
                    </p>
                </div>
                <button
                    @click="generateStrategy"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                >
                    <SparklesIcon class="w-5 h-5 mr-2" />
                    Yangi Strategiya
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Current Month Strategy Highlight -->
                <div v-if="currentMonthStrategy" class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 mb-8 text-white">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <CalendarIcon class="h-6 w-6" />
                                <h3 class="text-lg font-semibold">Joriy Oy Strategiyasi</h3>
                                <span :class="`inline-flex items-center px-2 py-1 rounded text-xs font-medium ${getStatusBadge(currentMonthStrategy.status).class}`">
                                    {{ getStatusBadge(currentMonthStrategy.status).label }}
                                </span>
                            </div>
                            <h4 class="text-2xl font-bold mb-2">{{ currentMonthStrategy.title }}</h4>
                            <p class="text-indigo-100 mb-4 line-clamp-2">
                                {{ currentMonthStrategy.executive_summary }}
                            </p>
                            <div class="flex items-center space-x-6 text-sm">
                                <div v-if="currentMonthStrategy.recommended_budget" class="flex items-center">
                                    <span class="opacity-75 mr-1">Byudjet:</span>
                                    <span class="font-semibold">{{ new Intl.NumberFormat('uz-UZ').format(currentMonthStrategy.recommended_budget) }} so'm</span>
                                </div>
                                <div v-if="currentMonthStrategy.confidence_score" class="flex items-center">
                                    <span class="opacity-75 mr-1">Ishonch:</span>
                                    <span class="font-semibold">{{ (currentMonthStrategy.confidence_score * 100).toFixed(0) }}%</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col space-y-2 ml-4">
                            <Link
                                :href="route('business.ai.strategy.show', currentMonthStrategy.id)"
                                class="px-4 py-2 bg-white text-indigo-600 rounded-md hover:bg-indigo-50 transition text-center font-medium"
                            >
                                Batafsil
                            </Link>
                            <button
                                v-if="currentMonthStrategy.status === 'draft'"
                                @click="approveStrategy(currentMonthStrategy.id)"
                                class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition text-center font-medium"
                            >
                                Tasdiqlash
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Year Filter -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <div class="flex items-center space-x-4">
                        <label class="text-sm font-medium text-gray-700">Yil:</label>
                        <select
                            v-model="selectedYear"
                            @change="changeYear"
                            class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option v-for="year in availableYears" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Strategies Grid -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">
                        {{ selectedYear }}-yil Strategiyalari
                    </h3>

                    <div v-if="strategies.length === 0" class="text-center py-12">
                        <CalendarIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Strategiyalar topilmadi</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Yangi strategiya yaratish uchun yuqoridagi tugmani bosing
                        </p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <Link
                            v-for="strategy in strategies"
                            :key="strategy.id"
                            :href="route('business.ai.strategy.show', strategy.id)"
                            class="block group"
                        >
                            <div class="border-2 border-gray-200 rounded-lg p-5 hover:border-indigo-500 transition-all duration-200 hover:shadow-lg">
                                <!-- Header -->
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center space-x-2">
                                        <CalendarIcon class="h-5 w-5 text-indigo-600" />
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ getMonthName(strategy.month) }} {{ strategy.year }}
                                        </span>
                                    </div>
                                    <span :class="`inline-flex items-center px-2 py-1 rounded text-xs font-medium ${getStatusBadge(strategy.status).class}`">
                                        {{ getStatusBadge(strategy.status).label }}
                                    </span>
                                </div>

                                <!-- Title -->
                                <h4 class="text-base font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-indigo-600">
                                    {{ strategy.title }}
                                </h4>

                                <!-- Summary -->
                                <p class="text-sm text-gray-600 line-clamp-3 mb-4">
                                    {{ strategy.executive_summary }}
                                </p>

                                <!-- Metrics -->
                                <div class="space-y-2 pt-3 border-t border-gray-200">
                                    <div v-if="strategy.recommended_budget" class="flex justify-between items-center text-xs">
                                        <span class="text-gray-500">Byudjet:</span>
                                        <span class="font-medium text-gray-900">
                                            {{ new Intl.NumberFormat('uz-UZ').format(strategy.recommended_budget) }} so'm
                                        </span>
                                    </div>
                                    <div v-if="strategy.success_rate" class="flex justify-between items-center text-xs">
                                        <span class="text-gray-500">Muvaffaqiyat:</span>
                                        <span class="font-medium text-green-600">
                                            {{ strategy.success_rate.toFixed(1) }}%
                                        </span>
                                    </div>
                                    <div v-if="strategy.confidence_score" class="flex justify-between items-center text-xs">
                                        <span class="text-gray-500">Ishonch darajasi:</span>
                                        <span class="font-medium text-gray-900">
                                            {{ (strategy.confidence_score * 100).toFixed(0) }}%
                                        </span>
                                    </div>
                                </div>

                                <!-- View Link -->
                                <div class="mt-4 flex items-center justify-between">
                                    <span class="text-xs text-gray-500">
                                        {{ formatDate(strategy.generated_at) }}
                                    </span>
                                    <ChevronRightIcon class="h-5 w-5 text-gray-400 group-hover:text-indigo-600" />
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
