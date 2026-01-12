<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';

const props = defineProps({
    survey: Object,
});

const copiedLink = ref(false);

const completedCount = computed(() => {
    return props.survey.completed_responses_count || 0;
});

const totalCount = computed(() => {
    return props.survey.responses_count || 0;
});

const completionRate = computed(() => {
    if (!totalCount.value) return 0;
    return Math.round((completedCount.value / totalCount.value) * 100);
});

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800',
        draft: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800',
        paused: 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800',
        closed: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800',
    };
    return colors[status] || colors.draft;
};

const getStatusText = (status) => {
    const texts = {
        active: 'Faol',
        draft: 'Qoralama',
        paused: 'To\'xtatilgan',
        closed: 'Yopilgan',
    };
    return texts[status] || status;
};

const toggleStatus = () => {
    router.post(route('marketing.custdev.toggle-status', { custdev: props.survey.id }), {}, {
        preserveScroll: true,
    });
};

const copyLink = () => {
    const link = `${window.location.origin}/s/${props.survey.slug}`;
    navigator.clipboard.writeText(link);
    copiedLink.value = true;
    setTimeout(() => {
        copiedLink.value = false;
    }, 2000);
};

const getCategoryColor = (category) => {
    const colors = {
        where_spend_time: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        info_sources: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
        frustrations: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        dreams: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        fears: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
        satisfaction: 'bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300',
        custom: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    };
    return colors[category] || colors.custom;
};

const getCategoryLabel = (category) => {
    const labels = {
        where_spend_time: 'Vaqt o\'tkazish',
        info_sources: 'Ma\'lumot manbalari',
        frustrations: 'Muammolar',
        dreams: 'Orzular',
        fears: 'Qo\'rquvlar',
        satisfaction: 'Qoniqish',
        custom: 'Maxsus',
    };
    return labels[category] || category;
};

const getTypeLabel = (type) => {
    const labels = {
        text: 'Qisqa matn',
        textarea: 'Uzun matn',
        select: 'Bir tanlov',
        multiselect: 'Ko\'p tanlov',
        rating: 'Reyting',
        scale: 'Shkala',
    };
    return labels[type] || type;
};
</script>

<template>
    <MarketingLayout :title="survey.title">
        <Head :title="survey.title" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('marketing.custdev.index')"
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
                    >
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ survey.title }}</h1>
                            <span :class="getStatusColor(survey.status)" class="px-3 py-1 rounded-full text-xs font-medium border">
                                {{ getStatusText(survey.status) }}
                            </span>
                        </div>
                        <p v-if="survey.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ survey.description }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="copyLink"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                    >
                        <svg v-if="!copiedLink" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <svg v-else class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ copiedLink ? 'Nusxalandi!' : 'Link nusxalash' }}
                    </button>
                    <button
                        @click="toggleStatus"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                    >
                        <svg v-if="survey.status === 'active'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ survey.status === 'active' ? 'To\'xtatish' : 'Faollashtirish' }}
                    </button>
                    <Link
                        :href="route('marketing.custdev.edit', { custdev: survey.id })"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Tahrirlash
                    </Link>
                    <Link
                        :href="route('marketing.custdev.results', { custdev: survey.id })"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/25 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Natijalar
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami javoblar</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ totalCount }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tugallangan</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ completedCount }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tugallash %</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ completionRate }}%</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Savollar</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ survey.questions?.length || 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions List -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Savollar</h2>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div
                        v-for="(question, index) in survey.questions"
                        :key="question.id"
                        class="p-6 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                    >
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-700 dark:text-emerald-300 font-bold flex-shrink-0">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ question.question }}
                                    <span v-if="question.is_required" class="text-red-500 ml-1">*</span>
                                </p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span :class="getCategoryColor(question.category)" class="px-2 py-1 rounded-lg text-xs font-medium">
                                        {{ getCategoryLabel(question.category) }}
                                    </span>
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400">
                                        {{ getTypeLabel(question.type) }}
                                    </span>
                                </div>
                                <div v-if="question.options?.length" class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="(option, optIndex) in question.options"
                                        :key="optIndex"
                                        class="inline-flex items-center px-3 py-1 bg-gray-50 dark:bg-gray-900/50 rounded-lg text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700"
                                    >
                                        {{ option }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!survey.questions?.length" class="p-12 text-center text-gray-500 dark:text-gray-400">
                        Savollar mavjud emas
                    </div>
                </div>
            </div>

            <!-- Survey Info -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Messages -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Xabarlar</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Kutib olish xabari</p>
                            <p class="text-gray-900 dark:text-gray-100">{{ survey.welcome_message || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Rahmat xabari</p>
                            <p class="text-gray-900 dark:text-gray-100">{{ survey.thank_you_message || '-' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Sozlamalar</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Kontakt yig'ish</span>
                            <span :class="survey.collect_contact ? 'text-green-600' : 'text-gray-400'">
                                {{ survey.collect_contact ? 'Ha' : 'Yo\'q' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Javoblar limiti</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ survey.response_limit || 'Cheksiz' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Muddati</span>
                            <span class="text-gray-900 dark:text-gray-100">{{ survey.expires_at || 'Cheksiz' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Tema rangi</span>
                            <div class="w-6 h-6 rounded-lg" :style="{ backgroundColor: survey.theme_color || '#10B981' }"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>
