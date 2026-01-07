<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

const props = defineProps({
    surveys: Array,
});

const deletingSurvey = ref(null);
const copiedSlug = ref(null);

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        inactive: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
        draft: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
        completed: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
    };
    return colors[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400';
};

const getStatusText = (status) => {
    const texts = {
        active: 'Faol',
        inactive: 'Nofaol',
        draft: 'Qoralama',
        completed: 'Tugallangan',
    };
    return texts[status] || status;
};

const toggleStatus = (survey) => {
    router.post(route('business.custdev.toggle-status', { custdev: survey.id }), {}, {
        preserveScroll: true,
    });
};

const copyLink = (survey) => {
    const link = `${window.location.origin}/s/${survey.slug}`;
    navigator.clipboard.writeText(link);
    copiedSlug.value = survey.slug;
    setTimeout(() => {
        copiedSlug.value = null;
    }, 2000);
};

const confirmDelete = (survey) => {
    deletingSurvey.value = survey;
};

const deleteSurvey = () => {
    if (deletingSurvey.value) {
        router.delete(route('business.custdev.destroy', { custdev: deletingSurvey.value.id }), {
            preserveScroll: true,
            onSuccess: () => {
                deletingSurvey.value = null;
            },
        });
    }
};

const cancelDelete = () => {
    deletingSurvey.value = null;
};
</script>

<template>
    <BusinessLayout title="CustDev So'rovnomalar">
        <Head title="CustDev So'rovnomalar" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/25">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">CustDev So'rovnomalar</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Mijozlaringiz haqida real ma'lumotlar to'plang</p>
                    </div>
                </div>
                <Link
                    :href="route('business.custdev.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/25 transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Yangi So'rovnoma
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="!surveys || surveys.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-12 text-center">
                    <!-- Animated Icon -->
                    <div class="relative w-32 h-32 mx-auto mb-8">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/20 to-teal-600/20 rounded-full animate-pulse"></div>
                        <div class="absolute inset-4 bg-gradient-to-br from-emerald-500/30 to-teal-600/30 rounded-full"></div>
                        <div class="absolute inset-8 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                    </div>

                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">CustDev So'rovnomasi Yarating</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-lg mx-auto">
                        Mijozlaringizdan real ma'lumotlar to'plang. Professional savollar tayyor - faqat link ulashing!
                    </p>

                    <!-- Features -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 max-w-3xl mx-auto">
                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-800">
                            <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Oson Link</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Link yaratib ulashing, javoblar avtomatik to'planadi</p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Professional</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">CustDev metodologiyasi asosida savollar</p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-4 border border-purple-100 dark:border-purple-800">
                            <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Tahlil</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Javoblar avtomatik tahlil qilinadi</p>
                        </div>
                    </div>

                    <Link
                        :href="route('business.custdev.create')"
                        class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/25 transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        So'rovnoma Yaratish
                    </Link>
                </div>
            </div>

            <!-- Surveys Table -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
                <!-- Desktop Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    So'rovnoma
                                </th>
                                <th class="px-4 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Holat
                                </th>
                                <th class="px-4 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Savollar
                                </th>
                                <th class="px-4 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Javoblar
                                </th>
                                <th class="px-4 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Tugallangan
                                </th>
                                <th class="px-4 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Link
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Amallar
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="survey in surveys"
                                :key="survey.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            >
                                <!-- Survey Name -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white flex-shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 truncate max-w-xs">{{ survey.title }}</h3>
                                            <p v-if="survey.description" class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ survey.description }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-4 text-center">
                                    <button
                                        @click="toggleStatus(survey)"
                                        :class="getStatusColor(survey.status)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition-all hover:ring-2 hover:ring-offset-2 hover:ring-emerald-500 dark:hover:ring-offset-gray-800"
                                        :title="survey.status === 'active' ? 'Toxtatish' : 'Faollashtirish'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="survey.status === 'active' ? 'bg-green-500' : 'bg-gray-400'"></span>
                                        {{ getStatusText(survey.status) }}
                                    </button>
                                </td>

                                <!-- Questions Count -->
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2.5rem] px-3 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg text-sm font-semibold">
                                        {{ survey.questions_count || 0 }}
                                    </span>
                                </td>

                                <!-- Responses Count -->
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2.5rem] px-3 py-1 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-lg text-sm font-semibold">
                                        {{ survey.responses_count || 0 }}
                                    </span>
                                </td>

                                <!-- Completed Count -->
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2.5rem] px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-semibold">
                                        {{ survey.completed_responses_count || 0 }}
                                    </span>
                                </td>

                                <!-- Link -->
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <code class="text-xs text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md truncate max-w-[120px]">/s/{{ survey.slug }}</code>
                                        <button
                                            @click="copyLink(survey)"
                                            class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors group relative"
                                            :title="copiedSlug === survey.slug ? 'Nusxalandi!' : 'Linkni nusxalash'"
                                        >
                                            <svg v-if="copiedSlug !== survey.slug" class="w-4 h-4 text-gray-500 group-hover:text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <svg v-else class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- View Results -->
                                        <Link
                                            :href="route('business.custdev.results', { custdev: survey.id })"
                                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-colors"
                                            title="Natijalarni ko'rish"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                            <span class="hidden lg:inline">Natijalar</span>
                                        </Link>

                                        <!-- Edit -->
                                        <Link
                                            :href="route('business.custdev.edit', { custdev: survey.id })"
                                            class="inline-flex items-center justify-center p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                            title="Tahrirlash"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </Link>

                                        <!-- Delete -->
                                        <button
                                            @click="confirmDelete(survey)"
                                            class="inline-flex items-center justify-center p-2 text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                            title="O'chirish"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Stats Footer -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Jami <span class="font-semibold text-gray-900 dark:text-gray-100">{{ surveys.length }}</span> ta so'rovnoma
                        </p>
                        <div class="flex items-center gap-6 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                <span class="text-gray-600 dark:text-gray-400">Faol: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ surveys.filter(s => s.status === 'active').length }}</span></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                                <span class="text-gray-600 dark:text-gray-400">Nofaol: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ surveys.filter(s => s.status !== 'active').length }}</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="deletingSurvey"
                class="fixed inset-0 z-50 overflow-y-auto"
            >
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="cancelDelete"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">So'rovnomani o'chirish</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Bu amalni qaytarib bo'lmaydi</p>
                            </div>
                        </div>

                        <p class="text-gray-700 dark:text-gray-300 mb-6">
                            <strong class="text-gray-900 dark:text-gray-100">{{ deletingSurvey.title }}</strong> nomli so'rovnomani va uning barcha javoblarini o'chirishni xohlaysizmi?
                        </p>

                        <div class="flex gap-3">
                            <button
                                @click="cancelDelete"
                                class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                            >
                                Bekor qilish
                            </button>
                            <button
                                @click="deleteSurvey"
                                class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors"
                            >
                                O'chirish
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </BusinessLayout>
</template>
