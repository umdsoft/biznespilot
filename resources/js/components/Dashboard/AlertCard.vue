<template>
    <div
        :class="[
            'rounded-lg border p-4 transition-all duration-200',
            severityClasses.border,
            severityClasses.bg,
            isExpanded ? 'shadow-md' : 'shadow-sm',
        ]"
    >
        <div class="flex items-start justify-between">
            <div class="flex items-start space-x-3">
                <div :class="['p-2 rounded-lg', severityClasses.iconBg]">
                    <component
                        :is="severityIcon"
                        :class="['w-5 h-5', severityClasses.icon]"
                    />
                </div>

                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <span
                            :class="[
                                'px-2 py-0.5 rounded text-xs font-medium',
                                severityClasses.badge
                            ]"
                        >
                            {{ severityLabel }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ formattedTime }}
                        </span>
                    </div>

                    <h4 class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                        {{ alert.title }}
                    </h4>

                    <p
                        v-if="isExpanded || !alert.message.includes('\n')"
                        class="mt-1 text-sm text-gray-600 dark:text-gray-400"
                    >
                        {{ alert.message }}
                    </p>

                    <button
                        v-if="alert.message.length > 100"
                        @click="isExpanded = !isExpanded"
                        class="mt-1 text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400"
                    >
                        {{ isExpanded ? 'Kamroq' : 'Batafsil' }}
                    </button>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <button
                    v-if="alert.status === 'active'"
                    @click="$emit('acknowledge', alert.id)"
                    class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded"
                    title="Qabul qilish"
                >
                    <CheckIcon class="w-4 h-4" />
                </button>

                <button
                    v-if="['active', 'acknowledged'].includes(alert.status)"
                    @click="$emit('resolve', alert.id)"
                    class="p-1.5 text-gray-400 hover:text-green-600 dark:hover:text-green-400 rounded"
                    title="Hal qilish"
                >
                    <CheckCircleIcon class="w-4 h-4" />
                </button>

                <button
                    @click="$emit('snooze', alert.id)"
                    class="p-1.5 text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400 rounded"
                    title="Kechiktirish"
                >
                    <ClockIcon class="w-4 h-4" />
                </button>

                <button
                    @click="$emit('dismiss', alert.id)"
                    class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded"
                    title="Rad etish"
                >
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Metric info -->
        <div
            v-if="alert.current_value !== null && alert.metric"
            class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700"
        >
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">{{ alert.metric }}</span>
                <span class="font-medium text-gray-900 dark:text-white">
                    {{ alert.current_value }}
                    <span v-if="alert.threshold_value" class="text-gray-400">
                        / {{ alert.threshold_value }}
                    </span>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import {
    ExclamationTriangleIcon,
    ExclamationCircleIcon,
    InformationCircleIcon,
    BellAlertIcon,
    CheckIcon,
    CheckCircleIcon,
    ClockIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { formatDistanceToNow } from 'date-fns';
import { uz } from 'date-fns/locale';

interface Alert {
    id: string;
    type: string;
    severity: 'critical' | 'high' | 'medium' | 'low' | 'info';
    title: string;
    message: string;
    metric: string | null;
    current_value: number | null;
    threshold_value: number | null;
    status: string;
    triggered_at: string;
}

const props = defineProps<{
    alert: Alert;
}>();

defineEmits<{
    acknowledge: [id: string];
    resolve: [id: string];
    snooze: [id: string];
    dismiss: [id: string];
}>();

const isExpanded = ref(false);

const formattedTime = computed(() => {
    try {
        return formatDistanceToNow(new Date(props.alert.triggered_at), {
            addSuffix: true,
            locale: uz,
        });
    } catch {
        return props.alert.triggered_at;
    }
});

const severityLabel = computed(() => {
    const labels: Record<string, string> = {
        critical: 'Kritik',
        high: 'Yuqori',
        medium: 'O\'rta',
        low: 'Past',
        info: 'Ma\'lumot',
    };
    return labels[props.alert.severity] || props.alert.severity;
});

const severityIcon = computed(() => {
    const icons: Record<string, any> = {
        critical: ExclamationTriangleIcon,
        high: ExclamationCircleIcon,
        medium: BellAlertIcon,
        low: InformationCircleIcon,
        info: InformationCircleIcon,
    };
    return icons[props.alert.severity] || BellAlertIcon;
});

const severityClasses = computed(() => {
    const classes: Record<string, {
        border: string;
        bg: string;
        iconBg: string;
        icon: string;
        badge: string;
    }> = {
        critical: {
            border: 'border-red-300 dark:border-red-800',
            bg: 'bg-red-50 dark:bg-red-900/20',
            iconBg: 'bg-red-100 dark:bg-red-900',
            icon: 'text-red-600 dark:text-red-400',
            badge: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        },
        high: {
            border: 'border-orange-300 dark:border-orange-800',
            bg: 'bg-orange-50 dark:bg-orange-900/20',
            iconBg: 'bg-orange-100 dark:bg-orange-900',
            icon: 'text-orange-600 dark:text-orange-400',
            badge: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        },
        medium: {
            border: 'border-yellow-300 dark:border-yellow-800',
            bg: 'bg-yellow-50 dark:bg-yellow-900/20',
            iconBg: 'bg-yellow-100 dark:bg-yellow-900',
            icon: 'text-yellow-600 dark:text-yellow-400',
            badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        },
        low: {
            border: 'border-gray-300 dark:border-gray-700',
            bg: 'bg-gray-50 dark:bg-gray-800',
            iconBg: 'bg-gray-100 dark:bg-gray-700',
            icon: 'text-gray-600 dark:text-gray-400',
            badge: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        },
        info: {
            border: 'border-blue-300 dark:border-blue-800',
            bg: 'bg-blue-50 dark:bg-blue-900/20',
            iconBg: 'bg-blue-100 dark:bg-blue-900',
            icon: 'text-blue-600 dark:text-blue-400',
            badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        },
    };
    return classes[props.alert.severity] || classes.info;
});
</script>
