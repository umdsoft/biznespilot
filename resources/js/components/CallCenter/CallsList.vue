<script setup>
import { ref, onMounted, watch } from 'vue';
import { useCallAnalysis } from '@/composables/useCallAnalysis';
import CallAnalysisModal from './CallAnalysisModal.vue';
import {
    PhoneIcon,
    PhoneArrowDownLeftIcon,
    PhoneArrowUpRightIcon,
    PlayIcon,
    SparklesIcon,
    CheckCircleIcon,
    ClockIcon,
    ExclamationCircleIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    leadId: {
        type: String,
        required: true
    }
});

const {
    calls,
    selectedCalls,
    loading,
    analyzing,
    error,
    selectedCount,
    pendingCalls,
    estimatedCost,
    fetchCalls,
    analyzeBulk,
    toggleSelection,
    selectAllAnalyzable,
    clearSelection,
    openAnalysisModal,
    closeAnalysisModal,
    currentAnalysis,
    analysisModalOpen,
    formatDuration,
    getStatusColor,
    getScoreColor
} = useCallAnalysis();

// Fetch calls on mount
onMounted(() => {
    if (props.leadId) {
        fetchCalls(props.leadId, { analyzable_only: false });
    }
});

// Watch for leadId changes
watch(() => props.leadId, (newId) => {
    if (newId) {
        fetchCalls(newId, { analyzable_only: false });
    }
});

// Handle bulk analyze
const handleAnalyze = async () => {
    const result = await analyzeBulk();
    if (result) {
        // Refresh calls list after short delay
        setTimeout(() => fetchCalls(props.leadId), 1000);
    }
};

// Format date
const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('uz-UZ', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Get analysis status icon
const getStatusIcon = (status) => {
    switch (status) {
        case 'completed': return CheckCircleIcon;
        case 'queued':
        case 'transcribing':
        case 'analyzing': return ClockIcon;
        case 'failed': return ExclamationCircleIcon;
        default: return null;
    }
};

// Check if call is being processed
const isProcessing = (status) => {
    return ['queued', 'transcribing', 'analyzing'].includes(status);
};

// Refresh calls
const refresh = () => {
    fetchCalls(props.leadId, { analyzable_only: false });
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <PhoneIcon class="w-5 h-5 text-gray-500" />
                <h3 class="text-sm font-semibold text-gray-900">Qo'ng'iroqlar</h3>
                <span v-if="calls.length" class="text-xs text-gray-500">({{ calls.length }})</span>
            </div>

            <div class="flex items-center gap-2">
                <!-- Refresh button -->
                <button
                    @click="refresh"
                    :disabled="loading"
                    class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    title="Yangilash"
                >
                    <ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': loading }" />
                </button>

                <!-- AI Analyze button -->
                <button
                    v-if="selectedCount > 0"
                    @click="handleAnalyze"
                    :disabled="analyzing"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-purple-600 hover:bg-purple-700 disabled:opacity-50 rounded-lg transition-colors"
                >
                    <SparklesIcon class="w-4 h-4" />
                    <span>AI Tahlil ({{ selectedCount }})</span>
                    <span class="text-purple-200">~{{ estimatedCost.formatted }}</span>
                </button>
            </div>
        </div>

        <!-- Loading state -->
        <div v-if="loading && !calls.length" class="p-8 text-center">
            <ArrowPathIcon class="w-8 h-8 text-gray-400 animate-spin mx-auto" />
            <p class="text-sm text-gray-500 mt-2">Yuklanmoqda...</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="!calls.length" class="p-8 text-center">
            <PhoneIcon class="w-12 h-12 text-gray-300 mx-auto" />
            <p class="text-sm text-gray-500 mt-2">Qo'ng'iroqlar mavjud emas</p>
        </div>

        <!-- Calls list -->
        <div v-else class="divide-y divide-gray-100">
            <!-- Select all bar -->
            <div v-if="pendingCalls.length > 0" class="px-4 py-2 bg-gray-50 flex items-center justify-between">
                <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                    <input
                        type="checkbox"
                        :checked="selectedCount === pendingCalls.length && selectedCount > 0"
                        :indeterminate="selectedCount > 0 && selectedCount < pendingCalls.length"
                        @change="selectedCount === pendingCalls.length ? clearSelection() : selectAllAnalyzable()"
                        class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                    />
                    <span>Barchasini tanlash ({{ pendingCalls.length }} ta tahlil qilish mumkin)</span>
                </label>
            </div>

            <!-- Call items -->
            <div
                v-for="call in calls"
                :key="call.id"
                class="px-4 py-3 hover:bg-gray-50 transition-colors cursor-pointer"
                @click="call.analysis_status === 'completed' ? openAnalysisModal(call.id) : null"
            >
                <div class="flex items-center gap-3">
                    <!-- Checkbox (only for analyzable calls) -->
                    <div class="flex-shrink-0 w-6">
                        <input
                            v-if="call.can_be_analyzed && !isProcessing(call.analysis_status)"
                            type="checkbox"
                            :checked="selectedCalls.includes(call.id)"
                            @click.stop
                            @change="toggleSelection(call.id)"
                            class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                        />
                    </div>

                    <!-- Direction icon -->
                    <div class="flex-shrink-0">
                        <component
                            :is="call.direction === 'inbound' ? PhoneArrowDownLeftIcon : PhoneArrowUpRightIcon"
                            class="w-5 h-5"
                            :class="call.direction === 'inbound' ? 'text-green-500' : 'text-blue-500'"
                        />
                    </div>

                    <!-- Call info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900">
                                {{ formatDate(call.started_at) }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ formatDuration(call.duration) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs text-gray-500">
                                {{ call.operator?.name || 'Noma\'lum' }}
                            </span>
                            <span class="text-xs text-gray-400">|</span>
                            <span class="text-xs text-gray-500">
                                {{ call.status_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Analysis status / score -->
                    <div class="flex-shrink-0 flex items-center gap-2">
                        <!-- Processing indicator -->
                        <div
                            v-if="isProcessing(call.analysis_status)"
                            class="flex items-center gap-1.5 text-blue-600"
                        >
                            <ClockIcon class="w-4 h-4 animate-pulse" />
                            <span class="text-xs">{{ call.analysis_status_label }}</span>
                        </div>

                        <!-- Completed with score -->
                        <div
                            v-else-if="call.analysis_status === 'completed' && call.analysis"
                            class="flex items-center gap-1.5"
                        >
                            <div
                                class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                :class="{
                                    'bg-green-100 text-green-700': call.analysis.overall_score >= 80,
                                    'bg-yellow-100 text-yellow-700': call.analysis.overall_score >= 60 && call.analysis.overall_score < 80,
                                    'bg-orange-100 text-orange-700': call.analysis.overall_score >= 40 && call.analysis.overall_score < 60,
                                    'bg-red-100 text-red-700': call.analysis.overall_score < 40
                                }"
                            >
                                {{ Math.round(call.analysis.overall_score) }} ball
                            </div>
                        </div>

                        <!-- Failed -->
                        <div
                            v-else-if="call.analysis_status === 'failed'"
                            class="flex items-center gap-1.5 text-red-500"
                        >
                            <ExclamationCircleIcon class="w-4 h-4" />
                            <span class="text-xs">Xatolik</span>
                        </div>

                        <!-- Not analyzed but has recording -->
                        <div
                            v-else-if="call.can_be_analyzed"
                            class="text-xs text-gray-400"
                        >
                            Tahlil qilinmagan
                        </div>

                        <!-- No recording -->
                        <div
                            v-else-if="!call.recording_url"
                            class="text-xs text-gray-300"
                        >
                            Yozuv yo'q
                        </div>

                        <!-- Play button for recording -->
                        <a
                            v-if="call.recording_url"
                            :href="call.recording_url"
                            target="_blank"
                            @click.stop
                            class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                            title="Tinglash"
                        >
                            <PlayIcon class="w-4 h-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error message -->
        <div v-if="error" class="px-4 py-3 bg-red-50 border-t border-red-100">
            <p class="text-sm text-red-600">{{ error }}</p>
        </div>

        <!-- Analysis Modal -->
        <CallAnalysisModal
            v-model="analysisModalOpen"
            :analysis="currentAnalysis"
            @close="closeAnalysisModal"
        />
    </div>
</template>
