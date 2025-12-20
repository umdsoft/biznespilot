<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
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
    offers: Array,
    stats: Object,
});

const deletingOffer = ref(null);

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-800 border-gray-200',
        active: 'bg-green-100 text-green-800 border-green-200',
        paused: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        archived: 'bg-red-100 text-red-800 border-red-200',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
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
    router.post(route('business.offers.duplicate', offer.id));
};

const confirmDelete = (offer) => {
    deletingOffer.value = offer;
};

const deleteOffer = () => {
    if (deletingOffer.value) {
        router.delete(route('business.offers.destroy', deletingOffer.value.id), {
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
    <BusinessLayout title="Irresistible Offers">
        <Head title="Offers" />

        <div class="py-12">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            <GiftIcon class="w-10 h-10 text-purple-600" />
                            Irresistible Offers
                        </h1>
                        <p class="mt-2 text-gray-600">
                            "$100M Offers" metodologiyasi asosida qarshilik ko'rsatib bo'lmaydigan takliflar yarating
                        </p>
                    </div>
                    <Link
                        :href="route('business.offers.create')"
                        class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg shadow-md transition-all duration-200 gap-2"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Yangi Offer
                    </Link>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <GiftIcon class="w-6 h-6 text-purple-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Jami Offers</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total_offers }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <SparklesIcon class="w-6 h-6 text-green-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Faol</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.active_offers }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <CurrencyDollarIcon class="w-6 h-6 text-yellow-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">O'rtacha CR</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.avg_conversion?.toFixed(1) || 0 }}%</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <ShieldCheckIcon class="w-6 h-6 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Value</p>
                                <p class="text-xl font-bold text-gray-900">{{ formatPrice(stats.total_value || 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!offers || offers.length === 0" class="text-center py-16">
                    <GiftIcon class="mx-auto h-24 w-24 text-gray-400" />
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Hali Offer yo'q</h3>
                    <p class="mt-2 text-gray-500">
                        AI yordamida irresistible offer yaratish orqali boshlang
                    </p>
                    <div class="mt-6">
                        <Link
                            :href="route('business.offers.create')"
                            class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg shadow-md transition-all duration-200 gap-2"
                        >
                            <PlusIcon class="w-5 h-5" />
                            Offer Yaratish
                        </Link>
                    </div>
                </div>

                <!-- Offers Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="offer in offers"
                        :key="offer.id"
                        class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-200"
                    >
                        <!-- Card Header -->
                        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 px-6 py-6 text-white">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="text-xl font-bold flex-1">{{ offer.name }}</h3>
                                <span
                                    :class="getStatusColor(offer.status)"
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold border"
                                >
                                    {{ getStatusLabel(offer.status) }}
                                </span>
                            </div>
                            <p v-if="offer.description" class="text-purple-100 text-sm line-clamp-2">
                                {{ offer.description }}
                            </p>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6">
                            <!-- Core Offer -->
                            <div v-if="offer.core_offer" class="mb-4 p-3 bg-purple-50 border border-purple-100 rounded-lg">
                                <p class="text-xs font-semibold text-purple-700 mb-1">CORE OFFER</p>
                                <p class="text-sm text-gray-800 line-clamp-2">{{ offer.core_offer }}</p>
                            </div>

                            <!-- Pricing -->
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="bg-green-50 rounded-lg p-3 border border-green-100">
                                    <div class="text-green-600 font-semibold text-xs mb-1">Narx</div>
                                    <div class="text-green-800 font-bold">{{ formatPrice(offer.pricing || 0) }}</div>
                                </div>
                                <div v-if="offer.total_value" class="bg-blue-50 rounded-lg p-3 border border-blue-100">
                                    <div class="text-blue-600 font-semibold text-xs mb-1">Total Value</div>
                                    <div class="text-blue-800 font-bold">{{ formatPrice(offer.total_value) }}</div>
                                </div>
                            </div>

                            <!-- Value Score -->
                            <div v-if="offer.value_score" class="mb-4 p-3 bg-yellow-50 border border-yellow-100 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-yellow-700">VALUE SCORE</span>
                                    <span class="text-2xl font-bold text-yellow-800">{{ offer.value_score }}</span>
                                </div>
                            </div>

                            <!-- Guarantee -->
                            <div v-if="offer.guarantee_type" class="mb-4 flex items-center gap-2 text-sm">
                                <ShieldCheckIcon class="w-4 h-4 text-green-600" />
                                <span class="text-gray-700">{{ offer.guarantee_type }}</span>
                            </div>

                            <!-- Components Count -->
                            <div class="mb-4 flex items-center gap-2 text-sm text-gray-600">
                                <GiftIcon class="w-4 h-4" />
                                <span>{{ offer.components_count }} bonus komponent</span>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 mt-6">
                                <Link
                                    :href="route('business.offers.show', offer.id)"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium rounded-lg transition-colors"
                                >
                                    <EyeIcon class="w-4 h-4" />
                                    Ko'rish
                                </Link>
                                <Link
                                    :href="route('business.offers.edit', offer.id)"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 font-medium rounded-lg transition-colors"
                                >
                                    <PencilIcon class="w-4 h-4" />
                                </Link>
                                <button
                                    @click="duplicateOffer(offer)"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium rounded-lg transition-colors"
                                >
                                    <DocumentDuplicateIcon class="w-4 h-4" />
                                </button>
                                <button
                                    @click="confirmDelete(offer)"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 font-medium rounded-lg transition-colors"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="deletingOffer"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="cancelDelete"
        >
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <TrashIcon class="w-6 h-6 text-red-600" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Offer o'chirish</h3>
                        <p class="text-sm text-gray-500">Bu amalni qaytarib bo'lmaydi</p>
                    </div>
                </div>

                <p class="text-gray-700 mb-6">
                    <strong>{{ deletingOffer.name }}</strong> nomli Offerni o'chirishni xohlaysizmi?
                </p>

                <div class="flex gap-3">
                    <button
                        @click="cancelDelete"
                        class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
                    >
                        Bekor qilish
                    </button>
                    <button
                        @click="deleteOffer"
                        class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors"
                    >
                        O'chirish
                    </button>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
