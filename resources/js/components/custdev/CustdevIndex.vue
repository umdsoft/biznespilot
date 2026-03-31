<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    surveys: Array,
    panelType: {
        type: String,
        required: true,
        validator: (value) => ['business', 'marketing'].includes(value),
    },
});

const getRoute = (name, params = null) => {
    const prefix = props.panelType === 'business' ? 'business.' : 'marketing.';
    return params ? route(prefix + name, params) : route(prefix + name);
};

const deletingSurvey = ref(null);
const copiedSlug = ref(null);

const statusMap = {
    active:    { label: 'Faol',        dot: 'bg-emerald-500', bg: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400' },
    inactive:  { label: 'Nofaol',      dot: 'bg-gray-400',    bg: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' },
    draft:     { label: 'Qoralama',    dot: 'bg-amber-400',   bg: 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400' },
    completed: { label: 'Tugallangan', dot: 'bg-blue-500',    bg: 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400' },
    paused:    { label: "To'xtatilgan",dot: 'bg-amber-400',   bg: 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400' },
    closed:    { label: 'Yopilgan',    dot: 'bg-red-400',     bg: 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' },
};

const getStatus = (s) => statusMap[s] || statusMap.inactive;

const toggleStatus = (survey) => {
    router.post(getRoute('custdev.toggle-status', { custdev: survey.id }), {}, { preserveScroll: true });
};

const copyLink = (survey) => {
    navigator.clipboard.writeText(`${window.location.origin}/s/${survey.slug}`);
    copiedSlug.value = survey.slug;
    setTimeout(() => copiedSlug.value = null, 2000);
};

const confirmDelete = (survey) => deletingSurvey.value = survey;
const cancelDelete = () => deletingSurvey.value = null;

const deleteSurvey = () => {
    if (deletingSurvey.value) {
        router.delete(getRoute('custdev.destroy', { custdev: deletingSurvey.value.id }), {
            preserveScroll: true,
            onSuccess: () => deletingSurvey.value = null,
        });
    }
};

const activeSurveysCount = computed(() => props.surveys?.filter(s => s.status === 'active').length || 0);
const totalResponses = computed(() => props.surveys?.reduce((sum, s) => sum + (s.responses_count || 0), 0) || 0);
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">So'rovnomalar</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Mijozlaringiz haqida real ma'lumotlar to'plang</p>
            </div>
            <Link
                :href="getRoute('custdev.create')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Yangi so'rovnoma
            </Link>
        </div>

        <!-- Stats (only when surveys exist) -->
        <div v-if="surveys && surveys.length > 0" class="grid grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3">
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ surveys.length }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Jami</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3">
                <p class="text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ activeSurveysCount }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Faol</p>
            </div>
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3">
                <p class="text-2xl font-semibold text-blue-600 dark:text-blue-400">{{ totalResponses }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Javoblar</p>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!surveys || surveys.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
            <div class="py-12 px-6 text-center">
                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-1">So'rovnoma yo'q</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto">
                    CustDev so'rovnomasi yarating va mijozlaringizdan real fikr-mulohazalar to'plang.
                </p>
                <Link
                    :href="getRoute('custdev.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    So'rovnoma yaratish
                </Link>
            </div>
        </div>

        <!-- CustDev haqida -->
        <div v-if="!surveys || surveys.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">CustDev nima va nima uchun kerak?</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                <strong>Customer Development (CustDev)</strong> — bu mijozlaringiz bilan tizimli suhbat o'tkazib, ularning haqiqiy muammolari, ehtiyojlari va xulq-atvorini o'rganish metodologiyasi. Oddiy so'rovnomadan farqi — CustDev ochiq savollar orqali chuqur tushunchalar beradi.
            </p>
            <div class="grid sm:grid-cols-3 gap-3">
                <div class="flex gap-2.5">
                    <div class="w-7 h-7 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-900 dark:text-gray-100">Muammoni aniqlang</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Mijozlar nimadan norozi? Qaysi muammo eng kuchli?</p>
                    </div>
                </div>
                <div class="flex gap-2.5">
                    <div class="w-7 h-7 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-900 dark:text-gray-100">G'oyani tekshiring</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Yangi mahsulot yoki xizmat uchun talab bormi?</p>
                    </div>
                </div>
                <div class="flex gap-2.5">
                    <div class="w-7 h-7 bg-purple-50 dark:bg-purple-900/20 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                        <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-900 dark:text-gray-100">Qaror qabul qiling</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Ma'lumotlarga asoslangan biznes qarorlar chiqaring.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Surveys List -->
        <div v-else class="space-y-3">
            <div
                v-for="survey in surveys"
                :key="survey.id"
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
            >
                <div class="flex items-center gap-4">
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2.5 mb-1">
                            <Link :href="getRoute('custdev.show', { custdev: survey.id })" class="font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 truncate transition-colors">
                                {{ survey.title }}
                            </Link>
                            <button @click="toggleStatus(survey)" :class="getStatus(survey.status).bg" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium flex-shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full" :class="getStatus(survey.status).dot"></span>
                                {{ getStatus(survey.status).label }}
                            </button>
                        </div>
                        <p v-if="survey.description" class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ survey.description }}</p>
                    </div>

                    <!-- Metrics -->
                    <div class="hidden sm:flex items-center gap-5 flex-shrink-0 text-center">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ survey.questions_count || survey.questions?.length || 0 }}</p>
                            <p class="text-[11px] text-gray-400">savol</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ survey.responses_count || 0 }}</p>
                            <p class="text-[11px] text-gray-400">javob</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ survey.completed_responses_count || 0 }}</p>
                            <p class="text-[11px] text-gray-400">tugallangan</p>
                        </div>
                    </div>

                    <!-- Link copy -->
                    <button
                        @click="copyLink(survey)"
                        class="hidden md:flex items-center gap-1.5 px-2.5 py-1.5 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors flex-shrink-0"
                    >
                        <svg v-if="copiedSlug !== survey.slug" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        <svg v-else class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        {{ copiedSlug === survey.slug ? 'Nusxalandi' : 'Link' }}
                    </button>

                    <!-- Actions -->
                    <div class="flex items-center gap-0.5 flex-shrink-0">
                        <Link
                            :href="getRoute('custdev.results', { custdev: survey.id })"
                            class="p-2 text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            title="Natijalar"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                        </Link>
                        <Link
                            :href="getRoute('custdev.edit', { custdev: survey.id })"
                            class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            title="Tahrirlash"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </Link>
                        <button
                            @click="confirmDelete(survey)"
                            class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            title="O'chirish"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <Teleport to="body">
        <div v-if="deletingSurvey" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/40" @click="cancelDelete"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-5 border border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">O'chirishni tasdiqlang</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
                    <strong>{{ deletingSurvey.title }}</strong> va uning barcha javoblari o'chiriladi. Bu amalni qaytarib bo'lmaydi.
                </p>
                <div class="flex gap-2.5">
                    <button @click="cancelDelete" class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                        Bekor qilish
                    </button>
                    <button @click="deleteSurvey" class="flex-1 px-3 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                        O'chirish
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
