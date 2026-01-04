<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

const props = defineProps({
    dreamBuyers: Array,
});

const deletingBuyer = ref(null);

const getPriorityColor = (priority) => {
    const colors = {
        high: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800',
        medium: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800',
        low: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800',
    };
    return colors[priority] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600';
};

const setPrimary = (dreamBuyer) => {
    router.post(route('business.dream-buyer.set-primary', dreamBuyer.id), {}, {
        preserveScroll: true,
    });
};

const confirmDelete = (dreamBuyer) => {
    deletingBuyer.value = dreamBuyer;
};

const deleteBuyer = () => {
    if (deletingBuyer.value) {
        router.delete(route('business.dream-buyer.destroy', deletingBuyer.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                deletingBuyer.value = null;
            },
        });
    }
};

const cancelDelete = () => {
    deletingBuyer.value = null;
};
</script>

<template>
    <BusinessLayout title="Ideal Mijozlar">
        <Head title="Ideal Mijozlar" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Ideal Mijozlar</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">"Sell Like Crazy" metodologiyasi asosida ideal mijoz profilini yarating</p>
                    </div>
                </div>
                <Link
                    :href="route('business.dream-buyer.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-medium rounded-xl shadow-lg shadow-indigo-500/25 transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Yangi Ideal Mijoz
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="!dreamBuyers || dreamBuyers.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-12 text-center">
                    <!-- Animated Icon -->
                    <div class="relative w-32 h-32 mx-auto mb-8">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 to-purple-600/20 rounded-full animate-pulse"></div>
                        <div class="absolute inset-4 bg-gradient-to-br from-indigo-500/30 to-purple-600/30 rounded-full"></div>
                        <div class="absolute inset-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                    </div>

                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">Ideal Mijoz Profilini Yarating</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-lg mx-auto">
                        9 ta savol orqali mijozlaringizni chuqur tushuning. AI yordamida to'liq psixologik portret va marketing tavsiyalari oling.
                    </p>

                    <!-- Features -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 max-w-3xl mx-auto">
                        <div class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-xl p-4 border border-red-100 dark:border-red-800">
                            <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Pain Points</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Mijozlarning og'riq nuqtalari va muammolari</p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border border-green-100 dark:border-green-800">
                            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Orzular</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Maqsadlari, istaklari va orzulari</p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">AI Tahlil</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Avtomatik marketing tavsiyalari</p>
                        </div>
                    </div>

                    <Link
                        :href="route('business.dream-buyer.create')"
                        class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-500/25 transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ideal Mijoz Yaratish
                    </Link>
                </div>
            </div>

            <!-- Ideal Mijozlar Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="buyer in dreamBuyers"
                    :key="buyer.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 group relative"
                >
                    <!-- Primary Badge -->
                    <div v-if="buyer.is_primary" class="absolute top-4 right-4 z-10">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-yellow-400 text-yellow-900 shadow-md">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            Primary
                        </span>
                    </div>

                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-6 text-white relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                        <div class="relative flex items-center gap-3">
                            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xl font-bold truncate">{{ buyer.name }}</h3>
                                <p v-if="buyer.data?.tagline" class="text-indigo-100 text-sm truncate">{{ buyer.data.tagline }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-6">
                        <!-- Description -->
                        <p v-if="buyer.description" class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                            {{ buyer.description }}
                        </p>

                        <!-- Priority Badge -->
                        <div v-if="buyer.priority" class="mb-4">
                            <span
                                :class="getPriorityColor(buyer.priority)"
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border"
                            >
                                {{ buyer.priority === 'high' ? 'Yuqori' : buyer.priority === 'medium' ? "O'rta" : 'Past' }} prioritet
                            </span>
                        </div>

                        <!-- AI Profile Indicator -->
                        <div v-if="buyer.data?.avatar_name" class="flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 mb-4 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg px-3 py-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                            <span class="font-medium">AI-Generated Profile</span>
                        </div>

                        <!-- Key Stats -->
                        <div v-if="buyer.data" class="grid grid-cols-2 gap-2 mb-4">
                            <div v-if="buyer.data.pain_points?.length" class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 border border-red-100 dark:border-red-800">
                                <div class="text-red-600 dark:text-red-400 font-medium text-xs mb-1">Pain Points</div>
                                <div class="text-red-800 dark:text-red-300 text-xl font-bold">{{ buyer.data.pain_points.length }}</div>
                            </div>
                            <div v-if="buyer.data.goals_dreams?.length" class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-100 dark:border-green-800">
                                <div class="text-green-600 dark:text-green-400 font-medium text-xs mb-1">Maqsadlar</div>
                                <div class="text-green-800 dark:text-green-300 text-xl font-bold">{{ buyer.data.goals_dreams.length }}</div>
                            </div>
                            <div v-if="buyer.data.fears_objections?.length" class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3 border border-amber-100 dark:border-amber-800">
                                <div class="text-amber-600 dark:text-amber-400 font-medium text-xs mb-1">Qo'rquvlar</div>
                                <div class="text-amber-800 dark:text-amber-300 text-xl font-bold">{{ buyer.data.fears_objections.length }}</div>
                            </div>
                            <div v-if="buyer.data.purchase_triggers?.length" class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 border border-blue-100 dark:border-blue-800">
                                <div class="text-blue-600 dark:text-blue-400 font-medium text-xs mb-1">Triggerlar</div>
                                <div class="text-blue-800 dark:text-blue-300 text-xl font-bold">{{ buyer.data.purchase_triggers.length }}</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <Link
                                :href="route('business.dream-buyer.show', buyer.id)"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-medium rounded-xl transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Ko'rish
                            </Link>
                            <Link
                                :href="route('business.dream-buyer.edit', buyer.id)"
                                class="inline-flex items-center justify-center gap-2 px-3 py-2.5 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </Link>
                            <button
                                @click="confirmDelete(buyer)"
                                class="inline-flex items-center justify-center gap-2 px-3 py-2.5 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 font-medium rounded-xl transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>

                        <!-- Set Primary Button -->
                        <button
                            v-if="!buyer.is_primary"
                            @click="setPrimary(buyer)"
                            class="w-full mt-3 inline-flex items-center justify-center gap-2 px-4 py-2.5 border-2 border-yellow-400 dark:border-yellow-500 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 font-medium rounded-xl transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            Primary qilish
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="deletingBuyer"
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
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Ideal Mijozni o'chirish</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Bu amalni qaytarib bo'lmaydi</p>
                            </div>
                        </div>

                        <p class="text-gray-700 dark:text-gray-300 mb-6">
                            <strong class="text-gray-900 dark:text-gray-100">{{ deletingBuyer.name }}</strong> nomli Ideal Mijozni o'chirishni xohlaysizmi?
                        </p>

                        <div class="flex gap-3">
                            <button
                                @click="cancelDelete"
                                class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                            >
                                Bekor qilish
                            </button>
                            <button
                                @click="deleteBuyer"
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
