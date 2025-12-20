<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref } from 'vue';
import {
    ArrowLeftIcon,
    CheckCircleIcon,
    TrashIcon,
    ArchiveBoxIcon,
    CalendarIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    LightBulbIcon,
    SparklesIcon,
    FlagIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    strategy: {
        type: Object,
        required: true
    }
});

const showCompleteForm = ref(false);
const actualResults = ref({});

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

// Approve strategy
const approveStrategy = () => {
    router.post(route('business.ai.strategy.approve', props.strategy.id), {}, {
        preserveState: true
    });
};

// Complete strategy
const completeStrategy = () => {
    router.post(route('business.ai.strategy.complete', props.strategy.id), {
        actual_results: actualResults.value
    }, {
        preserveState: true,
        onSuccess: () => {
            showCompleteForm.value = false;
        }
    });
};

// Archive strategy
const archiveStrategy = () => {
    if (confirm('Strategiyani arxivlamoqchimisiz?')) {
        router.post(route('business.ai.strategy.archive', props.strategy.id), {}, {
            preserveState: true
        });
    }
};

// Delete strategy
const deleteStrategy = () => {
    if (confirm('Haqiqatan ham strategiyani o\'chirmoqchimisiz?')) {
        router.delete(route('business.ai.strategy.destroy', props.strategy.id), {
            onSuccess: () => {
                router.visit(route('business.ai.strategy.index'));
            }
        });
    }
};

// Format currency
const formatCurrency = (amount) => {
    if (!amount) return '0';
    return new Intl.NumberFormat('uz-UZ').format(amount);
};
</script>

<template>
    <Head :title="strategy.title" />

    <BusinessLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('business.ai.strategy.index')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        <ArrowLeftIcon class="h-6 w-6" />
                    </Link>
                    <div>
                        <div class="flex items-center space-x-3">
                            <CalendarIcon class="h-7 w-7 text-indigo-600" />
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                                {{ strategy.period_label }}
                            </h2>
                            <span :class="`inline-flex items-center px-2 py-1 rounded text-xs font-medium ${getStatusBadge(strategy.status).class}`">
                                {{ getStatusBadge(strategy.status).label }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ strategy.title }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        v-if="strategy.status === 'draft'"
                        @click="approveStrategy"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition"
                    >
                        <CheckCircleIcon class="w-5 h-5 mr-2" />
                        Tasdiqlash
                    </button>
                    <button
                        v-if="strategy.status === 'active'"
                        @click="showCompleteForm = !showCompleteForm"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
                    >
                        <FlagIcon class="w-5 h-5 mr-2" />
                        Tugatish
                    </button>
                    <button
                        @click="archiveStrategy"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition"
                    >
                        <ArchiveBoxIcon class="w-5 h-5" />
                    </button>
                    <button
                        @click="deleteStrategy"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition"
                    >
                        <TrashIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <!-- Complete Form -->
                <div v-if="showCompleteForm" class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Strategiyani Tugatish</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Haqiqiy natijalarni kiriting va strategiya qanchalik samarali bo'lganini baholang.
                    </p>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Haqiqiy Daromad
                                </label>
                                <input
                                    v-model="actualResults.revenue"
                                    type="number"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="0"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Haqiqiy Buyurtmalar Soni
                                </label>
                                <input
                                    v-model="actualResults.orders"
                                    type="number"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="0"
                                />
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button
                                @click="showCompleteForm = false"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
                            >
                                Bekor Qilish
                            </button>
                            <button
                                @click="completeStrategy"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
                            >
                                Saqlash
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Metrics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center space-x-3 mb-2">
                            <CurrencyDollarIcon class="h-8 w-8 text-green-600" />
                            <div>
                                <div class="text-sm text-gray-500">Tavsiya Etilgan Byudjet</div>
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ formatCurrency(strategy.recommended_budget) }} so'm
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center space-x-3 mb-2">
                            <ChartBarIcon class="h-8 w-8 text-blue-600" />
                            <div>
                                <div class="text-sm text-gray-500">Ishonch Darajasi</div>
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ strategy.confidence_score ? (strategy.confidence_score * 100).toFixed(0) : 0 }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="strategy.success_rate" class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center space-x-3 mb-2">
                            <SparklesIcon class="h-8 w-8 text-purple-600" />
                            <div>
                                <div class="text-sm text-gray-500">Muvaffaqiyat</div>
                                <div class="text-2xl font-bold text-green-600">
                                    {{ strategy.success_rate.toFixed(1) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Executive Summary -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Qisqacha Mazmun</h3>
                    <p class="text-gray-700 leading-relaxed">{{ strategy.executive_summary }}</p>
                </div>

                <!-- Goals -->
                <div v-if="strategy.goals && strategy.goals.length > 0" class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Maqsadlar</h3>
                    <ul class="space-y-3">
                        <li
                            v-for="(goal, index) in strategy.goals"
                            :key="index"
                            class="flex items-start"
                        >
                            <FlagIcon class="h-5 w-5 text-indigo-600 mr-3 mt-0.5 flex-shrink-0" />
                            <span class="text-gray-700">{{ goal }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Action Plan -->
                <div v-if="strategy.action_plan && strategy.action_plan.length > 0" class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Amal Rejasi</h3>
                    <div class="space-y-3">
                        <div
                            v-for="(action, index) in strategy.action_plan"
                            :key="index"
                            class="flex items-start border-l-4 border-indigo-500 pl-4 py-2"
                        >
                            <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                <span class="text-indigo-700 font-semibold text-sm">{{ index + 1 }}</span>
                            </div>
                            <span class="text-gray-700">{{ action }}</span>
                        </div>
                    </div>
                </div>

                <!-- Focus Areas -->
                <div v-if="strategy.focus_areas && strategy.focus_areas.length > 0" class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Diqqat Markazida</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div
                            v-for="(area, index) in strategy.focus_areas"
                            :key="index"
                            class="bg-indigo-50 rounded-lg p-3 border border-indigo-200"
                        >
                            <span class="text-indigo-900 font-medium">{{ area }}</span>
                        </div>
                    </div>
                </div>

                <!-- Content Strategy -->
                <div v-if="strategy.content_strategy && strategy.content_strategy.length > 0" class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Kontent Strategiyasi</h3>
                    <ul class="space-y-3">
                        <li
                            v-for="(item, index) in strategy.content_strategy"
                            :key="index"
                            class="flex items-start"
                        >
                            <LightBulbIcon class="h-5 w-5 text-yellow-600 mr-3 mt-0.5 flex-shrink-0" />
                            <span class="text-gray-700">{{ item }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Budget Breakdown -->
                <div v-if="strategy.budget_breakdown && Object.keys(strategy.budget_breakdown).length > 0" class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Byudjet Taqsimoti</h3>
                    <div class="space-y-3">
                        <div
                            v-for="(amount, category) in strategy.budget_breakdown"
                            :key="category"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                        >
                            <span class="text-gray-700 font-medium capitalize">{{ category }}</span>
                            <span class="text-gray-900 font-semibold">{{ formatCurrency(amount) }} so'm</span>
                        </div>
                    </div>
                </div>

                <!-- Predicted Metrics -->
                <div v-if="strategy.predicted_metrics && Object.keys(strategy.predicted_metrics).length > 0" class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Bashorat Qilingan Natijalar</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="(value, metric) in strategy.predicted_metrics"
                            :key="metric"
                            class="border border-gray-200 rounded-lg p-4"
                        >
                            <div class="text-sm text-gray-500 mb-1 capitalize">{{ metric }}</div>
                            <div class="text-xl font-semibold text-gray-900">{{ value }}</div>
                            <div v-if="strategy.actual_results && strategy.actual_results[metric]" class="mt-2 text-sm">
                                <span class="text-gray-600">Haqiqiy: </span>
                                <span class="font-semibold text-green-600">{{ strategy.actual_results[metric] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
