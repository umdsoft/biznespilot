<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    PlusIcon,
    GiftIcon,
    SparklesIcon,
    DocumentDuplicateIcon,
    PencilIcon,
    TrashIcon,
    EyeIcon,
    ShieldCheckIcon,
    CurrencyDollarIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    offers: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    panelType: {
        type: String,
        required: true,
        validator: (v) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(v),
    },
});

const emit = defineEmits(['delete']);

const deletingOffer = ref(null);

const getRoute = (action, id = null) => {
    const prefix = props.panelType;
    const routes = {
        index: `${prefix}.offers.index`,
        create: `${prefix}.offers.create`,
        show: `${prefix}.offers.show`,
        edit: `${prefix}.offers.edit`,
        duplicate: `${prefix}.offers.duplicate`,
        destroy: `${prefix}.offers.destroy`,
    };
    return routes[action] || routes.index;
};

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
        active: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        paused: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        archived: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    };
    return colors[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Qoralama',
        active: 'Faol',
        paused: 'Pauza',
        archived: 'Arxiv',
    };
    return labels[status] || status;
};

const duplicateOffer = (offer) => {
    router.post(route(getRoute('duplicate'), offer.id));
};

const confirmDelete = (offer) => {
    deletingOffer.value = offer;
};

const deleteOffer = () => {
    if (deletingOffer.value) {
        router.delete(route(getRoute('destroy'), deletingOffer.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                deletingOffer.value = null;
            },
        });
    }
};

const cancelDelete = () => {
    deletingOffer.value = null;
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('uz-UZ').format(price) + ' so\'m';
};
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <GiftIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Irresistible Offers</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        "$100M Offers" metodologiyasi asosida qarshilik ko'rsatib bo'lmaydigan takliflar yarating
                    </p>
                </div>
            </div>
            <Link
                :href="route(getRoute('create'))"
                class="inline-flex items-center px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-xl transition-colors gap-2"
            >
                <PlusIcon class="w-5 h-5" />
                Yangi Offer
            </Link>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                        <GiftIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.total_offers || 0 }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami Offers</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                        <SparklesIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.active_offers || 0 }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Faol</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl">
                        <CurrencyDollarIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.avg_conversion?.toFixed(1) || 0 }}%</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha CR</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                        <ShieldCheckIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ formatPrice(stats?.total_value || 0) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Value</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!offers || offers.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="mx-auto w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mb-6">
                <GiftIcon class="w-10 h-10 text-purple-600 dark:text-purple-400" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">Hali Offer yo'q</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                AI yordamida irresistible offer yaratish orqali boshlang
            </p>
            <Link
                :href="route(getRoute('create'))"
                class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-xl transition-colors gap-2"
            >
                <PlusIcon class="w-5 h-5" />
                Offer Yaratish
            </Link>
        </div>

        <!-- Offers Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="offer in offers"
                :key="offer.id"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:border-purple-300 dark:hover:border-purple-600 transition-colors group"
            >
                <!-- Card Header -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 dark:from-purple-700 dark:to-indigo-700 px-5 py-5 text-white">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <h3 class="text-lg font-bold flex-1 line-clamp-1">{{ offer.name }}</h3>
                        <span
                            :class="getStatusColor(offer.status)"
                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium"
                        >
                            {{ getStatusLabel(offer.status) }}
                        </span>
                    </div>
                    <p v-if="offer.description" class="text-purple-100 text-sm line-clamp-2">
                        {{ offer.description }}
                    </p>
                </div>

                <!-- Card Body -->
                <div class="p-5 space-y-4">
                    <!-- Core Offer -->
                    <div v-if="offer.core_offer" class="p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800 rounded-xl">
                        <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mb-1">CORE OFFER</p>
                        <p class="text-sm text-gray-800 dark:text-gray-200 line-clamp-2">{{ offer.core_offer }}</p>
                    </div>

                    <!-- Pricing -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3 border border-green-100 dark:border-green-800">
                            <div class="text-green-600 dark:text-green-400 font-medium text-xs mb-1">Narx</div>
                            <div class="text-green-800 dark:text-green-300 font-bold text-sm">{{ formatPrice(offer.pricing || 0) }}</div>
                        </div>
                        <div v-if="offer.total_value" class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 border border-blue-100 dark:border-blue-800">
                            <div class="text-blue-600 dark:text-blue-400 font-medium text-xs mb-1">Total Value</div>
                            <div class="text-blue-800 dark:text-blue-300 font-bold text-sm">{{ formatPrice(offer.total_value) }}</div>
                        </div>
                    </div>

                    <!-- Value Score -->
                    <div v-if="offer.value_score" class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-800 rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-yellow-600 dark:text-yellow-400">VALUE SCORE</span>
                            <span class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ offer.value_score }}</span>
                        </div>
                    </div>

                    <!-- Info Row -->
                    <div class="flex items-center justify-between text-sm">
                        <!-- Guarantee -->
                        <div v-if="offer.guarantee_type" class="flex items-center gap-1.5 text-green-600 dark:text-green-400">
                            <ShieldCheckIcon class="w-4 h-4" />
                            <span class="text-xs">{{ offer.guarantee_type }}</span>
                        </div>

                        <!-- Components Count -->
                        <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                            <GiftIcon class="w-4 h-4" />
                            <span class="text-xs">{{ offer.components_count || 0 }} bonus</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <Link
                            :href="route(getRoute('show'), offer.id)"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-900/50 text-purple-700 dark:text-purple-400 font-medium rounded-lg transition-colors text-sm"
                        >
                            <EyeIcon class="w-4 h-4" />
                            Ko'rish
                        </Link>
                        <Link
                            :href="route(getRoute('edit'), offer.id)"
                            class="p-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-400 rounded-lg transition-colors"
                            title="Tahrirlash"
                        >
                            <PencilIcon class="w-4 h-4" />
                        </Link>
                        <button
                            @click="duplicateOffer(offer)"
                            class="p-2 bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-lg transition-colors"
                            title="Nusxa olish"
                        >
                            <DocumentDuplicateIcon class="w-4 h-4" />
                        </button>
                        <button
                            @click="confirmDelete(offer)"
                            class="p-2 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 rounded-lg transition-colors"
                            title="O'chirish"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
        <div v-if="deletingOffer" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="cancelDelete"></div>

                <!-- Modal Content -->
                <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl">
                    <div class="p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                <TrashIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Offer o'chirish</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Bu amalni qaytarib bo'lmaydi</p>
                            </div>
                        </div>

                        <p class="text-gray-700 dark:text-gray-300 mb-6">
                            <strong class="text-gray-900 dark:text-gray-100">{{ deletingOffer.name }}</strong> nomli Offerni o'chirishni xohlaysizmi?
                        </p>

                        <div class="flex gap-3">
                            <button
                                @click="cancelDelete"
                                class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                            >
                                Bekor qilish
                            </button>
                            <button
                                @click="deleteOffer"
                                class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors"
                            >
                                O'chirish
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
