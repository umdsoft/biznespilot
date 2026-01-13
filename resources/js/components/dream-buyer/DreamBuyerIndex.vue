<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    dreamBuyers: Array,
    panelType: {
        type: String,
        required: true,
        validator: (value) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(value),
    },
});

// Route helpers based on panel type
const getRoute = (name, params = null) => {
    const prefix = props.panelType + '.';
    return params ? route(prefix + name, params) : route(prefix + name);
};

// Check if panel is read-only (operator and saleshead can only view)
const isReadOnly = computed(() => ['operator', 'saleshead'].includes(props.panelType));

const deletingBuyer = ref(null);
const selectedBuyerId = ref(null);

const initializeSelection = () => {
    if (!props.dreamBuyers?.length) return null;
    const primary = props.dreamBuyers.find(b => b.is_primary);
    return primary?.id || props.dreamBuyers[0]?.id;
};
selectedBuyerId.value = initializeSelection();

const selectedBuyer = computed(() => {
    return props.dreamBuyers?.find(b => b.id === selectedBuyerId.value) || null;
});

const parseField = (text) => {
    if (!text) return [];
    return text.split('\n').filter(item => item.trim());
};

const whereSpendTime = computed(() => parseField(selectedBuyer.value?.where_spend_time));
const infoSources = computed(() => parseField(selectedBuyer.value?.info_sources));
const frustrations = computed(() => parseField(selectedBuyer.value?.frustrations));
const dreams = computed(() => parseField(selectedBuyer.value?.dreams));
const fears = computed(() => parseField(selectedBuyer.value?.fears));
const communicationPrefs = computed(() => parseField(selectedBuyer.value?.communication_preferences));
const languageStyle = computed(() => parseField(selectedBuyer.value?.language_style));
const dailyRoutine = computed(() => parseField(selectedBuyer.value?.daily_routine));
const happinessTriggers = computed(() => parseField(selectedBuyer.value?.happiness_triggers));

const hasData = computed(() => {
    return whereSpendTime.value.length > 0 ||
           frustrations.value.length > 0 ||
           dreams.value.length > 0 ||
           fears.value.length > 0;
});

const setPrimary = (dreamBuyer) => {
    router.post(getRoute('dream-buyer.set-primary', dreamBuyer.id), {}, {
        preserveScroll: true,
    });
};

const confirmDelete = (dreamBuyer) => {
    deletingBuyer.value = dreamBuyer;
};

const deleteBuyer = () => {
    if (deletingBuyer.value) {
        router.delete(getRoute('dream-buyer.destroy', deletingBuyer.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                deletingBuyer.value = null;
                if (selectedBuyerId.value === deletingBuyer.value?.id) {
                    selectedBuyerId.value = props.dreamBuyers?.[0]?.id || null;
                }
            },
        });
    }
};

const cancelDelete = () => {
    deletingBuyer.value = null;
};

const insightSections = [
    { key: 'whereSpendTime', title: 'Mijozlarimni qayerdan topaman?', subtitle: 'Qayerda vaqt o\'tkazadi', icon: '📍', color: 'blue', data: whereSpendTime, type: 'tags' },
    { key: 'infoSources', title: 'Qayerdan ma\'lumot oladi?', subtitle: 'Ma\'lumot manbalari', icon: '🔍', color: 'indigo', data: infoSources, type: 'tags' },
    { key: 'frustrations', title: 'Qanday muammolari bor?', subtitle: 'Frustratsiyalar va qiyinchiliklar', icon: '😤', color: 'red', data: frustrations, type: 'list' },
    { key: 'dreams', title: 'Nimani xohlaydi?', subtitle: 'Orzulari va maqsadlari', icon: '✨', color: 'green', data: dreams, type: 'list' },
    { key: 'fears', title: 'Nimadan qo\'rqadi?', subtitle: 'Qo\'rquvlar va e\'tirozlar', icon: '😰', color: 'amber', data: fears, type: 'list' },
    { key: 'communicationPrefs', title: 'Qanday muloqotni afzal ko\'radi?', subtitle: 'Kommunikatsiya usullari', icon: '💬', color: 'purple', data: communicationPrefs, type: 'tags' },
    { key: 'languageStyle', title: 'Qanday tilda gaplashadi?', subtitle: 'Til va jargon', icon: '🗣️', color: 'pink', data: languageStyle, type: 'tags' },
    { key: 'dailyRoutine', title: 'Kundalik hayoti qanday?', subtitle: 'Kunlik tartib', icon: '📅', color: 'cyan', data: dailyRoutine, type: 'list' },
    { key: 'happinessTriggers', title: 'Nima uni baxtli qiladi?', subtitle: 'Baxt omillari', icon: '😊', color: 'emerald', data: happinessTriggers, type: 'list' }
];

const getColorClasses = (color) => {
    const colors = {
        blue: { bg: 'bg-blue-500', bgLight: 'bg-blue-50 dark:bg-blue-900/20', text: 'text-blue-800 dark:text-blue-300', border: 'border-blue-100 dark:border-blue-800', gradient: 'from-blue-500 to-blue-600' },
        indigo: { bg: 'bg-indigo-500', bgLight: 'bg-indigo-50 dark:bg-indigo-900/20', text: 'text-indigo-800 dark:text-indigo-300', border: 'border-indigo-100 dark:border-indigo-800', gradient: 'from-indigo-500 to-indigo-600' },
        red: { bg: 'bg-red-500', bgLight: 'bg-red-50 dark:bg-red-900/20', text: 'text-red-800 dark:text-red-300', border: 'border-red-100 dark:border-red-800', gradient: 'from-red-500 to-red-600' },
        green: { bg: 'bg-green-500', bgLight: 'bg-green-50 dark:bg-green-900/20', text: 'text-green-800 dark:text-green-300', border: 'border-green-100 dark:border-green-800', gradient: 'from-green-500 to-green-600' },
        amber: { bg: 'bg-amber-500', bgLight: 'bg-amber-50 dark:bg-amber-900/20', text: 'text-amber-800 dark:text-amber-300', border: 'border-amber-100 dark:border-amber-800', gradient: 'from-amber-500 to-amber-600' },
        purple: { bg: 'bg-purple-500', bgLight: 'bg-purple-50 dark:bg-purple-900/20', text: 'text-purple-800 dark:text-purple-300', border: 'border-purple-100 dark:border-purple-800', gradient: 'from-purple-500 to-purple-600' },
        pink: { bg: 'bg-pink-500', bgLight: 'bg-pink-50 dark:bg-pink-900/20', text: 'text-pink-800 dark:text-pink-300', border: 'border-pink-100 dark:border-pink-800', gradient: 'from-pink-500 to-pink-600' },
        cyan: { bg: 'bg-cyan-500', bgLight: 'bg-cyan-50 dark:bg-cyan-900/20', text: 'text-cyan-800 dark:text-cyan-300', border: 'border-cyan-100 dark:border-cyan-800', gradient: 'from-cyan-500 to-cyan-600' },
        emerald: { bg: 'bg-emerald-500', bgLight: 'bg-emerald-50 dark:bg-emerald-900/20', text: 'text-emerald-800 dark:text-emerald-300', border: 'border-emerald-100 dark:border-emerald-800', gradient: 'from-emerald-500 to-emerald-600' },
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/25">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Ideal Mijoz</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Mijozlaringiz haqida barcha ma'lumotlar bir joyda</p>
                </div>
            </div>
            <Link
                v-if="!isReadOnly"
                :href="getRoute('dream-buyer.create')"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-medium rounded-xl shadow-lg shadow-indigo-500/25 transition-all"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Yangi Profil
            </Link>
        </div>

        <!-- Empty State -->
        <div v-if="!dreamBuyers || dreamBuyers.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-12 text-center">
                <div class="relative w-32 h-32 mx-auto mb-8">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 to-purple-600/20 rounded-full animate-pulse"></div>
                    <div class="absolute inset-4 bg-gradient-to-br from-indigo-500/30 to-purple-600/30 rounded-full"></div>
                    <div class="absolute inset-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                </div>

                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">Ideal Mijoz Profilini Yarating</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-lg mx-auto">
                    Mijozlaringizni yaxshi tushunish uchun profil yarating. Bu sizga marketing xabarlarini to'g'ri yo'naltirish va savdoni oshirishga yordam beradi.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 max-w-3xl mx-auto">
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800">
                        <div class="text-3xl mb-3">📍</div>
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Qayerdan Topaman?</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Mijozlaringiz qayerda vaqt o'tkazishini bilib oling</p>
                    </div>
                    <div class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-xl p-4 border border-red-100 dark:border-red-800">
                        <div class="text-3xl mb-3">😤</div>
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Muammolari Nima?</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Ularning og'riqli nuqtalarini aniqlang</p>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border border-green-100 dark:border-green-800">
                        <div class="text-3xl mb-3">✨</div>
                        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">Nimani Xohlaydi?</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Orzulari va maqsadlarini tushuning</p>
                    </div>
                </div>

                <Link
                    v-if="!isReadOnly"
                    :href="getRoute('dream-buyer.create')"
                    class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-500/25 transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Ideal Mijoz Yaratish
                </Link>
                <div v-else class="text-gray-500 dark:text-gray-400">
                    <p class="text-center">Hozircha Ideal Mijoz profillari yaratilmagan</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <template v-else>
            <!-- Profile Selector -->
            <div v-if="dreamBuyers.length > 1" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Profil tanlang:</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="buyer in dreamBuyers"
                        :key="buyer.id"
                        @click="selectedBuyerId = buyer.id"
                        :class="[
                            'px-4 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-2',
                            selectedBuyerId === buyer.id
                                ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-500/25'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                        ]"
                    >
                        {{ buyer.name }}
                        <svg v-if="buyer.is_primary" class="w-4 h-4 text-yellow-300" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Selected Buyer Header -->
            <div v-if="selectedBuyer" class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-2xl shadow-xl p-6 text-white">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-2xl font-bold">{{ selectedBuyer.name }}</h2>
                                <span v-if="selectedBuyer.is_primary" class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-400 text-yellow-900 rounded-full text-xs font-semibold">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                    </svg>
                                    Asosiy
                                </span>
                            </div>
                            <p v-if="selectedBuyer.description" class="text-indigo-100 mt-1">{{ selectedBuyer.description }}</p>
                        </div>
                    </div>

                    <div v-if="!isReadOnly" class="flex flex-wrap gap-2">
                        <Link
                            :href="getRoute('dream-buyer.edit', selectedBuyer.id)"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 rounded-xl transition-all text-sm font-medium"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Tahrirlash
                        </Link>
                        <button
                            v-if="!selectedBuyer.is_primary"
                            @click="setPrimary(selectedBuyer)"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-yellow-400 hover:bg-yellow-300 text-yellow-900 rounded-xl transition-all text-sm font-medium"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            Asosiy qilish
                        </button>
                        <button
                            @click="confirmDelete(selectedBuyer)"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-500/80 hover:bg-red-600 rounded-xl transition-all text-sm font-medium"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- No Data Warning -->
            <div v-if="!hasData && selectedBuyer" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-amber-800 dark:text-amber-300 mb-1">Profil to'ldirilmagan</h3>
                        <p class="text-amber-700 dark:text-amber-400 text-sm mb-3">
                            Ideal mijoz profilini to'ldiring - bu sizga marketing va savdoda katta yordam beradi.
                        </p>
                        <Link
                            v-if="!isReadOnly"
                            :href="getRoute('dream-buyer.edit', selectedBuyer.id)"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Profilni to'ldirish
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Insights Grid -->
            <div v-if="hasData" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template v-for="section in insightSections" :key="section.key">
                    <div
                        v-if="section.data.value.length > 0"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                    >
                        <div :class="['px-5 py-4 bg-gradient-to-r', getColorClasses(section.color).gradient]">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ section.icon }}</span>
                                <div>
                                    <h3 class="font-bold text-white">{{ section.title }}</h3>
                                    <p class="text-white/80 text-xs">{{ section.subtitle }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-5">
                            <div v-if="section.type === 'tags'" class="flex flex-wrap gap-2">
                                <span
                                    v-for="item in section.data.value"
                                    :key="item"
                                    :class="['px-3 py-1.5 rounded-full text-sm font-medium', getColorClasses(section.color).bgLight, getColorClasses(section.color).text]"
                                >
                                    {{ item }}
                                </span>
                            </div>
                            <ul v-else class="space-y-2">
                                <li
                                    v-for="item in section.data.value"
                                    :key="item"
                                    :class="['flex items-start gap-3 p-3 rounded-xl', getColorClasses(section.color).bgLight, 'border', getColorClasses(section.color).border]"
                                >
                                    <span :class="['w-2 h-2 mt-2 rounded-full flex-shrink-0', getColorClasses(section.color).bg]"></span>
                                    <span class="text-gray-800 dark:text-gray-200 text-sm">{{ item }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Quick Actions -->
            <div v-if="hasData" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-4">Keyingi qadamlar</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Link
                        :href="panelType === 'business' ? route('business.offers.create') : route('marketing.content.create')"
                        class="flex items-center gap-3 p-4 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800 hover:shadow-lg transition-all"
                    >
                        <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ panelType === 'business' ? 'Taklif yaratish' : 'Kontent yaratish' }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Mijozga moslashtirilgan</p>
                        </div>
                    </Link>

                    <Link
                        :href="getRoute('content.index')"
                        class="flex items-center gap-3 p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-100 dark:border-blue-800 hover:shadow-lg transition-all"
                    >
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Kontent yozish</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Mijozga mo'ljallangan kontent</p>
                        </div>
                    </Link>

                    <Link
                        :href="getRoute('custdev.create')"
                        class="flex items-center gap-3 p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-100 dark:border-purple-800 hover:shadow-lg transition-all"
                    >
                        <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">CustDev o'tkazish</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Ko'proq ma'lumot to'plash</p>
                        </div>
                    </Link>
                </div>
            </div>
        </template>
    </div>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
        <div v-if="deletingBuyer" class="fixed inset-0 z-50 overflow-y-auto">
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
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Profilni o'chirish</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Bu amalni qaytarib bo'lmaydi</p>
                        </div>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 mb-6">
                        <strong class="text-gray-900 dark:text-gray-100">{{ deletingBuyer.name }}</strong> nomli profilni o'chirishni xohlaysizmi?
                    </p>
                    <div class="flex gap-3">
                        <button @click="cancelDelete" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors">
                            Bekor qilish
                        </button>
                        <button @click="deleteBuyer" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors">
                            O'chirish
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
