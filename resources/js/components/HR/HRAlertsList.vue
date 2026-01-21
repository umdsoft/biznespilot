<script setup>
import { ref } from 'vue';
import {
    BellIcon,
    BellAlertIcon,
    CheckIcon,
    XMarkIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    alerts: { type: Array, default: () => [] },
    unreadCount: { type: Number, default: 0 },
    businessId: { type: String, default: '' },
});

const emit = defineEmits(['acknowledge', 'resolve', 'markAllSeen']);

const acknowledging = ref(null);
const resolving = ref(null);

const getPriorityColor = (priority) => {
    const colors = {
        critical: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800',
        high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 border-orange-200 dark:border-orange-800',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800',
        low: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800',
    };
    return colors[priority] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
};

const getAlertIcon = (type, isCelebration) => {
    if (isCelebration) return SparklesIcon;
    if (type.includes('critical') || type.includes('high')) return BellAlertIcon;
    return BellIcon;
};

const getAlertIconColor = (priority, isCelebration) => {
    if (isCelebration) return 'text-yellow-500';
    const colors = {
        critical: 'text-red-500',
        high: 'text-orange-500',
        medium: 'text-yellow-500',
        low: 'text-green-500',
    };
    return colors[priority] || 'text-gray-500';
};

const handleAcknowledge = async (alertId) => {
    acknowledging.value = alertId;
    emit('acknowledge', alertId);
    acknowledging.value = null;
};

const handleResolve = async (alertId) => {
    resolving.value = alertId;
    emit('resolve', alertId);
    resolving.value = null;
};

const handleMarkAllSeen = () => {
    emit('markAllSeen');
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <BellIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        <span
                            v-if="unreadCount > 0"
                            class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
                        >
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">HR Ogohlantirishlar</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ unreadCount }} ta yangi bildirishnoma
                        </p>
                    </div>
                </div>
                <button
                    v-if="unreadCount > 0"
                    @click="handleMarkAllSeen"
                    class="text-sm text-purple-600 dark:text-purple-400 hover:underline"
                >
                    Barchasini ko'rilgan deb belgilash
                </button>
            </div>
        </div>

        <!-- Alerts list -->
        <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
            <div
                v-for="alert in alerts"
                :key="alert.id"
                :class="[
                    'p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors',
                    alert.status === 'new' ? 'bg-purple-50/50 dark:bg-purple-900/10' : '',
                ]"
            >
                <div class="flex items-start gap-3">
                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', getPriorityColor(alert.priority)]">
                        <component
                            :is="getAlertIcon(alert.alert_type, alert.is_celebration)"
                            :class="['w-5 h-5', getAlertIconColor(alert.priority, alert.is_celebration)]"
                        />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ alert.title }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ alert.message }}
                                </p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span :class="['px-2 py-0.5 text-xs font-medium rounded-full border', getPriorityColor(alert.priority)]">
                                        {{ alert.priority_label }}
                                    </span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ alert.created_ago }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button
                                    v-if="alert.status !== 'acknowledged' && alert.status !== 'resolved'"
                                    @click="handleAcknowledge(alert.id)"
                                    :disabled="acknowledging === alert.id"
                                    class="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                    title="Tasdiqlash"
                                >
                                    <CheckIcon class="w-4 h-4" />
                                </button>
                                <button
                                    v-if="alert.status !== 'resolved'"
                                    @click="handleResolve(alert.id)"
                                    :disabled="resolving === alert.id"
                                    class="p-1.5 text-gray-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors"
                                    title="Yechildi"
                                >
                                    <XMarkIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="alerts.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                <BellIcon class="w-12 h-12 mx-auto opacity-50 mb-3" />
                <p>Hozircha ogohlantirishlar yo'q</p>
            </div>
        </div>

    </div>
</template>
