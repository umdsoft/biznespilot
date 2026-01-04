<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

const props = defineProps({
    dreamBuyer: {
        type: Object,
        required: true,
    },
});

const generatingContent = ref(false);
const generatingAds = ref(false);
const contentIdeas = ref(null);
const adCopy = ref(null);
const productForAd = ref('');
const showDeleteModal = ref(false);

const profile = props.dreamBuyer.data || {};

const generateContentIdeas = async () => {
    generatingContent.value = true;

    try {
        const response = await fetch(route('business.dream-buyer.content-ideas', props.dreamBuyer.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = await response.json();
        contentIdeas.value = data.content_ideas;
    } catch (error) {
        console.error('Error generating content ideas:', error);
    } finally {
        generatingContent.value = false;
    }
};

const generateAdCopy = async () => {
    if (!productForAd.value.trim()) {
        alert('Iltimos, mahsulot/xizmat nomini kiriting');
        return;
    }

    generatingAds.value = true;

    try {
        const response = await fetch(route('business.dream-buyer.ad-copy', props.dreamBuyer.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ product: productForAd.value }),
        });

        const data = await response.json();
        adCopy.value = data.ad_copy;
    } catch (error) {
        console.error('Error generating ad copy:', error);
    } finally {
        generatingAds.value = false;
    }
};

const deleteBuyer = () => {
    router.delete(route('business.dream-buyer.destroy', props.dreamBuyer.id));
    showDeleteModal.value = false;
};
</script>

<template>
    <BusinessLayout :title="dreamBuyer.name">
        <Head :title="dreamBuyer.name" />

        <div class="py-8 lg:py-12">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('business.dream-buyer.index')"
                        class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 mb-4 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Barcha Ideal Mijozlar
                    </Link>

                    <!-- Hero Card -->
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-2xl shadow-2xl p-6 lg:p-8 text-white">
                        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-6">
                            <div class="flex-1">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-16 h-16 lg:w-20 lg:h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                                        <svg class="w-10 h-10 lg:w-12 lg:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h1 class="text-2xl lg:text-3xl font-bold">{{ dreamBuyer.name }}</h1>
                                        <p v-if="profile.tagline" class="text-indigo-100 mt-1">
                                            {{ profile.tagline }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="dreamBuyer.description" class="mb-4">
                                    <p class="text-indigo-100">{{ dreamBuyer.description }}</p>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <span v-if="dreamBuyer.is_primary" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-yellow-400 text-yellow-900 rounded-full text-sm font-semibold">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                        </svg>
                                        Primary
                                    </span>
                                    <span v-if="profile.avatar_name" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/20 backdrop-blur-sm rounded-full text-sm font-semibold">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                        </svg>
                                        AI-Generated
                                    </span>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <Link
                                    :href="route('business.dream-buyer.edit', dreamBuyer.id)"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-xl transition-all"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Tahrirlash
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

                        <!-- Quote -->
                        <div v-if="profile.quote" class="mt-6 p-4 bg-white/10 backdrop-blur-sm rounded-xl border-l-4 border-white/50">
                            <p class="text-white italic text-lg">"{{ profile.quote }}"</p>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column (2/3) -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Demographics -->
                        <div v-if="profile.demographics" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
                                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Demografik Ma'lumotlar
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div v-if="profile.demographics.age_range" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Yosh</label>
                                        <p class="text-gray-900 dark:text-white font-semibold mt-1">{{ profile.demographics.age_range }}</p>
                                    </div>
                                    <div v-if="profile.demographics.gender" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Jins</label>
                                        <p class="text-gray-900 dark:text-white font-semibold mt-1">{{ profile.demographics.gender }}</p>
                                    </div>
                                    <div v-if="profile.demographics.location" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Joylashuv</label>
                                        <p class="text-gray-900 dark:text-white font-semibold mt-1">{{ profile.demographics.location }}</p>
                                    </div>
                                    <div v-if="profile.demographics.occupation" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Kasb</label>
                                        <p class="text-gray-900 dark:text-white font-semibold mt-1">{{ profile.demographics.occupation }}</p>
                                    </div>
                                    <div v-if="profile.demographics.income_level" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Daromad</label>
                                        <p class="text-gray-900 dark:text-white font-semibold mt-1">{{ profile.demographics.income_level }}</p>
                                    </div>
                                    <div v-if="profile.demographics.education" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Ta'lim</label>
                                        <p class="text-gray-900 dark:text-white font-semibold mt-1">{{ profile.demographics.education }}</p>
                                    </div>
                                    <div v-if="profile.demographics.family_status" class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Oila</label>
                                        <p class="text-gray-900 dark:text-white font-semibold mt-1">{{ profile.demographics.family_status }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Psychographics -->
                        <div v-if="profile.psychographics" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    Psixografik Ma'lumotlar
                                </h2>
                            </div>
                            <div class="p-6 space-y-5">
                                <div v-if="profile.psychographics.personality_traits?.length">
                                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 block">Shaxsiy xususiyatlar</label>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="trait in profile.psychographics.personality_traits"
                                            :key="trait"
                                            class="px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-full text-sm font-medium"
                                        >
                                            {{ trait }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="profile.psychographics.values?.length">
                                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 block">Qadriyatlar</label>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="value in profile.psychographics.values"
                                            :key="value"
                                            class="px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-sm font-medium"
                                        >
                                            {{ value }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="profile.psychographics.interests?.length">
                                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 block">Qiziqishlar</label>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="interest in profile.psychographics.interests"
                                            :key="interest"
                                            class="px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-sm font-medium"
                                        >
                                            {{ interest }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="profile.psychographics.lifestyle">
                                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">Hayot tarzi</label>
                                    <p class="text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-xl">{{ profile.psychographics.lifestyle }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Pain Points -->
                        <div v-if="profile.pain_points?.length" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Pain Points (Muammolar)
                                </h2>
                            </div>
                            <div class="p-6">
                                <ul class="space-y-3">
                                    <li
                                        v-for="point in profile.pain_points"
                                        :key="point"
                                        class="flex items-start gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/50 rounded-xl"
                                    >
                                        <span class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold">!</span>
                                        <span class="text-gray-800 dark:text-gray-200">{{ point }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Goals & Dreams -->
                        <div v-if="profile.goals_dreams?.length" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-4">
                                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                    Maqsadlar va Orzular
                                </h2>
                            </div>
                            <div class="p-6">
                                <ul class="space-y-3">
                                    <li
                                        v-for="goal in profile.goals_dreams"
                                        :key="goal"
                                        class="flex items-start gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800/50 rounded-xl"
                                    >
                                        <span class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span class="text-gray-800 dark:text-gray-200">{{ goal }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Fears & Objections -->
                        <div v-if="profile.fears_objections?.length" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-4">
                                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Qo'rquvlar va E'tirozlar
                                </h2>
                            </div>
                            <div class="p-6">
                                <ul class="space-y-3">
                                    <li
                                        v-for="fear in profile.fears_objections"
                                        :key="fear"
                                        class="flex items-start gap-3 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-800/50 rounded-xl"
                                    >
                                        <span class="w-6 h-6 bg-amber-500 text-white rounded-full flex items-center justify-center flex-shrink-0 text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01" />
                                            </svg>
                                        </span>
                                        <span class="text-gray-800 dark:text-gray-200">{{ fear }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Daily Journey -->
                        <div v-if="profile.daily_journey" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-6 py-4">
                                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Kundalik Hayot
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-if="profile.daily_journey.morning" class="p-4 bg-gradient-to-br from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20 border border-orange-100 dark:border-orange-800/50 rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">🌅</span>
                                            <label class="text-sm font-semibold text-orange-700 dark:text-orange-400">Ertalab</label>
                                        </div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">{{ profile.daily_journey.morning }}</p>
                                    </div>
                                    <div v-if="profile.daily_journey.afternoon" class="p-4 bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border border-yellow-100 dark:border-yellow-800/50 rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">☀️</span>
                                            <label class="text-sm font-semibold text-yellow-700 dark:text-yellow-400">Tushdan keyin</label>
                                        </div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">{{ profile.daily_journey.afternoon }}</p>
                                    </div>
                                    <div v-if="profile.daily_journey.evening" class="p-4 bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-100 dark:border-indigo-800/50 rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">🌙</span>
                                            <label class="text-sm font-semibold text-indigo-700 dark:text-indigo-400">Kechqurun</label>
                                        </div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">{{ profile.daily_journey.evening }}</p>
                                    </div>
                                    <div v-if="profile.daily_journey.peak_time" class="p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-100 dark:border-green-800/50 rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">⚡</span>
                                            <label class="text-sm font-semibold text-green-700 dark:text-green-400">Eng faol vaqt</label>
                                        </div>
                                        <p class="text-gray-800 dark:text-gray-200 text-sm">{{ profile.daily_journey.peak_time }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (1/3) -->
                    <div class="space-y-6">
                        <!-- Communication Style -->
                        <div v-if="profile.communication_style" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-500 to-blue-600 px-6 py-4">
                                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    Muloqot
                                </h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div v-if="profile.communication_style.preferred_channels?.length">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Afzal Kanallar</label>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="channel in profile.communication_style.preferred_channels"
                                            :key="channel"
                                            class="px-2.5 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 rounded-lg text-xs font-medium"
                                        >
                                            {{ channel }}
                                        </span>
                                    </div>
                                </div>

                                <div v-if="profile.communication_style.tone">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1 block">Ohang</label>
                                    <p class="text-gray-900 dark:text-gray-100 text-sm">{{ profile.communication_style.tone }}</p>
                                </div>

                                <div v-if="profile.communication_style.language_tips?.length">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Maslahatlar</label>
                                    <ul class="space-y-1.5">
                                        <li v-for="tip in profile.communication_style.language_tips" :key="tip" class="text-sm text-gray-800 dark:text-gray-200 flex items-start gap-2">
                                            <span class="text-green-500 mt-0.5">✓</span>
                                            <span>{{ tip }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <div v-if="profile.communication_style.avoid?.length">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Ishlatmaslik</label>
                                    <ul class="space-y-1.5">
                                        <li v-for="avoid in profile.communication_style.avoid" :key="avoid" class="text-sm text-gray-800 dark:text-gray-200 flex items-start gap-2">
                                            <span class="text-red-500 mt-0.5">✗</span>
                                            <span>{{ avoid }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Purchase Triggers -->
                        <div v-if="profile.purchase_triggers?.length" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-yellow-500 to-amber-600 px-6 py-4">
                                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                    Xarid Trigerlari
                                </h2>
                            </div>
                            <div class="p-5">
                                <ul class="space-y-2">
                                    <li
                                        v-for="trigger in profile.purchase_triggers"
                                        :key="trigger"
                                        class="text-sm text-gray-800 dark:text-gray-200 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-800/50 rounded-xl"
                                    >
                                        {{ trigger }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Marketing Insights -->
                        <div v-if="profile.marketing_insights" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="bg-gradient-to-r from-orange-500 to-red-600 px-6 py-4">
                                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    Marketing Insights
                                </h2>
                            </div>
                            <div class="p-5 space-y-4">
                                <div v-if="profile.marketing_insights.best_approach">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1 block">Eng yaxshi yondashuv</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ profile.marketing_insights.best_approach }}</p>
                                </div>

                                <div v-if="profile.marketing_insights.messaging_tips?.length">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Xabar Maslahatlar</label>
                                    <ul class="space-y-1">
                                        <li v-for="tip in profile.marketing_insights.messaging_tips" :key="tip" class="text-xs text-gray-800 dark:text-gray-200">
                                            • {{ tip }}
                                        </li>
                                    </ul>
                                </div>

                                <div v-if="profile.marketing_insights.content_ideas?.length">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Kontent G'oyalari</label>
                                    <ul class="space-y-1">
                                        <li v-for="idea in profile.marketing_insights.content_ideas" :key="idea" class="text-xs text-gray-800 dark:text-gray-200">
                                            • {{ idea }}
                                        </li>
                                    </ul>
                                </div>

                                <div v-if="profile.marketing_insights.offer_suggestions?.length">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2 block">Taklif Tavsiyalari</label>
                                    <ul class="space-y-1">
                                        <li v-for="offer in profile.marketing_insights.offer_suggestions" :key="offer" class="text-xs text-gray-800 dark:text-gray-200">
                                            • {{ offer }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- AI Tools -->
                        <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
                            <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                                AI Marketing Tools
                            </h2>
                            <div class="space-y-3">
                                <button
                                    @click="generateContentIdeas"
                                    :disabled="generatingContent"
                                    class="w-full px-4 py-3.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-xl transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                                >
                                    <svg v-if="generatingContent" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>{{ generatingContent ? 'Yaratilmoqda...' : 'Kontent G\'oyalari' }}</span>
                                </button>

                                <div>
                                    <input
                                        v-model="productForAd"
                                        type="text"
                                        placeholder="Mahsulot/Xizmat nomi"
                                        class="w-full px-4 py-3 mb-2 bg-white/20 backdrop-blur-sm border border-white/30 rounded-xl text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/50"
                                    />
                                    <button
                                        @click="generateAdCopy"
                                        :disabled="generatingAds || !productForAd.trim()"
                                        class="w-full px-4 py-3.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white font-medium rounded-xl transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                                    >
                                        <svg v-if="generatingAds" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                        </svg>
                                        <span>{{ generatingAds ? 'Yaratilmoqda...' : 'Reklama Matni' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generated Content Ideas -->
                <div v-if="contentIdeas?.length" class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Kontent G'oyalari
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="(idea, index) in contentIdeas"
                                :key="index"
                                class="p-5 bg-gray-50 dark:bg-gray-700/50 border-2 border-gray-200 dark:border-gray-600 rounded-xl hover:border-indigo-300 dark:hover:border-indigo-500 transition-colors"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300 text-xs font-semibold rounded-lg">{{ idea.type }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ idea.platform }}</span>
                                </div>
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2">{{ idea.title }}</h3>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">{{ idea.key_message }}</p>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold">CTA: {{ idea.cta }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generated Ad Copy -->
                <div v-if="adCopy?.length" class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-4">
                        <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                            Reklama Matnlari - {{ productForAd }}
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div
                            v-for="(ad, index) in adCopy"
                            :key="index"
                            class="p-6 bg-gray-50 dark:bg-gray-700/50 border-2 border-purple-200 dark:border-purple-700 rounded-xl hover:border-purple-400 dark:hover:border-purple-500 transition-colors"
                        >
                            <span class="inline-block px-3 py-1 bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-300 text-xs font-semibold rounded-lg mb-3">
                                {{ ad.type }}
                            </span>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">{{ ad.headline }}</h3>
                            <p class="text-gray-700 dark:text-gray-300 mb-3">{{ ad.body }}</p>
                            <p class="text-purple-600 dark:text-purple-400 font-semibold">{{ ad.cta }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-80 transition-opacity" @click="showDeleteModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
                        <div class="p-6">
                            <div class="w-16 h-16 mx-auto mb-4 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">O'chirishni tasdiqlash</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ dreamBuyer.name }}</span> nomli Ideal Mijozni o'chirishni xohlaysizmi? Bu amalni qaytarib bo'lmaydi.
                            </p>
                            <div class="flex gap-3">
                                <button
                                    @click="showDeleteModal = false"
                                    class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                >
                                    Bekor qilish
                                </button>
                                <button
                                    @click="deleteBuyer"
                                    class="flex-1 px-4 py-3 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 transition-colors"
                                >
                                    O'chirish
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </BusinessLayout>
</template>
