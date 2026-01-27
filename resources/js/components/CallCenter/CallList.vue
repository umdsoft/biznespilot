<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import CallListRow from './CallListRow.vue';
import {
    FireIcon,
    PhoneIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    calls: {
        type: Array,
        default: () => [],
    },
    activeTab: {
        type: String,
        default: 'all',
    },
    auditCount: {
        type: Number,
        default: 0,
    },
    canViewAudit: {
        type: Boolean,
        default: false,
    },
    routePrefix: {
        type: String,
        default: '/business/calls',
    },
});

const analyzingCalls = ref(new Set());

const tabs = computed(() => {
    const baseTabs = [
        {
            key: 'all',
            label: 'Barchasi',
            icon: PhoneIcon,
            count: props.calls.length,
        },
    ];

    if (props.canViewAudit) {
        baseTabs.push({
            key: 'audit',
            label: 'Diqqat Talab',
            icon: FireIcon,
            count: props.auditCount,
            highlight: true,
        });
    }

    return baseTabs;
});

const handleTabChange = (tabKey) => {
    router.get(props.routePrefix, { tab: tabKey }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleAnalyze = async (callId) => {
    if (analyzingCalls.value.has(callId)) return;

    analyzingCalls.value.add(callId);
    try {
        // TODO: Implement AI analysis API call
        await new Promise(resolve => setTimeout(resolve, 2000));
        console.log('Analyzing call:', callId);
        router.reload({ preserveScroll: true });
    } catch (err) {
        console.error('Failed to analyze call:', err);
    } finally {
        analyzingCalls.value.delete(callId);
    }
};

const handleViewReport = (callId) => {
    router.get(`${props.routePrefix}/${callId}`);
};

const isCallAnalyzing = (callId) => {
    return analyzingCalls.value.has(callId);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Modern Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex gap-1" aria-label="Tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="handleTabChange(tab.key)"
                    :class="[
                        'group relative px-6 py-4 font-semibold text-sm transition-all duration-200',
                        activeTab === tab.key
                            ? tab.highlight
                                ? 'text-orange-600 dark:text-orange-400'
                                : 'text-indigo-600 dark:text-indigo-400'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
                    ]"
                >
                    <span class="flex items-center gap-2.5">
                        <component
                            :is="tab.icon"
                            :class="[
                                'w-5 h-5 transition-all duration-200',
                                activeTab === tab.key && tab.highlight && 'animate-pulse'
                            ]"
                        />
                        <span>{{ tab.label }}</span>
                        <span
                            v-if="tab.count > 0"
                            :class="[
                                'px-2.5 py-0.5 rounded-full text-xs font-bold transition-all duration-200',
                                activeTab === tab.key
                                    ? tab.highlight
                                        ? 'bg-orange-500 text-white'
                                        : 'bg-indigo-500 text-white'
                                    : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
                            ]"
                        >
                            {{ tab.count > 99 ? '99+' : tab.count }}
                        </span>
                    </span>

                    <!-- Active Indicator -->
                    <span
                        :class="[
                            'absolute bottom-0 left-0 right-0 h-0.5 transition-all duration-200',
                            activeTab === tab.key
                                ? tab.highlight
                                    ? 'bg-gradient-to-r from-orange-500 to-red-500'
                                    : 'bg-gradient-to-r from-indigo-500 to-purple-500'
                                : 'bg-transparent'
                        ]"
                    ></span>
                </button>
            </nav>
        </div>

        <!-- Empty State (Audit Tab) -->
        <div
            v-if="activeTab === 'audit' && calls.length === 0"
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 dark:from-emerald-900/20 dark:via-teal-900/20 dark:to-cyan-900/20 border-2 border-emerald-200 dark:border-emerald-800 p-12"
        >
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-72 h-72 bg-emerald-400 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-teal-400 rounded-full blur-3xl"></div>
            </div>

            <div class="relative text-center space-y-4">
                <div class="flex justify-center">
                    <div class="relative">
                        <div class="w-24 h-24 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center shadow-lg">
                            <CheckCircleIcon class="w-14 h-14 text-white" />
                        </div>
                        <div class="absolute -top-1 -right-1 w-8 h-8 bg-yellow-400 rounded-full flex items-center justify-center shadow-lg animate-bounce">
                            <span class="text-xl">✨</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-bold text-emerald-900 dark:text-emerald-100 mb-2">
                        Ajoyib Ish!
                    </h3>
                    <p class="text-lg text-emerald-700 dark:text-emerald-300">
                        Hozircha diqqat talab qiladigan qo'ng'iroqlar yo'q
                    </p>
                </div>
            </div>
        </div>

        <!-- Empty State (All Tab) -->
        <div
            v-else-if="activeTab === 'all' && calls.length === 0"
            class="text-center py-16"
        >
            <PhoneIcon class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" />
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                Qo'ng'iroqlar topilmadi
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
                Tanlangan davr uchun qo'ng'iroqlar mavjud emas
            </p>
        </div>

        <!-- Call List -->
        <div v-else class="space-y-3">
            <CallListRow
                v-for="call in calls"
                :key="call.id"
                :call="call"
                :is-analyzing="isCallAnalyzing(call.id)"
                @analyze="handleAnalyze"
                @view-report="handleViewReport"
            />
        </div>

        <!-- Load More (if applicable) -->
        <div v-if="calls.length >= 100" class="text-center pt-6">
            <button
                class="px-6 py-3 rounded-xl bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-500 dark:hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold transition-all"
            >
                Ko'proq yuklash
            </button>
        </div>
    </div>
</template>
