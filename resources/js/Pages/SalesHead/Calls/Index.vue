<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import {
    PhoneIcon,
    PhoneArrowUpRightIcon,
    PhoneArrowDownLeftIcon,
    PhoneXMarkIcon,
    MagnifyingGlassIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    calls: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            incoming: 0,
            outgoing: 0,
            missed: 0,
            avg_duration: 0,
        }),
    },
});

const searchQuery = ref('');
const typeFilter = ref('');

const filteredCalls = computed(() => {
    let result = props.calls;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(c => c.lead?.name?.toLowerCase().includes(q) || c.phone?.includes(q));
    }
    if (typeFilter.value) {
        result = result.filter(c => c.type === typeFilter.value);
    }
    return result;
});

const formatDuration = (seconds) => {
    if (!seconds) return '0:00';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const formatDate = (date) => {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('uz-UZ') + ' ' + d.toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' });
};

const getCallIcon = (type) => {
    const icons = {
        incoming: PhoneArrowDownLeftIcon,
        outgoing: PhoneArrowUpRightIcon,
        missed: PhoneXMarkIcon,
    };
    return icons[type] || PhoneIcon;
};

const getCallColor = (type) => {
    const colors = {
        incoming: 'text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30',
        outgoing: 'text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30',
        missed: 'text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30',
    };
    return colors[type] || 'text-gray-600 bg-gray-100';
};

const getCallLabel = (type) => {
    const labels = { incoming: 'Kiruvchi', outgoing: 'Chiquvchi', missed: "O'tkazib yuborilgan" };
    return labels[type] || type;
};
</script>

<template>
    <SalesHeadLayout title="Qo'ng'iroqlar">
        <Head title="Qo'ng'iroqlar" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Qo'ng'iroqlar</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Barcha qo'ng'iroqlar tarixi</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jami</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kiruvchi</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.incoming }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Chiquvchi</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.outgoing }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">O'tkazib yuborilgan</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.missed }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha davomiylik</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatDuration(stats.avg_duration) }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Qidirish..."
                        class="w-full pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                    />
                </div>
                <select
                    v-model="typeFilter"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                >
                    <option value="">Barcha turlar</option>
                    <option value="incoming">Kiruvchi</option>
                    <option value="outgoing">Chiquvchi</option>
                    <option value="missed">O'tkazib yuborilgan</option>
                </select>
            </div>

            <!-- Calls List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="filteredCalls.length === 0" class="p-12 text-center">
                    <PhoneIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Qo'ng'iroq topilmadi</h3>
                    <p class="text-gray-500 dark:text-gray-400">Hali qo'ng'iroqlar tarixi yo'q</p>
                </div>

                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="call in filteredCalls" :key="call.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-center gap-4">
                            <div :class="[getCallColor(call.type), 'w-10 h-10 rounded-full flex items-center justify-center']">
                                <component :is="getCallIcon(call.type)" class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ call.lead?.name || call.phone }}</p>
                                    <span :class="[getCallColor(call.type), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                                        {{ getCallLabel(call.type) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ call.phone }}</p>
                            </div>
                            <div class="text-right">
                                <p class="flex items-center gap-1 text-sm text-gray-900 dark:text-white">
                                    <ClockIcon class="w-4 h-4" />
                                    {{ formatDuration(call.duration) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(call.created_at) }}</p>
                            </div>
                            <div v-if="call.operator" class="text-sm text-gray-600 dark:text-gray-400">
                                {{ call.operator.name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
