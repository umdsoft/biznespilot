<template>
    <AppLayout title="Ogohlantirishlar">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                    Ogohlantirishlar
                </h2>
                <Link
                    href="/business/alerts/rules"
                    class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    <CogIcon class="w-4 h-4 mr-2" />
                    Qoidalar
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Jami</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-red-200 dark:border-red-800">
                        <p class="text-sm text-red-600 dark:text-red-400">Faol</p>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.active }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-yellow-200 dark:border-yellow-800">
                        <p class="text-sm text-yellow-600 dark:text-yellow-400">Qabul qilingan</p>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.acknowledged }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-green-200 dark:border-green-800">
                        <p class="text-sm text-green-600 dark:text-green-400">Hal qilingan</p>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.resolved }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha vaqt</p>
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
                        <option value="all">Barcha statuslar</option>
                        <option value="active">Faol</option>
                        <option value="acknowledged">Qabul qilingan</option>
                        <option value="resolved">Hal qilingan</option>
                        <option value="snoozed">Kechiktirilgan</option>
                        <option value="dismissed">Rad etilgan</option>
                    </select>

                    <select
                        v-model="localFilters.severity"
                        @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 text-sm"
                    >
                        <option value="all">Barcha darajalar</option>
                        <option value="critical">Kritik</option>
                        <option value="high">Yuqori</option>
                        <option value="medium">O'rta</option>
                        <option value="low">Past</option>
                        <option value="info">Ma'lumot</option>
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
                        <p class="text-gray-500 dark:text-gray-400">Ogohlantirishlar topilmadi</p>
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
                    Ogohlantirishni hal qilish
                </h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Yechim tavsifi (ixtiyoriy)
                    </label>
                    <textarea
                        v-model="resolution"
                        rows="3"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                        placeholder="Qanday choralar ko'rildi..."
                    />
                </div>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="showResolve = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        Bekor qilish
                    </button>
                    <button
                        @click="resolveAlert"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg"
                    >
                        Hal qilish
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Snooze Modal -->
        <Modal :show="showSnooze" @close="showSnooze = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    Ogohlantirishni kechiktirish
                </h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Qancha vaqtga?
                    </label>
                    <select
                        v-model="snoozeHours"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                    >
                        <option :value="1">1 soat</option>
                        <option :value="4">4 soat</option>
                        <option :value="24">1 kun</option>
                        <option :value="72">3 kun</option>
                        <option :value="168">1 hafta</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="showSnooze = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        Bekor qilish
                    </button>
                    <button
                        @click="snoozeAlert"
                        class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 rounded-lg"
                    >
                        Kechiktirish
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
import AlertCard from '@/Components/Dashboard/AlertCard.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { CogIcon, BellSlashIcon } from '@heroicons/vue/24/outline';

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
    if (confirm('Haqiqatan ham bu ogohlantirishni rad etmoqchimisiz?')) {
        router.post(`/business/alerts/${alertId}/dismiss`, {}, {
            preserveScroll: true,
        });
    }
}
</script>
