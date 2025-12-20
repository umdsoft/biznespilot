<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref } from 'vue';
import {
    ArrowLeftIcon,
    CheckCircleIcon,
    TrashIcon,
    ClipboardDocumentCheckIcon,
    LightBulbIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    insight: {
        type: Object,
        required: true
    }
});

// Action form
const actionForm = ref({
    action: ''
});

const showActionForm = ref(false);

// Format date
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleString('uz-UZ', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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

// Get sentiment text and color
const getSentimentInfo = (sentiment) => {
    const info = {
        positive: { text: 'Ijobiy', color: 'text-green-600 bg-green-100' },
        neutral: { text: 'Neytral', color: 'text-gray-600 bg-gray-100' },
        negative: { text: 'Salbiy', color: 'text-red-600 bg-red-100' }
    };
    return info[sentiment] || info.neutral;
};

// Record action
const submitAction = () => {
    if (!actionForm.value.action.trim()) return;

    router.post(route('business.ai.insights.record-action', props.insight.id), actionForm.value, {
        preserveState: true,
        onSuccess: () => {
            actionForm.value.action = '';
            showActionForm.value = false;
        }
    });
};

// Delete insight
const deleteInsight = () => {
    if (confirm('Haqiqatan ham bu insightni o\'chirmoqchimisiz?')) {
        router.delete(route('business.ai.insights.destroy', props.insight.id), {
            onSuccess: () => {
                router.visit(route('business.ai.insights.index'));
            }
        });
    }
};
</script>

<template>
    <Head :title="insight.title" />

    <BusinessLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('business.ai.insights.index')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        <ArrowLeftIcon class="h-6 w-6" />
                    </Link>
                    <div>
                        <div class="flex items-center space-x-3">
                            <span class="text-3xl">{{ getTypeIcon(insight.type) }}</span>
                            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                                {{ insight.title }}
                            </h2>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ formatDate(insight.generated_at) }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        @click="showActionForm = !showActionForm"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition"
                    >
                        <ClipboardDocumentCheckIcon class="w-5 h-5 mr-2" />
                        Amal Qayd Qilish
                    </button>
                    <button
                        @click="deleteInsight"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition"
                    >
                        <TrashIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Metadata Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Priority -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-500 mb-2">Prioritet</div>
                        <span :class="`inline-flex items-center px-3 py-1 rounded text-sm font-medium border ${getPriorityColor(insight.priority)}`">
                            {{ insight.priority === 'high' ? 'Yuqori' : insight.priority === 'medium' ? 'O\'rta' : 'Past' }}
                        </span>
                    </div>

                    <!-- Sentiment -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-500 mb-2">Kayfiyat</div>
                        <span :class="`inline-flex items-center px-3 py-1 rounded text-sm font-medium ${getSentimentInfo(insight.sentiment).color}`">
                            {{ getSentimentInfo(insight.sentiment).text }}
                        </span>
                    </div>

                    <!-- Type -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-500 mb-2">Turi</div>
                        <div class="flex items-center space-x-2">
                            <span class="text-2xl">{{ getTypeIcon(insight.type) }}</span>
                            <span class="text-sm font-medium text-gray-900 capitalize">{{ insight.type }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Form -->
                <div v-if="showActionForm" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Amal Qayd Qilish</h3>
                    <form @submit.prevent="submitAction">
                        <textarea
                            v-model="actionForm.action"
                            rows="3"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Qanday amal qildingiz yoki qilmoqchisiz?"
                        ></textarea>
                        <div class="flex justify-end space-x-3 mt-4">
                            <button
                                type="button"
                                @click="showActionForm = false"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
                            >
                                Bekor Qilish
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition"
                            >
                                Saqlash
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Summary -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Qisqacha</h3>
                    <p class="text-gray-700 leading-relaxed">{{ insight.summary }}</p>
                </div>

                <!-- Full Analysis -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">To'liq Tahlil</h3>
                    <div class="prose prose-sm max-w-none text-gray-700">
                        <div v-html="insight.full_analysis || insight.summary" class="whitespace-pre-line"></div>
                    </div>
                </div>

                <!-- Recommendations -->
                <div v-if="insight.recommendations && insight.recommendations.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Tavsiyalar</h3>
                    <ul class="space-y-3">
                        <li
                            v-for="(recommendation, index) in insight.recommendations"
                            :key="index"
                            class="flex items-start"
                        >
                            <LightBulbIcon class="h-5 w-5 text-indigo-600 mr-3 mt-0.5 flex-shrink-0" />
                            <span class="text-gray-700">{{ recommendation }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Data Points -->
                <div v-if="insight.data_points && Object.keys(insight.data_points).length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Ma'lumotlar</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="(value, key) in insight.data_points"
                            :key="key"
                            class="border border-gray-200 rounded-lg p-4"
                        >
                            <div class="text-sm text-gray-500 mb-1">{{ key }}</div>
                            <div class="text-lg font-semibold text-gray-900">{{ value }}</div>
                        </div>
                    </div>
                </div>

                <!-- Actions Taken -->
                <div v-if="insight.actions_taken && insight.actions_taken.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Amalga Oshirilgan Amallar</h3>
                    <div class="space-y-3">
                        <div
                            v-for="(action, index) in insight.actions_taken"
                            :key="index"
                            class="border-l-4 border-green-500 pl-4 py-2"
                        >
                            <div class="flex items-start">
                                <CheckCircleIcon class="h-5 w-5 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                                <div>
                                    <p class="text-gray-700">{{ action.action }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ formatDate(action.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
