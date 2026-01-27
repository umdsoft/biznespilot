<script setup>
import { ref } from 'vue';
import {
    PhoneArrowDownLeftIcon,
    PhoneArrowUpRightIcon,
    ClockIcon,
    SparklesIcon,
    CheckCircleIcon,
    XCircleIcon,
    PlayIcon,
    PauseIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    call: {
        type: Object,
        required: true,
    },
    isAnalyzing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['analyze', 'viewReport']);

const isPlaying = ref(false);

const getDirectionIcon = () => {
    return props.call.type === 'incoming' ? PhoneArrowDownLeftIcon : PhoneArrowUpRightIcon;
};

const getDirectionColor = () => {
    return props.call.type === 'incoming'
        ? 'text-emerald-600 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-900/20'
        : 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20';
};

const getStatusBadge = () => {
    const answered = ['completed', 'answered'].includes(props.call.status);
    return answered
        ? { icon: CheckCircleIcon, text: 'Javob berildi', class: 'text-emerald-600 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-900/20' }
        : { icon: XCircleIcon, text: 'Javob yo\'q', class: 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20' };
};

const getAiScoreBadge = () => {
    const score = props.call.ai_score;
    if (!score) return null;

    if (score >= 80) {
        return { class: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300', label: 'A' };
    } else if (score >= 50) {
        return { class: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300', label: 'B' };
    } else {
        return { class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300', label: 'C' };
    }
};

const formatDuration = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const formatDate = (date) => {
    const d = new Date(date);
    return d.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' }) + ', ' +
           d.toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' });
};

const togglePlay = () => {
    isPlaying.value = !isPlaying.value;
};

const isRecommended = props.call.recommended_reason;
const isAnalyzed = props.call.ai_score !== null && props.call.ai_score !== undefined;
const isShortCall = props.call.duration < 30;
</script>

<template>
    <div
        :class="[
            'group relative overflow-hidden rounded-xl border transition-all duration-200',
            isRecommended
                ? 'bg-gradient-to-r from-amber-50/50 via-white to-white dark:from-amber-900/10 dark:via-gray-800 dark:to-gray-800 border-amber-200 dark:border-amber-800 shadow-sm hover:shadow-md'
                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600'
        ]"
    >
        <!-- Recommended Indicator -->
        <div
            v-if="isRecommended"
            class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-orange-500"
        ></div>

        <div class="p-5 pl-6">
            <div class="flex items-start gap-4">
                <!-- Direction Icon -->
                <div :class="[getDirectionColor(), 'w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 ring-1 ring-gray-900/5']">
                    <component :is="getDirectionIcon()" class="w-6 h-6" />
                </div>

                <!-- Main Content -->
                <div class="flex-1 min-w-0 space-y-3">
                    <!-- Header Row -->
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                    {{ call.lead?.name || call.phone }}
                                </h3>
                                <component
                                    :is="getStatusBadge().icon"
                                    :class="[getStatusBadge().class, 'w-4 h-4 flex-shrink-0']"
                                />
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">
                                {{ call.phone }}
                            </p>
                        </div>

                        <!-- Status Badge -->
                        <span :class="[getStatusBadge().class, 'px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap']">
                            {{ getStatusBadge().text }}
                        </span>
                    </div>

                    <!-- AI Recommendation Banner (if applicable) -->
                    <div
                        v-if="isRecommended && !isAnalyzed"
                        class="flex items-start gap-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800"
                    >
                        <SparklesIcon class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-amber-900 dark:text-amber-100 mb-0.5">
                                AI Tavsiyasi
                            </p>
                            <p class="text-xs text-amber-700 dark:text-amber-300">
                                {{ call.recommended_reason }}
                            </p>
                        </div>
                    </div>

                    <!-- Meta Information -->
                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex items-center gap-1.5">
                            <ClockIcon class="w-4 h-4" />
                            <span :class="isShortCall ? 'text-red-600 dark:text-red-400 font-semibold' : ''">
                                {{ call.formatted_duration || formatDuration(call.duration) }}
                            </span>
                        </div>
                        <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                        <span>{{ formatDate(call.created_at) }}</span>
                        <span v-if="call.operator" class="flex items-center gap-1.5">
                            <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                            <span class="text-gray-500 dark:text-gray-400">{{ call.operator.name }}</span>
                        </span>
                    </div>

                    <!-- Audio Player -->
                    <div v-if="call.recording_url" class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700">
                        <button
                            @click="togglePlay"
                            class="w-8 h-8 rounded-full bg-gray-900 dark:bg-white text-white dark:text-gray-900 flex items-center justify-center hover:scale-105 transition-transform"
                        >
                            <component :is="isPlaying ? PauseIcon : PlayIcon" class="w-4 h-4" />
                        </button>
                        <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500" style="width: 0%"></div>
                        </div>
                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400 tabular-nums">
                            {{ call.formatted_duration || formatDuration(call.duration) }}
                        </span>
                    </div>
                </div>

                <!-- Action Section (Right Side) -->
                <div class="flex flex-col items-end gap-2 flex-shrink-0">
                    <!-- AI Score Badge (if analyzed) -->
                    <div v-if="isAnalyzed" class="flex flex-col items-end gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ call.ai_score }}
                            </span>
                            <span
                                :class="[
                                    getAiScoreBadge().class,
                                    'w-10 h-10 rounded-lg flex items-center justify-center text-sm font-bold'
                                ]"
                            >
                                {{ getAiScoreBadge().label }}
                            </span>
                        </div>
                        <button
                            @click="emit('viewReport', call.id)"
                            class="px-4 py-2 rounded-lg bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 font-medium text-sm transition-colors flex items-center gap-2"
                        >
                            <DocumentTextIcon class="w-4 h-4" />
                            Hisobot
                        </button>
                    </div>

                    <!-- AI Analyze Button (if recommended but not analyzed) -->
                    <button
                        v-else-if="isRecommended"
                        @click="emit('analyze', call.id)"
                        :disabled="isAnalyzing"
                        :class="[
                            'px-6 py-3 rounded-xl font-semibold text-sm transition-all flex items-center gap-2 shadow-lg hover:shadow-xl',
                            isAnalyzing
                                ? 'bg-gray-300 dark:bg-gray-700 text-gray-500 cursor-not-allowed'
                                : 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white transform hover:scale-105'
                        ]"
                    >
                        <SparklesIcon class="w-5 h-5" />
                        <span v-if="isAnalyzing">Tahlil qilinmoqda...</span>
                        <span v-else>✨ Tahlil Qilish</span>
                    </button>

                    <!-- Normal state - just download/listen -->
                    <a
                        v-else-if="call.recording_url"
                        :href="call.recording_url"
                        download
                        class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 font-medium text-sm transition-colors"
                    >
                        Yuklab olish
                    </a>
                </div>
            </div>
        </div>

        <!-- Hover Gradient Effect -->
        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent"></div>
        </div>
    </div>
</template>
