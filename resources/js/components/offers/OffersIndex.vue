<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import { useOfferRoutes } from '@/composables/useOfferRoutes.js';
import {
    PlusIcon,
    GiftIcon,
    SparklesIcon,
    DocumentDuplicateIcon,
    PencilIcon,
    TrashIcon,
    EyeIcon,
    ShieldCheckIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    CheckCircleIcon,
    ClockIcon,
    ArchiveBoxIcon,
    PauseCircleIcon,
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

const { t } = useI18n();

const deletingOffer = ref(null);

// Use centralized route helper
const { getRouteName } = useOfferRoutes(props.panelType);

const getRoute = (action) => {
    return getRouteName(action);
};

// Check if panel is read-only (only operator can only view, saleshead has full access)
const isReadOnly = computed(() => ['operator'].includes(props.panelType));

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600',
        active: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-700',
        paused: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-700',
        archived: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-red-200 dark:border-red-700',
    };
    return colors[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
};

const getStatusIcon = (status) => {
    const icons = {
        draft: ClockIcon,
        active: CheckCircleIcon,
        paused: PauseCircleIcon,
        archived: ArchiveBoxIcon,
    };
    return icons[status] || ClockIcon;
};

const getStatusLabel = (status) => {
    return t(`offers.status.${status}`) || status;
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
    if (!price) return "0 so'm";
    if (price >= 1000000) {
        return (price / 1000000).toFixed(1) + " mln so'm";
    }
    return new Intl.NumberFormat('uz-UZ').format(price) + " so'm";
};
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- Sahifa Sarlavhasi -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <GiftIcon class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('offers.title') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ t('offers.subtitle') }}
                    </p>
                </div>
            </div>
            <Link
                v-if="!isReadOnly"
                :href="route(getRoute('create'))"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium rounded-xl transition-all shadow-lg shadow-purple-500/30"
            >
                <PlusIcon class="w-5 h-5" />
                {{ t('offers.new_offer') }}
            </Link>
        </div>

        <!-- Statistika Kartalari -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                        <GiftIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.total_offers || 0 }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('offers.total_offers') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                        <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats?.active_offers || 0 }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('offers.active') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl">
                        <ChartBarIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats?.avg_conversion?.toFixed(1) || 0 }}%</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('offers.avg_conversion') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                        <CurrencyDollarIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ formatPrice(stats?.total_value || 0) }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('offers.total_value') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bo'sh Holat -->
        <div v-if="!offers || offers.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="mx-auto w-20 h-20 bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 rounded-full flex items-center justify-center mb-6">
                <GiftIcon class="w-10 h-10 text-purple-600 dark:text-purple-400" />
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ t('offers.no_offers') }}</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                {{ t('offers.no_offers_desc') }}
            </p>
            <Link
                v-if="!isReadOnly"
                :href="route(getRoute('create'))"
                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium rounded-xl transition-all shadow-lg shadow-purple-500/30"
            >
                <SparklesIcon class="w-5 h-5" />
                {{ t('offers.create_offer') }}
            </Link>
            <p v-else class="text-gray-500 dark:text-gray-400">
                {{ t('offers.not_created') }}
            </p>
        </div>

        <!-- Takliflar Ro'yxati -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div
                v-for="offer in offers"
                :key="offer.id"
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg hover:border-purple-300 dark:hover:border-purple-600 transition-all group"
            >
                <!-- Karta Sarlavhasi -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-5 py-5 text-white relative overflow-hidden">
                    <div class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <h3 class="text-lg font-bold flex-1 line-clamp-1">{{ offer.name }}</h3>
                            <span
                                :class="getStatusColor(offer.status)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium border"
                            >
                                <component :is="getStatusIcon(offer.status)" class="w-3.5 h-3.5" />
                                {{ getStatusLabel(offer.status) }}
                            </span>
                        </div>
                        <p v-if="offer.description" class="text-purple-100 text-sm line-clamp-2">
                            {{ offer.description }}
                        </p>
                    </div>
                </div>

                <!-- Karta Tanasi -->
                <div class="p-5 space-y-4">
                    <!-- Asosiy Taklif -->
                    <div v-if="offer.core_offer" class="p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-100 dark:border-purple-800 rounded-xl">
                        <p class="text-xs font-semibold text-purple-600 dark:text-purple-400 mb-1">{{ t('offers.core_offer') }}</p>
                        <p class="text-sm text-gray-800 dark:text-gray-200 line-clamp-2">{{ offer.core_offer }}</p>
                    </div>

                    <!-- Narx va Qiymat -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3 border border-green-100 dark:border-green-800">
                            <div class="text-green-600 dark:text-green-400 font-medium text-xs mb-1">{{ t('offers.price') }}</div>
                            <div class="text-green-800 dark:text-green-300 font-bold text-sm">{{ formatPrice(offer.pricing || 0) }}</div>
                        </div>
                        <div v-if="offer.total_value" class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 border border-blue-100 dark:border-blue-800">
                            <div class="text-blue-600 dark:text-blue-400 font-medium text-xs mb-1">{{ t('offers.total_value') }}</div>
                            <div class="text-blue-800 dark:text-blue-300 font-bold text-sm">{{ formatPrice(offer.total_value) }}</div>
                        </div>
                    </div>

                    <!-- Qiymat Bahosi -->
                    <div v-if="offer.value_score" class="p-3 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-yellow-700 dark:text-yellow-400">{{ t('offers.value_score') }}</span>
                            <span class="text-2xl font-bold text-yellow-800 dark:text-yellow-300">{{ offer.value_score }}</span>
                        </div>
                    </div>

                    <!-- Qo'shimcha Ma'lumot -->
                    <div class="flex items-center justify-between text-sm">
                        <div v-if="offer.guarantee_type" class="flex items-center gap-1.5 text-green-600 dark:text-green-400">
                            <ShieldCheckIcon class="w-4 h-4" />
                            <span class="text-xs">{{ t('offers.guaranteed') }}</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                            <GiftIcon class="w-4 h-4" />
                            <span class="text-xs">{{ t('offers.bonuses', { count: offer.components_count || 0 }) }}</span>
                        </div>
                    </div>

                    <!-- Amallar -->
                    <div class="flex items-center gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <Link
                            :href="route(getRoute('show'), offer.id)"
                            :class="[
                                'inline-flex items-center justify-center gap-2 px-4 py-2 bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-900/50 text-purple-700 dark:text-purple-400 font-medium rounded-xl transition-colors text-sm',
                                isReadOnly ? 'flex-1' : ''
                            ]"
                        >
                            <EyeIcon class="w-4 h-4" />
                            {{ t('offers.view') }}
                        </Link>
                        <Link
                            v-if="!isReadOnly"
                            :href="route(getRoute('edit'), offer.id)"
                            class="p-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-400 rounded-xl transition-colors"
                            :title="t('offers.edit')"
                        >
                            <PencilIcon class="w-4 h-4" />
                        </Link>
                        <button
                            v-if="!isReadOnly"
                            @click="duplicateOffer(offer)"
                            class="p-2 bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-xl transition-colors"
                            :title="t('offers.duplicate')"
                        >
                            <DocumentDuplicateIcon class="w-4 h-4" />
                        </button>
                        <button
                            v-if="!isReadOnly"
                            @click="confirmDelete(offer)"
                            class="p-2 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 rounded-xl transition-colors"
                            :title="t('offers.delete')"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- O'chirish Tasdiqlash Modal -->
    <Teleport to="body">
        <div v-if="deletingOffer" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <!-- Orqa fon -->
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="cancelDelete"></div>

                <!-- Modal Kontent -->
                <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl">
                    <div class="p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                <TrashIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ t('offers.delete_title') }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('offers.delete_warning') }}</p>
                            </div>
                        </div>

                        <p class="text-gray-700 dark:text-gray-300 mb-6">
                            {{ t('offers.delete_confirm', { name: deletingOffer.name }) }}
                        </p>

                        <div class="flex gap-3">
                            <button
                                @click="cancelDelete"
                                class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                            >
                                {{ t('common.cancel') }}
                            </button>
                            <button
                                @click="deleteOffer"
                                class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors"
                            >
                                {{ t('offers.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
