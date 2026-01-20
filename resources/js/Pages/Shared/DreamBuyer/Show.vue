<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import BaseLayout from '@/layouts/BaseLayout.vue';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import FinanceLayout from '@/layouts/FinanceLayout.vue';
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { SparklesIcon, DocumentTextIcon, MegaphoneIcon, StarIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    dreamBuyer: { type: Object, required: true },
    panelType: {
        type: String,
        default: 'business',
        validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v),
    },
});

const layoutComponent = computed(() => {
    const layouts = {
        business: BusinessLayout,
        marketing: MarketingLayout,
        finance: FinanceLayout,
        operator: OperatorLayout,
        saleshead: SalesHeadLayout,
    };
    return layouts[props.panelType] || BaseLayout;
});

const getRoute = (name, params = null) => {
    const prefix = props.panelType + '.';
    return params ? route(prefix + name, params) : route(prefix + name);
};

const showDeleteModal = ref(false);

// Parse text fields
const parseField = (text) => {
    if (!text) return [];
    return text.split('\n').filter(item => item.trim());
};

const whereSpendTime = computed(() => parseField(props.dreamBuyer.where_spend_time));
const infoSources = computed(() => parseField(props.dreamBuyer.info_sources));
const frustrations = computed(() => parseField(props.dreamBuyer.frustrations));
const dreams = computed(() => parseField(props.dreamBuyer.dreams));
const fears = computed(() => parseField(props.dreamBuyer.fears));
const communicationPreferences = computed(() => parseField(props.dreamBuyer.communication_preferences));
const dailyRoutine = computed(() => parseField(props.dreamBuyer.daily_routine));
const happinessTriggers = computed(() => parseField(props.dreamBuyer.happiness_triggers));

const hasData = computed(() => {
    return whereSpendTime.value.length > 0 ||
           frustrations.value.length > 0 ||
           dreams.value.length > 0 ||
           fears.value.length > 0;
});

// AI Generation States
const generatingContentIdeas = ref(false);
const generatingAdCopy = ref(false);
const contentIdeas = ref([]);
const adCopy = ref(null);
const adCopyProduct = ref('');
const showAdCopyModal = ref(false);

// Generate Content Ideas
const generateContentIdeas = async () => {
    generatingContentIdeas.value = true;
    try {
        const response = await fetch(getRoute('dream-buyer.content-ideas', props.dreamBuyer.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const data = await response.json();
        if (data.success) {
            contentIdeas.value = data.content_ideas;
        }
    } catch (error) {
        console.error('Content Ideas xatosi:', error);
    } finally {
        generatingContentIdeas.value = false;
    }
};

// Generate Ad Copy
const generateAdCopy = async () => {
    if (!adCopyProduct.value.trim()) return;
    generatingAdCopy.value = true;
    try {
        const response = await fetch(getRoute('dream-buyer.ad-copy', props.dreamBuyer.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ product: adCopyProduct.value }),
        });
        const data = await response.json();
        if (data.success) {
            adCopy.value = data.ad_copy;
            showAdCopyModal.value = false;
        }
    } catch (error) {
        console.error('Ad Copy xatosi:', error);
    } finally {
        generatingAdCopy.value = false;
    }
};

// Set as Primary
const setPrimary = () => {
    router.post(getRoute('dream-buyer.set-primary', props.dreamBuyer.id));
};

const deleteBuyer = () => {
    router.delete(getRoute('dream-buyer.destroy', props.dreamBuyer.id));
    showDeleteModal.value = false;
};

const copyLink = () => {
    if (props.dreamBuyer.survey) {
        const link = `${window.location.origin}/s/${props.dreamBuyer.survey.slug}`;
        navigator.clipboard.writeText(link);
    }
};
</script>

<template>
    <component :is="layoutComponent" :title="dreamBuyer.name">
        <Head :title="dreamBuyer.name" />

        <div class="p-6 space-y-6">
            <!-- Back Link -->
            <Link
                :href="getRoute('dream-buyer.index')"
                class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                {{ t('dream_buyer.all_profiles') }}
            </Link>

            <!-- Header Card -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-2xl shadow-2xl p-6 text-white">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">{{ dreamBuyer.name }}</h1>
                            <p v-if="dreamBuyer.description" class="text-indigo-100 mt-1">{{ dreamBuyer.description }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span v-if="dreamBuyer.is_primary" class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-400 text-yellow-900 rounded-full text-xs font-semibold">
                                    <StarIcon class="w-3 h-3" />
                                    {{ t('dream_buyer.primary') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="!dreamBuyer.is_primary"
                            @click="setPrimary"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-yellow-400 hover:bg-yellow-300 text-yellow-900 font-medium rounded-xl transition-all"
                        >
                            <StarIcon class="w-4 h-4" />
                            {{ t('dream_buyer.set_primary') }}
                        </button>
                        <Link
                            :href="getRoute('dream-buyer.edit', dreamBuyer.id)"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 font-medium rounded-xl transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            {{ t('dream_buyer.edit') }}
                        </Link>
                        <button
                            @click="showDeleteModal = true"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-500/80 hover:bg-red-600 text-white font-medium rounded-xl transition-all"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Survey Link -->
                <div v-if="dreamBuyer.survey" class="mt-4 p-4 bg-white/10 backdrop-blur-sm rounded-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <div>
                                <p class="font-medium">{{ t('dream_buyer.custdev_survey') }}</p>
                                <code class="text-sm text-indigo-200">/s/{{ dreamBuyer.survey.slug }}</code>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="copyLink" class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors text-sm">
                                {{ t('dream_buyer.copy_link') }}
                            </button>
                            <Link :href="getRoute('custdev.results', { custdev: dreamBuyer.survey.id })" class="px-3 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors text-sm">
                                {{ t('dream_buyer.results') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Tools -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-2xl border border-purple-200 dark:border-purple-700 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center">
                        <SparklesIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ t('dream_buyer.ai_helper') }}</h2>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button
                        @click="generateContentIdeas"
                        :disabled="generatingContentIdeas"
                        class="flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition-all disabled:opacity-50"
                    >
                        <DocumentTextIcon class="w-5 h-5" />
                        <span v-if="generatingContentIdeas">{{ t('dream_buyer.generating') }}</span>
                        <span v-else>{{ t('dream_buyer.content_ideas') }}</span>
                    </button>
                    <button
                        @click="showAdCopyModal = true"
                        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all"
                    >
                        <MegaphoneIcon class="w-5 h-5" />
                        {{ t('dream_buyer.create_ad_copy') }}
                    </button>
                </div>
            </div>

            <!-- Generated Content Ideas -->
            <div v-if="contentIdeas.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <DocumentTextIcon class="w-5 h-5 text-purple-600" />
                    {{ t('dream_buyer.content_ideas') }}
                </h3>
                <div class="space-y-3">
                    <div v-for="(idea, index) in contentIdeas" :key="index" class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                        <p class="text-gray-800 dark:text-gray-200">{{ idea }}</p>
                    </div>
                </div>
            </div>

            <!-- Generated Ad Copy -->
            <div v-if="adCopy" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <MegaphoneIcon class="w-5 h-5 text-indigo-600" />
                    {{ t('dream_buyer.ad_copy') }}
                </h3>
                <div class="space-y-4">
                    <div v-if="adCopy.headline" class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium mb-1">{{ t('dream_buyer.headline') }}</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ adCopy.headline }}</p>
                    </div>
                    <div v-if="adCopy.body" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-xs text-gray-600 dark:text-gray-400 font-medium mb-1">{{ t('dream_buyer.main_text') }}</p>
                        <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ adCopy.body }}</p>
                    </div>
                    <div v-if="adCopy.cta" class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <p class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">CTA</p>
                        <p class="text-gray-800 dark:text-gray-200 font-semibold">{{ adCopy.cta }}</p>
                    </div>
                </div>
            </div>

            <!-- Data Grid -->
            <div v-if="hasData" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Where Spend Time -->
                <div v-if="whereSpendTime.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-5 py-4">
                        <h2 class="font-bold text-white flex items-center gap-2">
                            <span class="text-xl">📱</span> {{ t('dream_buyer.where_spend_time_title') }}
                        </h2>
                    </div>
                    <div class="p-5">
                        <div class="flex flex-wrap gap-2">
                            <span v-for="item in whereSpendTime" :key="item" class="px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-sm font-medium">
                                {{ item }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Info Sources -->
                <div v-if="infoSources.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-violet-600 px-5 py-4">
                        <h2 class="font-bold text-white flex items-center gap-2">
                            <span class="text-xl">🔍</span> {{ t('dream_buyer.info_sources_title') }}
                        </h2>
                    </div>
                    <div class="p-5">
                        <div class="flex flex-wrap gap-2">
                            <span v-for="item in infoSources" :key="item" class="px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 rounded-full text-sm font-medium">
                                {{ item }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Frustrations -->
                <div v-if="frustrations.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-red-500 to-rose-600 px-5 py-4">
                        <h2 class="font-bold text-white flex items-center gap-2">
                            <span class="text-xl">😤</span> {{ t('dream_buyer.frustrations_title') }}
                        </h2>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-2">
                            <li v-for="item in frustrations" :key="item" class="flex items-start gap-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/50 rounded-xl">
                                <span class="w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold">!</span>
                                <span class="text-gray-800 dark:text-gray-200 text-sm">{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Dreams -->
                <div v-if="dreams.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-5 py-4">
                        <h2 class="font-bold text-white flex items-center gap-2">
                            <span class="text-xl">✨</span> {{ t('dream_buyer.dreams_title') }}
                        </h2>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-2">
                            <li v-for="item in dreams" :key="item" class="flex items-start gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800/50 rounded-xl">
                                <span class="w-5 h-5 bg-green-500 text-white rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <span class="text-gray-800 dark:text-gray-200 text-sm">{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Fears -->
                <div v-if="fears.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-5 py-4">
                        <h2 class="font-bold text-white flex items-center gap-2">
                            <span class="text-xl">😰</span> {{ t('dream_buyer.fears_title') }}
                        </h2>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-2">
                            <li v-for="item in fears" :key="item" class="flex items-start gap-3 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/50 rounded-xl">
                                <span class="w-5 h-5 bg-amber-500 text-white rounded-full flex items-center justify-center flex-shrink-0 text-xs">⚠</span>
                                <span class="text-gray-800 dark:text-gray-200 text-sm">{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Communication Preferences -->
                <div v-if="communicationPreferences.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-5 py-4">
                        <h2 class="font-bold text-white flex items-center gap-2">
                            <span class="text-xl">💬</span> {{ t('dream_buyer.communication_title') }}
                        </h2>
                    </div>
                    <div class="p-5">
                        <div class="flex flex-wrap gap-2">
                            <span v-for="item in communicationPreferences" :key="item" class="px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-full text-sm font-medium">
                                {{ item }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Daily Routine -->
                <div v-if="dailyRoutine.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-cyan-500 to-teal-600 px-5 py-4">
                        <h2 class="font-bold text-white flex items-center gap-2">
                            <span class="text-xl">🌅</span> {{ t('dream_buyer.daily_routine_title') }}
                        </h2>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-2">
                            <li v-for="item in dailyRoutine" :key="item" class="p-3 bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-100 dark:border-cyan-800/50 rounded-xl text-gray-800 dark:text-gray-200 text-sm">
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Happiness Triggers -->
                <div v-if="happinessTriggers.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-yellow-500 to-amber-500 px-5 py-4">
                        <h2 class="font-bold text-white flex items-center gap-2">
                            <span class="text-xl">😊</span> {{ t('dream_buyer.happiness_title') }}
                        </h2>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-2">
                            <li v-for="item in happinessTriggers" :key="item" class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-800/50 rounded-xl text-gray-800 dark:text-gray-200 text-sm">
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- No Data State -->
            <div v-if="!hasData" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-amber-800 dark:text-amber-300 mb-1">{{ t('dream_buyer.profile_incomplete') }}</h3>
                        <p class="text-amber-700 dark:text-amber-400 text-sm mb-3">
                            {{ t('dream_buyer.profile_incomplete_desc') }}
                        </p>
                        <Link
                            :href="getRoute('dream-buyer.edit', dreamBuyer.id)"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors"
                        >
                            {{ t('dream_buyer.fill_profile') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ad Copy Modal -->
        <Teleport to="body">
            <div v-if="showAdCopyModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ t('dream_buyer.create_ad_copy') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('dream_buyer.product_name') }}
                            </label>
                            <input
                                v-model="adCopyProduct"
                                type="text"
                                :placeholder="t('dream_buyer.product_placeholder')"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="flex gap-3 justify-end">
                            <button @click="showAdCopyModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all">
                                {{ t('common.cancel') }}
                            </button>
                            <button
                                @click="generateAdCopy"
                                :disabled="generatingAdCopy || !adCopyProduct.trim()"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all disabled:opacity-50"
                            >
                                <span v-if="generatingAdCopy">{{ t('dream_buyer.generating') }}</span>
                                <span v-else>{{ t('dream_buyer.create') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ t('dream_buyer.delete_confirm_title') }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('dream_buyer.delete_warning') }}</p>
                            </div>
                        </div>
                        <p class="text-gray-700 dark:text-gray-300 mb-6">
                            {{ t('dream_buyer.delete_confirm', { name: dreamBuyer.name }) }}
                        </p>
                        <div class="flex gap-3">
                            <button @click="showDeleteModal = false" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors">
                                {{ t('common.cancel') }}
                            </button>
                            <button @click="deleteBuyer" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors">
                                {{ t('common.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </component>
</template>
