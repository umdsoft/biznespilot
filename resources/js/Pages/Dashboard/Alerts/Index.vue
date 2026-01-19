<template>
    <AppLayout :title="t('alerts.title')">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    {{ t('alerts.title') }}
                </h2>
                <Link
                    href="/business/alerts/rules"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    <CogIcon class="w-4 h-4 mr-2" />
                    {{ t('alerts.rules') }}
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('alerts.stats.total') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-red-200 dark:border-red-800">
                        <p class="text-sm text-red-600 dark:text-red-400">{{ t('alerts.stats.active') }}</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.active }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-yellow-200 dark:border-yellow-800">
                        <p class="text-sm text-yellow-600 dark:text-yellow-400">{{ t('alerts.stats.acknowledged') }}</p>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.acknowledged }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-green-200 dark:border-green-800">
                        <p class="text-sm text-green-600 dark:text-green-400">{{ t('alerts.stats.resolved') }}</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.resolved }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('alerts.stats.avg_time') }}</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ stats.avg_resolution_time ? stats.avg_resolution_time + ' daq' : '-' }}
                        </p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap gap-4 mb-6">
                    <select
                        v-model="localFilters.status"
                        @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 text-sm"
                    >
                        <option value="all">{{ t('alerts.filter.all_statuses') }}</option>
                        <option value="active">{{ t('alerts.filter.active') }}</option>
                        <option value="acknowledged">{{ t('alerts.filter.acknowledged') }}</option>
                        <option value="resolved">{{ t('alerts.filter.resolved') }}</option>
                        <option value="snoozed">{{ t('alerts.filter.snoozed') }}</option>
                        <option value="dismissed">{{ t('alerts.filter.dismissed') }}</option>
                    </select>

                    <select
                        v-model="localFilters.severity"
                        @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 text-sm"
                    >
                        <option value="all">{{ t('alerts.filter.all_severities') }}</option>
                        <option value="critical">{{ t('alerts.filter.critical') }}</option>
                        <option value="high">{{ t('alerts.filter.high') }}</option>
                        <option value="medium">{{ t('alerts.filter.medium') }}</option>
                        <option value="low">{{ t('alerts.filter.low') }}</option>
                        <option value="info">{{ t('alerts.filter.info') }}</option>
                    </select>
                </div>

                <!-- Alerts List -->
                <div class="space-y-4">
                    <AlertCard
                        v-for="alert in alerts.data"
                        :key="alert.id"
                        :alert="alert"
                        @acknowledge="acknowledgeAlert"
                        @resolve="showResolveModal"
                        @snooze="showSnoozeModal"
                        @dismiss="dismissAlert"
                    />

                    <div
                        v-if="alerts.data.length === 0"
                        class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <BellSlashIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                        <p class="text-gray-500 dark:text-gray-400">{{ t('alerts.empty') }}</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="alerts.last_page > 1" class="mt-6">
                    <Pagination :links="alerts.links" />
                </div>
            </div>
        </div>

        <!-- Resolve Modal -->
        <Modal :show="showResolve" @close="showResolve = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ t('alerts.resolve.title') }}
                </h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ t('alerts.resolve.desc_label') }}
                    </label>
                    <textarea
                        v-model="resolution"
                        rows="3"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                        :placeholder="t('alerts.resolve.placeholder')"
                    />
                </div>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="showResolve = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        @click="resolveAlert"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg"
                    >
                        {{ t('alerts.resolve.submit') }}
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Snooze Modal -->
        <Modal :show="showSnooze" @close="showSnooze = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    {{ t('alerts.snooze.title') }}
                </h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ t('alerts.snooze.duration') }}
                    </label>
                    <select
                        v-model="snoozeHours"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <option :value="1">{{ t('alerts.snooze.1h') }}</option>
                        <option :value="4">{{ t('alerts.snooze.4h') }}</option>
                        <option :value="24">{{ t('alerts.snooze.1d') }}</option>
                        <option :value="72">{{ t('alerts.snooze.3d') }}</option>
                        <option :value="168">{{ t('alerts.snooze.1w') }}</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="showSnooze = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        @click="snoozeAlert"
                        class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 rounded-lg"
                    >
                        {{ t('alerts.snooze.submit') }}
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AlertCard from '@/components/Dashboard/AlertCard.vue';
import Modal from '@/components/Modal.vue';
import Pagination from '@/components/Pagination.vue';
import { CogIcon, BellSlashIcon } from '@heroicons/vue/24/outline';
import { useI18n } from '@/i18n';

const { t } = useI18n();

interface Props {
    alerts: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    stats: {
        total: number;
        active: number;
        acknowledged: number;
        resolved: number;
        dismissed: number;
        avg_resolution_time: number | null;
    };
    filters: {
        status: string;
        severity: string;
    };
}

const props = defineProps<Props>();

const localFilters = reactive({ ...props.filters });
const showResolve = ref(false);
const showSnooze = ref(false);
const selectedAlertId = ref<string | null>(null);
const resolution = ref('');
const snoozeHours = ref(24);

function applyFilters() {
    router.get('/business/alerts', {
        status: localFilters.status !== 'all' ? localFilters.status : undefined,
        severity: localFilters.severity !== 'all' ? localFilters.severity : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

async function acknowledgeAlert(alertId: string) {
    router.post(`/business/alerts/${alertId}/acknowledge`, {}, {
        preserveScroll: true,
    });
}

function showResolveModal(alertId: string) {
    selectedAlertId.value = alertId;
    resolution.value = '';
    showResolve.value = true;
}

async function resolveAlert() {
    if (!selectedAlertId.value) return;

    router.post(`/business/alerts/${selectedAlertId.value}/resolve`, {
        resolution: resolution.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showResolve.value = false;
            selectedAlertId.value = null;
        },
    });
}

function showSnoozeModal(alertId: string) {
    selectedAlertId.value = alertId;
    snoozeHours.value = 24;
    showSnooze.value = true;
}

async function snoozeAlert() {
    if (!selectedAlertId.value) return;

    router.post(`/business/alerts/${selectedAlertId.value}/snooze`, {
        hours: snoozeHours.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showSnooze.value = false;
            selectedAlertId.value = null;
        },
    });
}

async function dismissAlert(alertId: string) {
    if (confirm(t('alerts.dismiss.confirm'))) {
        router.post(`/business/alerts/${alertId}/dismiss`, {}, {
            preserveScroll: true,
        });
    }
}
</script>
