<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import { SparklesIcon, DocumentTextIcon, MegaphoneIcon, StarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    dreamBuyer: Object,
});

const parseField = (text) => {
    if (!text) return [];
    return text.split('\n').filter(item => item.trim());
};

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
        const response = await fetch(route('marketing.dream-buyer.content-ideas', props.dreamBuyer.id), {
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
        const response = await fetch(route('marketing.dream-buyer.ad-copy', props.dreamBuyer.id), {
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
    router.post(route('marketing.dream-buyer.set-primary', props.dreamBuyer.id));
};
</script>

<template>
    <MarketingLayout title="Ideal Mijoz">
        <Head :title="dreamBuyer.name" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">{{ dreamBuyer.name }}</h1>
                            <p v-if="dreamBuyer.description" class="text-indigo-100 mt-1">{{ dreamBuyer.description }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button
                            v-if="!dreamBuyer.is_primary"
                            @click="setPrimary"
                            class="flex items-center gap-2 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl transition-all"
                        >
                            <StarIcon class="w-5 h-5" />
                            Asosiy qilish
                        </button>
                        <span v-else class="flex items-center gap-2 px-4 py-2 bg-yellow-500/30 text-yellow-100 rounded-xl">
                            <StarIcon class="w-5 h-5" />
                            Asosiy Mijoz
                        </span>
                        <Link :href="route('marketing.dream-buyer.edit', dreamBuyer.id)" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl transition-all">
                            Tahrirlash
                        </Link>
                        <Link :href="route('marketing.dream-buyer.index')" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl transition-all">
                            Orqaga
                        </Link>
                    </div>
                </div>
            </div>

            <!-- AI Tools -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-2xl border border-purple-200 dark:border-purple-700 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center">
                        <SparklesIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">AI Yordamchi</h2>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button
                        @click="generateContentIdeas"
                        :disabled="generatingContentIdeas"
                        class="flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition-all disabled:opacity-50"
                    >
                        <DocumentTextIcon class="w-5 h-5" />
                        <span v-if="generatingContentIdeas">Generatsiya...</span>
                        <span v-else>Kontent g'oyalari</span>
                    </button>
                    <button
                        @click="showAdCopyModal = true"
                        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all"
                    >
                        <MegaphoneIcon class="w-5 h-5" />
                        Reklama matni yaratish
                    </button>
                </div>
            </div>

            <!-- Generated Content Ideas -->
            <div v-if="contentIdeas.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <DocumentTextIcon class="w-5 h-5 text-purple-600" />
                    Kontent G'oyalari
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
                    Reklama Matni
                </h3>
                <div class="space-y-4">
                    <div v-if="adCopy.headline" class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl">
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium mb-1">Sarlavha</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ adCopy.headline }}</p>
                    </div>
                    <div v-if="adCopy.body" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <p class="text-xs text-gray-600 dark:text-gray-400 font-medium mb-1">Asosiy matn</p>
                        <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ adCopy.body }}</p>
                    </div>
                    <div v-if="adCopy.cta" class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                        <p class="text-xs text-green-600 dark:text-green-400 font-medium mb-1">CTA (Call to Action)</p>
                        <p class="text-gray-800 dark:text-gray-200 font-semibold">{{ adCopy.cta }}</p>
                    </div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Where -->
                <div v-if="dreamBuyer.where_spend_time" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-emerald-500 to-teal-600">
                        <div class="flex items-center gap-3 text-white">
                            <span class="text-2xl">📍</span>
                            <h3 class="font-bold">Qayerda vaqt o'tkazadi</h3>
                        </div>
                    </div>
                    <div class="p-5">
                        <div class="flex flex-wrap gap-2">
                            <span v-for="item in parseField(dreamBuyer.where_spend_time)" :key="item" class="px-3 py-1.5 rounded-full text-sm bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300">
                                {{ item }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Frustrations -->
                <div v-if="dreamBuyer.frustrations" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-red-500 to-rose-600">
                        <div class="flex items-center gap-3 text-white">
                            <span class="text-2xl">😤</span>
                            <h3 class="font-bold">Muammolari</h3>
                        </div>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-2">
                            <li v-for="item in parseField(dreamBuyer.frustrations)" :key="item" class="flex items-start gap-3 p-3 rounded-xl bg-red-50 dark:bg-red-900/20">
                                <span class="w-2 h-2 mt-2 rounded-full bg-red-500"></span>
                                <span class="text-gray-800 dark:text-gray-200 text-sm">{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Dreams -->
                <div v-if="dreamBuyer.dreams" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-green-500 to-emerald-600">
                        <div class="flex items-center gap-3 text-white">
                            <span class="text-2xl">✨</span>
                            <h3 class="font-bold">Orzulari</h3>
                        </div>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-2">
                            <li v-for="item in parseField(dreamBuyer.dreams)" :key="item" class="flex items-start gap-3 p-3 rounded-xl bg-green-50 dark:bg-green-900/20">
                                <span class="w-2 h-2 mt-2 rounded-full bg-green-500"></span>
                                <span class="text-gray-800 dark:text-gray-200 text-sm">{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Fears -->
                <div v-if="dreamBuyer.fears" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 bg-gradient-to-r from-amber-500 to-orange-600">
                        <div class="flex items-center gap-3 text-white">
                            <span class="text-2xl">😰</span>
                            <h3 class="font-bold">Qo'rquvlari</h3>
                        </div>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-2">
                            <li v-for="item in parseField(dreamBuyer.fears)" :key="item" class="flex items-start gap-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20">
                                <span class="w-2 h-2 mt-2 rounded-full bg-amber-500"></span>
                                <span class="text-gray-800 dark:text-gray-200 text-sm">{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ad Copy Modal -->
        <Teleport to="body">
            <div v-if="showAdCopyModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Reklama matni yaratish</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Mahsulot/Xizmat nomi
                            </label>
                            <input
                                v-model="adCopyProduct"
                                type="text"
                                placeholder="Masalan: Online kurs, Konsultatsiya..."
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div class="flex gap-3 justify-end">
                            <button
                                @click="showAdCopyModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all"
                            >
                                Bekor qilish
                            </button>
                            <button
                                @click="generateAdCopy"
                                :disabled="generatingAdCopy || !adCopyProduct.trim()"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all disabled:opacity-50"
                            >
                                <span v-if="generatingAdCopy">Generatsiya...</span>
                                <span v-else>Yaratish</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </MarketingLayout>
</template>
