<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const { t } = useI18n();

const props = defineProps({
    scripts: { type: Array, default: () => [] },
    competitors: { type: Array, default: () => [] },
    panelType: { type: String, required: true },
});

const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        marketing: MarketingLayout,
        saleshead: SalesHeadLayout,
    };
    return layouts[props.panelType] || BaseLayout;
});

const routePrefix = computed(() => {
    const prefix = props.panelType === 'saleshead' ? 'sales-head' : props.panelType;
    return '/' + prefix;
});

// Aktiv skript
const activeScript = ref(props.scripts[0] || null);

// Skript nusxalash
const copyScript = (script) => {
    let text = `${t('insights.competitor')} "${script.competitor}" ${t('scripts.opening').toLowerCase()}:\n\n`;
    text += `"${script.response}"\n\n`;

    script.points.forEach((point, index) => {
        text += `${index + 1}. ${t('scripts.their_weakness')}: ${point.their_weakness}\n`;
        text += `   ${t('scripts.our_answer')}: ${point.our_advantage}\n\n`;
    });

    if (script.price_point) {
        text += `${t('scripts.price_advantage')}: ${script.price_point.text}\n\n`;
    }

    text += `${t('scripts.closing')}: "${script.closing}"`;

    navigator.clipboard.writeText(text);
    alert(t('scripts.copied'));
};
</script>

<template>
    <component :is="layoutComponent" :title="t('scripts.title')">
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('scripts.title') }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('scripts.subtitle') }}
                    </p>
                </div>
                <Link
                    :href="`${routePrefix}/competitor-insights`"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    {{ t('scripts.back') }}
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="scripts.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="mx-auto w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ t('scripts.no_scripts') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ t('scripts.no_scripts_desc') }}
                </p>
            </div>

            <!-- Scripts Grid -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Competitors List -->
                <div class="lg:col-span-1 space-y-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 px-1">{{ t('scripts.competitors') }}</h3>
                    <div
                        v-for="script in scripts"
                        :key="script.competitor"
                        @click="activeScript = script"
                        :class="[
                            'p-4 rounded-xl border cursor-pointer transition-all',
                            activeScript?.competitor === script.competitor
                                ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-300 dark:border-indigo-700'
                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-indigo-200 dark:hover:border-indigo-800'
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                                {{ script.competitor.substring(0, 2).toUpperCase() }}
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ script.competitor }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ script.points.length }} {{ t('scripts.points') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Script Details -->
                <div v-if="activeScript" class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Header -->
                        <div class="p-6 bg-gradient-to-r from-indigo-500 to-purple-600 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-xl font-bold">{{ activeScript.competitor }}</h2>
                                    <p class="text-sm text-white/80 mt-1">{{ activeScript.trigger }}</p>
                                </div>
                                <button
                                    @click="copyScript(activeScript)"
                                    class="p-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors"
                                    :title="t('scripts.copy')"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Script Content -->
                        <div class="p-6 space-y-6">
                            <!-- Opening -->
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">{{ t('scripts.opening') }}:</p>
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 border-l-4 border-indigo-500">
                                    <p class="text-gray-900 dark:text-gray-100 italic">"{{ activeScript.response }}"</p>
                                </div>
                            </div>

                            <!-- Points -->
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3">{{ t('scripts.main_points') }}:</p>
                                <div class="space-y-4">
                                    <div
                                        v-for="(point, index) in activeScript.points"
                                        :key="index"
                                        class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                    >
                                        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="w-6 h-6 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center">
                                                    <svg class="w-3 h-3 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </div>
                                                <p class="text-xs font-medium text-red-700 dark:text-red-400">{{ t('scripts.their_weakness') }}</p>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ point.their_weakness }}</p>
                                        </div>

                                        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="w-6 h-6 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                                                    <svg class="w-3 h-3 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                                <p class="text-xs font-medium text-green-700 dark:text-green-400">{{ t('scripts.our_answer') }}</p>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ point.our_advantage }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Price Point -->
                            <div v-if="activeScript.price_point" class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm font-medium text-blue-700 dark:text-blue-400">{{ t('scripts.price_advantage') }}:</p>
                                </div>
                                <p class="text-gray-900 dark:text-gray-100">{{ activeScript.price_point.text }}</p>
                            </div>

                            <!-- Closing -->
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">{{ t('scripts.closing') }}:</p>
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4 border-l-4 border-green-500">
                                    <p class="text-gray-900 dark:text-gray-100 italic">"{{ activeScript.closing }}"</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
