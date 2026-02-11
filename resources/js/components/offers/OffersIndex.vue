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

const { t } = useI18n();
const deletingOffer = ref(null);
const { getRouteName } = useOfferRoutes(props.panelType);
const getRoute = (action) => getRouteName(action);
const isReadOnly = computed(() => ['operator'].includes(props.panelType));

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400',
        active: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        paused: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400',
        archived: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
    };
    return colors[status] || colors.draft;
};

const getStatusIcon = (status) => {
    const icons = { draft: ClockIcon, active: CheckCircleIcon, paused: PauseCircleIcon, archived: ArchiveBoxIcon };
    return icons[status] || ClockIcon;
};

const getStatusLabel = (status) => t(`offers.status.${status}`) || status;

const duplicateOffer = (offer) => {
    router.post(route(getRoute('duplicate'), offer.id));
};

const confirmDelete = (offer) => { deletingOffer.value = offer; };
const deleteOffer = () => {
    if (deletingOffer.value) {
        router.delete(route(getRoute('destroy'), deletingOffer.value.id), {
            preserveScroll: true,
            onSuccess: () => { deletingOffer.value = null; },
        });
    }
};
const cancelDelete = () => { deletingOffer.value = null; };

const formatPrice = (price) => {
    if (!price) return "0 so'm";
    if (price >= 1000000) return (price / 1000000).toFixed(1) + " mln so'm";
    return new Intl.NumberFormat('uz-UZ').format(price) + " so'm";
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('offers.title') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('offers.subtitle') }}</p>
            </div>
            <Link
                v-if="!isReadOnly"
                :href="route(getRoute('create'))"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
                <PlusIcon class="w-4 h-4" />
                {{ t('offers.new_offer') }}
            </Link>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <GiftIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('offers.total_offers') }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.total_offers || 0 }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <CheckCircleIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('offers.active') }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.active_offers || 0 }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                        <ChartBarIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('offers.avg_conversion') }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats?.avg_conversion?.toFixed(1) || 0 }}%</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <CurrencyDollarIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('offers.total_value') }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatPrice(stats?.total_value || 0) }}</p>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!offers || offers.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <GiftIcon class="w-7 h-7 text-gray-400 dark:text-gray-500" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ t('offers.no_offers') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">{{ t('offers.no_offers_desc') }}</p>
            <Link
                v-if="!isReadOnly"
                :href="route(getRoute('create'))"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
                <PlusIcon class="w-4 h-4" />
                {{ t('offers.create_offer') }}
            </Link>
        </div>

        <!-- Offers List -->
        <div v-else class="space-y-3">
            <div
                v-for="offer in offers"
                :key="offer.id"
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow"
            >
                <div class="flex items-start gap-4">
                    <!-- Icon -->
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                        <GiftIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-1">
                            <Link
                                :href="route(getRoute('show'), offer.id)"
                                class="text-base font-semibold text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition-colors truncate"
                            >
                                {{ offer.name }}
                            </Link>
                            <span :class="getStatusColor(offer.status)" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium flex-shrink-0">
                                <component :is="getStatusIcon(offer.status)" class="w-3 h-3" />
                                {{ getStatusLabel(offer.status) }}
                            </span>
                        </div>

                        <p v-if="offer.description" class="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-1">
                            {{ offer.description }}
                        </p>
                        <p v-else-if="offer.core_offer" class="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-1">
                            {{ offer.core_offer }}
                        </p>

                        <!-- Meta Info -->
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ t('offers.price') }}: <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatPrice(offer.pricing || 0) }}</span>
                            </span>
                            <span v-if="offer.total_value" class="text-gray-600 dark:text-gray-400">
                                {{ t('offers.total_value') }}: <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatPrice(offer.total_value) }}</span>
                            </span>
                            <span v-if="offer.value_score" class="text-gray-600 dark:text-gray-400">
                                Qiymat: <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ offer.value_score }}</span>
                            </span>
                            <span v-if="offer.guarantee_type" class="flex items-center gap-1 text-green-600 dark:text-green-400">
                                <ShieldCheckIcon class="w-3.5 h-3.5" />
                                {{ t('offers.guaranteed') }}
                            </span>
                            <span class="text-gray-400 dark:text-gray-500">{{ offer.created_at }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <Link
                            :href="route(getRoute('show'), offer.id)"
                            class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                            :title="t('offers.view')"
                        >
                            <EyeIcon class="w-4 h-4" />
                        </Link>
                        <Link
                            v-if="!isReadOnly"
                            :href="route(getRoute('edit'), offer.id)"
                            class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                            :title="t('offers.edit')"
                        >
                            <PencilIcon class="w-4 h-4" />
                        </Link>
                        <button
                            v-if="!isReadOnly"
                            @click="duplicateOffer(offer)"
                            class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                            :title="t('offers.duplicate')"
                        >
                            <DocumentDuplicateIcon class="w-4 h-4" />
                        </button>
                        <button
                            v-if="!isReadOnly"
                            @click="confirmDelete(offer)"
                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                            :title="t('offers.delete')"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <Teleport to="body">
        <div v-if="deletingOffer" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="cancelDelete"></div>
                <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                <TrashIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ t('offers.delete_title') }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('offers.delete_warning') }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-6">
                            {{ t('offers.delete_confirm', { name: deletingOffer.name }) }}
                        </p>
                        <div class="flex gap-3">
                            <button @click="cancelDelete" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors">
                                {{ t('common.cancel') }}
                            </button>
                            <button @click="deleteOffer" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                {{ t('offers.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
