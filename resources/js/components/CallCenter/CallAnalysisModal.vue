<script setup>
import { ref, computed } from 'vue';
import Modal from '@/components/Modal.vue';
import {
    XMarkIcon,
    PlayIcon,
    DocumentTextIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    ExclamationTriangleIcon,
    LightBulbIcon,
    CheckCircleIcon,
    ExclamationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    analysis: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['update:modelValue', 'close']);

// Local state
const showTranscript = ref(false);

// Close modal
const close = () => {
    emit('update:modelValue', false);
    emit('close');
};

// Get score color class
const getScoreColorClass = (score) => {
    if (score === null || score === undefined) return 'bg-gray-100 text-gray-600';
    if (score >= 80) return 'bg-green-100 text-green-700';
    if (score >= 60) return 'bg-yellow-100 text-yellow-700';
    if (score >= 40) return 'bg-orange-100 text-orange-700';
    return 'bg-red-100 text-red-700';
};

// Get progress bar color
const getProgressColor = (score) => {
    if (score === null || score === undefined) return 'bg-gray-300';
    if (score >= 80) return 'bg-green-500';
    if (score >= 60) return 'bg-yellow-500';
    if (score >= 40) return 'bg-orange-500';
    return 'bg-red-500';
};

// Get severity color
const getSeverityColor = (severity) => {
    switch (severity) {
        case 'critical': return 'text-red-600 bg-red-50 border-red-200';
        case 'high': return 'text-orange-600 bg-orange-50 border-orange-200';
        case 'medium': return 'text-yellow-600 bg-yellow-50 border-yellow-200';
        case 'low': return 'text-blue-600 bg-blue-50 border-blue-200';
        default: return 'text-gray-600 bg-gray-50 border-gray-200';
    }
};

// Stage labels in Uzbek
const stageLabels = {
    greeting: 'Salomlashish',
    discovery: 'Ehtiyoj aniqlash',
    presentation: 'Taqdimot',
    objection_handling: 'E\'tirozlar',
    closing: 'Yopish',
    rapport: 'Munosabat',
    cta: 'Keyingi qadam'
};

// Computed stages with labels and scores
const stages = computed(() => {
    if (!props.analysis?.stages) return [];

    return props.analysis.stages.map(stage => ({
        ...stage,
        label: stageLabels[stage.key] || stage.label || stage.key
    }));
});
</script>

<template>
    <Modal
        :model-value="modelValue"
        @update:model-value="emit('update:modelValue', $event)"
        max-width="3xl"
        @close="close"
    >
        <div v-if="analysis" class="max-h-[80vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Qo'ng'iroq tahlili</h2>
                        <p class="text-sm text-gray-500">AI tomonidan tahlil qilingan</p>
                    </div>
                </div>
                <button
                    @click="close"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <!-- Overall Score -->
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-xl p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Umumiy ball</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-gray-900">
                                {{ Math.round(analysis.overall_score) }}
                            </span>
                            <span class="text-lg text-gray-500">/100</span>
                        </div>
                        <p
                            class="text-sm font-medium mt-1"
                            :class="{
                                'text-green-600': analysis.overall_score >= 80,
                                'text-yellow-600': analysis.overall_score >= 60 && analysis.overall_score < 80,
                                'text-orange-600': analysis.overall_score >= 40 && analysis.overall_score < 60,
                                'text-red-600': analysis.overall_score < 40
                            }"
                        >
                            {{ analysis.score_label }}
                        </p>
                    </div>
                    <div class="w-32 h-32">
                        <!-- Circular progress -->
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                            <circle
                                cx="50"
                                cy="50"
                                r="45"
                                fill="none"
                                stroke="#e5e7eb"
                                stroke-width="8"
                            />
                            <circle
                                cx="50"
                                cy="50"
                                r="45"
                                fill="none"
                                :stroke="analysis.overall_score >= 80 ? '#22c55e' : analysis.overall_score >= 60 ? '#eab308' : analysis.overall_score >= 40 ? '#f97316' : '#ef4444'"
                                stroke-width="8"
                                stroke-linecap="round"
                                :stroke-dasharray="`${(analysis.overall_score / 100) * 283} 283`"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Stage Scores -->
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Bosqichlar bo'yicha ball</h3>
                <div class="space-y-3">
                    <div v-for="stage in stages" :key="stage.key" class="flex items-center gap-3">
                        <div class="w-36 flex-shrink-0">
                            <span class="text-sm text-gray-600">{{ stage.label }}</span>
                        </div>
                        <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="getProgressColor(stage.score)"
                                :style="{ width: `${stage.score}%` }"
                            />
                        </div>
                        <div class="w-12 text-right">
                            <span
                                class="text-sm font-semibold"
                                :class="{
                                    'text-green-600': stage.score >= 80,
                                    'text-yellow-600': stage.score >= 60 && stage.score < 80,
                                    'text-orange-600': stage.score >= 40 && stage.score < 60,
                                    'text-red-600': stage.score < 40,
                                    'text-gray-400': stage.score === null
                                }"
                            >
                                {{ stage.score ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Anti-patterns (Errors) -->
            <div v-if="analysis.anti_patterns?.length" class="mb-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <ExclamationTriangleIcon class="w-4 h-4 text-orange-500" />
                    Topilgan xatolar
                </h3>
                <div class="space-y-2">
                    <div
                        v-for="(pattern, index) in analysis.anti_patterns"
                        :key="index"
                        class="p-3 rounded-lg border"
                        :class="getSeverityColor(pattern.severity)"
                    >
                        <div class="flex items-start gap-2">
                            <ExclamationCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">{{ pattern.description }}</p>
                                <p v-if="pattern.suggestion" class="text-xs mt-1 opacity-80">
                                    Tavsiya: {{ pattern.suggestion }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Strengths & Weaknesses -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <!-- Strengths -->
                <div v-if="analysis.strengths?.length">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <CheckCircleIcon class="w-4 h-4 text-green-500" />
                        Kuchli tomonlar
                    </h3>
                    <ul class="space-y-1">
                        <li
                            v-for="(strength, index) in analysis.strengths"
                            :key="index"
                            class="text-sm text-gray-600 flex items-start gap-2"
                        >
                            <span class="text-green-500 mt-0.5">+</span>
                            {{ strength }}
                        </li>
                    </ul>
                </div>

                <!-- Weaknesses -->
                <div v-if="analysis.weaknesses?.length">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <ExclamationCircleIcon class="w-4 h-4 text-red-500" />
                        Zaif tomonlar
                    </h3>
                    <ul class="space-y-1">
                        <li
                            v-for="(weakness, index) in analysis.weaknesses"
                            :key="index"
                            class="text-sm text-gray-600 flex items-start gap-2"
                        >
                            <span class="text-red-500 mt-0.5">-</span>
                            {{ weakness }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Recommendations -->
            <div v-if="analysis.recommendations?.length" class="mb-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <LightBulbIcon class="w-4 h-4 text-yellow-500" />
                    Tavsiyalar
                </h3>
                <ul class="space-y-2">
                    <li
                        v-for="(rec, index) in analysis.recommendations"
                        :key="index"
                        class="text-sm text-gray-600 bg-yellow-50 px-3 py-2 rounded-lg border border-yellow-100"
                    >
                        {{ rec }}
                    </li>
                </ul>
            </div>

            <!-- Transcript (collapsible) -->
            <div v-if="analysis.transcript" class="mb-6">
                <button
                    @click="showTranscript = !showTranscript"
                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <DocumentTextIcon class="w-4 h-4 text-gray-500" />
                        <span class="text-sm font-medium text-gray-700">Transkript</span>
                    </div>
                    <component
                        :is="showTranscript ? ChevronUpIcon : ChevronDownIcon"
                        class="w-5 h-5 text-gray-400"
                    />
                </button>
                <div
                    v-if="showTranscript"
                    class="mt-2 p-4 bg-gray-50 rounded-lg text-sm text-gray-600 max-h-64 overflow-y-auto whitespace-pre-wrap"
                >
                    {{ analysis.transcript }}
                </div>
            </div>

            <!-- Cost info -->
            <div class="flex items-center justify-between text-xs text-gray-400 border-t border-gray-100 pt-4">
                <div class="flex items-center gap-4">
                    <span>Model: {{ analysis.models?.analysis || 'Claude Haiku' }}</span>
                    <span>|</span>
                    <span>Tokenlar: {{ analysis.tokens?.input || 0 }} / {{ analysis.tokens?.output || 0 }}</span>
                </div>
                <span>Xarajat: {{ analysis.cost?.formatted || '~0 so\'m' }}</span>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="py-8 text-center">
            <p class="text-gray-500">Tahlil ma'lumotlari yuklanmoqda...</p>
        </div>
    </Modal>
</template>
