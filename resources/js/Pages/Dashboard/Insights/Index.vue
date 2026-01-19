<template>
    <AppLayout :title="t('insights.title')">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    {{ t('insights.title') }}
                </h2>
                <button
                    @click="regenerateInsights"
                    :disabled="isRegenerating"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium disabled:opacity-50"
                >
                    <ArrowPathIcon :class="['w-4 h-4 mr-2', isRegenerating ? 'animate-spin' : '']" />
                    {{ isRegenerating ? t('insights.regenerating') : t('insights.regenerate') }}
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('insights.stats.total') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-600 dark:text-blue-400">{{ t('insights.stats.active') }}</p>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.active }}</p>
                    </div>
                    <div
                        v-for="(count, type) in stats.by_type"
                        :key="type"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700"
                    >
                        <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ typeLabels[type] || type }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count }}</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap gap-4 mb-6">
                    <select
                        v-model="localFilters.type"
                        @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 text-sm"
                    >
                        <option value="all">{{ t('insights.filter.all_types') }}</option>
                        <option value="trend">{{ t('insights.filter.trend') }}</option>
                        <option value="anomaly">{{ t('insights.filter.anomaly') }}</option>
                        <option value="recommendation">{{ t('insights.filter.recommendation') }}</option>
                        <option value="opportunity">{{ t('insights.filter.opportunity') }}</option>
                        <option value="warning">{{ t('insights.filter.warning') }}</option>
                        <option value="celebration">{{ t('insights.filter.celebration') }}</option>
                    </select>

                    <select
                        v-model="localFilters.category"
                        @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 text-sm"
                    >
                        <option value="all">{{ t('insights.filter.all_categories') }}</option>
                        <option value="revenue">{{ t('insights.filter.revenue') }}</option>
                        <option value="leads">{{ t('insights.filter.leads') }}</option>
                        <option value="marketing">{{ t('insights.filter.marketing') }}</option>
                        <option value="advertising">{{ t('insights.filter.advertising') }}</option>
                        <option value="sales">{{ t('insights.filter.sales') }}</option>
                    </select>

                    <label class="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                        <input
                            v-model="localFilters.active_only"
                            type="checkbox"
                            @change="applyFilters"
                            class="rounded border-gray-300 dark:border-gray-600 text-blue-600 mr-2"
                        />
                        {{ t('insights.filter.active_only') }}
                    </label>
                </div>

                <!-- Insights List -->
                <div class="space-y-4">
                    <InsightCard
                        v-for="insight in insights.data"
                        :key="insight.id"
                        :insight="insight"
                        @act="showActModal"
                        @dismiss="dismissInsight"
                    />

                    <div
                        v-if="insights.data.length === 0"
                        class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <LightBulbIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <p class="text-gray-500 dark:text-gray-400">{{ t('insights.empty') }}</p>
                        <button
                            @click="regenerateInsights"
                            class="mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium"
                        >
                            {{ t('insights.empty.regenerate') }}
                        </button>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="insights.last_page > 1" class="mt-6">
                    <Pagination :links="insights.links" />
                </div>
            </div>
        </div>

        <!-- Action Modal -->
        <Modal :show="showAction" @close="showAction = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ t('insights.action.title') }}
                </h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ t('insights.action.label') }}
                    </label>
                    <textarea
                        v-model="actionTaken"
                        rows="3"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                        :placeholder="t('insights.action.placeholder')"
                    />
                </div>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="showAction = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        @click="markAsActed"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg"
                    >
                        {{ t('insights.action.save') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import InsightCard from '@/components/Dashboard/InsightCard.vue';
import Modal from '@/components/Modal.vue';
import Pagination from '@/components/Pagination.vue';
import { ArrowPathIcon, LightBulbIcon } from '@heroicons/vue/24/outline';
import { useI18n } from '@/i18n';

const { t } = useI18n();

interface Props {
    insights: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    stats: {
        total: number;
        active: number;
        by_type: Record<string, number>;
    };
    filters: {
        type: string;
        category: string;
        active_only: boolean;
    };
}

const props = defineProps<Props>();

const typeLabels = computed(() => ({
    trend: t('insights.filter.trend'),
    anomaly: t('insights.filter.anomaly'),
    recommendation: t('insights.filter.recommendation'),
    opportunity: t('insights.filter.opportunity'),
    warning: t('insights.filter.warning'),
    celebration: t('insights.filter.celebration'),
}));

const localFilters = reactive({ ...props.filters });
const isRegenerating = ref(false);
const showAction = ref(false);
const selectedInsightId = ref<string | null>(null);
const actionTaken = ref('');

function applyFilters() {
    router.get('/business/insights', {
        type: localFilters.type !== 'all' ? localFilters.type : undefined,
        category: localFilters.category !== 'all' ? localFilters.category : undefined,
        active_only: localFilters.active_only,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

async function regenerateInsights() {
    isRegenerating.value = true;

    router.post('/business/insights/regenerate', {}, {
        preserveScroll: true,
        onFinish: () => {
            isRegenerating.value = false;
        },
    });
}

function showActModal(insightId: string) {
    selectedInsightId.value = insightId;
    actionTaken.value = '';
    showAction.value = true;
}

async function markAsActed() {
    if (!selectedInsightId.value) return;

    router.post(`/business/insights/${selectedInsightId.value}/acted`, {
        action_taken: actionTaken.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAction.value = false;
            selectedInsightId.value = null;
        },
    });
}

async function dismissInsight(insightId: string) {
    if (confirm(t('insights.dismiss.confirm'))) {
        router.post(`/business/insights/${insightId}/dismiss`, {}, {
            preserveScroll: true,
        });
    }
}
</script>
